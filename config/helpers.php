<?php

/**
 * Funções Helper Utilitárias
 * Incluir em config/database.php para usar em todo o projeto
 */

/**
 * Redirecionar com mensagem
 */
function redirecionar($url, $mensagem = null, $tipo = 'mensagem')
{
    if ($mensagem) {
        $_SESSION[$tipo] = $mensagem;
    }
    header("Location: $url");
    exit;
}

/**
 * Obter dados da sessão com segurança
 */
function session_get($chave, $padrao = null)
{
    return $_SESSION[$chave] ?? $padrao;
}

/**
 * Definir dado da sessão
 */
function session_set($chave, $valor)
{
    $_SESSION[$chave] = $valor;
}

/**
 * Sanitizar dados
 */
function sanitizar($dados)
{
    if (is_array($dados)) {
        return array_map('sanitizar', $dados);
    }
    return htmlspecialchars(trim($dados), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function validar_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar senha forte
 */
function validar_senha($senha)
{
    return strlen((string)$senha) >= 8
        && preg_match('/[A-Za-z]/', (string)$senha)
        && preg_match('/\d/', (string)$senha);
}

/**
 * Gerar slug a partir de texto
 */
function gerar_slug($texto)
{
    $texto = transliterar($texto);
    $texto = mb_strtolower($texto);
    $texto = preg_replace('/[^\w\s-]/', '', $texto);
    $texto = preg_replace('/[\s-]+/', '-', $texto);
    return trim($texto, '-');
}

/**
 * Transliterar caracteres especiais
 */
function transliterar($texto)
{
    $conversoes = [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ã' => 'a',
        'õ' => 'o',
        'ç' => 'c',
        'Á' => 'a',
        'É' => 'e',
        'Í' => 'i',
        'Ó' => 'o',
        'Ú' => 'u',
        'Ã' => 'a',
        'Õ' => 'o',
        'Ç' => 'c'
    ];
    return strtr($texto, $conversoes);
}

/**
 * Formatar data para exibição
 */
function formatar_data($data, $formato = 'd/m/Y')
{
    return date($formato, strtotime($data));
}

/**
 * Formatar valor monetário
 */
function formatar_moeda($valor)
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Calcular tempo decorrido (ex: "há 2 horas")
 */
function tempo_decorrido($data)
{
    $agora = new DateTime();
    $data_obj = new DateTime($data);
    $diff = $agora->diff($data_obj);

    if ($diff->y > 0) return $diff->y . ' ano' . ($diff->y > 1 ? 's' : '') . ' atrás';
    if ($diff->m > 0) return $diff->m . ' mês' . ($diff->m > 1 ? 'es' : '') . ' atrás';
    if ($diff->d > 0) return $diff->d . ' dia' . ($diff->d > 1 ? 's' : '') . ' atrás';
    if ($diff->h > 0) return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '') . ' atrás';
    if ($diff->i > 0) return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '') . ' atrás';
    return 'agora';
}

/**
 * Gerar UUID
 */
function gerar_uuid()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

/**
 * Fazer upload de arquivo
 */
