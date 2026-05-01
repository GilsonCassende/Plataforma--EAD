<?php
/**
 * Model: User
 * Gerencia operações com usuários e credenciais
 */

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function obterPorEmail(string $email, bool $comCredenciais = false)
    {
        $fields = $comCredenciais
            ? 'id, nome, email, role, fotografia, senha_hash, email_verified, verification_token, verification_token_expires_at, reset_token, reset_token_expires_at, login_attempts, locked_until, created_at, updated_at'
            : 'id, nome, email, role, fotografia, email_verified, created_at, updated_at';

        $stmt = $this->pdo->prepare("SELECT {$fields} FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function obterPorId($id)
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, email, role, fotografia, email_verified, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function obterComSenhaPorId($id)
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, email, role, fotografia, senha_hash, email_verified, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function emailDisponivel($email, $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE email = ?';
        $params = [$email];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return !$stmt->fetch();
    }

    public function criar(string $nome, string $email, string $senhaHash, string $role): array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT id, email_verified FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $existente = $stmt->fetch();

            if ($existente && (int)($existente['email_verified'] ?? 0) === 1) {
                return ['sucesso' => false, 'mensagem' => 'Já existe uma conta ativa com este email.'];
            }

            if ($existente) {
                $stmt = $this->pdo->prepare(
                    'UPDATE users
                     SET nome = ?, senha_hash = ?, role = ?, email_verified = 1,
                         verification_token = NULL, verification_token_expires_at = NULL,
                         reset_token = NULL, reset_token_expires_at = NULL,
                         login_attempts = 0, locked_until = NULL, updated_at = NOW()
                     WHERE id = ?'
                );
                $stmt->execute([
                    $nome,
                    $senhaHash,
                    $role,
                    $existente['id']
                ]);

                return ['sucesso' => true, 'user_id' => (int)$existente['id'], 'mensagem' => 'Conta atualizada com sucesso.'];
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO users
                    (nome, email, senha_hash, role, email_verified, verification_token, verification_token_expires_at, reset_token, reset_token_expires_at, login_attempts, locked_until)
                 VALUES
                    (?, ?, ?, ?, 1, NULL, NULL, NULL, NULL, 0, NULL)'
            );
            $stmt->execute([$nome, $email, $senhaHash, $role]);

            return ['sucesso' => true, 'user_id' => (int)$this->pdo->lastInsertId(), 'mensagem' => 'Conta criada com sucesso.'];
        } catch (Throwable $e) {
            return ['sucesso' => false, 'mensagem' => 'Não foi possível concluir o cadastro agora.'];
        }
    }

    public function salvarResetToken(int $userId, string $tokenHash, string $expiresAt): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET reset_token = ?, reset_token_expires_at = ?, updated_at = NOW()
             WHERE id = ?'
        );
        return $stmt->execute([$tokenHash, $expiresAt, $userId]);
    }

    public function obterPorResetToken(string $tokenHash)
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email, role, email_verified, reset_token_expires_at
             FROM users
             WHERE reset_token = ?
               AND reset_token_expires_at IS NOT NULL
               AND reset_token_expires_at >= NOW()
             LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        return $stmt->fetch();
    }

    public function redefinirSenhaPorToken(string $tokenHash, string $senhaHash): array
    {
        $usuario = $this->obterPorResetToken($tokenHash);
        if (!$usuario) {
            return ['sucesso' => false, 'mensagem' => 'O link de redefinição é inválido ou expirou.'];
        }

        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET senha_hash = ?,
                 reset_token = NULL,
                 reset_token_expires_at = NULL,
                 login_attempts = 0,
                 locked_until = NULL,
                 updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$senhaHash, $usuario['id']]);

        return ['sucesso' => true, 'mensagem' => 'Sua senha foi redefinida com sucesso.'];
    }

    public function limparResetToken(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET reset_token = NULL, reset_token_expires_at = NULL, updated_at = NOW()
             WHERE id = ?'
        );
        return $stmt->execute([$id]);
    }

    public function registrarFalhaLogin(int $userId, int $maxTentativas, int $lockMinutes): array
    {
        $stmt = $this->pdo->prepare('SELECT login_attempts FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $current = $stmt->fetch() ?: ['login_attempts' => 0];
        $nextAttempts = (int)($current['login_attempts'] ?? 0) + 1;
        $lockAccount = $nextAttempts >= $maxTentativas;

        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET login_attempts = ?,
                 locked_until = ?,
                 updated_at = NOW()
             WHERE id = ?'
        );

        $lockedUntil = $lockAccount
            ? (new DateTimeImmutable('+' . $lockMinutes . ' minutes'))->format('Y-m-d H:i:s')
            : null;

        $stmt->execute([$nextAttempts, $lockedUntil, $userId]);

        return [
            'login_attempts' => $nextAttempts,
            'locked_until' => $lockedUntil,
        ];
    }

    public function resetarFalhasLogin(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET login_attempts = 0, locked_until = NULL, updated_at = NOW()
             WHERE id = ?'
        );
        return $stmt->execute([$userId]);
    }

    public function atualizar($id, $nome, $email, $fotografia = null)
    {
        try {
            if ($fotografia !== null) {
                $stmt = $this->pdo->prepare('UPDATE users SET nome = ?, email = ?, fotografia = ? WHERE id = ?');
                return $stmt->execute([$nome, $email, $fotografia, $id]);
            }

            $stmt = $this->pdo->prepare('UPDATE users SET nome = ?, email = ? WHERE id = ?');
            return $stmt->execute([$nome, $email, $id]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function atualizarSenha($id, $senhaHash)
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE users
                 SET senha_hash = ?, reset_token = NULL, reset_token_expires_at = NULL, updated_at = NOW()
                 WHERE id = ?'
            );
            return $stmt->execute([$senhaHash, $id]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function deletar($id)
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function listar($role = null)
    {
        if ($role) {
            $stmt = $this->pdo->prepare('SELECT id, nome, email, role, email_verified, created_at FROM users WHERE role = ? ORDER BY created_at DESC');
            $stmt->execute([$role]);
        } else {
            $stmt = $this->pdo->query('SELECT id, nome, email, role, email_verified, created_at FROM users ORDER BY created_at DESC');
        }
        return $stmt->fetchAll();
    }

    public function contar($role = null)
    {
        if ($role) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) as total FROM users WHERE role = ?');
            $stmt->execute([$role]);
        } else {
            $stmt = $this->pdo->query('SELECT COUNT(*) as total FROM users');
        }

        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
}
