<?php

/**
 * Inicializador CLI seguro para Plataforma-EAD
 * Uso: APP_ENV=development INIT_CONFIRM_TOKEN=token php scripts/initialize.php <confirm_token>
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$tokenArg = $argv[1] ?? null;
$expected = getenv('INIT_CONFIRM_TOKEN') ?: null;
$app_env = getenv('APP_ENV') ?: 'production';

if ($app_env !== 'development') {
    fwrite(STDERR, "Refusing to run initializer: APP_ENV must be 'development'. Current: $app_env\n");
    exit(2);
}

if (empty($expected) || empty($tokenArg) || !hash_equals($expected, $tokenArg)) {
    fwrite(STDERR, "Invalid or missing confirmation token. Set INIT_CONFIRM_TOKEN and pass it as first argument.\n");
    exit(3);
}

require_once __DIR__ . '/../config/database.php';

$schemaFile = __DIR__ . '/../migrations/schema.sql';
if (!file_exists($schemaFile)) {
    fwrite(STDERR, "schema.sql not found: $schemaFile\n");
    exit(4);
}

$sql = file_get_contents($schemaFile);
if ($sql === false) {
    fwrite(STDERR, "Failed to read schema file\n");
    exit(5);
}

try {
    // Executar statements via PDO transaction
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $pdo->beginTransaction();
    foreach ($statements as $stmt) {
        $clean = preg_replace('/--.*?(\n|$)/', '\n', $stmt);
        $clean = trim($clean);
        if ($clean === '') continue;
        $pdo->exec($clean);
        fwrite(STDOUT, "Executed: " . (strlen($clean) > 80 ? substr($clean, 0, 80) . '...' : $clean) . "\n");
    }
    $pdo->commit();
    fwrite(STDOUT, "Schema applied successfully.\n");
    exit(0);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "Error applying schema: " . $e->getMessage() . "\n");
    exit(6);
}
