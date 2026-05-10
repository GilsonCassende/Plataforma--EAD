<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script deve ser executado via CLI.\n");
    exit(1);
}

if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "ZipArchive não está disponível.\n");
    exit(2);
}

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

$projectRoot = realpath(__DIR__ . '/..');
if ($projectRoot === false) {
    fwrite(STDERR, "Não foi possível localizar a raiz do projeto.\n");
    exit(3);
}

$options = parseArguments(array_slice($argv, 1));
$timestamp = date('Ymd_His');
$defaultOutput = $projectRoot . '/storage/backups/Plataforma-EAD-migracao-' . $timestamp . '.zip';
$outputPath = $options['output'] ?? $defaultOutput;
$outputPath = normalizePath($outputPath, $projectRoot);

$tempBase = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plataforma-ead-migration-' . bin2hex(random_bytes(6));
$stagingRoot = $tempBase . DIRECTORY_SEPARATOR . 'Plataforma-EAD';

try {
    ensureDirectory($stagingRoot);

    $excludes = [
        $tempBase,
        $outputPath,
        $projectRoot . '/.git',
        $projectRoot . '/.agents',
        $projectRoot . '/.codex',
        $projectRoot . '/logs',
        $projectRoot . '/storage/image-cache',
        $projectRoot . '/storage/tmp-backups',
    ];

    if (!empty($options['skip_node_modules'])) {
        $excludes[] = $projectRoot . '/node_modules';
    }
    if (!empty($options['skip_vendor'])) {
        $excludes[] = $projectRoot . '/vendor';
    }

    copyProjectTree($projectRoot, $stagingRoot, $excludes);

    $migrationDir = $stagingRoot . DIRECTORY_SEPARATOR . '_migration';
    ensureDirectory($migrationDir);

    $externalAssets = collectExternalAssets($projectRoot);
    if ($externalAssets !== []) {
        $externalRoot = $migrationDir . DIRECTORY_SEPARATOR . 'external';
        ensureDirectory($externalRoot);

        foreach ($externalAssets as $asset) {
            $target = $externalRoot . DIRECTORY_SEPARATOR . $asset['target'];
            copyAssetIntoPackage($asset['source'], $target);
        }
    }

    $databaseDumpPath = $migrationDir . DIRECTORY_SEPARATOR . 'database.sql';
    dumpDatabase($databaseDumpPath);

    $readmePath = $migrationDir . DIRECTORY_SEPARATOR . 'README.txt';
    file_put_contents($readmePath, buildMigrationReadme($options), LOCK_EX);

    $manifestPath = $migrationDir . DIRECTORY_SEPARATOR . 'manifest.json';
    file_put_contents($manifestPath, json_encode(buildManifest($projectRoot, $options, $externalAssets), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);

    ensureDirectory(dirname($outputPath));
    createZipFromDirectory($stagingRoot, $outputPath);

    echo "Pacote de migração criado com sucesso.\n";
    echo 'Arquivo: ' . $outputPath . "\n";
    echo 'Tamanho: ' . formatBytes((int)(filesize($outputPath) ?: 0)) . "\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, "Falha ao gerar pacote de migração: " . $exception->getMessage() . "\n");
    exit(4);
} finally {
    deleteDirectory($tempBase);
}

function parseArguments(array $args): array
{
    $options = [
        'skip_node_modules' => false,
        'skip_vendor' => false,
    ];

    foreach ($args as $arg) {
        if ($arg === '--skip-node-modules') {
            $options['skip_node_modules'] = true;
            continue;
        }

        if ($arg === '--skip-vendor') {
            $options['skip_vendor'] = true;
            continue;
        }

        if (str_starts_with($arg, '--output=')) {
            $options['output'] = substr($arg, 9);
        }
    }

    return $options;
}

function normalizePath(string $path, string $projectRoot): string
{
    $path = trim($path);
    if ($path === '') {
        throw new RuntimeException('Caminho de saída inválido.');
    }

    if ($path[0] === DIRECTORY_SEPARATOR) {
        return $path;
    }

    if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
        return $path;
    }

    return $projectRoot . DIRECTORY_SEPARATOR . $path;
}

function ensureDirectory(string $path): void
{
    if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Não foi possível preparar o diretório: ' . $path);
    }
}