function fazer_upload($arquivo, $pasta_destino, $extensoes_permitidas = [])
{
    // Verificações padrão
    if (!isset($arquivo['tmp_name'])) {
        return ['sucesso' => false, 'mensagem' => 'Nenhum arquivo enviado'];
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        return ['sucesso' => false, 'mensagem' => 'Erro no upload'];
    }

    $nome_original = $arquivo['name'];
    $tipo = mime_content_type($arquivo['tmp_name']);
    $ext = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));

    // Limite de tamanho (padrão 5MB)
    $max_size = 5 * 1024 * 1024;
    if (isset($arquivo['size']) && $arquivo['size'] > $max_size) {
        return ['sucesso' => false, 'mensagem' => 'Arquivo muito grande (máx 5MB)'];
    }

    // Validar extensão
    if (!empty($extensoes_permitidas) && !in_array($ext, $extensoes_permitidas)) {
        return ['sucesso' => false, 'mensagem' => 'Tipo de arquivo não permitido'];
    }

    // Validar MIME type com finfo para evitar spoofing (usar API OO para reduzir warnings)
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($arquivo['tmp_name']);
        // liberar recurso
        unset($finfo);

        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4'
        ];

        if (!empty($extensoes_permitidas) && isset($mimeMap[$ext]) && $mimeMap[$ext] !== $mime) {
            return ['sucesso' => false, 'mensagem' => 'Tipo de arquivo inconsistente com extensão'];
        }
    }

    $isImageUpload = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    $targetExt = $isImageUpload ? 'webp' : $ext;

    // Gerar nome único
    $nome_novo = uniqid() . '_' . date('YmdHis') . '.' . $targetExt;
    $caminho = $pasta_destino . '/' . $nome_novo;
    $nome_fallback = uniqid() . '_' . date('YmdHis') . '.' . $ext;
    $caminhoFallback = $pasta_destino . '/' . $nome_fallback;

    // Criar pasta se não existir
    if (!is_dir($pasta_destino)) {
        @mkdir($pasta_destino, 0777, true);
    }

    if (is_dir($pasta_destino) && !is_writable($pasta_destino)) {
        @chmod($pasta_destino, 0777);
    }

    if (!is_dir($pasta_destino) || !is_writable($pasta_destino)) {
        return ['sucesso' => false, 'mensagem' => 'A pasta de destino não tem permissão de escrita'];
    }

    // Mover arquivo
    $tempPath = $isImageUpload
        ? $pasta_destino . '/' . uniqid('upload_tmp_', true) . '.' . $ext
        : $caminho;

    if (move_uploaded_file($arquivo['tmp_name'], $tempPath)) {
        if ($isImageUpload && function_exists('getimagesize')) {
            $maxW = 1600;
            $maxH = 1600;
            try {
                image_resize($tempPath, $caminho, $maxW, $maxH, 'webp');
                if ($tempPath !== $caminho && is_file($tempPath)) {
                    @unlink($tempPath);
                }
            } catch (Exception $e) {
                @error_log('Erro ao converter imagem para WebP: ' . $e->getMessage());
                try {
                    image_resize($tempPath, $caminhoFallback, $maxW, $maxH, null);
                    $nome_novo = $nome_fallback;
                    $caminho = $caminhoFallback;
                    if ($tempPath !== $caminhoFallback && is_file($tempPath)) {
                        @unlink($tempPath);
                    }
                } catch (Exception $fallbackException) {
                    if (!@rename($tempPath, $caminhoFallback)) {
                        @unlink($tempPath);
                        return ['sucesso' => false, 'mensagem' => 'Erro ao processar imagem'];
                    }
                    $nome_novo = $nome_fallback;
                    $caminho = $caminhoFallback;
                }
            }
        }

        return [
            'sucesso' => true,
            'nome' => $nome_novo,
            'caminho' => $caminho,
            'mensagem' => 'Arquivo enviado com sucesso'
        ];
    }

    return ['sucesso' => false, 'mensagem' => 'Erro ao mover arquivo'];
}

/**
 * Verificar se usuário está autenticado
 */
function usuario_autenticado()
{
    return isset($_SESSION['usuario']);
}

/**
 * Obter usuário atual
 */
function usuario_atual()
{
    return $_SESSION['usuario'] ?? null;
}

/**
 * Verificar permissão do usuário
 */
function tem_permissao($roles)
{
    if (!usuario_autenticado()) return false;

    $usuario = usuario_atual();
    $roles = is_array($roles) ? $roles : [$roles];

    return in_array($usuario['role'], $roles);
}

/**
 * Exigir autenticação (redireciona se não autenticado)
 */
function exigir_autenticacao()
{
    if (!usuario_autenticado()) {
        redirecionar('/Plataforma-EAD/public/index.php?page=login', 'Por favor, faça login', 'erro');
    }
}

/**
 * Exigir permissão
 */
function exigir_permissao($roles)
{
    exigir_autenticacao();

    if (!tem_permissao($roles)) {
        die('Acesso Negado');
    }
}

/**
 * Log de atividades (opcional)
 */
