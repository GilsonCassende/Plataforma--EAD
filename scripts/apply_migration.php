<?php
/**
 * Runner simples de migrations SQL.
 * Uso (CLI):
 *   php scripts/apply_migration.php migrations/009_add_video_id_to_lessons.sql
 * Este script usa a conexão PDO definida em config/database.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    echo "Este runner deve ser executado via CLI para segurança.\n";
    echo "Exemplo: php scripts/apply_migration.php migrations/009_add_video_id_to_lessons.sql\n";
    exit(1);
}

$migrationFile = $argv[1] ?? __DIR__ . '/../migrations/009_add_video_id_to_lessons.sql';

if (!file_exists($migrationFile)) {
    fwrite(STDERR, "Arquivo de migration não encontrado: $migrationFile\n");
    exit(2);
}

// Carrega a configuração do banco (define $pdo)
$dbConfigPath = __DIR__ . '/../config/database.php';
if (!file_exists($dbConfigPath)) {
    fwrite(STDERR, "Arquivo de configuração do banco não encontrado: $dbConfigPath\n");
    exit(3);
}

require $dbConfigPath;

if (!isset($pdo) || !$pdo instanceof PDO) {
    fwrite(STDERR, "Conexão PDO não encontrada após incluir config/database.php\n");
    exit(4);
}

$sql = file_get_contents($migrationFile);
if ($sql === false) {
    fwrite(STDERR, "Falha ao ler arquivo SQL: $migrationFile\n");
    exit(5);
}

// Divide por ponto-e-vírgula preservando blocos; simples split é suficiente para migrations simples
$statements = array_filter(array_map('trim', explode(';', $sql)));

try {
    $pdo->beginTransaction();
    foreach ($statements as $stmt) {
        if ($stmt === '') {
            continue;
        }
        // Alguns arquivos podem conter comentários SQL iniciando com --, removemos linhas vazias/comentários
        $clean = preg_replace('/--.*?(\n|$)/', '\n', $stmt);
        $clean = trim($clean);
        if ($clean === '') {
            continue;
        }
        $pdo->exec($clean);
        echo "Executado: " . (strlen($clean) > 60 ? substr($clean, 0, 60) . '...' : $clean) . "\n";
    }
    $pdo->commit();
    echo "Migration aplicada com sucesso: $migrationFile\n";
    exit(0);
} catch (Exception $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Erro ao aplicar migration: " . $e->getMessage() . "\n");
    exit(6);
}

?>
