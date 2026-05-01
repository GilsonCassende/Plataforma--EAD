<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$configuredToken = trim((string)env_value('IMAGE_MIGRATION_TOKEN', ''));
$providedToken = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));

if ($configuredToken === '' || !hash_equals($configuredToken, $providedToken)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Acesso negado.\n";
    exit;
}

$uploadsDir = __DIR__ . '/uploads';
$results = [];

function migrate_image_reference(PDO $pdo, string $table, string $idColumn, string $fileColumn, string $whereClause = ''): array
{
    $sql = "SELECT {$idColumn} AS id, {$fileColumn} AS file_name FROM {$table}";
    if ($whereClause !== '') {
        $sql .= " WHERE {$whereClause}";
    }

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $updated = 0;
    $skipped = 0;
    $errors = [];

    foreach ($rows as $row) {
        $fileName = trim((string)($row['file_name'] ?? ''));
        if ($fileName === '' || preg_match('/\.webp$/i', $fileName)) {
            $skipped += 1;
            continue;
        }

        $sourcePath = __DIR__ . '/uploads/' . basename($fileName);
        if (!is_file($sourcePath)) {
            $errors[] = "{$table}#{$row['id']}: arquivo não encontrado ({$fileName})";
            continue;
        }

        $targetName = preg_replace('/\.[^.]+$/', '.webp', basename($fileName));
        $targetPath = __DIR__ . '/uploads/' . $targetName;

        try {
            image_resize($sourcePath, $targetPath, 1600, 1600, 'webp');
        } catch (Throwable $e) {
            $errors[] = "{$table}#{$row['id']}: " . $e->getMessage();
            continue;
        }

        $stmt = $pdo->prepare("UPDATE {$table} SET {$fileColumn} = ? WHERE {$idColumn} = ?");
        if ($stmt->execute([$targetName, $row['id']])) {
            $updated += 1;
        } else {
            $errors[] = "{$table}#{$row['id']}: falha ao atualizar referência";
        }
    }

    return ['updated' => $updated, 'skipped' => $skipped, 'errors' => $errors];
}

$results['courses.thumbnail'] = migrate_image_reference($pdo, 'courses', 'id', 'thumbnail');
$results['users.fotografia'] = migrate_image_reference($pdo, 'users', 'id', 'fotografia');
$results['lessons.url_arquivo'] = migrate_image_reference($pdo, 'lessons', 'id', 'url_arquivo', "url_arquivo IS NOT NULL AND url_arquivo <> '' AND tipo IN ('arquivo')");

header('Content-Type: text/plain; charset=utf-8');
foreach ($results as $label => $result) {
    echo $label . "\n";
    echo 'Atualizados: ' . ($result['updated'] ?? 0) . "\n";
    echo 'Ignorados: ' . ($result['skipped'] ?? 0) . "\n";
    echo 'Erros: ' . count($result['errors'] ?? []) . "\n\n";
    if (!empty($result['errors'])) {
        echo implode("\n", $result['errors']) . "\n\n";
    }
}
