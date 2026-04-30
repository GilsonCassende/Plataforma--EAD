<?php
// Validação de sintaxe PHP portátil. Uso: php validate.php
// Somente CLI por segurança (evita execução de comandos via web)
if (PHP_SAPI !== 'cli') {
    echo "This script must be run from CLI.\n";
    exit(1);
}
$files = [
    __DIR__ . '/../public/index.php',
    __DIR__ . '/../app/controllers/AuthController.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "$file: arquivo não encontrado\n\n";
        continue;
    }

    // Usar o binário PHP atual para checar sintaxe
    $php = defined('PHP_BINARY') ? PHP_BINARY : 'php';
    $cmd = escapeshellcmd($php) . ' -l ' . escapeshellarg($file);
    $output = null;
    $retval = null;
    exec($cmd . ' 2>&1', $output, $retval);

    echo "$file:\n";
    echo implode("\n", $output) . "\n\n";
}
