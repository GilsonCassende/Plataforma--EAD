<?php

/**
 * Controller: ImportController
 * Faz upload, validação e restauração segura de backups estruturados.
 */
class ImportController
{
    private PDO $pdo;
    private string $importDir;
    private BackupLogService $backupLogs;

    private const PREVIEW_SESSION_KEY = 'backup_restore_preview';
    private const MAX_UPLOAD_BYTES = 104857600; // 100MB
    private const REQUIRED_DATA_FILES = [
        'data/user.json',
        'data/courses.json',
        'data/modules.json',
        'data/lessons.json',
        'data/quizzes.json',
        'data/quiz_attempts.json',
        'data/progress.json',
        'data/certificates.json',
        'data/enrollments.json',
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        new Lesson($pdo);
        $this->importDir = dirname(__DIR__, 2) . '/storage/imports';
        $this->backupLogs = new BackupLogService($pdo);
        $this->ensureImportDirectory();
    }

    public function uploadBackup(array $file, int $currentUserId, ?string $password = null): array
    {
        $currentUserId = (int)$currentUserId;
        if ($currentUserId <= 0) {
            throw new RuntimeException('Usuário inválido para restauração.');
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Envie um arquivo ZIP válido para restauração.');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_UPLOAD_BYTES) {
            throw new RuntimeException('O arquivo de backup excede o limite permitido de 100MB.');
        }

        $originalName = (string)($file['name'] ?? 'backup.zip');
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'zip') {
            throw new RuntimeException('A restauração aceita apenas arquivos ZIP.');
        }

        $realMime = $this->detectMime((string)($file['tmp_name'] ?? ''));
        if (!in_array($realMime, ['application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream'], true)) {
            throw new RuntimeException('O arquivo enviado não possui um MIME compatível com ZIP.');
        }

        $token = bin2hex(random_bytes(16));
        $stagingDir = $this->importDir . '/' . $token;
        $archivePath = $stagingDir . '/source.zip';
        $extractDir = $stagingDir . '/contents';

        if (!@mkdir($extractDir, 0775, true) && !is_dir($extractDir)) {
            throw new RuntimeException('Não foi possível preparar a área de restauração.');
        }

        if (!@move_uploaded_file((string)$file['tmp_name'], $archivePath)) {
            throw new RuntimeException('Não foi possível receber o arquivo de backup.');
        }