function registrar_log($acao, $descricao, $user_id = null)
{
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/app.log';
    if ((!is_dir($logDir) || !is_writable($logDir)) && is_writable(sys_get_temp_dir())) {
        $logFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plataforma-ead-app.log';
    }
    $entry = "[" . date('Y-m-d H:i:s') . "] " . $acao . ": " . $descricao;
    if ($user_id) $entry .= " (user_id=" . $user_id . ")";
    $entry .= PHP_EOL;
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * CSRF helpers
 */
function gerar_csrf()
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validar_csrf($token)
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($token)) return false;
    if (empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_input()
{
    $token = gerar_csrf();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}


/**
 * Sanitize HTML string by removing script/style tags and dangerous attributes
 * Returns cleaned HTML safe for insertion in the DOM.
 */
function sanitize_html($html)
{
    if (empty($html)) return '';

    // Use DOMDocument to remove scripts and event handlers
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    // Ensure proper encoding
    $loaded = $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    if (!$loaded) {
        // Fallback to escaping if parsing fails
        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }

    // Remove script and style nodes
    $tags = ['script', 'style', 'iframe', 'object'];
    foreach ($tags as $t) {
        $nodes = $doc->getElementsByTagName($t);
        for ($i = $nodes->length - 1; $i >= 0; $i--) {
            $node = $nodes->item($i);
            if ($node) $node->parentNode->removeChild($node);
        }
    }

    // Remove attributes that start with 'on' (event handlers) and javascript: URIs
    $xpath = new DOMXPath($doc);
    foreach ($xpath->query('//*') as $el) {
        if ($el->hasAttributes()) {
            $attrs = [];
            foreach ($el->attributes as $attr) {
                $attrs[] = $attr->name;
            }
            foreach ($attrs as $name) {
                if (stripos($name, 'on') === 0) {
                    $el->removeAttribute($name);
                    continue;
                }
                // remove javascript: in href/src/style-like attributes
                if (in_array(strtolower($name), ['href', 'src', 'xlink:href'])) {
                    $val = $el->getAttribute($name);
                    if (preg_match('/^\s*javascript:/i', $val)) {
                        $el->removeAttribute($name);
                    }
                }
                // remove style attributes containing expression/javascript
                if (strtolower($name) === 'style') {
                    $val = $el->getAttribute('style');
                    if (preg_match('/expression\(|javascript:/i', $val)) {
                        $el->removeAttribute('style');
                    }
                }
            }
        }
    }

    // Return cleaned innerHTML
    $body = $doc->getElementsByTagName('body')->item(0);
    if ($body) {
        $inner = '';
        foreach ($body->childNodes as $child) {
            $inner .= $doc->saveHTML($child);
        }
        return $inner;
    }

    return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
}

/**
 * Safe echo helper: escape string for HTML output
 */
function safe_echo($str)
{
    echo htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Construir URL pública para thumbnails armazenados na pasta uploads
 */
function thumbnail_url($thumb)
{
    return upload_image_url($thumb, [
        'w' => 640,
        'h' => 360,
        'fit' => 'cover',
        'q' => 82,
    ]);
}

/**
 * Construir URL pública para imagens armazenadas em uploads, com suporte a redimensionamento.
 */
function upload_image_url($file, array $params = [])
{
    if (empty($file)) return '';
    if (preg_match('#^https?://#i', $file) || strpos($file, '/') === 0) {
        return $file;
    }

    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $query = http_build_query(array_merge(['src' => $file], array_filter($params, static fn($value) => $value !== null && $value !== '')));

    return ($base !== '' ? $base : '') . '/image.php?' . $query;
}

/**
 * Enviar email (requer configuração SMTP)
 */
function enviar_email($para, $assunto, $mensagem, $tipo_conteudo = 'text/plain', array $attachments = [])
{
    set_last_mail_error(null);

    if (function_exists('load_project_env')) {
        load_project_env(__DIR__ . '/../.env');
    }

    // Validar endereço de email destinatário
    if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
        set_last_mail_error('Endereço de email inválido.');
        return false;
    }

    $assunto = str_replace(["\r", "\n"], ' ', (string)$assunto);
    $tipo_conteudo = trim((string)$tipo_conteudo) ?: 'text/plain';

    $envReader = function (string $key, $default = null) {
        if (function_exists('env_value')) {
            return env_value($key, $default);
        }

        $value = getenv($key);
        return $value === false ? $default : $value;
    };

    $smtpHost = trim((string)$envReader('SMTP_HOST', ''));
    $smtpPort = function_exists('env_int') ? env_int('SMTP_PORT', 587) : (int)$envReader('SMTP_PORT', 587);
    $smtpUser = trim((string)$envReader('SMTP_USER', ''));
    $smtpPass = (string)$envReader('SMTP_PASS', '');
    $smtpSecure = strtolower(trim((string)$envReader('SMTP_SECURE', 'tls')));
    $mailFrom = trim((string)$envReader('MAIL_FROM', ''));
    $mailFromName = trim((string)$envReader('MAIL_FROM_NAME', 'Plataforma EAD'));
    $smtpTimeout = function_exists('env_int') ? env_int('SMTP_TIMEOUT', 15) : (int)$envReader('SMTP_TIMEOUT', 15);
    $fallbackMode = strtolower(trim((string)$envReader('MAIL_FALLBACK_MODE', 'none')));

    if ($mailFrom === '' && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
        $mailFrom = $smtpUser;
    }

    $smtpMissing = [];
    if ($smtpHost === '') {
        $smtpMissing[] = 'SMTP_HOST';
    }
    if ($smtpPort <= 0) {
        $smtpMissing[] = 'SMTP_PORT';
    }
    if ($smtpUser === '') {
        $smtpMissing[] = 'SMTP_USER';
    }
    if ($smtpPass === '') {
        $smtpMissing[] = 'SMTP_PASS';
    }
    if ($mailFrom === '' || !filter_var($mailFrom, FILTER_VALIDATE_EMAIL)) {
        $smtpMissing[] = 'MAIL_FROM';
    }

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $composerAutoload = __DIR__ . '/../vendor/autoload.php';
        if (is_file($composerAutoload)) {
            require_once $composerAutoload;
        }
    }

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $erro = 'PHPMailer não está disponível. Execute composer install para carregar vendor/autoload.php.';
        set_last_mail_error($erro);
        if (function_exists('registrar_log')) {
            registrar_log('smtp_error', $erro);
        }
        error_log($erro);
        return email_fallback_outbox($para, $assunto, $mensagem, $tipo_conteudo, $fallbackMode, $erro);
    }

    if ($smtpMissing) {
        $erro = 'Configuração SMTP incompleta: ' . implode(', ', array_unique($smtpMissing));
        set_last_mail_error($erro);
        if (function_exists('registrar_log')) {
            registrar_log('smtp_error', $erro);
        }
        error_log($erro);
        return email_fallback_outbox($para, $assunto, $mensagem, $tipo_conteudo, $fallbackMode, $erro);
    }

    $secureMap = [
        'tls' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
        'starttls' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
        'ssl' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS,
        'smtps' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS,
        'none' => '',
        '' => '',
    ];

    if (!array_key_exists($smtpSecure, $secureMap)) {
        $erro = 'Valor inválido para SMTP_SECURE. Use tls, ssl ou none.';
        set_last_mail_error($erro);
        if (function_exists('registrar_log')) {
            registrar_log('smtp_error', $erro);
        }
        error_log($erro);
        return email_fallback_outbox($para, $assunto, $mensagem, $tipo_conteudo, $fallbackMode, $erro);
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->Port = $smtpPort;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->Timeout = $smtpTimeout > 0 ? $smtpTimeout : 15;
        $mail->SMTPAutoTLS = $secureMap[$smtpSecure] === \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        if ($secureMap[$smtpSecure] !== '') {
            $mail->SMTPSecure = $secureMap[$smtpSecure];
        }

        $mail->setFrom($mailFrom, $mailFromName);
        $mail->addAddress($para);
        $mail->Subject = $assunto;
        $mail->Body = (string)$mensagem;
        $mail->AltBody = strip_tags(
            preg_replace('/<br\s*\/?>/i', PHP_EOL, (string)$mensagem) ?? (string)$mensagem
        );

        foreach ($attachments as $attachment) {
            $path = (string)($attachment['path'] ?? '');
            if ($path === '' || !is_file($path)) {
                continue;
            }

            $name = (string)($attachment['name'] ?? basename($path));
            $mime = trim((string)($attachment['mime'] ?? ''));
            if ($mime !== '') {
                $mail->addAttachment($path, $name, \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64, $mime);
            } else {
                $mail->addAttachment($path, $name);
            }
        }

        if (stripos($tipo_conteudo, 'html') !== false) {
            $mail->isHTML(true);
        }

        $sent = $mail->send();
        if ($sent && function_exists('registrar_log')) {
            registrar_log('smtp_success', 'Email enviado via SMTP para ' . $para);
        }

        return $sent;
    } catch (\Throwable $e) {
        $erro = 'Falha SMTP ao enviar para ' . $para . ': ' . $e->getMessage();
        set_last_mail_error($erro);
        if (function_exists('registrar_log')) {
            registrar_log('smtp_error', $erro);
        }
        error_log($erro);
        return email_fallback_outbox($para, $assunto, $mensagem, $tipo_conteudo, $fallbackMode, $erro);
    }
}

