<?php

/**
 * Controller: ExportController
 * Gera backups versionados com manifest, checksums e estrutura segmentada.
 */
class ExportController
{
    private PDO $pdo;
    private string $backupDir;
    private StorageService $storage;
    private BackupLogService $backupLogs;

    private const DOWNLOAD_SESSION_KEY = 'backup_downloads';
    private const DOWNLOAD_TTL = 1800;
    private const BACKUP_VERSION = '1.0';
    private const PLATFORM_NAME = 'Plataforma EAD';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->backupDir = $this->resolveBackupDirectory();
        $this->storage = new StorageService();
        $this->backupLogs = new BackupLogService($pdo);
        $this->ensureBackupDirectory();
    }

    public function exportarAluno($userId, ?string $password = null): array
    {
        $userId = (int)$userId;
        $this->assertCurrentUserOwnsExport($userId);

        $usuario = $this->fetchUser($userId);
        if (!$usuario) {
            throw new RuntimeException('Usuário não encontrado para exportação.');
        }

        $cursos = $this->fetchStudentCourses($userId);
        $courseIds = $this->extractIds($cursos, 'id');
        $enrollments = $this->fetchStudentEnrollments($userId, $courseIds);
        $progress = $this->fetchStudentLessonProgress($userId, $courseIds);
        $quizAttempts = $this->fetchStudentQuizAttemptsDetailed($userId, $courseIds);
        $certificates = $this->fetchStudentCertificates($userId, $courseIds);

        $quizIds = $this->extractIds($quizAttempts, 'quiz_id');
        $quizzes = $this->fetchQuizzesByIds($quizIds);
        $questionsByQuiz = $this->fetchQuestionsByQuizIds($quizIds);
        $attemptIds = $this->extractIds($quizAttempts, 'source_id');
        $attemptAnswers = $this->fetchAttemptAnswersByAttemptIds($attemptIds);

        $moduleIds = array_merge(
            $this->extractIds($certificates, 'module_id'),
            $this->extractIds($quizzes, 'module_id'),
            $this->extractIds($progress, 'module_id')
        );
        $lessonIds = array_merge(
            $this->extractIds($quizzes, 'lesson_id'),
            $this->extractIds($progress, 'lesson_id')
        );

        $modules = $this->fetchModulesByIds($moduleIds);
        $lessons = $this->fetchLessonsByIds($lessonIds);

        $files = [];
        $userDocument = [
            'owner' => $this->transformOwnerRecord($usuario, $files),
            'related_users' => [],
        ];

        $documents = [
            'data/user.json' => $userDocument,
            'data/courses.json' => array_map([$this, 'transformCourseRecord'], $cursos),
            'data/modules.json' => array_map([$this, 'transformModuleRecord'], $modules),
            'data/lessons.json' => array_map(function (array $lesson) use (&$files) {
                return $this->transformLessonRecord($lesson, $files);
            }, $lessons),
            'data/quizzes.json' => $this->transformQuizzesWithQuestions($quizzes, $questionsByQuiz),
            'data/quiz_attempts.json' => $this->transformQuizAttempts($quizAttempts, $attemptAnswers),
            'data/progress.json' => array_map([$this, 'transformProgressRecord'], $progress),
            'data/certificates.json' => $this->transformCertificates($certificates, $files, (int)$usuario['id']),
            'data/enrollments.json' => array_map([$this, 'transformEnrollmentRecord'], $enrollments),
        ];

        $manifest = [
            'type' => 'user',
            'user_id' => $userId,
            'scope' => 'all',
        ];

        if ($password !== null && trim($password) !== '') {
            $manifest['password'] = $password;
        }

        $package = $this->buildBackupArchive($manifest, $documents, $files, $this->buildBackupFilename('user', $usuario, null));
        $this->dispatchBackupEmail($usuario, $package);

        $this->logExport($userId, 'aluno', [
            'cursos' => count($cursos),
            'tentativas' => count($quizAttempts),
            'certificados' => count($certificates),
        ]);

        return $package;
    }

    public function exportarProfessor($userId, $courseId = null, $scope = 'all', ?string $password = null): array
    {
        $userId = (int)$userId;
        $this->assertCurrentUserOwnsExport($userId);

        $professor = $this->fetchUser($userId);
        if (!$professor) {
            throw new RuntimeException('Professor não encontrado para exportação.');
        }

        $scope = $this->normalizeProfessorScope((string)$scope);
        $courseId = $courseId !== null ? (int)$courseId : null;

        $courses = $this->fetchProfessorCourses($userId, $courseId);
        if ($courseId && empty($courses)) {
            throw new RuntimeException('Curso não encontrado ou sem permissão de exportação.');
        }

        $courseIds = $this->extractIds($courses, 'id');
        $modules = $scope === 'students' ? [] : $this->fetchModulesByCourseIds($courseIds);
        $lessons = $scope === 'students' ? [] : $this->fetchLessonsByCourseIds($courseIds);
        $quizzes = $scope === 'students' ? [] : $this->fetchProfessorQuizzes($courseIds);
        $quizIds = $this->extractIds($quizzes, 'id');
        $questionsByQuiz = $this->fetchQuestionsByQuizIds($quizIds);

        $enrollments = $this->fetchProfessorEnrollments($courseIds);
        $lessonProgress = $this->fetchProfessorLessonProgress($courseIds);
        $quizAttempts = $this->fetchProfessorQuizAttemptsDetailed($courseIds);
        $attemptIds = $this->extractIds($quizAttempts, 'source_id');
        $attemptAnswers = $this->fetchAttemptAnswersByAttemptIds($attemptIds);
        $certificates = $this->fetchProfessorCertificates($courseIds);

        $participants = $this->fetchUsersByIds($this->extractIds($enrollments, 'user_id'));

        $files = [];
        $userDocument = [
            'owner' => $this->transformOwnerRecord($professor, $files),
            'related_users' => array_map(function (array $participant) use (&$files) {
                return $this->transformOwnerRecord($participant, $files);
            }, $participants),
        ];

        $documents = [
            'data/user.json' => $userDocument,
            'data/courses.json' => array_map(function (array $course) use (&$files) {
                return $this->transformCourseRecord($course, $files);
            }, $courses),
            'data/modules.json' => array_map([$this, 'transformModuleRecord'], $modules),
            'data/lessons.json' => array_map(function (array $lesson) use (&$files) {
                return $this->transformLessonRecord($lesson, $files);
            }, $lessons),
            'data/quizzes.json' => $this->transformQuizzesWithQuestions($quizzes, $questionsByQuiz),
            'data/quiz_attempts.json' => $this->transformQuizAttempts($quizAttempts, $attemptAnswers),
            'data/progress.json' => array_map([$this, 'transformProgressRecord'], $lessonProgress),
            'data/certificates.json' => $this->transformCertificates($certificates, $files),
            'data/enrollments.json' => array_map([$this, 'transformEnrollmentRecord'], $enrollments),
        ];

        if ($scope === 'course') {
            $documents['data/quiz_attempts.json'] = [];
            $documents['data/progress.json'] = [];
            $documents['data/certificates.json'] = [];
            $documents['data/enrollments.json'] = [];
            $manifestType = 'course';
        } elseif ($scope === 'students') {
            $documents['data/modules.json'] = [];
            $documents['data/lessons.json'] = [];
            $documents['data/quizzes.json'] = [];
            $manifestType = 'course';
        } else {
            $manifestType = 'full';
        }

        $courseContext = count($courses) === 1 ? $courses[0] : null;
        $manifestPayload = [
            'type' => $manifestType,
            'user_id' => $userId,
            'scope' => $scope,
            'course_id' => $courseId ?: null,
        ];
        if ($password !== null && trim($password) !== '') {
            $manifestPayload['password'] = $password;
        }

        $package = $this->buildBackupArchive(
            $manifestPayload,
            $documents,
            $files,
            $this->buildBackupFilename($manifestType, $professor, $courseContext)
        );
        $this->dispatchBackupEmail($professor, $package);

        $this->logExport($userId, 'professor_' . $scope, [
            'cursos' => count($courses),
            'matriculas' => count($enrollments),
            'tentativas' => count($quizAttempts),
            'course_id' => $courseId ?: 'all',
        ]);

        return $package;
    }

    public function buildDownloadUrl(string $token): string
    {
        return BASE_URL . '/index.php?page=download-backup&token=' . rawurlencode($token);
    }

    public function buildPersistentDownloadUrl(string $token): string
    {
        return BASE_URL . '/index.php?page=download-backup-log&token=' . rawurlencode($token);
    }

    public function exportarGlobalSistema(?string $password = null): array
    {
        $users = $this->fetchAllUsers();
        $courses = $this->fetchAllCourses();
        $courseIds = $this->extractIds($courses, 'id');
        $modules = $this->fetchModulesByCourseIds($courseIds);
        $lessons = $this->fetchLessonsByCourseIds($courseIds);
        $quizzes = $this->fetchProfessorQuizzes($courseIds);
        $quizIds = $this->extractIds($quizzes, 'id');
        $questionsByQuiz = $this->fetchQuestionsByQuizIds($quizIds);
        $enrollments = $this->fetchAllEnrollments();
        $progress = $this->fetchAllLessonProgress();
        $attempts = $this->fetchAllQuizAttempts();
        $attemptAnswers = $this->fetchAttemptAnswersByAttemptIds($this->extractIds($attempts, 'source_id'));
        $certificates = $this->fetchAllCertificates();

        $files = [];
        $relatedUsers = [];
        foreach ($users as $user) {
            $relatedUsers[] = $this->transformOwnerRecord($user, $files);
        }

        $documents = [
            'data/user.json' => [
                'owner' => [
                    'source_id' => 0,
                    'nome' => 'Sistema',
                    'email' => '',
                    'role' => 'system',
                    'fotografia' => '',
                    'email_verified' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                'related_users' => $relatedUsers,
            ],
            'data/courses.json' => array_map(function (array $course) use (&$files) {
                return $this->transformCourseRecord($course, $files);
            }, $courses),
            'data/modules.json' => array_map([$this, 'transformModuleRecord'], $modules),
            'data/lessons.json' => array_map(function (array $lesson) use (&$files) {
                return $this->transformLessonRecord($lesson, $files);
            }, $lessons),
            'data/quizzes.json' => $this->transformQuizzesWithQuestions($quizzes, $questionsByQuiz),
            'data/quiz_attempts.json' => $this->transformQuizAttempts($attempts, $attemptAnswers),
            'data/progress.json' => array_map([$this, 'transformProgressRecord'], $progress),
            'data/certificates.json' => $this->transformCertificates($certificates, $files),
            'data/enrollments.json' => array_map([$this, 'transformEnrollmentRecord'], $enrollments),
        ];

        $manifest = [
            'type' => 'full',
            'user_id' => 0,
            'scope' => 'global',
            'password' => $password,
        ];

        return $this->buildBackupArchive($manifest, $documents, $files, 'backup-global-' . date('Y') . '.zip');
    }

    public function streamDownload(string $token, int $currentUserId): void
    {
        $currentUserId = (int)$currentUserId;
        $token = trim($token);
        if ($token === '') {
            throw new RuntimeException('Token de download inválido.');
        }

        $downloads = $_SESSION[self::DOWNLOAD_SESSION_KEY] ?? [];
        $record = $downloads[$token] ?? null;

        if (!is_array($record)) {
            throw new RuntimeException('Backup indisponível ou expirado.');
        }

        if ((int)($record['user_id'] ?? 0) !== $currentUserId) {
            throw new RuntimeException('Acesso negado ao arquivo solicitado.');
        }

        if ((int)($record['expires_at'] ?? 0) < time()) {
            unset($_SESSION[self::DOWNLOAD_SESSION_KEY][$token]);
            throw new RuntimeException('O link de download expirou. Gere um novo backup.');
        }

        $filePath = (string)($record['path'] ?? '');
        if ($filePath === '' || !is_file($filePath)) {
            unset($_SESSION[self::DOWNLOAD_SESSION_KEY][$token]);
            throw new RuntimeException('Arquivo de backup não encontrado.');
        }

        unset($_SESSION[self::DOWNLOAD_SESSION_KEY][$token]);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename((string)($record['filename'] ?? 'backup.zip')) . '"');
        header('Content-Length: ' . (string)filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($filePath);
        exit;
    }

    private function buildBackupArchive(array $manifest, array $documents, array $files, string $filename): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive não está disponível no servidor.');
        }

        $workDir = $this->backupDir . '/build_' . bin2hex(random_bytes(8));
        $rootDir = $workDir . '/package';
        if (!@mkdir($rootDir, 0775, true) && !is_dir($rootDir)) {
            throw new RuntimeException('Não foi possível preparar a área temporária do backup.');
        }

        try {
            $recordCount = 0;
            foreach ($documents as $relativePath => $payload) {
                $absolutePath = $rootDir . '/' . ltrim($relativePath, '/');
                $this->writeJsonFile($absolutePath, $payload);
                $recordCount += $this->countDocumentRecords($relativePath, $payload);
            }

            foreach ($files as $file) {
                $relativePath = ltrim((string)($file['zip_path'] ?? ''), '/');
                if ($relativePath === '') {
                    continue;
                }

                $absolutePath = $rootDir . '/' . $relativePath;
                $this->ensureParentDirectory($absolutePath);

                if (($file['mode'] ?? 'path') === 'string') {
                    if (@file_put_contents($absolutePath, (string)($file['contents'] ?? '')) === false) {
                        throw new RuntimeException('Não foi possível gravar um anexo do backup.');
                    }
                    continue;
                }

                $sourcePath = (string)($file['disk_path'] ?? '');
                if ($sourcePath === '' || !is_file($sourcePath)) {
                    continue;
                }

                if (!@copy($sourcePath, $absolutePath)) {
                    throw new RuntimeException('Não foi possível copiar um arquivo do backup.');
                }
            }

            $checksums = $this->generateChecksums($rootDir);
            $password = trim((string)($manifest['password'] ?? ''));
            $manifestPayload = [
                'version' => self::BACKUP_VERSION,
                'platform' => self::PLATFORM_NAME,
                'platform_version' => (string)env_value('APP_VERSION', '1.0.0'),
                'type' => (string)($manifest['type'] ?? 'user'),
                'user_id' => (int)($manifest['user_id'] ?? 0),
                'generated_at' => date(DATE_ATOM),
                'total_files' => count($checksums) + 2,
                'total_records' => $recordCount,
                'checksum' => hash('sha256', json_encode($checksums, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: ''),
                'scope' => (string)($manifest['scope'] ?? 'all'),
                'course_id' => !empty($manifest['course_id']) ? (int)$manifest['course_id'] : null,
                'encrypted' => $password !== '',
            ];

            $this->writeJsonFile($rootDir . '/manifest.json', $manifestPayload);
            $checksums['manifest.json'] = hash_file('sha256', $rootDir . '/manifest.json') ?: '';
            $this->writeJsonFile($rootDir . '/checksums.json', $checksums);

            $tempZipPath = $workDir . '/' . $filename;
            $this->createZipFromDirectory($rootDir, $tempZipPath, $password);

            $storageKey = date('Y/m') . '/' . $filename;
            $storageDescriptor = $this->storage->storeFile($tempZipPath, $storageKey);
            $userId = !empty($manifestPayload['user_id']) ? (int)$manifestPayload['user_id'] : null;
            $log = $this->backupLogs->createLog(
                $userId,
                (string)$manifestPayload['type'],
                (string)$storageDescriptor['key'],
                (int)$storageDescriptor['size'],
                'ready',
                [
                    'storage_disk' => $storageDescriptor['disk'],
                    'filename' => $filename,
                    'scope' => $manifestPayload['scope'],
                    'course_id' => $manifestPayload['course_id'],
                    'encrypted' => $manifestPayload['encrypted'],
                ]
            );

            return [
                'zip_path' => $storageDescriptor['path'],
                'storage_key' => $storageDescriptor['key'],
                'storage_disk' => $storageDescriptor['disk'],
                'filename' => $filename,
                'size' => (int)$storageDescriptor['size'],
                'log_id' => (int)$log['id'],
                'persistent_token' => (string)$log['access_token'],
                'persistent_download_url' => $this->buildPersistentDownloadUrl((string)$log['access_token']),
                'token' => $this->registerDownload($storageDescriptor['path'], $filename, (int)($userId ?? 0), (string)$manifestPayload['type'], (int)($manifestPayload['course_id'] ?? 0)),
                'encrypted' => $manifestPayload['encrypted'],
            ];
        } finally {
            $this->removeDirectory($workDir);
        }
    }

    private function generateChecksums(string $rootDir): array
    {
        $checksums = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootDir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($rootDir) + 1));
            $checksums[$relative] = hash_file('sha256', $file->getPathname()) ?: '';
        }

        ksort($checksums);
        return $checksums;
    }

    private function createZipFromDirectory(string $sourceDir, string $zipPath, string $password = ''): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Não foi possível criar o arquivo ZIP do backup.');
        }

        if ($password !== '' && method_exists($zip, 'setPassword')) {
            $zip->setPassword($password);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($sourceDir) + 1));
            $zip->addFile($file->getPathname(), $relative);
            if ($password !== '' && method_exists($zip, 'setEncryptionName') && defined('ZipArchive::EM_AES_256')) {
                $zip->setEncryptionName($relative, ZipArchive::EM_AES_256, $password);
            }
        }

        $zip->close();
    }

    private function writeJsonFile(string $path, $payload): void
    {
        $this->ensureParentDirectory($path);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || @file_put_contents($path, $json, LOCK_EX) === false) {
            throw new RuntimeException('Não foi possível gravar um arquivo do backup.');
        }
    }

    private function ensureParentDirectory(string $path): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Não foi possível preparar os diretórios do backup.');
        }
    }

    private function countDocumentRecords(string $relativePath, $payload): int
    {
        if ($relativePath === 'data/user.json') {
            return (int)!empty($payload['owner']) + count($payload['related_users'] ?? []);
        }

        return is_array($payload) ? count($payload) : 0;
    }

    private function transformOwnerRecord(array $user, array &$files): array
    {
        $record = [
            'source_id' => (int)($user['id'] ?? 0),
            'nome' => (string)($user['nome'] ?? ''),
            'email' => (string)($user['email'] ?? ''),
            'role' => (string)($user['role'] ?? ''),
            'fotografia' => (string)($user['fotografia'] ?? ''),
            'email_verified' => (int)($user['email_verified'] ?? 0),
            'created_at' => $user['created_at'] ?? null,
            'updated_at' => $user['updated_at'] ?? null,
        ];

        if (!empty($user['fotografia'])) {
            $filename = $this->sanitizeFilename((string)$user['fotografia']);
            $record['avatar_backup_path'] = 'files/avatars/' . $filename;
            $this->appendUploadFile($files, (string)$user['fotografia'], $record['avatar_backup_path']);
        }

        return $record;
    }

    private function transformCourseRecord(array $course, array &$files = []): array
    {
        $record = [
            'source_id' => (int)($course['id'] ?? 0),
            'titulo' => (string)($course['titulo'] ?? ''),
            'descricao' => (string)($course['descricao'] ?? ''),
            'categoria' => (string)($course['categoria'] ?? ''),
            'status' => (string)($course['status'] ?? 'ativo'),
            'course_structure' => (string)($course['course_structure'] ?? 'single_module'),
            'teacher_id' => (int)($course['teacher_id'] ?? 0),
            'teacher_name' => (string)($course['professor_nome'] ?? $course['teacher_name'] ?? ''),
            'teacher_email' => (string)($course['teacher_email'] ?? ''),
            'thumbnail' => (string)($course['thumbnail'] ?? ''),
            'created_at' => $course['created_at'] ?? null,
            'updated_at' => $course['updated_at'] ?? null,
        ];

        if (!empty($course['thumbnail'])) {
            $filename = $this->sanitizeFilename((string)$course['thumbnail']);
            $record['thumbnail_backup_path'] = 'files/thumbnails/' . ((int)($course['id'] ?? 0)) . '_' . $filename;
            $this->appendUploadFile($files, (string)$course['thumbnail'], $record['thumbnail_backup_path']);
        }

        return $record;
    }

    private function transformModuleRecord(array $module): array
    {
        return [
            'source_id' => (int)($module['id'] ?? 0),
            'course_id' => (int)($module['course_id'] ?? 0),
            'titulo' => (string)($module['titulo'] ?? ''),
            'descricao' => (string)($module['descricao'] ?? ''),
            'ordem' => (int)($module['ordem'] ?? 1),
            'is_default' => (int)($module['is_default'] ?? 0),
            'created_at' => $module['created_at'] ?? null,
            'updated_at' => $module['updated_at'] ?? null,
        ];
    }

    private function transformLessonRecord(array $lesson, array &$files): array
    {
        $record = [
            'source_id' => (int)($lesson['id'] ?? 0),
            'course_id' => (int)($lesson['course_id'] ?? 0),
            'module_id' => !empty($lesson['module_id']) ? (int)$lesson['module_id'] : null,
            'titulo' => (string)($lesson['titulo'] ?? ''),
            'descricao' => (string)($lesson['descricao'] ?? ''),
            'tipo' => (string)($lesson['tipo'] ?? 'texto'),
            'conteudo' => (string)($lesson['conteudo'] ?? ''),
            'resumo' => (string)($lesson['resumo'] ?? ''),
            'url_arquivo' => (string)($lesson['url_arquivo'] ?? ''),
            'audio_url' => (string)($lesson['audio_url'] ?? ''),
            'audio_storage_disk' => (string)($lesson['audio_storage_disk'] ?? ''),
            'audio_storage_key' => (string)($lesson['audio_storage_key'] ?? ''),
            'video_id' => (string)($lesson['video_id'] ?? ''),
            'ordem' => (int)($lesson['ordem'] ?? 1),
            'created_at' => $lesson['created_at'] ?? null,
            'updated_at' => $lesson['updated_at'] ?? null,
        ];

        if (!empty($lesson['url_arquivo'])) {
            $filename = $this->sanitizeFilename((string)$lesson['url_arquivo']);
            $record['upload_backup_path'] = 'files/course_files/' . ((int)($lesson['course_id'] ?? 0)) . '/' . ((int)($lesson['id'] ?? 0)) . '_' . $filename;
            $this->appendUploadFile($files, (string)$lesson['url_arquivo'], $record['upload_backup_path']);
        }

        if (!empty($lesson['audio_url'])) {
            $filename = $this->sanitizeFilename((string)$lesson['audio_url']);
            $record['audio_backup_path'] = 'files/course_audio/' . ((int)($lesson['course_id'] ?? 0)) . '/' . ((int)($lesson['id'] ?? 0)) . '_' . $filename;
            $this->appendUploadFile($files, (string)$lesson['audio_url'], $record['audio_backup_path']);
        }

        if (!empty($lesson['audio_storage_key'])) {
            try {
                $storage = new StorageService((string)($lesson['audio_storage_disk'] ?? 'local'));
                $descriptor = $storage->getDescriptor((string)$lesson['audio_storage_key']);
                $filename = basename((string)($lesson['audio_url'] ?? 'audio.mp3'));
                $record['audio_backup_path'] = 'files/course_audio/' . ((int)($lesson['course_id'] ?? 0)) . '/' . ((int)($lesson['id'] ?? 0)) . '_' . $this->sanitizeFilename($filename);
                $files[] = [
                    'disk_path' => (string)$descriptor['path'],
                    'zip_path' => $record['audio_backup_path'],
                ];
            } catch (Throwable $exception) {
                // Mantém o backup funcional mesmo se o storage remoto estiver indisponível.
            }
        }

        return $record;
    }

    private function transformQuizzesWithQuestions(array $quizzes, array $questionsByQuiz): array
    {
        return array_map(function (array $quiz) use ($questionsByQuiz) {
            return [
                'source_id' => (int)($quiz['id'] ?? 0),
                'lesson_id' => !empty($quiz['lesson_id']) ? (int)$quiz['lesson_id'] : null,
                'course_id' => !empty($quiz['resolved_course_id']) ? (int)$quiz['resolved_course_id'] : (!empty($quiz['course_id']) ? (int)$quiz['course_id'] : null),
                'module_id' => !empty($quiz['module_id']) ? (int)$quiz['module_id'] : null,
                'titulo' => (string)($quiz['titulo'] ?? ''),
                'descricao' => (string)($quiz['descricao'] ?? ''),
                'tipo' => (string)($quiz['tipo'] ?? 'aula'),
                'dificuldade' => (string)($quiz['dificuldade'] ?? 'normal'),
                'peso' => (float)($quiz['peso'] ?? 0),
                'nota_minima' => (float)($quiz['nota_minima'] ?? 0),
                'obrigatorio' => (int)($quiz['obrigatorio'] ?? 0),
                'tentativas_maximas' => isset($quiz['tentativas_maximas']) ? (int)$quiz['tentativas_maximas'] : null,
                'tempo_limite' => isset($quiz['tempo_limite']) ? (int)$quiz['tempo_limite'] : null,
                'embaralhar_perguntas' => (int)($quiz['embaralhar_perguntas'] ?? 0),
                'embaralhar_respostas' => (int)($quiz['embaralhar_respostas'] ?? 0),
                'mostrar_respostas' => (int)($quiz['mostrar_respostas'] ?? 1),
                'mostrar_nota' => (int)($quiz['mostrar_nota'] ?? 1),
                'pontos_totais' => isset($quiz['pontos_totais']) ? (int)$quiz['pontos_totais'] : null,
                'created_at' => $quiz['created_at'] ?? null,
                'updated_at' => $quiz['updated_at'] ?? null,
                'questions' => array_values($questionsByQuiz[(int)($quiz['id'] ?? 0)] ?? []),
            ];
        }, $quizzes);
    }

    private function transformQuizAttempts(array $attempts, array $answersByAttempt): array
    {
        return array_map(function (array $attempt) use ($answersByAttempt) {
            $sourceId = (int)($attempt['source_id'] ?? 0);
            return [
                'source_id' => $sourceId,
                'user_id' => (int)($attempt['user_id'] ?? 0),
                'quiz_id' => (int)($attempt['quiz_id'] ?? 0),
                'course_id' => !empty($attempt['course_id']) ? (int)$attempt['course_id'] : null,
                'lesson_id' => !empty($attempt['lesson_id']) ? (int)$attempt['lesson_id'] : null,
                'module_id' => !empty($attempt['module_id']) ? (int)$attempt['module_id'] : null,
                'pontuacao' => (float)($attempt['pontuacao'] ?? 0),
                'percentual' => (float)($attempt['percentual'] ?? 0),
                'total_correto' => (int)($attempt['total_correto'] ?? 0),
                'total_questoes' => (int)($attempt['total_questoes'] ?? 0),
                'tentativa_numero' => (int)($attempt['tentativa_numero'] ?? 1),
                'tempo_gasto' => (int)($attempt['tempo_gasto'] ?? 0),
                'aprovado' => (int)($attempt['aprovado'] ?? 0),
                'data_realizacao' => $attempt['data_realizacao'] ?? null,
                'answers' => array_values($answersByAttempt[$sourceId] ?? []),
            ];
        }, $attempts);
    }

    private function transformProgressRecord(array $progress): array
    {
        return [
            'source_id' => (int)($progress['source_id'] ?? 0),
            'user_id' => (int)($progress['user_id'] ?? 0),
            'course_id' => (int)($progress['course_id'] ?? 0),
            'module_id' => !empty($progress['module_id']) ? (int)$progress['module_id'] : null,
            'lesson_id' => (int)($progress['lesson_id'] ?? 0),
            'concluida' => (int)($progress['concluida'] ?? 0),
            'data_conclusao' => $progress['data_conclusao'] ?? null,
            'tempo_assistido' => isset($progress['tempo_assistido']) ? (int)$progress['tempo_assistido'] : null,
            'created_at' => $progress['created_at'] ?? null,
        ];
    }

    private function transformCertificates(array $certificates, array &$files, ?int $ownerUserId = null): array
    {
        $result = [];
        foreach ($certificates as $certificate) {
            $record = [
                'source_id' => (int)($certificate['id'] ?? 0),
                'user_id' => (int)($certificate['user_id'] ?? 0),
                'course_id' => (int)($certificate['course_id'] ?? 0),
                'module_id' => !empty($certificate['module_id']) ? (int)$certificate['module_id'] : null,
                'type' => (string)($certificate['type'] ?? 'course'),
                'certificate_code' => (string)($certificate['certificate_code'] ?? ''),
                'codigo_certificado' => (string)($certificate['codigo_certificado'] ?? ''),
                'grade' => isset($certificate['grade']) ? (float)$certificate['grade'] : null,
                'nota_final' => isset($certificate['nota_final']) ? (float)$certificate['nota_final'] : null,
                'issued_at' => $certificate['issued_at'] ?? null,
                'data_emissao' => $certificate['data_emissao'] ?? null,
            ];

            if ($ownerUserId !== null && $ownerUserId === (int)($certificate['user_id'] ?? 0)) {
                try {
                    $certificateController = new CertificateController($this->pdo);
                    $payload = $certificateController->downloadOwnedCertificatePdf(
                        $ownerUserId,
                        (int)($certificate['course_id'] ?? 0),
                        (string)($certificate['type'] ?? 'course'),
                        !empty($certificate['module_id']) ? (int)$certificate['module_id'] : null
                    );

                    if (!empty($payload['content'])) {
                        $filename = basename((string)($payload['filename'] ?? ('certificate_' . ($record['certificate_code'] ?: uniqid('', true)) . '.pdf')));
                        $record['attachment_backup_path'] = 'files/attachments/' . $filename;
                        $files[] = [
                            'mode' => 'string',
                            'zip_path' => $record['attachment_backup_path'],
                            'contents' => (string)$payload['content'],
                        ];
                    }
                } catch (Throwable $exception) {
                    if (function_exists('registrar_log')) {
                        registrar_log('EXPORT', 'Falha ao anexar PDF do certificado: ' . $exception->getMessage(), $ownerUserId);
                    }
                }
            }

            $result[] = $record;
        }

        return $result;
    }

    private function transformEnrollmentRecord(array $enrollment): array
    {
        return [
            'source_id' => (int)($enrollment['id'] ?? 0),
            'user_id' => (int)($enrollment['user_id'] ?? 0),
            'course_id' => (int)($enrollment['course_id'] ?? 0),
            'progress' => (int)($enrollment['progress'] ?? 0),
            'data_inscricao' => $enrollment['data_inscricao'] ?? null,
            'data_conclusao' => $enrollment['data_conclusao'] ?? null,
            'student_email' => (string)($enrollment['student_email'] ?? ''),
            'student_name' => (string)($enrollment['student_name'] ?? ''),
        ];
    }

    private function assertCurrentUserOwnsExport(int $userId): void
    {
        $sessionUser = AuthController::obterUsuarioAtual();
        $sessionUserId = (int)($sessionUser['id'] ?? 0);
        if ($userId <= 0 || $sessionUserId !== $userId) {
            throw new RuntimeException('Acesso negado');
        }
    }

    private function ensureBackupDirectory(): void
    {
        if (!$this->prepareBackupDirectory($this->backupDir)) {
            $this->backupDir = $this->resolveBackupDirectory();
            if (!$this->prepareBackupDirectory($this->backupDir)) {
                throw new RuntimeException('O diretório de backups não possui permissão de escrita.');
            }
        }

        $this->cleanupExpiredDownloads();
        $this->cleanupOldBackupFiles();
    }

    private function prepareBackupDirectory(string $directory): bool
    {
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            return false;
        }

        if (!is_writable($directory)) {
            @chmod($directory, 0777);
        }

        return is_writable($directory);
    }

    private function resolveBackupDirectory(): string
    {
        $tmpBase = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $candidates = [
            $tmpBase . DIRECTORY_SEPARATOR . 'plataforma-ead-tmp-backups-runtime',
            dirname(__DIR__, 2) . '/storage/tmp-backups',
            $tmpBase . DIRECTORY_SEPARATOR . 'plataforma-ead-tmp-backups',
        ];

        foreach ($candidates as $candidate) {
            if ($this->prepareBackupDirectory($candidate)) {
                return $candidate;
            }
        }

        return $candidates[0];
    }

    private function cleanupExpiredDownloads(): void
    {
        $downloads = $_SESSION[self::DOWNLOAD_SESSION_KEY] ?? [];
        if (!is_array($downloads)) {
            $_SESSION[self::DOWNLOAD_SESSION_KEY] = [];
            return;
        }

        $now = time();
        foreach ($downloads as $token => $download) {
            if (!is_array($download) || (int)($download['expires_at'] ?? 0) < $now) {
                unset($downloads[$token]);
            }
        }

        $_SESSION[self::DOWNLOAD_SESSION_KEY] = $downloads;
    }

    private function cleanupOldBackupFiles(): void
    {
        $days = function_exists('env_int') ? env_int('BACKUP_RETENTION_DAYS', 30) : 30;
        $ttl = max(1, $days) * 86400;
        if (!is_dir($this->backupDir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->backupDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $path) {
            if (!$path->isFile()) {
                continue;
            }

            $modifiedAt = @filemtime($path->getPathname());
            if ($modifiedAt !== false && $modifiedAt < (time() - $ttl)) {
                @unlink($path->getPathname());
            }
        }
    }

    private function registerDownload(string $filePath, string $filename, int $userId, string $tipo, int $courseId = 0): string
    {
        $token = bin2hex(random_bytes(24));
        $_SESSION[self::DOWNLOAD_SESSION_KEY][$token] = [
            'path' => $filePath,
            'filename' => $filename,
            'user_id' => $userId,
            'tipo' => $tipo,
            'course_id' => $courseId,
            'created_at' => time(),
            'expires_at' => time() + self::DOWNLOAD_TTL,
        ];

        return $token;
    }

    private function fetchUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email, role, fotografia, email_verified, created_at, updated_at
             FROM users
             WHERE id = ?
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    private function fetchUsersByIds(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (empty($userIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($userIds);
        $stmt = $this->pdo->prepare(
            "SELECT id, nome, email, role, fotografia, email_verified, created_at, updated_at
             FROM users
             WHERE id IN ($placeholders)
             ORDER BY nome ASC, id ASC"
        );
        $stmt->execute($userIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchAllUsers(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, nome, email, role, fotografia, email_verified, created_at, updated_at
             FROM users
             ORDER BY id ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchAllCourses(): array
    {
        $stmt = $this->pdo->query(
            'SELECT c.id, c.titulo, c.descricao, c.categoria, c.status, c.course_structure, c.thumbnail, c.teacher_id,
                    c.created_at, c.updated_at,
                    u.nome AS professor_nome, u.email AS teacher_email
             FROM courses c
             LEFT JOIN users u ON u.id = c.teacher_id
             ORDER BY c.id ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchAllEnrollments(): array
    {
        $stmt = $this->pdo->query(
            'SELECT e.id, e.user_id, e.course_id, e.progress, e.data_inscricao, e.data_conclusao,
                    u.nome AS student_name, u.email AS student_email
             FROM enrollments e
             INNER JOIN users u ON u.id = e.user_id
             ORDER BY e.id ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchAllLessonProgress(): array
    {
        $stmt = $this->pdo->query(
            'SELECT lp.id AS source_id, lp.user_id, l.course_id, l.module_id, lp.lesson_id, lp.concluida,
                    lp.data_conclusao, lp.tempo_assistido, lp.created_at
             FROM lesson_progress lp
             INNER JOIN lessons l ON l.id = lp.lesson_id
             ORDER BY lp.id ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchAllQuizAttempts(): array
    {
        $stmt = $this->pdo->query(
            'SELECT qa.id AS source_id, qa.user_id, qa.quiz_id, qa.pontuacao, qa.percentual, qa.total_correto,
                    qa.total_questoes, qa.tentativa_numero, qa.tempo_gasto, qa.aprovado, qa.data_realizacao,
                    COALESCE(q.course_id, l.course_id) AS course_id, q.lesson_id, q.module_id
             FROM quiz_attempts qa
             INNER JOIN quizzes q ON q.id = qa.quiz_id
             LEFT JOIN lessons l ON l.id = q.lesson_id
             ORDER BY qa.id ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchAllCertificates(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, user_id, course_id, module_id, type, certificate_code, codigo_certificado,
                    grade, nota_final, issued_at, data_emissao
             FROM certificates
             ORDER BY id ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchStudentCourses(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.titulo, c.descricao, c.categoria, c.status, c.course_structure, c.thumbnail, c.teacher_id,
                    c.created_at, c.updated_at,
                    e.progress AS progresso_registrado, e.data_inscricao, e.data_conclusao,
                    u.nome AS professor_nome, u.email AS teacher_email
             FROM enrollments e
             INNER JOIN courses c ON c.id = e.course_id
             LEFT JOIN users u ON u.id = c.teacher_id
             WHERE e.user_id = ?
             ORDER BY e.data_inscricao DESC, c.id DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchStudentEnrollments(int $userId, array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($courseIds);
        $params = array_merge([$userId], $courseIds);
        $stmt = $this->pdo->prepare(
            "SELECT e.id, e.user_id, e.course_id, e.progress, e.data_inscricao, e.data_conclusao,
                    u.nome AS student_name, u.email AS student_email
             FROM enrollments e
             INNER JOIN users u ON u.id = e.user_id
             WHERE e.user_id = ?
               AND e.course_id IN ($placeholders)
             ORDER BY e.data_inscricao DESC, e.id DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchStudentLessonProgress(int $userId, array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($courseIds);
        $params = array_merge([$userId], $courseIds);
        $stmt = $this->pdo->prepare(
            "SELECT lp.id AS source_id, lp.user_id, l.course_id, l.module_id, lp.lesson_id, lp.concluida,
                    lp.data_conclusao, lp.tempo_assistido, lp.created_at
             FROM lesson_progress lp
             INNER JOIN lessons l ON l.id = lp.lesson_id
             WHERE lp.user_id = ?
               AND l.course_id IN ($placeholders)
             ORDER BY l.course_id ASC, l.module_id ASC, lp.lesson_id ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchStudentQuizAttemptsDetailed(int $userId, array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($courseIds);
        $params = array_merge([$userId], $courseIds);
        $stmt = $this->pdo->prepare(
            "SELECT qa.id AS source_id, qa.user_id, qa.quiz_id, qa.pontuacao, qa.percentual, qa.total_correto,
                    qa.total_questoes, qa.tentativa_numero, qa.tempo_gasto, qa.aprovado, qa.data_realizacao,
                    COALESCE(q.course_id, l.course_id) AS course_id, q.lesson_id, q.module_id
             FROM quiz_attempts qa
             INNER JOIN quizzes q ON q.id = qa.quiz_id
             LEFT JOIN lessons l ON l.id = q.lesson_id
             WHERE qa.user_id = ?
               AND COALESCE(q.course_id, l.course_id) IN ($placeholders)
             ORDER BY qa.data_realizacao DESC, qa.id DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchStudentCertificates(int $userId, array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($courseIds);
        $params = array_merge([$userId], $courseIds);
        $stmt = $this->pdo->prepare(
            "SELECT cert.id, cert.user_id, cert.course_id, cert.module_id, cert.type, cert.certificate_code,
                    cert.codigo_certificado, cert.grade, cert.nota_final, cert.issued_at, cert.data_emissao
             FROM certificates cert
             WHERE cert.user_id = ?
               AND cert.course_id IN ($placeholders)
             ORDER BY cert.issued_at DESC, cert.id DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchProfessorCourses(int $userId, ?int $courseId = null): array
    {
        $sql = 'SELECT c.id, c.titulo, c.descricao, c.categoria, c.status, c.course_structure, c.thumbnail,
                       c.teacher_id, c.created_at, c.updated_at,
                       u.nome AS professor_nome, u.email AS teacher_email
                FROM courses c
                LEFT JOIN users u ON u.id = c.teacher_id
                WHERE c.teacher_id = ?';
        $params = [$userId];

        if ($courseId !== null && $courseId > 0) {
            $sql .= ' AND c.id = ?';
            $params[] = $courseId;
        }

        $sql .= ' ORDER BY c.created_at DESC, c.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchModulesByCourseIds(array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($courseIds);
        $stmt = $this->pdo->prepare(
            "SELECT cm.*
             FROM course_modules cm
             WHERE cm.course_id IN ($placeholders)
             ORDER BY cm.course_id ASC, cm.ordem ASC, cm.id ASC"
        );
        $stmt->execute($courseIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchModulesByIds(array $moduleIds): array
    {
        $moduleIds = array_values(array_unique(array_filter(array_map('intval', $moduleIds))));
        if (empty($moduleIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($moduleIds);
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM course_modules
             WHERE id IN ($placeholders)
             ORDER BY course_id ASC, ordem ASC, id ASC"
        );
        $stmt->execute($moduleIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchLessonsByCourseIds(array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($courseIds);
        $stmt = $this->pdo->prepare(
            "SELECT l.*
             FROM lessons l
             WHERE l.course_id IN ($placeholders)
             ORDER BY l.course_id ASC, l.module_id ASC, l.ordem ASC, l.id ASC"
        );
        $stmt->execute($courseIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchLessonsByIds(array $lessonIds): array
    {
        $lessonIds = array_values(array_unique(array_filter(array_map('intval', $lessonIds))));
        if (empty($lessonIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($lessonIds);
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM lessons
             WHERE id IN ($placeholders)
             ORDER BY course_id ASC, module_id ASC, ordem ASC, id ASC"
        );
        $stmt->execute($lessonIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchProfessorQuizzes(array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($courseIds);
        $stmt = $this->pdo->prepare(
            "SELECT q.*, COALESCE(q.course_id, l.course_id) AS resolved_course_id
             FROM quizzes q
             LEFT JOIN lessons l ON l.id = q.lesson_id
             WHERE COALESCE(q.course_id, l.course_id) IN ($placeholders)
             ORDER BY resolved_course_id ASC, FIELD(q.tipo, 'final', 'modulo', 'aula'), q.id ASC"
        );
        $stmt->execute($courseIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchQuizzesByIds(array $quizIds): array
    {
        $quizIds = array_values(array_unique(array_filter(array_map('intval', $quizIds))));
        if (empty($quizIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($quizIds);
        $stmt = $this->pdo->prepare(
            "SELECT q.*, COALESCE(q.course_id, l.course_id) AS resolved_course_id
             FROM quizzes q
             LEFT JOIN lessons l ON l.id = q.lesson_id
             WHERE q.id IN ($placeholders)
             ORDER BY q.id ASC"
        );
        $stmt->execute($quizIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchQuestionsByQuizIds(array $quizIds): array
    {
        $quizIds = array_values(array_unique(array_filter(array_map('intval', $quizIds))));
        if (empty($quizIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($quizIds);
        $stmt = $this->pdo->prepare(
            "SELECT id AS source_id, quiz_id, texto, tipo, opcoes, resposta_correta, explicacao, pontos, ordem, created_at
             FROM questions
             WHERE quiz_id IN ($placeholders)
             ORDER BY quiz_id ASC, ordem ASC, id ASC"
        );
        $stmt->execute($quizIds);

        $grouped = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $question) {
            $question['quiz_id'] = (int)$question['quiz_id'];
            $question['source_id'] = (int)$question['source_id'];
            $question['pontos'] = (float)($question['pontos'] ?? 1);
            $grouped[(int)$question['quiz_id']][] = $question;
        }

        return $grouped;
    }

    private function fetchAttemptAnswersByAttemptIds(array $attemptIds): array
    {
        $attemptIds = array_values(array_unique(array_filter(array_map('intval', $attemptIds))));
        if (empty($attemptIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($attemptIds);
        $stmt = $this->pdo->prepare(
            "SELECT id AS source_id, attempt_id, question_id, resposta_usuario, correta, created_at
             FROM quiz_attempt_answers
             WHERE attempt_id IN ($placeholders)
             ORDER BY attempt_id ASC, id ASC"
        );
        $stmt->execute($attemptIds);

        $grouped = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $answer) {
            $answer['source_id'] = (int)$answer['source_id'];
            $answer['attempt_id'] = (int)$answer['attempt_id'];
            $answer['question_id'] = (int)$answer['question_id'];
            $answer['correta'] = (int)($answer['correta'] ?? 0);
            $grouped[(int)$answer['attempt_id']][] = $answer;
        }

        return $grouped;
    }

    private function fetchProfessorEnrollments(array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($courseIds);
        $stmt = $this->pdo->prepare(
            "SELECT e.id, e.user_id, e.course_id, e.progress, e.data_inscricao, e.data_conclusao,
                    u.nome AS student_name, u.email AS student_email
             FROM enrollments e
             INNER JOIN users u ON u.id = e.user_id
             WHERE e.course_id IN ($placeholders)
             ORDER BY e.course_id ASC, e.user_id ASC"
        );
        $stmt->execute($courseIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchProfessorLessonProgress(array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($courseIds);
        $stmt = $this->pdo->prepare(
            "SELECT lp.id AS source_id, lp.user_id, l.course_id, l.module_id, lp.lesson_id, lp.concluida,
                    lp.data_conclusao, lp.tempo_assistido, lp.created_at
             FROM lesson_progress lp
             INNER JOIN lessons l ON l.id = lp.lesson_id
             WHERE l.course_id IN ($placeholders)
             ORDER BY l.course_id ASC, lp.user_id ASC, l.module_id ASC, lp.lesson_id ASC"
        );
        $stmt->execute($courseIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchProfessorQuizAttemptsDetailed(array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($courseIds);
        $stmt = $this->pdo->prepare(
            "SELECT qa.id AS source_id, qa.user_id, qa.quiz_id, qa.pontuacao, qa.percentual, qa.total_correto,
                    qa.total_questoes, qa.tentativa_numero, qa.tempo_gasto, qa.aprovado, qa.data_realizacao,
                    COALESCE(q.course_id, l.course_id) AS course_id, q.lesson_id, q.module_id
             FROM quiz_attempts qa
             INNER JOIN quizzes q ON q.id = qa.quiz_id
             LEFT JOIN lessons l ON l.id = q.lesson_id
             WHERE COALESCE(q.course_id, l.course_id) IN ($placeholders)
             ORDER BY course_id ASC, qa.user_id ASC, qa.id ASC"
        );
        $stmt->execute($courseIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchProfessorCertificates(array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $placeholders = $this->buildInPlaceholders($courseIds);
        $stmt = $this->pdo->prepare(
            "SELECT id, user_id, course_id, module_id, type, certificate_code, codigo_certificado,
                    grade, nota_final, issued_at, data_emissao
             FROM certificates
             WHERE course_id IN ($placeholders)
             ORDER BY course_id ASC, user_id ASC, id ASC"
        );
        $stmt->execute($courseIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function appendUploadFile(array &$files, string $relativeName, string $zipPath): void
    {
        $path = $this->resolveUploadPath($relativeName);
        if ($path === null) {
            return;
        }

        $files[] = [
            'disk_path' => $path,
            'zip_path' => $zipPath,
        ];
    }

    private function resolveUploadPath(string $relativeName): ?string
    {
        $filename = basename(trim($relativeName));
        if ($filename === '') {
            return null;
        }

        $uploadsDir = defined('UPLOADS_DIR') ? UPLOADS_DIR : (dirname(__DIR__, 2) . '/public/uploads');
        $path = rtrim($uploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        return is_file($path) ? $path : null;
    }

    private function extractIds(array $rows, string $key): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $value = isset($row[$key]) ? (int)$row[$key] : 0;
            if ($value > 0) {
                $ids[] = $value;
            }
        }

        return array_values(array_unique($ids));
    }

    private function buildInPlaceholders(array $items): string
    {
        return implode(', ', array_fill(0, count($items), '?'));
    }

    private function normalizeProfessorScope(string $scope): string
    {
        $scope = trim($scope);
        if (in_array($scope, ['course', 'students'], true)) {
            return $scope;
        }

        return 'all';
    }

    private function buildBackupFilename(string $type, array $owner, ?array $course = null): string
    {
        $parts = ['backup'];
        $parts[] = $this->slugify((string)($owner['nome'] ?? 'usuario'));

        if ($course) {
            $parts[] = 'curso';
            $parts[] = $this->slugify((string)($course['titulo'] ?? 'curso'));
        } elseif ($type === 'full') {
            $parts[] = 'completo';
        } else {
            $parts[] = $type;
        }

        $parts[] = date('Y');
        return implode('-', array_filter($parts)) . '.zip';
    }

    private function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($normalized !== false) {
            $value = $normalized;
        }

        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: 'backup';
        $value = trim($value, '-');
        return $value !== '' ? $value : 'backup';
    }

    private function sanitizeFilename(string $name): string
    {
        $base = basename($name);
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base) ?: 'arquivo';
        return trim($base, '-');
    }

    private function removeDirectory(string $path): void
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

        @rmdir($path);
    }

    private function dispatchBackupEmail(array $user, array $package): void
    {
        $userId = (int)($user['id'] ?? 0);
        if ($userId > 0) {
            $preference = $this->backupLogs->getPreference($userId);
            if (empty($preference['receive_email'])) {
                return;
            }
        }

        $email = trim((string)($user['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $subject = 'Seu backup da plataforma EAD está pronto';
        $downloadUrl = APP_URL . '/index.php?page=download-backup-log&token=' . rawurlencode((string)($package['persistent_token'] ?? ''));
        $message = '<p>Seu backup da plataforma EAD foi gerado com sucesso.</p>'
            . '<p><strong>Arquivo:</strong> ' . htmlspecialchars((string)($package['filename'] ?? 'backup.zip'), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>Tamanho:</strong> ' . htmlspecialchars($this->formatBytes((int)($package['size'] ?? 0)), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>Baixe com segurança pelo link:</p>'
            . '<p><a href="' . htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') . '</a></p>';

        $attachments = [];
        if ((int)($package['size'] ?? 0) > 0 && (int)($package['size'] ?? 0) <= 10485760) {
            $attachments[] = [
                'path' => (string)($package['zip_path'] ?? ''),
                'name' => (string)($package['filename'] ?? 'backup.zip'),
                'mime' => 'application/zip',
            ];
        }

        @enviar_email($email, $subject, $message, 'text/html', $attachments);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }
        if ($bytes < 1073741824) {
            return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
        }

        return number_format($bytes / 1073741824, 1, ',', '.') . ' GB';
    }

    private function logExport(int $userId, string $tipo, array $context = []): void
    {
        if (!function_exists('registrar_log')) {
            return;
        }

        $details = 'user_id=' . $userId . ' tipo=' . $tipo . ' data=' . date(DATE_ATOM);
        foreach ($context as $key => $value) {
            $details .= ' ' . $key . '=' . $value;
        }

        registrar_log('EXPORT', $details, $userId);
    }
}
