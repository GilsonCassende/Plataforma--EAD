<?php

class BackupLogService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureSchema();
    }

    public function createLog(?int $userId, string $type, string $filePath, int $size, string $status, array $meta = []): array
    {
        $token = bin2hex(random_bytes(24));
        $stmt = $this->pdo->prepare(
            'INSERT INTO backup_logs (user_id, type, file_path, size, created_at, status, access_token, storage_disk, meta_json)
             VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId ?: null,
            $type,
            $filePath,
            $size,
            $status,
            $token,
            (string)($meta['storage_disk'] ?? 'local'),
            json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return [
            'id' => (int)$this->pdo->lastInsertId(),
            'access_token' => $token,
        ];
    }

    public function updateStatus(int $logId, string $status, array $meta = []): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE backup_logs
             SET status = ?, meta_json = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $status,
            json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $logId,
        ]);
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM backup_logs WHERE access_token = ? LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $row['meta_json'] = !empty($row['meta_json']) ? (json_decode((string)$row['meta_json'], true) ?: []) : [];
        return $row;
    }

    public function getPreference(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM backup_preferences WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }

        return [
            'user_id' => $userId,
            'auto_enabled' => 0,
            'frequency' => 'daily',
            'receive_email' => 0,
            'encryption_enabled' => 0,
            'last_backup_at' => null,
        ];
    }

    public function savePreference(int $userId, array $data): void
    {
        $frequency = in_array((string)($data['frequency'] ?? 'daily'), ['daily', 'weekly'], true)
            ? (string)$data['frequency']
            : 'daily';
        $stmt = $this->pdo->prepare(
            'INSERT INTO backup_preferences (user_id, auto_enabled, frequency, receive_email, encryption_enabled, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                auto_enabled = VALUES(auto_enabled),
                frequency = VALUES(frequency),
                receive_email = VALUES(receive_email),
                encryption_enabled = VALUES(encryption_enabled),
                updated_at = NOW()'
        );
        $stmt->execute([
            $userId,
            !empty($data['auto_enabled']) ? 1 : 0,
            $frequency,
            !empty($data['receive_email']) ? 1 : 0,
            !empty($data['encryption_enabled']) ? 1 : 0,
        ]);
    }

    public function markAutomaticRun(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE backup_preferences
             SET last_backup_at = NOW(), updated_at = NOW()
             WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
    }

    public function listUsersForAutomaticBackup(): array
    {
        $stmt = $this->pdo->query(
            "SELECT bp.*, u.id AS user_id, u.email, u.nome, u.role
             FROM backup_preferences bp
             INNER JOIN users u ON u.id = bp.user_id
             WHERE bp.auto_enabled = 1
             ORDER BY u.id ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function isDue(array $preference, ?DateTimeImmutable $now = null): bool
    {
        $now = $now ?: new DateTimeImmutable('now');
        $last = !empty($preference['last_backup_at']) ? new DateTimeImmutable((string)$preference['last_backup_at']) : null;
        if ($last === null) {
            return true;
        }

        $interval = (string)($preference['frequency'] ?? 'daily') === 'weekly' ? 'P7D' : 'P1D';
        return $last->add(new DateInterval($interval)) <= $now;
    }

    private function ensureSchema(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS backup_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                type VARCHAR(50) NOT NULL,
                file_path TEXT NOT NULL,
                size BIGINT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                status VARCHAR(30) NOT NULL DEFAULT "ready",
                access_token VARCHAR(80) NULL,
                storage_disk VARCHAR(30) NOT NULL DEFAULT "local",
                meta_json LONGTEXT NULL,
                INDEX idx_backup_logs_user_created (user_id, created_at),
                UNIQUE KEY uniq_backup_logs_token (access_token)
            )'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS backup_preferences (
                user_id INT PRIMARY KEY,
                auto_enabled TINYINT(1) NOT NULL DEFAULT 0,
                frequency VARCHAR(20) NOT NULL DEFAULT "daily",
                receive_email TINYINT(1) NOT NULL DEFAULT 0,
                encryption_enabled TINYINT(1) NOT NULL DEFAULT 0,
                last_backup_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )'
        );
    }
}