function email_fallback_outbox($para, $assunto, $mensagem, $tipo_conteudo, $fallbackMode = 'none', $motivo = '')
{
    if ($fallbackMode !== 'outbox') {
        return false;
    }

    $preferredOutbox = __DIR__ . '/../logs/mail-outbox';
    $fallbackOutbox = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plataforma-ead-mail-outbox';
    $outboxCandidates = [$preferredOutbox, $fallbackOutbox, rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)];
    $outboxDir = null;

    foreach ($outboxCandidates as $candidate) {
        if (!is_dir($candidate) && basename($candidate) !== basename(sys_get_temp_dir())) {
            @mkdir($candidate, 0777, true);
        }
        if (is_dir($candidate) && is_writable($candidate)) {
            $outboxDir = $candidate;
            break;
        }
    }

    if ($outboxDir === null) {
        return false;
    }

    $filename = $outboxDir . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.html';
    $body = stripos($tipo_conteudo, 'html') !== false
        ? $mensagem
        : nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'));

    $content = "<h2>Email salvo em outbox local</h2>\n"
        . '<p><strong>Para:</strong> ' . htmlspecialchars($para, ENT_QUOTES, 'UTF-8') . "</p>\n"
        . '<p><strong>Assunto:</strong> ' . htmlspecialchars($assunto, ENT_QUOTES, 'UTF-8') . "</p>\n"
        . ($motivo !== '' ? '<p><strong>Motivo do fallback:</strong> ' . htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8') . "</p>\n" : '')
        . "<hr>\n"
        . $body;

    $saved = @file_put_contents($filename, $content);
    if ($saved !== false) {
        if (function_exists('registrar_log')) {
            registrar_log('mail_outbox', 'Email salvo em outbox local por fallback controlado: ' . $filename);
        }
        return true;
    }

    return false;
}

