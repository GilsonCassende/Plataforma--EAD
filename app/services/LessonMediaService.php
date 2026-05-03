<?php

class LessonMediaService
{
    private string $uploadsDir;
    private string $tempAudioDir;
    private ?string $ffmpegBinary = null;
    private bool $ffmpegChecked = false;
    private StorageService $storage;

    public function __construct(?string $uploadsDir = null)
    {
        $this->uploadsDir = $uploadsDir ?: dirname(__DIR__, 2) . '/public/uploads';
        $this->tempAudioDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plataforma-ead-lesson-audio';
        $this->storage = new StorageService();
    }

    public function generateAudioFromVideo(string $videoFilename): ?string
    {
        $videoFilename = basename(trim($videoFilename));
        if ($videoFilename === '' || strtolower(pathinfo($videoFilename, PATHINFO_EXTENSION)) !== 'mp4') {
            return null;
        }

        $ffmpeg = $this->resolveFfmpegBinary();
        if ($ffmpeg === null) {
            $this->logNotice('FFmpeg indisponível; áudio automático não foi gerado.');
            return null;
        }

        $inputPath = $this->uploadsDir . '/' . $videoFilename;
        if (!is_file($inputPath)) {
            $this->logNotice('Vídeo não encontrado para extração de áudio: ' . $videoFilename);
            return null;
        }

        $outputName = pathinfo($videoFilename, PATHINFO_FILENAME) . '_audio.mp3';
        $outputPath = $this->uploadsDir . '/' . $outputName;
        $suffix = 1;
        while (is_file($outputPath)) {
            $outputName = pathinfo($videoFilename, PATHINFO_FILENAME) . '_audio_' . $suffix . '.mp3';
            $outputPath = $this->uploadsDir . '/' . $outputName;
            $suffix++;
        }

        $command = escapeshellarg($ffmpeg)
            . ' -y -i ' . escapeshellarg($inputPath)
            . ' -vn -ac 1 -b:a 64k -map a ' . escapeshellarg($outputPath)
            . ' 2>&1';

        $output = [];
        $exitCode = 1;
        @exec($command, $output, $exitCode);

        if ($exitCode !== 0 || !is_file($outputPath) || filesize($outputPath) === 0) {
            if (is_file($outputPath)) {
                @unlink($outputPath);
            }
            $this->logNotice('Falha ao gerar áudio automático para ' . $videoFilename . '.');
            return null;
        }

        return $outputName;
    }

    public function offloadAudioToStorage(string $audioFilename, int $courseId): ?array
    {
        $audioFilename = basename(trim($audioFilename));
        if ($audioFilename === '') {
            return null;
        }

        $localPath = $this->uploadsDir . '/' . $audioFilename;
        if (!is_file($localPath)) {
            return null;
        }

        return $this->offloadAudioFileToStorage($localPath, $audioFilename, $courseId);
    }

