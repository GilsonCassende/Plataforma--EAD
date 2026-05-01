<?php
/**
 * Controller: Auth
 * Gerencia cadastro, login, confirmação de email e recuperação de senha
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthController
{
    private PDO $pdo;
    private User $userModel;

    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;
    private const RESET_TOKEN_MINUTES = 15;
    private const VERIFICATION_TOKEN_HOURS = 24;
    private const VERIFICATION_CODE_LENGTH = 6;
    private const DUMMY_PASSWORD_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    private const DISPOSABLE_EMAIL_DOMAINS = [
        '10minutemail.com',
        '10minutemail.net',
        'guerrillamail.com',
        'guerrillamailblock.com',
        'mailinator.com',
        'maildrop.cc',
        'tempmail.com',
        'temp-mail.org',
        'yopmail.com',
        'sharklasers.com',
        'dispostable.com',
        'getnada.com',
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../models/User.php';
        $this->userModel = new User($pdo);
        $this->ensureUsersSecuritySchema();
    }

    private function ensureUsersSecuritySchema(): void
    {
        try {
            $columns = [];
            $stmt = $this->pdo->query('SHOW COLUMNS FROM users');
            foreach ($stmt->fetchAll() as $column) {
                $columns[$column['Field']] = $column;
            }

            $addedEmailVerified = false;

            if (!isset($columns['email_verified'])) {
                $this->pdo->exec('ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER fotografia');
                $addedEmailVerified = true;
            }
            if (!isset($columns['verification_token'])) {
                $this->pdo->exec('ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL AFTER email_verified');
            }
            if (!isset($columns['verification_token_expires_at'])) {
                $this->pdo->exec('ALTER TABLE users ADD COLUMN verification_token_expires_at DATETIME NULL AFTER verification_token');
            }
            if (!isset($columns['reset_token'])) {
                $this->pdo->exec('ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL AFTER verification_token_expires_at');
            }
            if (!isset($columns['reset_token_expires_at'])) {
                $this->pdo->exec('ALTER TABLE users ADD COLUMN reset_token_expires_at DATETIME NULL AFTER reset_token');
            }
            if (!isset($columns['login_attempts'])) {
                $this->pdo->exec('ALTER TABLE users ADD COLUMN login_attempts INT NOT NULL DEFAULT 0 AFTER reset_token_expires_at');
            }
            if (!isset($columns['locked_until'])) {
                $this->pdo->exec('ALTER TABLE users ADD COLUMN locked_until DATETIME NULL AFTER login_attempts');
            }

            try {
                $this->pdo->exec('CREATE INDEX idx_users_verification_token ON users (verification_token)');
            } catch (Throwable $e) {
            }
            try {
                $this->pdo->exec('CREATE INDEX idx_users_reset_token ON users (reset_token)');
            } catch (Throwable $e) {
            }

            if ($addedEmailVerified) {
                $this->pdo->exec('UPDATE users SET email_verified = 1, login_attempts = 0, locked_until = NULL WHERE verification_token IS NULL');
            }
        } catch (Throwable $e) {
            if (function_exists('registrar_log')) {
                registrar_log('migration_error', 'AuthController schema: ' . $e->getMessage());
            }
        }
    }

    public function registrar($nome, $email, $senha, $confirmSenha, $role = 'aluno'): array
    {
        $nome = trim((string)$nome);
        $email = $this->normalizarEmail((string)$email);
        $senha = (string)$senha;
        $confirmSenha = (string)$confirmSenha;
        $role = $this->normalizarRole((string)$role);

        $errors = [];

        if ($nome === '' || mb_strlen($nome) < 3) {
            $errors[] = 'Informe um nome com pelo menos 3 caracteres.';
        }

        $emailValidation = $this->validarEmailCadastro($email);
        if (!$emailValidation['sucesso']) {
            $errors[] = $emailValidation['mensagem'];
        }

        $passwordValidation = $this->validarSenhaForte($senha, $confirmSenha);
        if (!$passwordValidation['sucesso']) {
            $errors = array_merge($errors, $passwordValidation['erros']);
        }

        if (!$this->userModel->emailDisponivel($email)) {
            $usuarioExistente = $this->userModel->obterPorEmail($email, true);
            if ($usuarioExistente && (int)($usuarioExistente['email_verified'] ?? 0) === 1) {
                $errors[] = 'Já existe uma conta ativa com este email.';
            }
        }

        if ($errors) {
            return ['sucesso' => false, 'erros' => array_values(array_unique($errors))];
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $codigo = $this->gerarCodigoVerificacao();
        $tokenHash = $this->hashToken($codigo);
        $expiresAt = $this->formatFutureDate('+' . self::VERIFICATION_TOKEN_HOURS . ' hours');

        $resultado = $this->userModel->criarPendente($nome, $email, $senhaHash, $role, $tokenHash, $expiresAt);
        if (!$resultado['sucesso']) {
            return $resultado;
        }

        $emailEnviado = $this->enviarEmailConfirmacao($nome, $email, $codigo, $role);
        if (!$emailEnviado) {
            if (function_exists('registrar_log')) {
                registrar_log('signup_email_failed', 'Falha ao enviar email de confirmação para ' . $email);
            }
            if ($this->allowInlineVerificationFallback()) {
                $this->storeInlineVerificationCode($email, $codigo);
                return ['sucesso' => true, 'mensagem' => 'Cadastro realizado. Como o email falhou agora, use este código para ativar a conta: ' . $codigo];
            }

            $this->userModel->deletarPendentePorId((int)($resultado['user_id'] ?? 0));
            return ['sucesso' => false, 'mensagem' => 'Não foi possível enviar o email de ativação agora. Tente novamente em instantes.'];
        }

        if (function_exists('registrar_log')) {
            registrar_log('signup_created', 'Conta pendente criada para ' . $email);
        }

        return [
            'sucesso' => true,
            'mensagem' => 'Cadastro realizado. Verifique seu email para ativar sua conta.',
        ];
    }

    public function login($email, $senha): array
    {
        $email = $this->normalizarEmail((string)$email);
        $senha = (string)$senha;

        if ($email === '' || $senha === '') {
            return ['sucesso' => false, 'mensagem' => 'Informe email e senha para continuar.'];
        }

        $usuario = $this->userModel->obterPorEmail($email, true);
        if (!$usuario) {
            password_verify($senha, self::DUMMY_PASSWORD_HASH);
            $this->registrarEventoLogin('login_failed', $email, null, 'usuario_nao_encontrado');
            return ['sucesso' => false, 'mensagem' => 'Credenciais inválidas.'];
        }

        if ($this->isUsuarioBloqueado($usuario)) {
            $this->registrarEventoLogin('login_blocked', $email, (int)$usuario['id'], 'bloqueio_temporario');
            return ['sucesso' => false, 'mensagem' => 'Muitas tentativas de login. Aguarde alguns minutos e tente novamente.'];
        }

        if (!password_verify($senha, (string)$usuario['senha_hash'])) {
            $state = $this->userModel->registrarFalhaLogin((int)$usuario['id'], self::MAX_LOGIN_ATTEMPTS, self::LOCK_MINUTES);
            $this->registrarEventoLogin('login_failed', $email, (int)$usuario['id'], 'senha_invalida');

            if (!empty($state['locked_until'])) {
                return ['sucesso' => false, 'mensagem' => 'Muitas tentativas de login. Aguarde alguns minutos e tente novamente.'];
            }

            return ['sucesso' => false, 'mensagem' => 'Credenciais inválidas.'];
        }

        if ((int)($usuario['email_verified'] ?? 0) !== 1) {
            $this->registrarEventoLogin('login_denied', $email, (int)$usuario['id'], 'email_nao_confirmado');
            return ['sucesso' => false, 'mensagem' => 'Verifique seu email para ativar sua conta antes de entrar.'];
        }

        $this->userModel->resetarFalhasLogin((int)$usuario['id']);

        if (password_needs_rehash((string)$usuario['senha_hash'], PASSWORD_DEFAULT)) {
            $this->userModel->atualizarSenha((int)$usuario['id'], password_hash($senha, PASSWORD_DEFAULT));
        }

        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'role' => $usuario['role'],
            'fotografia' => $usuario['fotografia'] ?? null,
        ];

        $this->registrarEventoLogin('login_success', $email, (int)$usuario['id'], 'ok');

        return ['sucesso' => true, 'mensagem' => 'Login realizado com sucesso.'];
    }

    public function confirmarEmailPorCodigo(?string $email, ?string $codigo): array
    {
        $email = $this->normalizarEmail((string)$email);
        $codigo = preg_replace('/\D/', '', (string)$codigo);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['sucesso' => false, 'mensagem' => 'Informe um email válido para confirmar a conta.'];
        }

        if ($codigo === '' || strlen($codigo) !== self::VERIFICATION_CODE_LENGTH) {
            return ['sucesso' => false, 'mensagem' => 'Informe o código de 6 dígitos enviado para o seu email.'];
        }

        $resultado = $this->userModel->confirmarEmailPorCodigo($email, $this->hashToken($codigo));
        if (function_exists('registrar_log')) {
            registrar_log($resultado['sucesso'] ? 'email_verified' : 'email_verify_failed', 'Confirmacao de email');
        }

        if (!empty($resultado['sucesso'])) {
            $this->clearInlineVerificationCode($email);
        }

        return $resultado;
    }

    public function solicitarReenvioConfirmacaoEmail(string $email): array
    {
        $email = $this->normalizarEmail($email);
        $mensagemGenerica = 'Se existir uma conta pendente para este email, enviaremos uma nova mensagem de confirmação.';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['sucesso' => true, 'mensagem' => $mensagemGenerica];
        }

        $usuario = $this->userModel->obterPorEmail($email, true);
        if (!$usuario) {
            $this->registrarEventoLogin('email_verification_resent', $email, null, 'generic_response');
            return ['sucesso' => true, 'mensagem' => $mensagemGenerica];
        }

        if ((int)($usuario['email_verified'] ?? 0) === 1) {
            return ['sucesso' => true, 'mensagem' => 'Esta conta já está confirmada. Você já pode entrar normalmente.'];
        }

        $codigo = $this->gerarCodigoVerificacao();
        $tokenHash = $this->hashToken($codigo);
        $expiresAt = $this->formatFutureDate('+' . self::VERIFICATION_TOKEN_HOURS . ' hours');
        $this->userModel->atualizarTokenVerificacaoPorEmail($email, $tokenHash, $expiresAt);

        $emailEnviado = $this->enviarEmailConfirmacao(
            (string)($usuario['nome'] ?? 'utilizador'),
            $email,
            $codigo,
            (string)($usuario['role'] ?? 'aluno')
        );

        if (!$emailEnviado) {
            if (function_exists('registrar_log')) {
                registrar_log('verification_email_failed', 'Falha ao reenviar confirmação para ' . $email, (int)($usuario['id'] ?? 0));
            }
            if ($this->allowInlineVerificationFallback()) {
                $this->storeInlineVerificationCode($email, $codigo);
                return ['sucesso' => true, 'mensagem' => 'O email falhou agora. Use este código para continuar a ativação: ' . $codigo];
            }

            return ['sucesso' => false, 'mensagem' => 'Não foi possível reenviar o email agora. Tente novamente em instantes.'];
        }

        $this->registrarEventoLogin('email_verification_resent', $email, (int)($usuario['id'] ?? 0), 'resent');
        return ['sucesso' => true, 'mensagem' => 'Enviamos um novo email de confirmação. Verifique sua caixa de entrada e o spam.'];
    }

    public function solicitarRecuperacaoSenha(string $email): array
    {
        $email = $this->normalizarEmail($email);

        $mensagemGenerica = 'Se o email informado estiver cadastrado, enviaremos um link de recuperação.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['sucesso' => true, 'mensagem' => $mensagemGenerica];
        }

        $usuario = $this->userModel->obterPorEmail($email, true);
        if (!$usuario || (int)($usuario['email_verified'] ?? 0) !== 1) {
            $this->registrarEventoLogin('password_reset_requested', $email, $usuario['id'] ?? null, 'generic_response');
            return ['sucesso' => true, 'mensagem' => $mensagemGenerica];
        }

        $token = $this->gerarTokenSeguro();
        $tokenHash = $this->hashToken($token);
        $expiresAt = $this->formatFutureDate('+' . self::RESET_TOKEN_MINUTES . ' minutes');
        $this->userModel->salvarResetToken((int)$usuario['id'], $tokenHash, $expiresAt);

        $emailEnviado = $this->enviarEmailRecuperacao((string)$usuario['nome'], (string)$usuario['email'], $token);
        if (!$emailEnviado && function_exists('registrar_log')) {
            registrar_log('password_reset_email_failed', 'Falha ao enviar reset para ' . $email, (int)$usuario['id']);
        }

        $this->registrarEventoLogin('password_reset_requested', $email, (int)$usuario['id'], 'generic_response');
        return ['sucesso' => true, 'mensagem' => $mensagemGenerica];
    }

    public function validarResetToken(?string $token): array
    {
        $token = trim((string)$token);
        if ($token === '') {
            return ['sucesso' => false, 'mensagem' => 'O link de redefinição é inválido ou expirou.'];
        }

        $usuario = $this->userModel->obterPorResetToken($this->hashToken($token));
        if (!$usuario) {
            return ['sucesso' => false, 'mensagem' => 'O link de redefinição é inválido ou expirou.'];
        }

        return ['sucesso' => true, 'usuario' => $usuario];
    }

    public function redefinirSenha(?string $token, string $senha, string $confirmSenha): array
    {
        $token = trim((string)$token);
        if ($token === '') {
            return ['sucesso' => false, 'mensagem' => 'O link de redefinição é inválido ou expirou.'];
        }

        $validacaoSenha = $this->validarSenhaForte($senha, $confirmSenha);
        if (!$validacaoSenha['sucesso']) {
            return ['sucesso' => false, 'erros' => $validacaoSenha['erros']];
        }

        $resultado = $this->userModel->redefinirSenhaPorToken($this->hashToken($token), password_hash($senha, PASSWORD_DEFAULT));
        if (function_exists('registrar_log')) {
            registrar_log($resultado['sucesso'] ? 'password_reset_success' : 'password_reset_failed', 'Fluxo de redefinicao de senha');
        }
        return $resultado;
    }

    public function logout(): array
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
        }

        return ['sucesso' => true];
    }

    private function validarEmailCadastro(string $email): array
    {
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['sucesso' => false, 'mensagem' => 'Informe um email válido.'];
        }

        $domain = strtolower((string)substr(strrchr($email, '@') ?: '', 1));
        if ($domain === '') {
            return ['sucesso' => false, 'mensagem' => 'Informe um email válido.'];
        }

        if (in_array($domain, self::DISPOSABLE_EMAIL_DOMAINS, true)) {
            return ['sucesso' => false, 'mensagem' => 'Emails temporários ou descartáveis não são permitidos.'];
        }

        if (function_exists('checkdnsrr') && !checkdnsrr($domain, 'MX')) {
            return ['sucesso' => false, 'mensagem' => 'O domínio do email informado não é válido para recebimento.'];
        }

        return ['sucesso' => true];
    }

    private function validarSenhaForte(string $senha, string $confirmSenha): array
    {
        $errors = [];

        if (strlen($senha) < 8) {
            $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
        }
        if (!preg_match('/[A-Za-z]/', $senha)) {
            $errors[] = 'A senha deve conter pelo menos uma letra.';
        }
        if (!preg_match('/\d/', $senha)) {
            $errors[] = 'A senha deve conter pelo menos um número.';
        }
        if ($senha !== $confirmSenha) {
            $errors[] = 'A confirmação da senha não confere.';
        }

        return ['sucesso' => empty($errors), 'erros' => $errors];
    }

    private function enviarEmailConfirmacao(string $nome, string $email, string $codigo, string $role): bool
    {
        $perfil = $role === 'professor' ? 'instrutor' : 'aluno';
        $assunto = 'Código de confirmação - Plataforma EAD';
        $mensagem = '
            <div style="font-family:Arial,sans-serif;color:#172033;line-height:1.6">
                <h2>Olá, ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '.</h2>
                <p>Seu cadastro como ' . htmlspecialchars($perfil, ENT_QUOTES, 'UTF-8') . ' foi recebido com sucesso.</p>
                <p>Use este código para confirmar seu email:</p>
                <p style="margin:20px 0;font-size:32px;font-weight:800;letter-spacing:8px;">' . htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') . '</p>
                <p>Digite o código na página de confirmação da conta.</p>
                <p>O código expira em ' . self::VERIFICATION_TOKEN_HOURS . ' horas.</p>
            </div>
        ';

        return enviar_email($email, $assunto, $mensagem, 'text/html');
    }

    private function enviarEmailRecuperacao(string $nome, string $email, string $token): bool
    {
        $link = APP_URL . '/index.php?page=redefinir-senha&token=' . urlencode($token);
        $assunto = 'Redefinição de senha - Plataforma EAD';
        $mensagem = '
            <div style="font-family:Arial,sans-serif;color:#172033;line-height:1.6">
                <h2>Olá, ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '.</h2>
                <p>Recebemos um pedido para redefinir sua senha.</p>
                <p>Use o botão abaixo para criar uma nova senha:</p>
                <p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:12px 18px;border-radius:999px;background:#3159d7;color:#fff;text-decoration:none;font-weight:700">Redefinir senha</a></p>
                <p>Se preferir, copie e cole este link no navegador:</p>
                <p>' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '</p>
                <p>Este link expira em ' . self::RESET_TOKEN_MINUTES . ' minutos.</p>
                <p>Se você não fez esta solicitação, pode ignorar esta mensagem com segurança.</p>
            </div>
        ';

        return enviar_email($email, $assunto, $mensagem, 'text/html');
    }

    private function registrarEventoLogin(string $acao, string $email, $userId = null, string $detalhe = ''): void
    {
        if (!function_exists('registrar_log')) {
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
        $descricao = trim('email=' . $email . ' ip=' . $ip . ' ' . $detalhe);
        registrar_log($acao, $descricao, $userId ? (int)$userId : null);
    }

    private function isUsuarioBloqueado(array $usuario): bool
    {
        $lockedUntil = $usuario['locked_until'] ?? null;
        if (!$lockedUntil) {
            return false;
        }

        $lockedAt = strtotime((string)$lockedUntil);
        return $lockedAt !== false && $lockedAt > time();
    }

    private function normalizarEmail(string $email): string
    {
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return mb_strtolower($email);
    }

    private function normalizarRole(string $role): string
    {
        $allowed = ['aluno', 'professor', 'admin'];
        return in_array($role, $allowed, true) ? $role : 'aluno';
    }

    private function gerarTokenSeguro(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function gerarCodigoVerificacao(): string
    {
        return str_pad((string)random_int(0, (10 ** self::VERIFICATION_CODE_LENGTH) - 1), self::VERIFICATION_CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function formatFutureDate(string $modifier): string
    {
        return (new DateTimeImmutable($modifier))->format('Y-m-d H:i:s');
    }

    private function allowInlineVerificationFallback(): bool
    {
        if (function_exists('env_bool')) {
            return env_bool('EMAIL_VERIFICATION_INLINE_FALLBACK', true);
        }

        return true;
    }

    private function storeInlineVerificationCode(string $email, string $codigo): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['inline_verification_code'] = [
            'email' => $email,
            'codigo' => $codigo,
            'created_at' => time(),
        ];
    }

    private function clearInlineVerificationCode(string $email): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $stored = $_SESSION['inline_verification_code'] ?? null;
        if (is_array($stored) && (($stored['email'] ?? '') === $email)) {
            unset($_SESSION['inline_verification_code']);
        }
    }

    public static function estaAutenticado()
    {
        return isset($_SESSION['usuario']);
    }

    public static function obterUsuarioAtual()
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function verificarPermissao($roleNecessaria)
    {
        if (!self::estaAutenticado()) {
            return false;
        }

        $usuario = self::obterUsuarioAtual();
        $rolesPermitidas = is_array($roleNecessaria) ? $roleNecessaria : [$roleNecessaria];

        return in_array($usuario['role'], $rolesPermitidas, true);
    }

    public static function exigirAutenticacao()
    {
        if (!self::estaAutenticado()) {
            $base = defined('BASE_URL') ? BASE_URL : '';
            header('Location: ' . $base . '/index.php?page=login');
            exit;
        }
    }

    public static function exigirPermissao($roles)
    {
        self::exigirAutenticacao();
        if (!self::verificarPermissao($roles)) {
            die('Acesso negado');
        }
    }
}
?>
