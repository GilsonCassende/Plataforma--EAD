<?php

class LessonTranscriptService
{
    private PDO $pdo;
    private Lesson $lessonModel;
    private string $projectRoot;
    private ?string $ffmpegBinary = null;
    private bool $ffmpegChecked = false;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->lessonModel = new Lesson($pdo);
        $this->projectRoot = dirname(__DIR__, 2);
    }

    public function saveManualTranscript(int $lessonId, ?string $transcript, ?int $userId = null): array
    {
        $normalized = $this->normalizeTranscript($transcript);
        $generatedAt = $normalized !== null ? date('Y-m-d H:i:s') : null;
        $this->lessonModel->atualizarTranscript($lessonId, $normalized, $generatedAt);

        if (function_exists('registrar_log')) {
            registrar_log(
                'lesson_transcript_manual',
                'Transcrição manual salva para a aula ' . $lessonId . ' (' . mb_strlen((string)($normalized ?? '')) . ' chars)',
                $userId
            );
        }

        return [
            'sucesso' => true,
            'mensagem' => $normalized !== null
                ? 'Transcrição manual salva com sucesso.'
                : 'Transcrição removida com sucesso.',
            'transcript' => $normalized,
        ];
    }

    public function clearTranscript(int $lessonId, ?int $userId = null): array
    {
        return $this->saveManualTranscript($lessonId, null, $userId);
    }

    public function generateAndStoreForLesson(int $lessonId, ?int $userId = null): array
    {
        $startedAt = microtime(true);
        $lesson = $this->lessonModel->obterPorId($lessonId);
        if (!$lesson) {
            return ['sucesso' => false, 'mensagem' => 'Aula não encontrada para gerar transcrição.'];
        }

        $result = $this->generateTranscriptForLessonSource($lesson);
        if (empty($result['sucesso'])) {
            if (function_exists('registrar_log')) {
                registrar_log(
                    'lesson_transcript_error',
                    'Falha ao gerar transcrição da aula ' . $lessonId . ': ' . ($result['mensagem'] ?? 'erro desconhecido'),
                    $userId
                );
            }
            return $result;
        }

        $transcript = $this->normalizeTranscript((string)($result['transcript'] ?? ''));
        if ($transcript === null) {
            $message = 'A transcrição retornou conteúdo vazio.';
            if (function_exists('registrar_log')) {
                registrar_log('lesson_transcript_error', 'Aula ' . $lessonId . ': ' . $message, $userId);
            }
            return ['sucesso' => false, 'mensagem' => $message];
        }

        $generatedAt = date('Y-m-d H:i:s');
        $this->lessonModel->atualizarTranscript($lessonId, $transcript, $generatedAt);

        if (function_exists('registrar_log')) {
            $durationMs = (int)round((microtime(true) - $startedAt) * 1000);
            registrar_log(
                'lesson_transcript_generated',
                'Transcrição gerada para a aula ' . $lessonId . ' via ' . ($result['source'] ?? 'fonte desconhecida') . ' (' . mb_strlen($transcript) . ' chars, ' . $durationMs . ' ms)',
                $userId
            );
        }

        return [
            'sucesso' => true,
            'mensagem' => 'Transcrição gerada com sucesso.',
            'transcript' => $transcript,
            'generated_at' => $generatedAt,
        ];
    }

    public function generateTranscriptFromYouTubeVideoId(string $videoId): array
    {
        $videoId = trim($videoId);
        if ($videoId === '') {
            return ['sucesso' => false, 'mensagem' => 'Vídeo do YouTube inválido para transcrição.'];
        }

        $result = $this->fetchTranscriptFromYouTube($videoId);
        if (empty($result['sucesso'])) {
            return $result;
        }

        $transcript = $this->normalizeTranscript((string)($result['transcript'] ?? ''));
        if ($transcript === null) {
            return ['sucesso' => false, 'mensagem' => 'A transcrição retornou conteúdo vazio.'];
        }

        return [
            'sucesso' => true,
            'mensagem' => 'Transcrição gerada com sucesso.',
            'transcript' => $transcript,
        ];
    }

    private function generateTranscriptForLessonSource(array $lesson): array
    {
        $videoId = trim((string)($lesson['video_id'] ?? ''));
        if ($videoId !== '') {
            $result = $this->fetchTranscriptFromYouTube($videoId);
            if (!empty($result['sucesso'])) {
                $result['source'] = 'YouTube';
            }
            return $result;
        }

        $videoFilename = basename(trim((string)($lesson['url_arquivo'] ?? '')));
        if ($videoFilename !== '' && strtolower(pathinfo($videoFilename, PATHINFO_EXTENSION)) === 'mp4') {
            $result = $this->fetchTranscriptFromLocalVideo($videoFilename);
            if (!empty($result['sucesso'])) {
                $result['source'] = 'MP4 local';
            }
            return $result;
        }

        return ['sucesso' => false, 'mensagem' => 'Esta aula não possui uma fonte de vídeo compatível para transcrição automática.'];
    }

    private function fetchTranscriptFromYouTube(string $videoId): array
    {
        $scriptPath = $this->projectRoot . '/scripts/fetch-youtube-transcript.js';
        if (!is_file($scriptPath)) {
            return ['sucesso' => false, 'mensagem' => 'Script local de transcrição não encontrado.'];
        }

        $timeoutSeconds = max(2, env_int('LESSON_TRANSCRIPT_TIMEOUT', 10));
        $nodeBinary = defined('NODE_BIN') && NODE_BIN !== '' ? NODE_BIN : 'node';
        $languages = $this->resolveTranscriptLanguages();
        $lastFailureMessage = 'Não foi possível gerar a transcrição deste vídeo.';

        foreach ($languages as $language) {
            $execution = $this->runProcess([
                '/usr/bin/env',
                '-u',
                'LD_LIBRARY_PATH',
                $nodeBinary,
                $scriptPath,
                '--video-id',
                $videoId,
                '--lang',
                $language,
            ], $timeoutSeconds);

            if (empty($execution['sucesso'])) {
                $lastFailureMessage = (string)($execution['mensagem'] ?? 'Não foi possível executar a transcrição local.');
                continue;
            }

            $stdout = trim((string)($execution['stdout'] ?? ''));
            $jsonPayload = $stdout;
            if ($stdout !== '' && strpos($stdout, "\n") !== false) {
                $lines = preg_split('/\R/u', $stdout) ?: [];
                $lastLine = trim((string)end($lines));
                if ($lastLine !== '' && ($lastLine[0] ?? '') === '{') {
                    $jsonPayload = $lastLine;
                }
            }

            $decoded = json_decode($jsonPayload, true);
            if (!is_array($decoded)) {
                if (function_exists('registrar_log') && !empty($execution['stdout'])) {
                    registrar_log('lesson_transcript_error', 'stdout inválido na transcrição: ' . mb_substr((string)$execution['stdout'], 0, 500));
                }
                $lastFailureMessage = 'Script local de transcrição retornou resposta inválida.';
                continue;
            }

            if (empty($decoded['sucesso'])) {
                $lastFailureMessage = (string)($decoded['mensagem'] ?? 'Não foi possível gerar a transcrição deste vídeo.');
                continue;
            }

            $transcript = $decoded['transcript'] ?? null;
            if (!is_string($transcript) || trim($transcript) === '') {
                $lastFailureMessage = 'Transcrição não encontrada na resposta do script local.';
                continue;
            }

            return ['sucesso' => true, 'transcript' => $transcript];
        }

        return ['sucesso' => false, 'mensagem' => $lastFailureMessage];
    }

    private function resolveTranscriptLanguages(): array
    {
        $preferred = trim((string)env_value('LESSON_TRANSCRIPT_LANGUAGE', 'pt'));
        $candidates = array_filter([
            $preferred !== '' ? $preferred : null,
            'pt',
            'pt-BR',
            'en',
            'en-US',
        ]);

        $unique = [];
        foreach ($candidates as $candidate) {
            $normalized = trim((string)$candidate);
            if ($normalized === '' || in_array($normalized, $unique, true)) {
                continue;
            }
            $unique[] = $normalized;
        }

        return $unique !== [] ? $unique : ['pt', 'en'];
    }

    private function fetchTranscriptFromLocalVideo(string $videoFilename): array
    {
        $uploadsDir = defined('UPLOADS_DIR') ? UPLOADS_DIR : ($this->projectRoot . '/public/uploads');
        $videoPath = rtrim($uploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $videoFilename;
        if (!is_file($videoPath)) {
            return ['sucesso' => false, 'mensagem' => 'Arquivo de vídeo da aula não foi encontrado no servidor.'];
        }

        $ffmpeg = $this->resolveFfmpegBinary();
        if ($ffmpeg === null) {
            return ['sucesso' => false, 'mensagem' => 'FFmpeg indisponível para extrair o áudio do vídeo.'];
        }

        $tempAudioPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'lesson-transcript-' . bin2hex(random_bytes(12)) . '.mp3';

        try {
            $extractResult = $this->extractAudioForTranscript($ffmpeg, $videoPath, $tempAudioPath);
            if (empty($extractResult['sucesso'])) {
                return $extractResult;
            }

            return $this->transcribeAudioWithGroq($tempAudioPath, 'audio/mpeg');
        } finally {
            if (is_file($tempAudioPath)) {
                @unlink($tempAudioPath);
            }
        }
    }

    private function extractAudioForTranscript(string $ffmpeg, string $videoPath, string $tempAudioPath): array
    {
        $command = [
            $ffmpeg,
            '-y',
            '-i',
            $videoPath,
            '-vn',
            '-ac',
            '1',
            '-ar',
            '16000',
            '-b:a',
            '48k',
            '-map',
            'a',
            $tempAudioPath,
        ];

        $timeoutSeconds = max(5, env_int('LESSON_TRANSCRIPT_TIMEOUT', 10));
        $execution = $this->runProcess($command, $timeoutSeconds);
        if (empty($execution['sucesso'])) {
            return [
                'sucesso' => false,
                'mensagem' => (string)($execution['mensagem'] ?? 'Não foi possível extrair o áudio do vídeo para transcrição.'),
            ];
        }

        if (!is_file($tempAudioPath) || filesize($tempAudioPath) === 0) {
            return ['sucesso' => false, 'mensagem' => 'O áudio extraído do vídeo ficou vazio.'];
        }

        return ['sucesso' => true];
    }

    private function transcribeAudioWithGroq(string $audioPath, string $mimeType): array
    {
        if (!is_file($audioPath)) {
            return ['sucesso' => false, 'mensagem' => 'Áudio temporário não encontrado para transcrição.'];
        }

        $apiKeys = $this->resolveGroqApiKeys();
        if ($apiKeys === []) {
            return ['sucesso' => false, 'mensagem' => 'GROQ_API_KEY não configurada para transcrever vídeos MP4.'];
        }

        $configuredModel = trim((string)env_value('GROQ_TRANSCRIPTION_MODEL', 'whisper-large-v3-turbo'));
        $timeoutSeconds = max(10, env_int('GROQ_TIMEOUT', env_int('LESSON_TRANSCRIPT_TIMEOUT', 10)));
        $language = trim((string)env_value('LESSON_TRANSCRIPT_LANGUAGE', 'pt'));
        $prompt = 'Transcreva fielmente este áudio da aula. Não resuma, não explique e não adicione observações.';
        $models = [
            $configuredModel !== '' ? $configuredModel : 'whisper-large-v3-turbo',
            'whisper-large-v3-turbo',
            'whisper-large-v3',
        ];
        $models = array_values(array_unique(array_filter($models)));
        $lastMessage = 'Não foi possível transcrever o vídeo MP4 no momento pela Groq.';

        foreach ($apiKeys as $apiKey) {
            foreach ($models as $model) {
                try {
                    $response = $this->performMultipartRequest(
                        'https://api.groq.com/openai/v1/audio/transcriptions',
                        [
                            'model' => $model,
                            'language' => $language !== '' ? $language : 'pt',
                            'prompt' => $prompt,
                            'response_format' => 'json',
                            'temperature' => '0',
                            'file' => curl_file_create($audioPath, $mimeType, basename($audioPath)),
                        ],
                        [
                            'Accept: application/json',
                            'Authorization: Bearer ' . $apiKey,
                        ],
                        $timeoutSeconds
                    );
                } catch (Throwable $exception) {
                    $lastMessage = $exception->getMessage();
                    continue;
                }

                $decoded = json_decode((string)($response['body'] ?? ''), true);
                if (!is_array($decoded)) {
                    $lastMessage = 'Resposta inválida da Groq ao transcrever o vídeo.';
                    continue;
                }

                $statusCode = (int)($response['status_code'] ?? 0);
                if ($statusCode >= 400) {
                    $lastMessage = (string)($decoded['error']['message'] ?? ('Erro HTTP ' . $statusCode . ' ao transcrever o vídeo.'));
                    continue;
                }

                $transcript = trim((string)($decoded['text'] ?? ''));
                if (trim($transcript) !== '') {
                    return ['sucesso' => true, 'transcript' => $transcript];
                }

                $lastMessage = 'A Groq não retornou texto de transcrição para este vídeo.';
            }
        }

        return ['sucesso' => false, 'mensagem' => $lastMessage];
    }

    private function normalizeTranscript(?string $transcript): ?string
    {
        if ($transcript === null) {
            return null;
        }

        $normalized = preg_replace('/\R+/u', "\n", html_entity_decode(strip_tags((string)$transcript), ENT_QUOTES, 'UTF-8'));
        $normalized = preg_replace('/[ \t]+/u', ' ', (string)$normalized);
        $normalized = trim((string)$normalized);

        return $normalized !== '' ? $normalized : null;
    }

    private function runProcess(array $command, int $timeoutSeconds): array
    {
        if (function_exists('proc_open')) {
            $procResult = $this->runProcessViaProcOpen($command, $timeoutSeconds);
            if (!empty($procResult['sucesso'])) {
                return $procResult;
            }

            if (function_exists('registrar_log')) {
                registrar_log('lesson_transcript_error', 'proc_open falhou, tentando exec: ' . ($procResult['mensagem'] ?? 'falha desconhecida'));
            }
        }

        if (function_exists('exec')) {
            return $this->runProcessViaExec($command, $timeoutSeconds);
        }

        return ['sucesso' => false, 'mensagem' => 'Nenhum método de execução de processo está disponível no PHP.'];
    }

    private function runProcessViaProcOpen(array $command, int $timeoutSeconds): array
    {
        $escapedCommand = implode(' ', array_map('escapeshellarg', $command));
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($escapedCommand, $descriptorSpec, $pipes, $this->projectRoot);
        if (!is_resource($process)) {
            return ['sucesso' => false, 'mensagem' => 'Não foi possível iniciar o script de transcrição local.'];
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $startedAt = microtime(true);
        $timedOut = false;

        do {
            $stdout .= stream_get_contents($pipes[1]) ?: '';
            $stderr .= stream_get_contents($pipes[2]) ?: '';

            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }

            if ((microtime(true) - $startedAt) >= $timeoutSeconds) {
                $timedOut = true;
                proc_terminate($process, 9);
                break;
            }

            usleep(100000);
        } while (true);

        $stdout .= stream_get_contents($pipes[1]) ?: '';
        $stderr .= stream_get_contents($pipes[2]) ?: '';

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($timedOut) {
            return ['sucesso' => false, 'mensagem' => 'A geração da transcrição excedeu o tempo limite configurado.'];
        }

        if ($exitCode !== 0 && trim($stdout) === '') {
            if (function_exists('registrar_log') && trim($stderr) !== '') {
                registrar_log('lesson_transcript_error', 'stderr transcrição: ' . trim($stderr));
            }

            return [
                'sucesso' => false,
                'mensagem' => 'Falha ao executar a transcrição local.',
            ];
        }

        return [
            'sucesso' => true,
            'stdout' => trim($stdout),
            'stderr' => trim($stderr),
            'exit_code' => $exitCode,
        ];
    }

    private function runProcessViaExec(array $command, int $timeoutSeconds): array
    {
        $escapedCommand = implode(' ', array_map('escapeshellarg', $command));
        $shellCommand = 'timeout ' . (int)$timeoutSeconds . 's ' . $escapedCommand . ' 2>&1';
        $output = [];
        $exitCode = 0;

        @exec($shellCommand, $output, $exitCode);
        $stdout = trim(implode("\n", $output));

        if ($exitCode !== 0 && $stdout === '') {
            return ['sucesso' => false, 'mensagem' => 'Falha ao executar a transcrição local via exec.'];
        }

        if ($exitCode === 124) {
            return ['sucesso' => false, 'mensagem' => 'A geração da transcrição excedeu o tempo limite configurado.'];
        }

        return [
            'sucesso' => true,
            'stdout' => $stdout,
            'stderr' => '',
            'exit_code' => $exitCode,
        ];
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

    private function resolveGroqApiKeys(): array
    {
        $rawList = trim((string)env_value('GROQ_API_KEYS', ''));
        $keys = [];

        if ($rawList !== '') {
            $parts = preg_split('/[\s,;]+/u', $rawList) ?: [];
            foreach ($parts as $part) {
                $part = trim((string)$part);
                if ($part !== '') {
                    $keys[] = $part;
                }
            }
        }

        $singleKey = trim((string)env_value('GROQ_API_KEY', ''));
        if ($singleKey !== '') {
            $keys[] = $singleKey;
        }

        return array_values(array_unique($keys));
    }

    private function performJsonRequest(string $url, string $payload, array $headers, int $timeoutSeconds): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL indisponível para consulta ao provedor de IA.');
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Não foi possível inicializar cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeoutSeconds),
        ]);

        $body = curl_exec($ch);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            if ($curlErrno === CURLE_OPERATION_TIMEDOUT) {
                throw new RuntimeException('Timeout ao consultar Groq para transcrição.');
            }
            throw new RuntimeException('Falha ao consultar Groq via cURL: ' . $curlError);
        }

        if ($statusCode === 0) {
            throw new RuntimeException('Resposta vazia ou sem status ao consultar Groq para transcrição.');
        }

        return [
            'body' => (string)$body,
            'status_code' => $statusCode,
        ];
    }

    private function performMultipartRequest(string $url, array $fields, array $headers, int $timeoutSeconds): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL indisponível para consulta ao provedor de IA.');
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Não foi possível inicializar cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeoutSeconds),
        ]);

        $body = curl_exec($ch);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            if ($curlErrno === CURLE_OPERATION_TIMEDOUT) {
                throw new RuntimeException('Timeout ao consultar Groq para transcrição.');
            }
            throw new RuntimeException('Falha ao consultar Groq via cURL: ' . $curlError);
        }

        if ($statusCode === 0) {
            throw new RuntimeException('Resposta vazia ou sem status ao consultar Groq para transcrição.');
        }

        return [
            'body' => (string)$body,
            'status_code' => $statusCode,
        ];
    }
}
