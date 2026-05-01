<?php

require_once __DIR__ . '/../config/env.php';

load_project_env(__DIR__ . '/../.env');

$configuredToken = trim((string)env_value('UPLOAD_RESTORE_TOKEN', ''));
$providedToken = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));

if ($configuredToken === '' || !hash_equals($configuredToken, $providedToken)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Acesso negado.\n";
    exit;
}

$source = dirname(__DIR__) . '/bootstrap_uploads';
$destination = __DIR__ . '/uploads';

if (!is_dir($source)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Fonte de uploads não encontrada.\n";
    exit;
}

if (!is_dir($destination) && !mkdir($destination, 0775, true) && !is_dir($destination)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Não foi possível preparar a pasta de uploads.\n";
    exit;
}

$copied = 0;
$skipped = 0;
$errors = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
    $relativePath = substr($item->getPathname(), strlen($source) + 1);
    $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;

    if ($item->isDir()) {
        if (!is_dir($targetPath) && !mkdir($targetPath, 0775, true) && !is_dir($targetPath)) {
            $errors[] = "Falha ao criar diretório: {$relativePath}";
        }
        continue;
    }

    if (is_file($targetPath)) {
        $skipped += 1;
        continue;
    }

    $targetDir = dirname($targetPath);
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        $errors[] = "Falha ao preparar diretório: {$relativePath}";
        continue;
    }

    if (!copy($item->getPathname(), $targetPath)) {
        $errors[] = "Falha ao copiar arquivo: {$relativePath}";
        continue;
    }

    $copied += 1;
}

header('Content-Type: text/plain; charset=utf-8');
echo "Uploads restaurados.\n";
echo "Copiados: {$copied}\n";
echo "Ignorados: {$skipped}\n";
echo "Erros: " . count($errors) . "\n";

if ($errors !== []) {
    echo "\n";
    echo implode("\n", $errors) . "\n";
}