function copyProjectTree(string $source, string $destination, array $excludes): void
{
    $source = rtrim($source, DIRECTORY_SEPARATOR);
    $destination = rtrim($destination, DIRECTORY_SEPARATOR);
    $excludeMap = [];
    foreach ($excludes as $exclude) {
        $excludeMap[canonicalish($exclude)] = true;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $sourcePath = $item->getPathname();
        $canonical = canonicalish($sourcePath);
        if (isExcluded($canonical, $excludeMap)) {
            continue;
        }

        $relative = substr($sourcePath, strlen($source) + 1);
        $targetPath = $destination . DIRECTORY_SEPARATOR . $relative;

        if ($item->isDir()) {
            ensureDirectory($targetPath);
            continue;
        }

        ensureDirectory(dirname($targetPath));
        if (!@copy($sourcePath, $targetPath)) {
            throw new RuntimeException('Falha ao copiar arquivo para o pacote: ' . $relative);
        }
    }
}

function isExcluded(string $path, array $excludeMap): bool
{
    foreach ($excludeMap as $exclude => $_) {
        if ($path === $exclude || str_starts_with($path, $exclude . DIRECTORY_SEPARATOR)) {
            return true;
        }
    }

    return false;
}

function canonicalish(string $path): string
{
    $real = realpath($path);
    if ($real !== false) {
        return rtrim($real, DIRECTORY_SEPARATOR);
    }

    return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
}

function dumpDatabase(string $outputPath): void
{
    $dbHost = (string)env_value('DB_HOST', 'localhost');
    $dbPort = (string)env_value('DB_PORT', '3306');
    $dbUser = (string)env_value('DB_USER', 'root');
    $dbPass = env_value('DB_PASS', '');
    $dbName = (string)env_value('DB_NAME', 'ead_platform');

    $binary = findMysqldumpBinary();
    if ($binary === null) {
        throw new RuntimeException('mysqldump não foi encontrado neste ambiente.');
    }

    $baseCommand = escapeshellarg($binary)
        . ' --host=' . escapeshellarg($dbHost)
        . ' --port=' . escapeshellarg($dbPort)
        . ' --user=' . escapeshellarg($dbUser)
        . ' --password=' . escapeshellarg((string)$dbPass)
        . ' --default-character-set=utf8mb4 --single-transaction --skip-lock-tables --triggers ';

    $variants = [
        '--routines --events ' . escapeshellarg($dbName),
        '--routines ' . escapeshellarg($dbName),
        escapeshellarg($dbName),
    ];

    $lastError = 'mysqldump falhou.';
    foreach ($variants as $variant) {
        [$exitCode, $stderr] = runDumpCommand($baseCommand . $variant, $outputPath);
        if ($exitCode === 0 && is_file($outputPath) && filesize($outputPath) > 0) {
            return;
        }

        $lastError = trim($stderr) !== '' ? trim($stderr) : $lastError;
        @unlink($outputPath);
    }

    throw new RuntimeException('mysqldump falhou: ' . $lastError);
}

function runDumpCommand(string $command, string $outputPath): array
{
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Não foi possível iniciar o mysqldump.');
    }

    fclose($pipes[0]);
    $output = fopen($outputPath, 'wb');
    if (!is_resource($output)) {
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        throw new RuntimeException('Não foi possível gravar o dump do banco.');
    }

    stream_copy_to_stream($pipes[1], $output);
    fclose($pipes[1]);
    fclose($output);

    $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    return [$exitCode, $stderr];
}