        return $this->validateBackup($token, $currentUserId, $password);
    }

    public function validateBackup(string $token, int $currentUserId, ?string $password = null): array
    {
        $staging = $this->getStagingPaths($token);
        if (!is_file($staging['archive'])) {
            throw new RuntimeException('Arquivo de backup não encontrado para validação.');
        }

        $this->extractArchiveSafely($staging['archive'], $staging['contents'], $password);
        $this->normalizeLegacyBackupIfNeeded($staging['contents']);
        $this->assertRequiredStructure($staging['contents']);

        $manifest = $this->readJsonFile($staging['contents'] . '/manifest.json');
        $checksums = $this->readJsonFile($staging['contents'] . '/checksums.json');
        $documents = $this->loadDocuments($staging['contents']);

        $this->assertManifest($manifest, $currentUserId);
        $this->verifyChecksums($staging['contents'], $checksums);
        $this->validateDocumentSchemas($documents);

        $summary = $this->buildSummary($manifest, $documents);
        $preview = [
            'token' => $token,
            'manifest' => $manifest,
            'summary' => $summary,
            'password' => $password,
        ];

        $_SESSION[self::PREVIEW_SESSION_KEY] = $preview;
        return $preview;
    }

    public function restoreBackup(string $token, int $currentUserId, ?string $password = null, string $scope = 'full', ?int $sourceCourseId = null, ?int $sourceModuleId = null): array
    {
        $preview = $this->validateBackup($token, $currentUserId, $password ?: (string)(($this->getPreview()['password'] ?? '')));
        $staging = $this->getStagingPaths($token);
        $manifest = $preview['manifest'];
        $documents = $this->loadDocuments($staging['contents']);
        [$documents, $scopeLabel] = $this->filterDocumentsForScope($documents, $scope, $sourceCourseId, $sourceModuleId);

        $this->pdo->beginTransaction();
        try {
            $owner = is_array($documents['data/user.json']['owner'] ?? null) ? $documents['data/user.json']['owner'] : [];
            if (in_array($scopeLabel, ['full', 'user'], true)) {
                $this->restoreOwnerProfile($owner, $staging['contents'], $currentUserId);
            }

            $userMap = $this->buildUserMap($documents['data/user.json'], $currentUserId);
            $courseMap = [];
            $moduleMap = [];
            $lessonMap = [];
            $quizMap = [];
            $questionMap = [];

            $type = (string)($manifest['type'] ?? 'user');
            if (in_array($type, ['course', 'full'], true)) {
                $courseMap = $this->restoreCourses($documents['data/courses.json'], $staging['contents'], $currentUserId);
                $moduleMap = $this->restoreModules($documents['data/modules.json'], $courseMap);
                $lessonMap = $this->restoreLessons($documents['data/lessons.json'], $courseMap, $moduleMap, $staging['contents']);
                [$quizMap, $questionMap] = $this->restoreQuizzes($documents['data/quizzes.json'], $courseMap, $moduleMap, $lessonMap);
            } else {
                $courseMap = $this->mapExistingCoursesFromStudentBackup($documents['data/courses.json'], $currentUserId);
                $moduleMap = $this->mapExistingModules($documents['data/modules.json'], $courseMap);
                $lessonMap = $this->mapExistingLessons($documents['data/lessons.json'], $courseMap, $moduleMap);
                [$quizMap, $questionMap] = $this->mapExistingQuizzes($documents['data/quizzes.json'], $courseMap, $moduleMap, $lessonMap);
            }

            $restored = [
                'courses' => count($courseMap),
                'modules' => count($moduleMap),
                'lessons' => count($lessonMap),
                'quizzes' => count($quizMap),
                'enrollments' => $this->restoreEnrollments($documents['data/enrollments.json'], $userMap, $courseMap),
                'progress' => $this->restoreProgress($documents['data/progress.json'], $userMap, $lessonMap),
                'attempts' => $this->restoreQuizAttempts($documents['data/quiz_attempts.json'], $userMap, $quizMap, $questionMap),
                'certificates' => $this->restoreCertificates($documents['data/certificates.json'], $userMap, $courseMap, $moduleMap),
            ];

            $this->pdo->commit();
            unset($_SESSION[self::PREVIEW_SESSION_KEY]);
            $this->backupLogs->createLog($currentUserId, 'import_' . $scopeLabel, 'restore:' . $token, 0, 'restored', [
                'scope' => $scopeLabel,
                'course_source_id' => $sourceCourseId,
                'module_source_id' => $sourceModuleId,
            ]);

            return [
                'sucesso' => true,
                'resumo' => $restored,
                'mensagem' => 'Backup restaurado com sucesso.',
            ];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $this->backupLogs->createLog($currentUserId, 'import_' . $scopeLabel, 'restore:' . $token, 0, 'erro', [
                'erro' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function restoreCourse(string $token, int $currentUserId, int $sourceCourseId, ?string $password = null): array
    {
        return $this->restoreBackup($token, $currentUserId, $password, 'course', $sourceCourseId, null);
    }

    public function restoreModule(string $token, int $currentUserId, int $sourceModuleId, ?string $password = null): array
    {
        return $this->restoreBackup($token, $currentUserId, $password, 'module', null, $sourceModuleId);
    }

    public function getPreview(): ?array
    {
        $preview = $_SESSION[self::PREVIEW_SESSION_KEY] ?? null;
        return is_array($preview) ? $preview : null;
    }

    private function ensureImportDirectory(): void
    {
        if (!is_dir($this->importDir) && !@mkdir($this->importDir, 0775, true) && !is_dir($this->importDir)) {
            throw new RuntimeException('Não foi possível preparar o diretório de restauração.');
        }

        if (!is_writable($this->importDir)) {
            throw new RuntimeException('O diretório de restauração não possui permissão de escrita.');
        }
    }

    private function getStagingPaths(string $token): array
    {
        $token = preg_replace('/[^a-f0-9]/i', '', trim($token)) ?: '';
        if ($token === '') {
            throw new RuntimeException('Token de restauração inválido.');
        }

        $base = $this->importDir . '/' . $token;
        return [
            'base' => $base,
            'archive' => $base . '/source.zip',
            'contents' => $base . '/contents',
        ];
    }

    private function extractArchiveSafely(string $archivePath, string $destination, ?string $password = null): void
    {
        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('Não foi possível abrir o arquivo ZIP do backup.');
        }

        $password = trim((string)$password);
        if ($password !== '' && method_exists($zip, 'setPassword')) {
            $zip->setPassword($password);
        }

        $this->clearDirectory($destination);
        if (!is_dir($destination) && !@mkdir($destination, 0775, true) && !is_dir($destination)) {
            throw new RuntimeException('Não foi possível preparar o diretório de extração.');
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);
            if ($entryName === false) {
                continue;
            }

            $entryName = str_replace('\\', '/', $entryName);
            if ($this->isUnsafeZipPath($entryName)) {
                $zip->close();
                throw new RuntimeException('O backup contém caminhos inválidos ou potencialmente maliciosos.');
            }

            $targetPath = $destination . '/' . ltrim($entryName, '/');
            if (substr($entryName, -1) === '/') {
                if (!is_dir($targetPath) && !@mkdir($targetPath, 0775, true) && !is_dir($targetPath)) {
                    $zip->close();
                    throw new RuntimeException('Não foi possível criar um diretório da restauração.');
                }
                continue;
            }

            $parent = dirname($targetPath);
            if (!is_dir($parent) && !@mkdir($parent, 0775, true) && !is_dir($parent)) {
                $zip->close();
                throw new RuntimeException('Não foi possível preparar um diretório da restauração.');
            }

            $stream = $zip->getStream($entryName);
            if (!is_resource($stream)) {
                $zip->close();
                throw new RuntimeException('Não foi possível ler um arquivo do backup. Se o backup estiver criptografado, confirme a senha informada.');
            }

            $out = @fopen($targetPath, 'wb');
            if (!is_resource($out)) {
                fclose($stream);
                $zip->close();
                throw new RuntimeException('Não foi possível gravar um arquivo extraído do backup.');
            }

            stream_copy_to_stream($stream, $out);
            fclose($stream);
            fclose($out);
        }

        $zip->close();
    }

    private function normalizeLegacyBackupIfNeeded(string $root): void
    {
        if (is_file($root . '/manifest.json') && is_file($root . '/checksums.json')) {
            return;
        }

        $legacyFiles = glob($root . '/dados/*.json') ?: [];
        if (empty($legacyFiles)) {
            return;
        }

        $legacy = $this->readJsonFile($legacyFiles[0]);
        $meta = is_array($legacy['meta'] ?? null) ? $legacy['meta'] : [];
        $owner = $legacy['usuario'] ?? $legacy['professor'] ?? [];
        $type = ($meta['tipo'] ?? 'aluno') === 'professor' ? ((($meta['scope'] ?? 'all') === 'all') ? 'full' : 'course') : 'user';
        $legacyQuizAttempts = is_array($legacy['quiz'] ?? null) ? $this->normalizeLegacyQuizAttemptRows($legacy['quiz']) : [];
        $legacyQuizCatalog = is_array($legacy['quiz'] ?? null) ? $this->normalizeLegacyQuizCatalog($legacy['quiz']) : [];

        $userDoc = [
            'owner' => is_array($owner) ? $owner : [],
            'related_users' => [],
        ];
        $courseRows = is_array($legacy['cursos'] ?? null) ? $legacy['cursos'] : [];
        $moduleRows = is_array($legacy['modulos'] ?? null) ? $legacy['modulos'] : (is_array($legacy['modulos_concluidos'] ?? null) ? $legacy['modulos_concluidos'] : []);
        $lessonRows = is_array($legacy['aulas'] ?? null) ? $legacy['aulas'] : [];
        $quizRows = is_array($legacy['quiz'] ?? null) ? $this->normalizeLegacyQuizRows($legacy['quiz']) : [];
        $progressRows = is_array($legacy['progresso'] ?? null) ? $this->normalizeLegacyProgressRows($legacy['progresso']) : [];
        $certificateRows = is_array($legacy['certificados'] ?? null) ? $legacy['certificados'] : [];
        $enrollmentRows = is_array($legacy['alunos'] ?? null) ? $this->normalizeLegacyEnrollmentRows($legacy['alunos']) : [];

        $dataDir = $root . '/data';
        if (!is_dir($dataDir) && !@mkdir($dataDir, 0775, true) && !is_dir($dataDir)) {
            throw new RuntimeException('Não foi possível normalizar o backup legado.');
        }

        $documents = [
            'data/user.json' => $userDoc,
            'data/courses.json' => $courseRows,
            'data/modules.json' => $moduleRows,
            'data/lessons.json' => $lessonRows,
            'data/quizzes.json' => $type === 'user' ? $legacyQuizCatalog : $quizRows,
            'data/quiz_attempts.json' => $type === 'user' ? $legacyQuizAttempts : (is_array($legacy['quiz_attempts'] ?? null) ? $legacy['quiz_attempts'] : []),
            'data/progress.json' => $progressRows,
            'data/certificates.json' => $certificateRows,
            'data/enrollments.json' => $type === 'user' ? (is_array($legacy['cursos'] ?? null) ? $this->legacyEnrollmentsFromCourses($legacy['cursos'], (int)($owner['id'] ?? 0)) : []) : $enrollmentRows,
        ];

        foreach ($documents as $relative => $payload) {
            $this->writeJsonFile($root . '/' . $relative, $payload);
        }

        $checksums = $this->generateChecksumsForExtractedRoot($root);
        $manifest = [
            'version' => 'legacy-bridge',
            'platform' => 'Plataforma EAD',
            'platform_version' => 'legacy',
            'type' => $type,
            'user_id' => (int)($meta['user_id'] ?? ($owner['id'] ?? 0)),
            'generated_at' => (string)($meta['gerado_em'] ?? date(DATE_ATOM)),
            'total_files' => count($checksums) + 2,
            'total_records' => count($courseRows) + count($moduleRows) + count($lessonRows) + count($quizRows) + count($progressRows) + count($certificateRows) + count($documents['data/enrollments.json']),
            'checksum' => hash('sha256', json_encode($checksums, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: ''),
            'scope' => (string)($meta['scope'] ?? 'all'),
            'course_id' => !empty($meta['course_id']) ? (int)$meta['course_id'] : null,
            'encrypted' => false,
            'legacy' => true,
        ];

        $this->writeJsonFile($root . '/manifest.json', $manifest);
        $checksums['manifest.json'] = hash_file('sha256', $root . '/manifest.json') ?: '';
        $this->writeJsonFile($root . '/checksums.json', $checksums);
    }

    private function assertRequiredStructure(string $root): void
    {
        if (!is_file($root . '/manifest.json') || !is_file($root . '/checksums.json')) {
            throw new RuntimeException('Estrutura do backup inválida: manifest ou checksums ausentes.');
        }

        foreach (self::REQUIRED_DATA_FILES as $file) {
            if (!is_file($root . '/' . $file)) {
                throw new RuntimeException('Estrutura do backup inválida: arquivo obrigatório ausente (' . $file . ').');
            }
        }
    }

    private function assertManifest(array $manifest, int $currentUserId): void
    {
        $required = ['version', 'platform', 'type', 'user_id', 'generated_at', 'total_files', 'total_records', 'checksum'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $manifest)) {
                throw new RuntimeException('Manifesto do backup incompleto: campo obrigatório "' . $field . '" ausente.');
            }
        }

        if ((int)$manifest['user_id'] !== $currentUserId) {
            throw new RuntimeException('Este backup pertence a outro usuário e não pode ser restaurado nesta conta.');
        }

        $type = (string)$manifest['type'];
        $currentRole = (string)(AuthController::obterUsuarioAtual()['role'] ?? '');
        if (in_array($type, ['course', 'full'], true) && !in_array($currentRole, ['professor', 'admin'], true)) {
            throw new RuntimeException('Apenas professores podem restaurar backups de curso ou completos.');
        }
    }

    private function verifyChecksums(string $root, array $checksums): void
    {
        if (empty($checksums) || !is_array($checksums)) {
            throw new RuntimeException('checksums.json inválido.');
        }

        foreach ($checksums as $relativePath => $expectedHash) {
            $relativePath = str_replace('\\', '/', (string)$relativePath);
            if ($this->isUnsafeZipPath($relativePath)) {
                throw new RuntimeException('checksums.json contém caminhos inválidos.');
            }

            $absolute = $root . '/' . ltrim($relativePath, '/');
            if (!is_file($absolute)) {
                throw new RuntimeException('Arquivo listado em checksums não foi encontrado: ' . $relativePath);
            }

            $actual = hash_file('sha256', $absolute) ?: '';
            if (!hash_equals((string)$expectedHash, $actual)) {
                throw new RuntimeException('Falha de integridade detectada no arquivo ' . $relativePath . '.');
            }
        }
    }

    private function loadDocuments(string $root): array
    {
        $documents = [];
        foreach (self::REQUIRED_DATA_FILES as $file) {
            $documents[$file] = $this->readJsonFile($root . '/' . $file);
        }

        return $documents;
    }

    private function readJsonFile(string $path): array
    {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException('Não foi possível ler um arquivo do backup.');
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('O backup contém um JSON inválido em ' . basename($path) . '.');
        }

        return $decoded;
    }

    private function buildSummary(array $manifest, array $documents): array
    {
        $userDoc = $documents['data/user.json'] ?? [];
        $courses = array_values(array_filter($documents['data/courses.json'] ?? [], 'is_array'));
        $modules = array_values(array_filter($documents['data/modules.json'] ?? [], 'is_array'));
        return [
            'type' => (string)($manifest['type'] ?? 'user'),
            'generated_at' => (string)($manifest['generated_at'] ?? ''),
            'owner' => [
                'nome' => (string)($userDoc['owner']['nome'] ?? ''),
                'email' => (string)($userDoc['owner']['email'] ?? ''),
            ],
            'counts' => [
                'courses' => count($documents['data/courses.json'] ?? []),
                'modules' => count($documents['data/modules.json'] ?? []),
                'lessons' => count($documents['data/lessons.json'] ?? []),
                'quizzes' => count($documents['data/quizzes.json'] ?? []),
                'attempts' => count($documents['data/quiz_attempts.json'] ?? []),
                'progress' => count($documents['data/progress.json'] ?? []),
                'certificates' => count($documents['data/certificates.json'] ?? []),
                'enrollments' => count($documents['data/enrollments.json'] ?? []),
            ],
            'courses' => array_map(static function (array $course): array {
                return [
                    'source_id' => (int)($course['source_id'] ?? $course['id'] ?? 0),
                    'titulo' => (string)($course['titulo'] ?? ''),
                ];
            }, $courses),
            'modules' => array_map(static function (array $module): array {
                return [
                    'source_id' => (int)($module['source_id'] ?? $module['id'] ?? 0),
                    'course_id' => (int)($module['course_id'] ?? 0),
                    'titulo' => (string)($module['titulo'] ?? $module['module_title'] ?? ''),
                ];
            }, $modules),
        ];
    }

    private function validateDocumentSchemas(array $documents): void
    {
        foreach (self::REQUIRED_DATA_FILES as $file) {
            if (!array_key_exists($file, $documents) || !is_array($documents[$file])) {
                throw new RuntimeException('Schema inválido no documento ' . $file . '.');
            }
        }

        $userOwner = $documents['data/user.json']['owner'] ?? null;
        if (!is_array($userOwner)) {
            throw new RuntimeException('Schema inválido em data/user.json.');
        }
    }

    private function filterDocumentsForScope(array $documents, string $scope, ?int $sourceCourseId, ?int $sourceModuleId): array
    {
        $scope = in_array($scope, ['full', 'user', 'course', 'module'], true) ? $scope : 'full';
        if ($scope === 'full' || $scope === 'user') {
            if ($scope === 'user') {
                $documents['data/courses.json'] = [];
                $documents['data/modules.json'] = [];
                $documents['data/lessons.json'] = [];
                $documents['data/quizzes.json'] = [];
                $documents['data/quiz_attempts.json'] = [];
                $documents['data/progress.json'] = [];
                $documents['data/certificates.json'] = [];
                $documents['data/enrollments.json'] = [];
            }
            return [$documents, $scope];
        }

        if ($scope === 'course') {
            $targetCourseId = (int)$sourceCourseId;
            $documents['data/courses.json'] = array_values(array_filter($documents['data/courses.json'], static fn($row) => (int)($row['source_id'] ?? 0) === $targetCourseId));
            $documents['data/modules.json'] = array_values(array_filter($documents['data/modules.json'], static fn($row) => (int)($row['course_id'] ?? 0) === $targetCourseId));
            $moduleIds = array_map(static fn($row) => (int)($row['source_id'] ?? 0), $documents['data/modules.json']);
            $documents['data/lessons.json'] = array_values(array_filter($documents['data/lessons.json'], static fn($row) => (int)($row['course_id'] ?? 0) === $targetCourseId));
            $lessonIds = array_map(static fn($row) => (int)($row['source_id'] ?? 0), $documents['data/lessons.json']);
            $documents['data/quizzes.json'] = array_values(array_filter($documents['data/quizzes.json'], static fn($row) => (int)($row['course_id'] ?? 0) === $targetCourseId || in_array((int)($row['module_id'] ?? 0), $moduleIds, true) || in_array((int)($row['lesson_id'] ?? 0), $lessonIds, true)));
            $quizIds = array_map(static fn($row) => (int)($row['source_id'] ?? 0), $documents['data/quizzes.json']);
            $documents['data/quiz_attempts.json'] = array_values(array_filter($documents['data/quiz_attempts.json'], static fn($row) => in_array((int)($row['quiz_id'] ?? 0), $quizIds, true)));
            $documents['data/progress.json'] = array_values(array_filter($documents['data/progress.json'], static fn($row) => in_array((int)($row['lesson_id'] ?? 0), $lessonIds, true)));
            $documents['data/certificates.json'] = array_values(array_filter($documents['data/certificates.json'], static fn($row) => (int)($row['course_id'] ?? 0) === $targetCourseId));
            $documents['data/enrollments.json'] = array_values(array_filter($documents['data/enrollments.json'], static fn($row) => (int)($row['course_id'] ?? 0) === $targetCourseId));
            return [$documents, 'course'];
        }

        $targetModuleId = (int)$sourceModuleId;
        $documents['data/modules.json'] = array_values(array_filter($documents['data/modules.json'], static fn($row) => (int)($row['source_id'] ?? 0) === $targetModuleId));
        $courseIds = array_map(static fn($row) => (int)($row['course_id'] ?? 0), $documents['data/modules.json']);
        $documents['data/courses.json'] = array_values(array_filter($documents['data/courses.json'], static fn($row) => in_array((int)($row['source_id'] ?? 0), $courseIds, true)));
        $documents['data/lessons.json'] = array_values(array_filter($documents['data/lessons.json'], static fn($row) => (int)($row['module_id'] ?? 0) === $targetModuleId));
        $lessonIds = array_map(static fn($row) => (int)($row['source_id'] ?? 0), $documents['data/lessons.json']);
        $documents['data/quizzes.json'] = array_values(array_filter($documents['data/quizzes.json'], static fn($row) => (int)($row['module_id'] ?? 0) === $targetModuleId || in_array((int)($row['lesson_id'] ?? 0), $lessonIds, true)));
        $quizIds = array_map(static fn($row) => (int)($row['source_id'] ?? 0), $documents['data/quizzes.json']);
        $documents['data/quiz_attempts.json'] = array_values(array_filter($documents['data/quiz_attempts.json'], static fn($row) => in_array((int)($row['quiz_id'] ?? 0), $quizIds, true)));
        $documents['data/progress.json'] = array_values(array_filter($documents['data/progress.json'], static fn($row) => in_array((int)($row['lesson_id'] ?? 0), $lessonIds, true)));
        $documents['data/certificates.json'] = array_values(array_filter($documents['data/certificates.json'], static fn($row) => (int)($row['module_id'] ?? 0) === $targetModuleId));
        $documents['data/enrollments.json'] = [];
        return [$documents, 'module'];
    }

    private function restoreOwnerProfile(array $owner, string $root, int $currentUserId): void
    {
        if (empty($owner)) {
            return;
        }

        $nome = trim((string)($owner['nome'] ?? ''));
        $email = trim((string)($owner['email'] ?? ''));
        $foto = $this->restoreFileFromBackup($root, (string)($owner['avatar_backup_path'] ?? ''), 'restored-avatar');

        $fields = [];
        $params = [];
        if ($nome !== '') {
            $fields[] = 'nome = ?';
            $params[] = $nome;
        }
        if ($email !== '') {
            $fields[] = 'email = ?';
            $params[] = $email;
        }
        if ($foto !== null) {
            $fields[] = 'fotografia = ?';
            $params[] = $foto;
        }

        if (empty($fields)) {
            return;
        }

        $params[] = $currentUserId;
        $stmt = $this->pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);
    }

    private function buildUserMap(array $userDocument, int $currentUserId): array
    {
        $map = [];
        $owner = $userDocument['owner'] ?? null;
        if (is_array($owner) && (int)($owner['source_id'] ?? 0) > 0) {
            $map[(int)$owner['source_id']] = $currentUserId;
        }

        foreach (($userDocument['related_users'] ?? []) as $related) {
            if (!is_array($related)) {
                continue;
            }

            $sourceId = (int)($related['source_id'] ?? 0);
            $email = trim((string)($related['email'] ?? ''));
            if ($sourceId <= 0 || $email === '') {
                continue;
            }

            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $resolved = (int)($stmt->fetchColumn() ?: 0);
            if ($resolved > 0) {
                $map[$sourceId] = $resolved;
            }
        }

        return $map;
    }

    private function restoreCourses(array $courses, string $root, int $teacherId): array
    {
        $map = [];
        foreach ($courses as $course) {
            if (!is_array($course)) {
                continue;
            }

            $sourceId = (int)($course['source_id'] ?? 0);
            $titulo = trim((string)($course['titulo'] ?? ''));
            if ($titulo === '') {
                continue;
            }

            $thumbnail = $this->restoreFileFromBackup($root, (string)($course['thumbnail_backup_path'] ?? ''), 'restored-thumb');

            $existingId = 0;
            if ($sourceId > 0) {
                $stmt = $this->pdo->prepare('SELECT id FROM courses WHERE id = ? AND teacher_id = ? LIMIT 1');
                $stmt->execute([$sourceId, $teacherId]);
                $existingId = (int)($stmt->fetchColumn() ?: 0);
            }

            if ($existingId <= 0) {
                $stmt = $this->pdo->prepare('SELECT id FROM courses WHERE teacher_id = ? AND titulo = ? LIMIT 1');
                $stmt->execute([$teacherId, $titulo]);
                $existingId = (int)($stmt->fetchColumn() ?: 0);
            }

            if ($existingId > 0) {
                $stmt = $this->pdo->prepare(
                    'UPDATE courses
                     SET titulo = ?, descricao = ?, categoria = ?, course_structure = ?, status = ?, thumbnail = COALESCE(?, thumbnail)
                     WHERE id = ?'
                );
                $stmt->execute([
                    $titulo,
                    (string)($course['descricao'] ?? ''),
                    (string)($course['categoria'] ?? ''),
                    (string)($course['course_structure'] ?? 'single_module'),
                    (string)($course['status'] ?? 'ativo'),
                    $thumbnail,
                    $existingId,
                ]);
                $map[$sourceId] = $existingId;
                continue;
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO courses (titulo, descricao, teacher_id, thumbnail, categoria, course_structure, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $titulo,
                (string)($course['descricao'] ?? ''),
                $teacherId,
                $thumbnail,
                (string)($course['categoria'] ?? ''),
                (string)($course['course_structure'] ?? 'single_module'),
                (string)($course['status'] ?? 'ativo'),
            ]);
            $map[$sourceId] = (int)$this->pdo->lastInsertId();
        }

        return $map;
    }

    private function restoreModules(array $modules, array $courseMap): array
    {
        $map = [];
        foreach ($modules as $module) {
            if (!is_array($module)) {
                continue;
            }

            $sourceId = (int)($module['source_id'] ?? 0);
            $courseId = $courseMap[(int)($module['course_id'] ?? 0)] ?? 0;
            if ($courseId <= 0) {
                continue;
            }

            $titulo = trim((string)($module['titulo'] ?? ''));
            $ordem = (int)($module['ordem'] ?? 1);
            $stmt = $this->pdo->prepare('SELECT id FROM course_modules WHERE course_id = ? AND ordem = ? AND titulo = ? LIMIT 1');
            $stmt->execute([$courseId, $ordem, $titulo]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);

            if ($existingId > 0) {
                $stmt = $this->pdo->prepare('UPDATE course_modules SET descricao = ?, is_default = ? WHERE id = ?');
                $stmt->execute([
                    (string)($module['descricao'] ?? ''),
                    (int)($module['is_default'] ?? 0),
                    $existingId,
                ]);
                $map[$sourceId] = $existingId;
                continue;
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO course_modules (course_id, titulo, descricao, ordem, is_default)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $courseId,
                $titulo,
                (string)($module['descricao'] ?? ''),
                $ordem,
                (int)($module['is_default'] ?? 0),
            ]);
            $map[$sourceId] = (int)$this->pdo->lastInsertId();
        }

        return $map;
    }

    private function restoreLessons(array $lessons, array $courseMap, array $moduleMap, string $root): array
    {
        $map = [];
        foreach ($lessons as $lesson) {
            if (!is_array($lesson)) {
                continue;
            }

            $sourceId = (int)($lesson['source_id'] ?? 0);
            $courseId = $courseMap[(int)($lesson['course_id'] ?? 0)] ?? 0;
            if ($courseId <= 0) {
                continue;
            }

            $moduleId = !empty($lesson['module_id']) ? ($moduleMap[(int)$lesson['module_id']] ?? null) : null;
            $titulo = trim((string)($lesson['titulo'] ?? ''));
            $ordem = (int)($lesson['ordem'] ?? 1);
            $upload = $this->restoreFileFromBackup($root, (string)($lesson['upload_backup_path'] ?? ''), 'restored-file');
            $audioUpload = $this->restoreFileFromBackup($root, (string)($lesson['audio_backup_path'] ?? ''), 'restored-audio');

            $stmt = $this->pdo->prepare('SELECT id FROM lessons WHERE course_id = ? AND ordem = ? AND titulo = ? LIMIT 1');
            $stmt->execute([$courseId, $ordem, $titulo]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);

            if ($existingId > 0) {
                $stmt = $this->pdo->prepare(
                    'UPDATE lessons
                     SET module_id = ?, descricao = ?, tipo = ?, conteudo = ?, resumo = ?, url_arquivo = COALESCE(?, url_arquivo), audio_url = COALESCE(?, audio_url), audio_storage_disk = NULL, audio_storage_key = NULL, video_id = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    $moduleId,
                    (string)($lesson['descricao'] ?? ''),
                    (string)($lesson['tipo'] ?? 'texto'),
                    (string)($lesson['conteudo'] ?? ''),
                    (string)($lesson['resumo'] ?? ''),
                    $upload,
                    $audioUpload ?: ((string)($lesson['audio_url'] ?? '') ?: null),
                    (string)($lesson['video_id'] ?? ''),
                    $existingId,
                ]);
                $map[$sourceId] = $existingId;
                continue;
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO lessons (course_id, module_id, titulo, descricao, tipo, conteudo, resumo, url_arquivo, audio_url, audio_storage_disk, audio_storage_key, video_id, ordem)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?)'
            );
            $stmt->execute([
                $courseId,
                $moduleId,
                $titulo,
                (string)($lesson['descricao'] ?? ''),
                (string)($lesson['tipo'] ?? 'texto'),
                (string)($lesson['conteudo'] ?? ''),
                (string)($lesson['resumo'] ?? ''),
                $upload,
                $audioUpload ?: ((string)($lesson['audio_url'] ?? '') ?: null),
                (string)($lesson['video_id'] ?? ''),
                $ordem,
            ]);
            $map[$sourceId] = (int)$this->pdo->lastInsertId();
        }

        return $map;
    }

    private function restoreQuizzes(array $quizzes, array $courseMap, array $moduleMap, array $lessonMap): array
    {
        $quizMap = [];
        $questionMap = [];

        foreach ($quizzes as $quiz) {
            if (!is_array($quiz)) {
                continue;
            }

            $sourceId = (int)($quiz['source_id'] ?? 0);
            $courseId = !empty($quiz['course_id']) ? ($courseMap[(int)$quiz['course_id']] ?? 0) : 0;
            $lessonId = !empty($quiz['lesson_id']) ? ($lessonMap[(int)$quiz['lesson_id']] ?? null) : null;
            $moduleId = !empty($quiz['module_id']) ? ($moduleMap[(int)$quiz['module_id']] ?? null) : null;
            $titulo = trim((string)($quiz['titulo'] ?? ''));
            if ($titulo === '') {
                continue;
            }

            $stmt = $this->pdo->prepare(
                'SELECT id
                 FROM quizzes
                 WHERE COALESCE(course_id, 0) = ?
                   AND COALESCE(module_id, 0) = ?
                   AND COALESCE(lesson_id, 0) = ?
                   AND titulo = ?
                   AND tipo = ?
                 LIMIT 1'
            );
            $stmt->execute([$courseId, (int)$moduleId, (int)$lessonId, $titulo, (string)($quiz['tipo'] ?? 'aula')]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);

            $payload = [
                $lessonId,
                $courseId > 0 ? $courseId : null,
                $moduleId,
                $titulo,
                (string)($quiz['descricao'] ?? ''),
                (string)($quiz['tipo'] ?? 'aula'),
                (string)($quiz['dificuldade'] ?? 'normal'),
                (float)($quiz['peso'] ?? 0),
                (float)($quiz['nota_minima'] ?? 0),
                (int)($quiz['obrigatorio'] ?? 0),
                isset($quiz['tentativas_maximas']) ? (int)$quiz['tentativas_maximas'] : null,
                isset($quiz['tempo_limite']) ? (int)$quiz['tempo_limite'] : null,
                (int)($quiz['embaralhar_perguntas'] ?? 0),
                (int)($quiz['embaralhar_respostas'] ?? 0),
                (int)($quiz['mostrar_respostas'] ?? 1),
                (int)($quiz['mostrar_nota'] ?? 1),
                isset($quiz['pontos_totais']) ? (int)$quiz['pontos_totais'] : null,
            ];

            if ($existingId > 0) {
                $stmt = $this->pdo->prepare(
                    'UPDATE quizzes
                     SET lesson_id = ?, course_id = ?, module_id = ?, titulo = ?, descricao = ?, tipo = ?, dificuldade = ?, peso = ?, nota_minima = ?,
                         obrigatorio = ?, tentativas_maximas = ?, tempo_limite = ?, embaralhar_perguntas = ?, embaralhar_respostas = ?, mostrar_respostas = ?,
                         mostrar_nota = ?, pontos_totais = ?
                     WHERE id = ?'
                );
                $stmt->execute(array_merge($payload, [$existingId]));
                $quizId = $existingId;
            } else {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO quizzes (
                        lesson_id, course_id, module_id, titulo, descricao, tipo, dificuldade, peso, nota_minima,
                        obrigatorio, tentativas_maximas, tempo_limite, embaralhar_perguntas, embaralhar_respostas,
                        mostrar_respostas, mostrar_nota, pontos_totais
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute($payload);
                $quizId = (int)$this->pdo->lastInsertId();
            }

            $quizMap[$sourceId] = $quizId;
            $questionMap += $this->restoreQuizQuestions($quizId, $quiz['questions'] ?? []);
        }

        return [$quizMap, $questionMap];
    }

    private function restoreQuizQuestions(int $quizId, array $questions): array
    {
        $map = [];
        foreach ($questions as $question) {
            if (!is_array($question)) {
                continue;
            }

            $sourceId = (int)($question['source_id'] ?? 0);
            $texto = trim((string)($question['texto'] ?? ''));
            $ordem = (int)($question['ordem'] ?? 1);
            if ($texto === '') {
                continue;
            }

            $stmt = $this->pdo->prepare('SELECT id FROM questions WHERE quiz_id = ? AND ordem = ? AND texto = ? LIMIT 1');
            $stmt->execute([$quizId, $ordem, $texto]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);

            $payload = [
                $quizId,
                $texto,
                (string)($question['tipo'] ?? 'multipla'),
                (string)($question['opcoes'] ?? '[]'),
                (string)($question['resposta_correta'] ?? ''),
                (string)($question['explicacao'] ?? ''),
                (float)($question['pontos'] ?? 1),
                $ordem,
            ];

            if ($existingId > 0) {
                $stmt = $this->pdo->prepare(
                    'UPDATE questions
                     SET texto = ?, tipo = ?, opcoes = ?, resposta_correta = ?, explicacao = ?, pontos = ?, ordem = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    $texto,
                    (string)($question['tipo'] ?? 'multipla'),
                    (string)($question['opcoes'] ?? '[]'),
                    (string)($question['resposta_correta'] ?? ''),
                    (string)($question['explicacao'] ?? ''),
                    (float)($question['pontos'] ?? 1),
                    $ordem,
                    $existingId,
                ]);
                $map[$sourceId] = $existingId;
                continue;
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO questions (quiz_id, texto, tipo, opcoes, resposta_correta, explicacao, pontos, ordem)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute($payload);
            $map[$sourceId] = (int)$this->pdo->lastInsertId();
        }

        return $map;
    }

    private function mapExistingCoursesFromStudentBackup(array $courses, int $currentUserId): array
    {
        $map = [];
        foreach ($courses as $course) {
            if (!is_array($course)) {
                continue;
            }

            $sourceId = (int)($course['source_id'] ?? 0);
            $titulo = trim((string)($course['titulo'] ?? ''));
            $teacherEmail = trim((string)($course['teacher_email'] ?? ''));
            if ($sourceId <= 0 || $titulo === '') {
                continue;
            }

            $existingId = 0;
            $stmt = $this->pdo->prepare('SELECT id, teacher_id FROM courses WHERE titulo = ? ORDER BY id ASC');
            $stmt->execute([$titulo]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $teacherId = (int)($row['teacher_id'] ?? 0);
                if ($teacherEmail === '') {
                    $existingId = (int)$row['id'];
                    break;
                }

                $teacherStmt = $this->pdo->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
                $teacherStmt->execute([$teacherId]);
                if (trim((string)$teacherStmt->fetchColumn()) === $teacherEmail) {
                    $existingId = (int)$row['id'];
                    break;
                }
            }

            if ($existingId <= 0) {
                $stmt = $this->pdo->prepare('SELECT course_id FROM enrollments WHERE user_id = ? AND course_id = ? LIMIT 1');
                $stmt->execute([$currentUserId, $sourceId]);
                $existingId = (int)($stmt->fetchColumn() ?: 0);
            }

            if ($existingId > 0) {
                $map[$sourceId] = $existingId;
            }
        }

        return $map;
    }

    private function mapExistingModules(array $modules, array $courseMap): array
    {
        $map = [];
        foreach ($modules as $module) {
            if (!is_array($module)) {
                continue;
            }

            $sourceId = (int)($module['source_id'] ?? 0);
            $courseId = $courseMap[(int)($module['course_id'] ?? 0)] ?? 0;
            if ($sourceId <= 0 || $courseId <= 0) {
                continue;
            }

            $stmt = $this->pdo->prepare('SELECT id FROM course_modules WHERE course_id = ? AND ordem = ? AND titulo = ? LIMIT 1');
            $stmt->execute([$courseId, (int)($module['ordem'] ?? 1), (string)($module['titulo'] ?? '')]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);
            if ($existingId > 0) {
                $map[$sourceId] = $existingId;
            }
        }

        return $map;
    }

    private function mapExistingLessons(array $lessons, array $courseMap, array $moduleMap): array
    {
        $map = [];
        foreach ($lessons as $lesson) {
            if (!is_array($lesson)) {
                continue;
            }

            $sourceId = (int)($lesson['source_id'] ?? 0);
            $courseId = $courseMap[(int)($lesson['course_id'] ?? 0)] ?? 0;
            if ($sourceId <= 0 || $courseId <= 0) {
                continue;
            }

            $stmt = $this->pdo->prepare('SELECT id FROM lessons WHERE course_id = ? AND ordem = ? AND titulo = ? LIMIT 1');
            $stmt->execute([$courseId, (int)($lesson['ordem'] ?? 1), (string)($lesson['titulo'] ?? '')]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);
            if ($existingId > 0) {
                $map[$sourceId] = $existingId;
            }
        }

        return $map;
    }

    private function mapExistingQuizzes(array $quizzes, array $courseMap, array $moduleMap, array $lessonMap): array
    {
        $quizMap = [];
        $questionMap = [];

        foreach ($quizzes as $quiz) {
            if (!is_array($quiz)) {
                continue;
            }

            $sourceId = (int)($quiz['source_id'] ?? 0);
            $courseId = !empty($quiz['course_id']) ? ($courseMap[(int)$quiz['course_id']] ?? 0) : 0;
            $lessonId = !empty($quiz['lesson_id']) ? ($lessonMap[(int)$quiz['lesson_id']] ?? null) : null;
            $moduleId = !empty($quiz['module_id']) ? ($moduleMap[(int)$quiz['module_id']] ?? null) : null;

            $stmt = $this->pdo->prepare(
                'SELECT id
                 FROM quizzes
                 WHERE COALESCE(course_id, 0) = ?
                   AND COALESCE(module_id, 0) = ?
                   AND COALESCE(lesson_id, 0) = ?
                   AND titulo = ?
                   AND tipo = ?
                 LIMIT 1'
            );
            $stmt->execute([$courseId, (int)$moduleId, (int)$lessonId, (string)($quiz['titulo'] ?? ''), (string)($quiz['tipo'] ?? 'aula')]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);
            if ($existingId > 0) {
                $quizMap[$sourceId] = $existingId;
                foreach (($quiz['questions'] ?? []) as $question) {
                    if (!is_array($question)) {
                        continue;
                    }
                    $questionStmt = $this->pdo->prepare('SELECT id FROM questions WHERE quiz_id = ? AND ordem = ? AND texto = ? LIMIT 1');
                    $questionStmt->execute([$existingId, (int)($question['ordem'] ?? 1), (string)($question['texto'] ?? '')]);
                    $questionId = (int)($questionStmt->fetchColumn() ?: 0);
                    if ($questionId > 0) {
                        $questionMap[(int)($question['source_id'] ?? 0)] = $questionId;
                    }
                }
            }
        }

        return [$quizMap, $questionMap];
    }

    private function restoreEnrollments(array $enrollments, array $userMap, array $courseMap): int
    {
        $restored = 0;
        foreach ($enrollments as $enrollment) {
            if (!is_array($enrollment)) {
                continue;
            }

            $userId = $userMap[(int)($enrollment['user_id'] ?? 0)] ?? 0;
            $courseId = $courseMap[(int)($enrollment['course_id'] ?? 0)] ?? 0;
            if ($userId <= 0 || $courseId <= 0) {
                continue;
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO enrollments (user_id, course_id, progress, data_conclusao)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE progress = VALUES(progress), data_conclusao = VALUES(data_conclusao)'
            );
            $stmt->execute([
                $userId,
                $courseId,
                (int)($enrollment['progress'] ?? 0),
                $enrollment['data_conclusao'] ?? null,
            ]);
            $restored++;
        }

        return $restored;
    }

    private function restoreProgress(array $progressRows, array $userMap, array $lessonMap): int
    {
        $restored = 0;
        foreach ($progressRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $userId = $userMap[(int)($row['user_id'] ?? 0)] ?? 0;
            $lessonId = $lessonMap[(int)($row['lesson_id'] ?? 0)] ?? 0;
            if ($userId <= 0 || $lessonId <= 0) {
                continue;
            }

            $stmt = $this->pdo->prepare('SELECT id FROM lesson_progress WHERE user_id = ? AND lesson_id = ? LIMIT 1');
            $stmt->execute([$userId, $lessonId]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);

            if ($existingId > 0) {
                $stmt = $this->pdo->prepare(
                    'UPDATE lesson_progress
                     SET concluida = ?, data_conclusao = ?, tempo_assistido = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    (int)($row['concluida'] ?? 0),
                    $row['data_conclusao'] ?? null,
                    isset($row['tempo_assistido']) ? (int)$row['tempo_assistido'] : null,
                    $existingId,
                ]);
            } else {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO lesson_progress (user_id, lesson_id, concluida, data_conclusao, tempo_assistido)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $userId,
                    $lessonId,
                    (int)($row['concluida'] ?? 0),
                    $row['data_conclusao'] ?? null,
                    isset($row['tempo_assistido']) ? (int)$row['tempo_assistido'] : null,
                ]);
            }

            $restored++;
        }

        return $restored;
    }

    private function restoreQuizAttempts(array $attempts, array $userMap, array $quizMap, array $questionMap): int
    {
        $restored = 0;
        foreach ($attempts as $attempt) {
            if (!is_array($attempt)) {
                continue;
            }

            $userId = $userMap[(int)($attempt['user_id'] ?? 0)] ?? 0;
            $quizId = $quizMap[(int)($attempt['quiz_id'] ?? 0)] ?? 0;
            if ($userId <= 0 || $quizId <= 0) {
                continue;
            }

            $stmt = $this->pdo->prepare(
                'SELECT id
                 FROM quiz_attempts
                 WHERE user_id = ? AND quiz_id = ? AND tentativa_numero = ? AND data_realizacao <=> ?
                 LIMIT 1'
            );
            $stmt->execute([
                $userId,
                $quizId,
                (int)($attempt['tentativa_numero'] ?? 1),
                $attempt['data_realizacao'] ?? null,
            ]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);

            if ($existingId > 0) {
                $stmt = $this->pdo->prepare(
                    'UPDATE quiz_attempts
                     SET pontuacao = ?, percentual = ?, total_correto = ?, total_questoes = ?, tempo_gasto = ?, aprovado = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    (float)($attempt['pontuacao'] ?? 0),
                    (float)($attempt['percentual'] ?? 0),
                    (int)($attempt['total_correto'] ?? 0),
                    (int)($attempt['total_questoes'] ?? 0),
                    (int)($attempt['tempo_gasto'] ?? 0),
                    (int)($attempt['aprovado'] ?? 0),
                    $existingId,
                ]);
                $attemptId = $existingId;
            } else {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO quiz_attempts (
                        user_id, quiz_id, pontuacao, percentual, total_correto, total_questoes,
                        tentativa_numero, tempo_gasto, aprovado, data_realizacao
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $userId,
                    $quizId,
                    (float)($attempt['pontuacao'] ?? 0),
                    (float)($attempt['percentual'] ?? 0),
                    (int)($attempt['total_correto'] ?? 0),
                    (int)($attempt['total_questoes'] ?? 0),
                    (int)($attempt['tentativa_numero'] ?? 1),
                    (int)($attempt['tempo_gasto'] ?? 0),
                    (int)($attempt['aprovado'] ?? 0),
                    $attempt['data_realizacao'] ?? null,
                ]);
                $attemptId = (int)$this->pdo->lastInsertId();
            }

            $this->restoreAttemptAnswers($attemptId, $attempt['answers'] ?? [], $questionMap);
            $restored++;
        }

        return $restored;
    }

    private function restoreAttemptAnswers(int $attemptId, array $answers, array $questionMap): void
    {
        foreach ($answers as $answer) {
            if (!is_array($answer)) {
                continue;
            }

            $questionId = $questionMap[(int)($answer['question_id'] ?? 0)] ?? 0;
            if ($questionId <= 0) {
                continue;
            }

            $stmt = $this->pdo->prepare('SELECT id FROM quiz_attempt_answers WHERE attempt_id = ? AND question_id = ? LIMIT 1');
            $stmt->execute([$attemptId, $questionId]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);

            if ($existingId > 0) {
                $stmt = $this->pdo->prepare(
                    'UPDATE quiz_attempt_answers SET resposta_usuario = ?, correta = ? WHERE id = ?'
                );
                $stmt->execute([
                    (string)($answer['resposta_usuario'] ?? ''),
                    (int)($answer['correta'] ?? 0),
                    $existingId,
                ]);
                continue;
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO quiz_attempt_answers (attempt_id, question_id, resposta_usuario, correta)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $attemptId,
                $questionId,
                (string)($answer['resposta_usuario'] ?? ''),
                (int)($answer['correta'] ?? 0),
            ]);
        }
    }

    private function restoreCertificates(array $certificates, array $userMap, array $courseMap, array $moduleMap): int
    {
        $restored = 0;
        foreach ($certificates as $certificate) {
            if (!is_array($certificate)) {
                continue;
            }

            $userId = $userMap[(int)($certificate['user_id'] ?? 0)] ?? 0;
            $courseId = $courseMap[(int)($certificate['course_id'] ?? 0)] ?? 0;
            $moduleId = !empty($certificate['module_id']) ? ($moduleMap[(int)$certificate['module_id']] ?? null) : null;
            if ($userId <= 0 || $courseId <= 0) {
                continue;
            }

            $stmt = $this->pdo->prepare(
                'SELECT id
                 FROM certificates
                 WHERE user_id = ? AND course_id = ? AND type = ? AND (module_id <=> ?)
                 LIMIT 1'
            );
            $stmt->execute([
                $userId,
                $courseId,
                (string)($certificate['type'] ?? 'course'),
                $moduleId,
            ]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);

            $payload = [
                $userId,
                $courseId,
                $moduleId,
                (string)($certificate['type'] ?? 'course'),
                (string)($certificate['certificate_code'] ?? null),
                isset($certificate['grade']) ? (float)$certificate['grade'] : null,
                $certificate['issued_at'] ?? null,
                (string)($certificate['codigo_certificado'] ?? null),
                isset($certificate['nota_final']) ? (float)$certificate['nota_final'] : null,
                $certificate['data_emissao'] ?? null,
            ];

            if ($existingId > 0) {
                $stmt = $this->pdo->prepare(
                    'UPDATE certificates
                     SET certificate_code = ?, grade = ?, issued_at = ?, codigo_certificado = ?, nota_final = ?, data_emissao = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    (string)($certificate['certificate_code'] ?? null),
                    isset($certificate['grade']) ? (float)$certificate['grade'] : null,
                    $certificate['issued_at'] ?? null,
                    (string)($certificate['codigo_certificado'] ?? null),
                    isset($certificate['nota_final']) ? (float)$certificate['nota_final'] : null,
                    $certificate['data_emissao'] ?? null,
                    $existingId,
                ]);
            } else {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO certificates (
                        user_id, course_id, module_id, type, certificate_code, grade, issued_at,
                        codigo_certificado, nota_final, data_emissao
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute($payload);
            }

            $restored++;
        }

        return $restored;
    }

    private function restoreFileFromBackup(string $root, string $relativePath, string $prefix): ?string
    {
        $relativePath = str_replace('\\', '/', trim($relativePath));
        if ($relativePath === '' || $this->isUnsafeZipPath($relativePath)) {
            return null;
        }

        $source = $root . '/' . ltrim($relativePath, '/');
        if (!is_file($source)) {
            return null;
        }

        $uploadsDir = dirname(__DIR__, 2) . '/public/uploads';
        if (!is_dir($uploadsDir) && !@mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
            throw new RuntimeException('Não foi possível preparar o diretório de uploads para restauração.');
        }

        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $targetName = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . ($extension !== '' ? '.' . $extension : '');
        $target = $uploadsDir . '/' . $targetName;

        if (!@copy($source, $target)) {
            throw new RuntimeException('Não foi possível restaurar um arquivo do backup.');
        }

        return $targetName;
    }

    private function isUnsafeZipPath(string $path): bool
    {
        if ($path === '' || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return true;
        }

        return str_contains($path, '../') || str_contains($path, '..\\');
    }

    private function detectMime(string $path): string
    {
        if ($path === '' || !is_file($path)) {
            return 'application/octet-stream';
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)(finfo_file($finfo, $path) ?: 'application/octet-stream');
                finfo_close($finfo);
                return $mime;
            }
        }

        return 'application/octet-stream';
    }

    private function writeJsonFile(string $path, $payload): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Não foi possível escrever um arquivo da restauração.');
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || @file_put_contents($path, $json, LOCK_EX) === false) {
            throw new RuntimeException('Não foi possível escrever um arquivo da restauração.');
        }
    }

    private function generateChecksumsForExtractedRoot(string $root): array
    {
        $checksums = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            if (in_array($relative, ['manifest.json', 'checksums.json'], true)) {
                continue;
            }
            $checksums[$relative] = hash_file('sha256', $file->getPathname()) ?: '';
        }

        ksort($checksums);
        return $checksums;
    }

    private function normalizeLegacyQuizRows(array $legacyQuizRows): array
    {
        if (empty($legacyQuizRows)) {
            return [];
        }

        $first = $legacyQuizRows[0] ?? null;
        if (is_array($first) && array_key_exists('tentativa_id', $first)) {
            return [];
        }

        return $legacyQuizRows;
    }

    private function normalizeLegacyQuizCatalog(array $legacyQuizRows): array
    {
        $catalog = [];
        foreach ($legacyQuizRows as $row) {
            if (!is_array($row) || !array_key_exists('quiz_id', $row)) {
                continue;
            }

            $quizId = (int)($row['quiz_id'] ?? 0);
            if ($quizId <= 0 || isset($catalog[$quizId])) {
                continue;
            }

            $catalog[$quizId] = [
                'source_id' => $quizId,
                'lesson_id' => !empty($row['lesson_id']) ? (int)$row['lesson_id'] : null,
                'course_id' => !empty($row['course_id']) ? (int)$row['course_id'] : null,
                'module_id' => !empty($row['module_id']) ? (int)$row['module_id'] : null,
                'titulo' => (string)($row['quiz_title'] ?? ('Quiz ' . $quizId)),
                'descricao' => '',
                'tipo' => (string)($row['quiz_type'] ?? 'aula'),
                'dificuldade' => 'normal',
                'peso' => 0,
                'nota_minima' => isset($row['nota_minima']) ? (float)$row['nota_minima'] : 0,
                'obrigatorio' => 1,
                'tentativas_maximas' => null,
                'tempo_limite' => null,
                'embaralhar_perguntas' => 0,
                'embaralhar_respostas' => 0,
                'mostrar_respostas' => 1,
                'mostrar_nota' => 1,
                'pontos_totais' => null,
                'questions' => [],
            ];
        }

        return array_values($catalog);
    }

    private function normalizeLegacyQuizAttemptRows(array $legacyQuizRows): array
    {
        $attempts = [];
        foreach ($legacyQuizRows as $row) {
            if (!is_array($row) || !array_key_exists('tentativa_id', $row)) {
                continue;
            }

            $attempts[] = [
                'source_id' => (int)($row['tentativa_id'] ?? 0),
                'user_id' => (int)($row['user_id'] ?? 0),
                'quiz_id' => (int)($row['quiz_id'] ?? 0),
                'course_id' => !empty($row['course_id']) ? (int)$row['course_id'] : null,
                'lesson_id' => !empty($row['lesson_id']) ? (int)$row['lesson_id'] : null,
                'module_id' => !empty($row['module_id']) ? (int)$row['module_id'] : null,
                'pontuacao' => (float)($row['pontuacao'] ?? 0),
                'percentual' => (float)($row['percentual'] ?? 0),
                'total_correto' => (int)($row['total_correto'] ?? 0),
                'total_questoes' => (int)($row['total_questoes'] ?? 0),
                'tentativa_numero' => (int)($row['tentativa_numero'] ?? 1),
                'tempo_gasto' => (int)($row['tempo_gasto'] ?? 0),
                'aprovado' => (int)($row['aprovado'] ?? 0),
                'data_realizacao' => $row['data_realizacao'] ?? null,
                'answers' => [],
            ];
        }

        return $attempts;
    }

    private function normalizeLegacyProgressRows(array $progressRows): array
    {
        return $progressRows;
    }

    private function normalizeLegacyEnrollmentRows(array $rows): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized[] = [
                'source_id' => (int)($row['source_id'] ?? 0),
                'user_id' => (int)($row['user_id'] ?? $row['student_id'] ?? 0),
                'course_id' => (int)($row['course_id'] ?? 0),
                'progress' => (int)($row['progress'] ?? $row['progresso_matricula'] ?? 0),
                'data_inscricao' => $row['data_inscricao'] ?? null,
                'data_conclusao' => $row['data_conclusao'] ?? null,
                'student_email' => (string)($row['student_email'] ?? $row['aluno_email'] ?? ''),
                'student_name' => (string)($row['student_name'] ?? $row['aluno_nome'] ?? ''),
            ];
        }
        return $normalized;
    }

    private function legacyEnrollmentsFromCourses(array $courses, int $userId): array
    {
        $rows = [];
        foreach ($courses as $course) {
            if (!is_array($course)) {
                continue;
            }
            $rows[] = [
                'source_id' => 0,
                'user_id' => $userId,
                'course_id' => (int)($course['id'] ?? 0),
                'progress' => (int)($course['progresso_registrado'] ?? 0),
                'data_inscricao' => $course['data_inscricao'] ?? null,
                'data_conclusao' => $course['data_conclusao'] ?? null,
                'student_email' => '',
                'student_name' => '',
            ];
        }
        return $rows;
    }

    private function clearDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
    }
}