    public function storeUploadedAudio(array $file, int $courseId): ?array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $this->ensureTempAudioDirectory();
        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION)) ?: 'mp3';
        $tempName = 'lesson-audio-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $tempPath = $this->tempAudioDir . DIRECTORY_SEPARATOR . $tempName;

        if (!@move_uploaded_file((string)($file['tmp_name'] ?? ''), $tempPath)) {
            throw new RuntimeException('Não foi possível receber o áudio temporariamente.');
        }

        return $this->offloadAudioFileToStorage($tempPath, (string)($file['name'] ?? $tempName), $courseId);
    }

    private function offloadAudioFileToStorage(string $localPath, string $audioName, int $courseId): ?array
    {
        if (!is_file($localPath)) {
            return null;
        }

        $extension = strtolower(pathinfo($audioName, PATHINFO_EXTENSION)) ?: 'mp3';
        $key = sprintf(
            'lesson-audio/course-%d/%s/%s.%s',
            max(0, $courseId),
            date('Y/m'),
            bin2hex(random_bytes(16)),
            preg_replace('/[^a-z0-9]+/i', '', $extension)
        );

        try {
            $descriptor = $this->storage->storeFile($localPath, $key);
            @unlink($localPath);

            return [
                'audio_url' => basename((string)$audioName),
                'disk' => (string)($descriptor['disk'] ?? $this->storage->getDisk()),
                'key' => (string)($descriptor['key'] ?? $key),
            ];
        } catch (Throwable $exception) {
            $this->logNotice('Falha ao mover áudio para storage: ' . $exception->getMessage());
            return null;
        }
    }

    public function cleanupLessonMedia(array $lesson, array $options = []): void
    {
        $removeVideo = !empty($options['remove_video']);
        $removeAudio = !empty($options['remove_audio']);

        if ($removeVideo) {
            $this->deleteLocalUpload((string)($lesson['url_arquivo'] ?? ''));
        }

        if ($removeAudio) {
            $storageKey = trim((string)($lesson['audio_storage_key'] ?? ''));
            if ($storageKey !== '') {
                try {
                    $storage = new StorageService((string)($lesson['audio_storage_disk'] ?? 'local'));
                    $storage->delete($storageKey);
                } catch (Throwable $exception) {
                    $this->logNotice('Falha ao remover áudio do storage: ' . $exception->getMessage());
                }
            }

            $this->deleteLocalUpload((string)($lesson['audio_url'] ?? ''));
        }
    }

    public function pruneOrphanedLessonAudio(): array
    {
        $result = [
            'removed_local' => 0,
            'scanned_local' => 0,
        ];

        if (!is_dir($this->uploadsDir)) {
            return $result;
        }

        $activeAudioFiles = $this->fetchReferencedLessonAudioFilenames();
        $iterator = new DirectoryIterator($this->uploadsDir);
        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $filename = $item->getFilename();
            if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'mp3') {
                continue;
            }

            $result['scanned_local']++;
            if (in_array($filename, $activeAudioFiles, true)) {
                continue;
            }

            if (@unlink($item->getPathname())) {
                $result['removed_local']++;
            }
        }

        return $result;
    }

    private function deleteLocalUpload(string $filename): void
    {
        $filename = basename(trim($filename));
        if ($filename === '') {
            return;
        }

        $path = $this->uploadsDir . '/' . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function ensureTempAudioDirectory(): void
    {
        if (!is_dir($this->tempAudioDir) && !@mkdir($this->tempAudioDir, 0777, true) && !is_dir($this->tempAudioDir)) {
            throw new RuntimeException('Não foi possível preparar o diretório temporário de áudio.');
        }

        if (!is_writable($this->tempAudioDir)) {
            @chmod($this->tempAudioDir, 0777);
        }

        if (!is_writable($this->tempAudioDir)) {
            throw new RuntimeException('O diretório temporário de áudio não possui permissão de escrita.');
        }
    }

    private function fetchReferencedLessonAudioFilenames(): array
    {
        try {
            if (!function_exists('load_project_env')) {
                return [];
            }

            load_project_env(dirname(__DIR__, 2) . '/.env');
            require dirname(__DIR__, 2) . '/config/database.php';
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                return [];
            }

            $stmt = $pdo->query("SELECT audio_url FROM lessons WHERE audio_url IS NOT NULL AND audio_url <> ''");
            return array_values(array_unique(array_map(static function ($value) {
                return basename((string)$value);
            }, $stmt->fetchAll(PDO::FETCH_COLUMN))));
        } catch (Throwable $exception) {
            $this->logNotice('Falha ao mapear áudios referenciados: ' . $exception->getMessage());
            return [];
        }
    }

    private function resolveFfmpegBinary(): ?string
    {
        if ($this->ffmpegChecked) {
            return $this->ffmpegBinary;
        }

        $this->ffmpegChecked = true;
        $candidates = ['ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'];

        foreach ($candidates as $candidate) {
            if ($candidate !== 'ffmpeg' && is_file($candidate) && is_executable($candidate)) {
                $this->ffmpegBinary = $candidate;
                return $this->ffmpegBinary;
            }

            if ($candidate === 'ffmpeg') {
                $result = [];
                $exitCode = 1;
                @exec('command -v ffmpeg 2>/dev/null', $result, $exitCode);
                $resolved = trim((string)($result[0] ?? ''));
                if ($exitCode === 0 && $resolved !== '') {
                    $this->ffmpegBinary = $resolved;
                    return $this->ffmpegBinary;
                }
            }
        }

        return null;
    }

    private function logNotice(string $message): void
    {
        if (function_exists('registrar_log')) {
            registrar_log('lesson_media', $message);
            return;
        }

        @error_log($message);
    }
}
