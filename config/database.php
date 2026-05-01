<?php

/**
 * Configuração de Banco de Dados
 * PDO com MySQL - Proteção contra SQL Injection
 */

require_once __DIR__ . '/env.php';

load_project_env(__DIR__ . '/../.env');

$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
$isLocalHost = in_array($host, ['localhost', '127.0.0.1', '::1'], true) || PHP_SAPI === 'cli';

// Ler configuração sensível de variáveis de ambiente (12-factor)
$db_host = env_value('DB_HOST');
$db_port = env_value('DB_PORT');
$db_user = env_value('DB_USER');
$db_pass = env_value('DB_PASS');
$db_name = env_value('DB_NAME');

// Em desenvolvimento/local, permitir valores padrão para facilitar setup no XAMPP.
$app_env = env_value('APP_ENV', $isLocalHost ? 'development' : 'production');
if ($app_env === 'development' || $isLocalHost) {
    if (!$db_host) $db_host = 'localhost';
    if (!$db_port) $db_port = '3306';
    if (!$db_user) $db_user = 'root';
    if ($db_pass === false) $db_pass = '';
    if (!$db_name) $db_name = 'ead_platform';
}

if (empty($db_host) || empty($db_user) || empty($db_name)) {
    error_log('Database configuration missing environment variables.');
    throw new RuntimeException('Database environment variables DB_HOST, DB_USER and DB_NAME must be set.');
}

try {
    $pdo = new PDO(
        'mysql:host=' . $db_host . ';port=' . ($db_port ?: '3306') . ';dbname=' . $db_name . ';charset=utf8mb4',
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // Não vazar detalhes sensíveis em produção; registrar e abortar de forma genérica
    if (function_exists('error_log')) {
        error_log('DB connection error: ' . $e->getMessage());
    }
    // Em ambiente de desenvolvimento pode ser útil ver a mensagem;
    if (getenv('APP_ENV') === 'development') {
        die('Erro de conexão com banco de dados: ' . $e->getMessage());
    }
    die('Erro de conexão com banco de dados.');
}
