<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script deve ser executado via CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

$checks = [
    [
        'label' => 'Fotos de perfil',
        'sql' => "SELECT id, fotografia AS filename FROM users WHERE fotografia IS NOT NULL AND fotografia <> ''",
    ],
    [
        'label' => 'Thumbnails de cursos',
        'sql' => "SELECT id, thumbnail AS filename FROM courses WHERE thumbnail IS NOT NULL AND thumbnail <> ''",
    ],
    [
        'label' => 'Arquivos locais de aulas',
        'sql' => "SELECT id, url_arquivo AS filename FROM lessons WHERE url_arquivo IS NOT NULL AND url_arquivo <> ''",
    ],
    [
        'label' => 'Áudios locais de aulas',
        'sql' => "SELECT id, titulo, audio_url AS filename, audio_storage_disk, audio_storage_key FROM lessons WHERE audio_url IS NOT NULL AND audio_url <> ''",
    ],
];

$totals = [
    'checked' => 0,
    'missing' => 0,
];

$report = [];

foreach ($checks as $check) {
    $stmt = $pdo->query($check['sql']);
    $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    $missing = [];

    foreach ($rows as $row) {
        $filename = trim((string)($row['filename'] ?? ''));
        if ($filename === '') {
            continue;
        }

        if (!empty($row['audio_storage_key']) && !empty($row['audio_storage_disk'])) {
            continue;
        }

        if (preg_match('#^https?://#i', $filename) || strpos($filename, '/') === 0) {
            continue;
        }

        $totals['checked']++;
        if (resolve_upload_path($filename, false) === null) {
            $totals['missing']++;
            $missing[] = [
                'id' => (int)($row['id'] ?? 0),
                'title' => trim((string)($row['titulo'] ?? '')),
                'filename' => basename($filename),
            ];
        }
    }

    $report[] = [
        'label' => $check['label'],
        'total' => count($rows),
        'missing' => $missing,
    ];
}

$storageSummary = [
    'uploads_dir' => defined('UPLOADS_DIR') ? UPLOADS_DIR : (__DIR__ . '/../public/uploads'),
    'public_uploads_exists' => is_dir(__DIR__ . '/../public/uploads'),
    'storage_exists' => is_dir(__DIR__ . '/../storage'),
    'env_exists' => is_file(__DIR__ . '/../.env'),
    'external_storage_local_root' => trim((string)env_value('STORAGE_LOCAL_ROOT', '')),
    'external_storage_remote_root' => trim((string)env_value('STORAGE_REMOTE_ROOT', '')),
];

echo "Diagnóstico de portabilidade\n";
echo "===========================\n\n";
echo 'Uploads dir: ' . $storageSummary['uploads_dir'] . "\n";
echo 'Pasta public/uploads: ' . ($storageSummary['public_uploads_exists'] ? 'ok' : 'ausente') . "\n";
echo 'Pasta storage: ' . ($storageSummary['storage_exists'] ? 'ok' : 'ausente') . "\n";
echo 'Arquivo .env: ' . ($storageSummary['env_exists'] ? 'ok' : 'ausente') . "\n";
if ($storageSummary['external_storage_local_root'] !== '') {
    echo 'STORAGE_LOCAL_ROOT externo: ' . $storageSummary['external_storage_local_root'] . "\n";
}
if ($storageSummary['external_storage_remote_root'] !== '') {
    echo 'STORAGE_REMOTE_ROOT externo: ' . $storageSummary['external_storage_remote_root'] . "\n";
}
echo "\n";

foreach ($report as $section) {
    echo '- ' . $section['label'] . ': ' . $section['total'] . ' registos';
    echo ', faltando ' . count($section['missing']) . "\n";

    foreach (array_slice($section['missing'], 0, 10) as $missing) {
        echo '  * ID ' . $missing['id'] . ': ' . $missing['filename'];
        if (!empty($missing['title'])) {
            echo ' [' . $missing['title'] . ']';
        }
        echo "\n";
    }

    if (count($section['missing']) > 10) {
        echo '  * ... +' . (count($section['missing']) - 10) . " ficheiros\n";
    }
}

echo "\nResumo: " . $totals['checked'] . ' ficheiros locais referenciados, ' . $totals['missing'] . " ausentes.\n";

exit($totals['missing'] > 0 ? 2 : 0);
