<?php

require_once __DIR__ . '/env.php';

load_project_env(__DIR__ . '/../.env');

// Configurações da aplicação
// Ajuste BASE_URL conforme o caminho onde o projeto está servido
// Ex: '/Plataforma-EAD/public'
if (!defined('BASE_URL')) {
    $configuredBaseUrl = trim((string)env_value('BASE_URL', '/Plataforma-EAD/public'));
    if ($configuredBaseUrl === '' || preg_match('#^https?://#i', $configuredBaseUrl)) {
        $configuredBaseUrl = parse_url($configuredBaseUrl, PHP_URL_PATH) ?: '/Plataforma-EAD/public';
    }
    define('BASE_URL', rtrim($configuredBaseUrl, '/'));
}

if (!defined('APP_URL')) {
    $appUrl = trim((string)env_value('APP_URL', ''));
    if ($appUrl === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
        $appUrl = $scheme . '://' . $host . BASE_URL;
    }
    define('APP_URL', rtrim($appUrl, '/'));
}

if (!defined('CERTIFICATE_PDF_SECRET')) {
    define('CERTIFICATE_PDF_SECRET', hash('sha256', __DIR__ . '::plataforma-ead::certificate-pdf'));
}

if (!defined('CHROME_BIN')) {
    define('CHROME_BIN', '/usr/bin/google-chrome');
}

if (!defined('NODE_BIN')) {
    $projectNodeBin = dirname(__DIR__) . '/bin/node';
    define('NODE_BIN', is_file($projectNodeBin) ? $projectNodeBin : 'node');
}
?>
