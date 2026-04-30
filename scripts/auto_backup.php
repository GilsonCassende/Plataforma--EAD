<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script deve ser executado via CLI.\n");
    exit(1);
}

set_time_limit(0);
ini_set('memory_limit', '1024M');

session_start();

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/autoload.php';

$backupLogService = new BackupLogService($pdo);
$exportController = new ExportController($pdo);
$now = new DateTimeImmutable('now');

$results = [
    'processed_users' => 0,
    'global_backup' => false,
    'errors' => [],
];

foreach (array_chunk($backupLogService->listUsersForAutomaticBackup(), 25) as $userChunk) {
    foreach ($userChunk as $userRow) {
        try {
            if (!$backupLogService->isDue($userRow, $now)) {
                continue;
            }

            $_SESSION['usuario'] = [
                'id' => (int)$userRow['user_id'],
                'nome' => (string)$userRow['nome'],
                'email' => (string)$userRow['email'],
                'role' => (string)$userRow['role'],
            ];

            if ((string)$userRow['role'] === 'professor') {
                $exportController->exportarProfessor((int)$userRow['user_id'], null, 'all');
            } else {
                $exportController->exportarAluno((int)$userRow['user_id']);
            }

            $backupLogService->markAutomaticRun((int)$userRow['user_id']);
            $results['processed_users']++;
        } catch (Throwable $exception) {
            $results['errors'][] = [
                'user_id' => (int)($userRow['user_id'] ?? 0),
                'message' => $exception->getMessage(),
            ];
            if (function_exists('registrar_log')) {
                registrar_log('AUTO_BACKUP', 'Falha auto backup user=' . (int)($userRow['user_id'] ?? 0) . ' erro=' . $exception->getMessage(), (int)($userRow['user_id'] ?? 0));
            }
        }
    }
}

try {
    $_SESSION['usuario'] = [
        'id' => 1,
        'nome' => 'Sistema',
        'email' => '',
        'role' => 'admin',
    ];
    $exportController->exportarGlobalSistema();
    $results['global_backup'] = true;
} catch (Throwable $exception) {
    $results['errors'][] = [
        'user_id' => 0,
        'message' => 'global: ' . $exception->getMessage(),
    ];
    if (function_exists('registrar_log')) {
        registrar_log('AUTO_BACKUP', 'Falha backup global: ' . $exception->getMessage(), 1);
    }
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