function set_last_mail_error(?string $message): void
{
    $GLOBALS['last_mail_error'] = $message;
}

function get_last_mail_error(): ?string
{
    $value = $GLOBALS['last_mail_error'] ?? null;
    return is_string($value) && $value !== '' ? $value : null;
}

/**
 * Validar CPF (opcional)
 */
function validar_cpf($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    if (strlen($cpf) != 11 || preg_match('/^(\d)\1+$/', $cpf)) {
        return false;
    }

    // Validação de dígitos verificadores
    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($i = 0; $i < $t; $i++) {
            $d += $cpf[$i] * (($t + 1) - $i);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$t] != $d) return false;
    }

    return true;
}

/**
 * Truncar texto
 */
function truncar($texto, $limite = 100, $sufixo = '...')
{
    if (strlen($texto) <= $limite) return $texto;
    return substr($texto, 0, $limite) . $sufixo;
}

/**
 * Contar palavras
 */
function contar_palavras($texto)
{
    return count(array_filter(explode(' ', $texto)));
}

/**
 * Gerar QR Code (usando Google Charts)
 */
function gerar_qrcode($dados, $tamanho = 200)
{
    $dados_encoded = urlencode($dados);
    return "https://chart.googleapis.com/chart?chs={$tamanho}x{$tamanho}&chld=M|0&cht=qr&chl={$dados_encoded}";
}

/**
 * Extrair ID do YouTube a partir de uma URL ou ID direto
 */
function youtube_id_from_url($url)
{
    if (empty($url)) return null;
    $url = trim($url);
    // se já for um id curto
    if (preg_match('/^[a-zA-Z0-9_-]{8,}$/', $url)) return $url;

    $patterns = [
        '/youtu\.be\/([^\?&\/]+)/i',
        '/youtube\.com\/watch\?v=([^\?&\/]+)/i',
        '/youtube\.com\/embed\/([^\?&\/]+)/i',
        '/youtube\.com\/v\/([^\?&\/]+)/i',
    ];

    foreach ($patterns as $p) {
        if (preg_match($p, $url, $m)) return $m[1];
    }

    // fallback: tentar parsear query string v=
    $parts = parse_url($url);
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $qs);
        if (!empty($qs['v'])) return $qs['v'];
    }

    return null;
}

/**
 * Verifica se o usuário atual é o professor dono de um curso (passando o array $curso)
 */