function findMysqldumpBinary(): ?string
{
    $candidates = [
        (string)env_value('MYSQLDUMP_BIN', ''),
        '/opt/lampp/bin/mysqldump',
        '/usr/bin/mysqldump',
        'mysqldump',
    ];

    foreach ($candidates as $candidate) {
        $candidate = trim($candidate);
        if ($candidate === '') {
            continue;
        }

        if ($candidate === 'mysqldump') {
            $resolved = trim((string)shell_exec('command -v mysqldump 2>/dev/null'));
            if ($resolved !== '' && is_executable($resolved)) {
                return $resolved;
            }
            continue;
        }

        if (is_file($candidate) && is_executable($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function createZipFromDirectory(string $sourceDir, string $outputPath): void
{
    $zip = new ZipArchive();
    if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Não foi possível criar o ZIP final.');
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $absolutePath = $item->getPathname();
        $relativePath = substr($absolutePath, strlen($sourceDir) + 1);

        if ($item->isDir()) {
            $zip->addEmptyDir(str_replace('\\', '/', $relativePath));
            continue;
        }

        $zip->addFile($absolutePath, str_replace('\\', '/', $relativePath));
    }

    $zip->close();
}

function buildMigrationReadme(array $options): string
{
    $lines = [
        'Pacote de migracao da Plataforma EAD',
        '',
        'Conteudo principal:',
        '- Codigo da aplicacao',
        '- Banco de dados em _migration/database.sql',
        '- Uploads locais em public/uploads',
        '- Conteudo local de storage',
        '- Arquivo .env atual',
        '',
        'Como restaurar em outro PC:',
        '1. Extraia o ZIP para a pasta htdocs do XAMPP.',
        '2. Crie a base de dados de destino.',
        '3. Importe o arquivo _migration/database.sql.',
        '4. Confirme as credenciais em .env ou config/database.php.',
        '5. Inicie Apache e MySQL e abra /public/index.php.',
        '',
        'Observacoes:',
        '- Este pacote foi criado para evitar perda de ficheiros fora do banco.',
        '- Se o novo PC nao tiver Node ou dependencias opcionais, algumas funcoes auxiliares podem exigir ajuste.',
    ];

    if (!empty($options['skip_node_modules'])) {
        $lines[] = '- node_modules foi excluido deste pacote.';
    }
    if (!empty($options['skip_vendor'])) {
        $lines[] = '- vendor foi excluido deste pacote.';
    }

    return implode("\n", $lines) . "\n";
}

function buildManifest(string $projectRoot, array $options, array $externalAssets): array
{
    return [
        'generated_at' => date(DATE_ATOM),
        'project_root' => $projectRoot,
        'db_name' => (string)env_value('DB_NAME', 'ead_platform'),
        'includes' => [
            'database_dump' => '_migration/database.sql',
            'uploads' => 'public/uploads',
            'storage' => 'storage',
            'env' => '.env',
            'vendor' => empty($options['skip_vendor']),
            'node_modules' => empty($options['skip_node_modules']),
        ],
        'external_assets' => array_map(static function (array $asset): array {
            return [
                'source' => $asset['source'],
                'target' => '_migration/external/' . $asset['target'],
            ];
        }, $externalAssets),
    ];
}

function collectExternalAssets(string $projectRoot): array
{
    $assets = [];
    $definitions = [
        [
            'source' => trim((string)env_value('UPLOADS_DIR', '')),
            'target' => 'uploads',
        ],
        [
            'source' => trim((string)env_value('STORAGE_LOCAL_ROOT', '')),
            'target' => 'storage-local-root',
        ],
        [
            'source' => trim((string)env_value('STORAGE_REMOTE_ROOT', '')),
            'target' => 'storage-remote-root',
        ],
    ];

    foreach ($definitions as $definition) {
        $source = trim($definition['source']);
        if ($source === '') {
            continue;
        }

        $normalized = canonicalish($source);
        if (!file_exists($normalized)) {
            continue;
        }

        if (str_starts_with($normalized, canonicalish($projectRoot) . DIRECTORY_SEPARATOR)) {
            continue;
        }

        $assets[] = [
            'source' => $normalized,
            'target' => $definition['target'],
        ];
    }

    return $assets;
}

function copyAssetIntoPackage(string $source, string $target): void
{
    if (is_dir($source)) {
        ensureDirectory($target);
        copyProjectTree($source, $target, []);
        return;
    }

    ensureDirectory(dirname($target));
    if (!@copy($source, $target)) {
        throw new RuntimeException('Falha ao copiar asset externo: ' . $source);
    }
}

function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $value = (float)$bytes;
    $unit = 0;
    while ($value >= 1024 && $unit < count($units) - 1) {
        $value /= 1024;
        $unit++;
    }

    return number_format($value, $unit === 0 ? 0 : 2, '.', '') . ' ' . $units[$unit];
}

function deleteDirectory(string $path): void
{
    if ($path === '' || !is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
            continue;
        }

        @unlink($item->getPathname());
    }

    @rmdir($path);
}