function is_course_owner($curso)
{
    if (!$curso) return false;
    $usuario = usuario_atual();
    if (!$usuario) return false;
    if (($usuario['role'] ?? '') !== 'professor') return false;
    return isset($curso['teacher_id']) && $curso['teacher_id'] == $usuario['id'];
}

/**
 * Verifica se o usuário atual é o professor dono pelo course_id (usa PDO global)
 */
function is_course_owner_by_id($course_id)
{
    if (!$course_id) return false;
    $usuario = usuario_atual();
    if (!$usuario || ($usuario['role'] ?? '') !== 'professor') return false;
    global $pdo;
    if (!isset($pdo)) return false;
    $stmt = $pdo->prepare('SELECT teacher_id FROM courses WHERE id = ?');
    $stmt->execute([$course_id]);
    $row = $stmt->fetch();
    if (!$row) return false;
    return $row['teacher_id'] == $usuario['id'];
}

/**
 * Redimensiona uma imagem mantendo proporção.
 * Sobrescreve o arquivo de destino.
 * Requer extensão GD disponível.
 *
 * @param string $src Caminho da imagem origem
 * @param string $dst Caminho da imagem destino
 * @param int $max_width Largura máxima permitida
 * @param int $max_height Altura máxima permitida
 * @throws Exception
 */
function image_resize($src, $dst, $max_width, $max_height, $outputFormat = null)
{
    if (!extension_loaded('gd')) {
        throw new Exception('GD não disponível');
    }

    $info = getimagesize($src);
    if ($info === false) {
        throw new Exception('Arquivo não é uma imagem válida');
    }

    list($width, $height, $type) = $info;

    // Calcular proporção mantendo aspect ratio
    $ratio = $width / $height;
    $targetW = $max_width;
    $targetH = intval($targetW / $ratio);

    if ($targetH > $max_height) {
        $targetH = $max_height;
        $targetW = intval($targetH * $ratio);
    }

    // Criar recursos de imagem conforme tipo
    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImg = imagecreatefromjpeg($src);
            break;
        case IMAGETYPE_PNG:
            $srcImg = imagecreatefrompng($src);
            break;
        case IMAGETYPE_GIF:
            $srcImg = imagecreatefromgif($src);
            break;
        case IMAGETYPE_WEBP:
            if (!function_exists('imagecreatefromwebp')) {
                throw new Exception('WebP não suportado neste servidor');
            }
            $srcImg = imagecreatefromwebp($src);
            break;
        default:
            throw new Exception('Formato de imagem não suportado');
    }

    if (!$srcImg) {
        throw new Exception('Não foi possível abrir imagem');
    }

    $dstImg = imagecreatetruecolor($targetW, $targetH);

    $shouldPreserveAlpha = in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)
        || $outputFormat === 'webp';

    if ($shouldPreserveAlpha) {
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
        $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
        imagefilledrectangle($dstImg, 0, 0, $targetW, $targetH, $transparent);
    }

    if ($type == IMAGETYPE_GIF) {
        $transparent_index = imagecolortransparent($srcImg);
        if ($transparent_index >= 0) {
            $transparent_color = imagecolorsforindex($srcImg, $transparent_index);
            $transparent_index = imagecolorallocate($dstImg, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
            imagefill($dstImg, 0, 0, $transparent_index);
            imagecolortransparent($dstImg, $transparent_index);
        }
    }

    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $width, $height);

    $resolvedOutputFormat = $outputFormat;
    if ($resolvedOutputFormat === null) {
        $resolvedOutputFormat = match ($type) {
            IMAGETYPE_JPEG => 'jpeg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
            default => 'jpeg',
        };
    }

    switch ($resolvedOutputFormat) {
        case 'webp':
            if (!function_exists('imagewebp')) {
                throw new Exception('WebP não suportado neste servidor');
            }
            imagewebp($dstImg, $dst, 82);
            break;
        case 'jpeg':
        case 'jpg':
            imagejpeg($dstImg, $dst, 85);
            break;
        case 'png':
            imagepng($dstImg, $dst, 6);
            break;
        case 'gif':
            imagegif($dstImg, $dst);
            break;
        default:
            throw new Exception('Formato de saída não suportado');
    }

    // Liberar referências aos recursos de imagem (GC cuidará da liberação)
    if (isset($srcImg)) unset($srcImg);
    if (isset($dstImg)) unset($dstImg);
}
