<?php

class LessonAiTutorService
{
    private PDO $pdo;
    private Lesson $lessonModel;
    private Enrollment $enrollmentModel;
    private const FALLBACK_MESSAGE = 'O tutor está temporariamente indisponível. Tente novamente em instantes.';
    private const RATE_LIMIT_MESSAGE = 'Aguarde alguns segundos antes de perguntar novamente.';
    private const MAX_QUESTION_LENGTH = 500;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->lessonModel = new Lesson($pdo);
        $this->enrollmentModel = new Enrollment($pdo);
        $this->ensureSchema();
    }

    public function answerQuestion(array $user, int $lessonId, string $question): array
    {
        $userId = (int)($user['id'] ?? 0);
        $startedAt = microtime(true);
        $question = $this->sanitizeQuestion($question);

        if ($userId <= 0) {
            return ['sucesso' => false, 'mensagem' => 'Faça login para usar o tutor da aula.', 'status_code' => 401];
        }

        if ($lessonId <= 0) {
            return ['sucesso' => false, 'mensagem' => 'Aula inválida.', 'status_code' => 400];
        }

        if ($question === '' || mb_strlen($question) < 3) {
            return ['sucesso' => false, 'mensagem' => 'Escreva uma pergunta com pelo menos 3 caracteres.', 'status_code' => 422];
        }

        $lesson = $this->lessonModel->obterPorId($lessonId);
        if (!$lesson) {
            return ['sucesso' => false, 'mensagem' => 'Aula não encontrada.', 'status_code' => 404];
        }

        $access = $this->validateLessonAccess($user, $lesson);
        if (empty($access['sucesso'])) {
            return $access;
        }

        $rateLimit = $this->checkRateLimit($userId);
        if (empty($rateLimit['sucesso'])) {
            $this->logInteraction($userId, $lessonId, $question, null, 'rate_limited', $rateLimit['mensagem'], $startedAt);
            return $rateLimit;
        }

        $context = $this->buildContext($lesson, $question);
        if (trim((string)($context['context'] ?? '')) === '') {
            $message = 'Esta aula ainda não possui conteúdo suficiente para o tutor responder.';
            $this->logInteraction($userId, $lessonId, $question, null, 'no_context', $message, $startedAt);
            return ['sucesso' => false, 'mensagem' => $message, 'status_code' => 422];
        }

        $gemini = $this->callGemini($context['context'], $question);
        if (empty($gemini['sucesso'])) {
            $this->logInteraction($userId, $lessonId, $question, null, 'api_error', $gemini['mensagem'] ?? 'Falha Gemini', $startedAt);
            return [
                'sucesso' => false,
                'mensagem' => self::FALLBACK_MESSAGE,
                'status_code' => (int)($gemini['status_code'] ?? 503),
                'modo_limitado' => !empty($context['limited_mode']),
            ];
        }

        $answer = trim((string)($gemini['answer'] ?? ''));
        if ($answer === '') {
            $answer = 'Não foi possível gerar uma resposta com base no conteúdo desta aula.';
        }

        $this->logInteraction($userId, $lessonId, $question, $answer, 'success', null, $startedAt);

        return [
            'sucesso' => true,
            'resposta' => $answer,
            'modo_limitado' => !empty($context['limited_mode']),
            'fontes' => $context['sources'],
        ];
    }

    private function validateLessonAccess(array $user, array $lesson): array
    {
        $userId = (int)($user['id'] ?? 0);
        $role = (string)($user['role'] ?? '');
        $courseId = (int)($lesson['course_id'] ?? 0);

        if ($role === 'admin') {
            return ['sucesso' => true];
        }

        if ($role === 'professor') {
            $stmt = $this->pdo->prepare('SELECT teacher_id FROM courses WHERE id = ? LIMIT 1');
            $stmt->execute([$courseId]);
            $teacherId = (int)$stmt->fetchColumn();
            if ($teacherId === $userId) {
                return ['sucesso' => true];
            }
        }

        if ($this->enrollmentModel->estaMatriculado($userId, $courseId)) {
            return ['sucesso' => true];
        }

        return ['sucesso' => false, 'mensagem' => 'Você não tem acesso a esta aula.', 'status_code' => 403];
    }

    private function checkRateLimit(int $userId): array
    {
        $maxRequests = max(1, env_int('LESSON_AI_RATE_LIMIT', 5));
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM lesson_ai_logs
             WHERE user_id = ?
               AND created_at >= (NOW() - INTERVAL 1 MINUTE)'
        );
        $stmt->execute([$userId]);
        $count = (int)$stmt->fetchColumn();

        if ($count >= $maxRequests) {
            return [
                'sucesso' => false,
                'mensagem' => self::RATE_LIMIT_MESSAGE,
                'status_code' => 429,
            ];
        }

        return ['sucesso' => true];
    }

    private function buildContext(array $lesson, string $question): array
    {
        $sources = [];
        $sections = [];

        $transcript = $this->normalizeContent((string)($lesson['lesson_transcript'] ?? ''));
        $content = $this->normalizeContent((string)($lesson['conteudo_texto'] ?? $lesson['conteudo'] ?? ''));
        $description = $this->normalizeContent((string)($lesson['descricao'] ?? ''));
        $title = $this->normalizeContent((string)($lesson['titulo'] ?? ''));

        if ($transcript !== '') {
            $sections[] = "Transcrição da aula:\n" . $this->extractRelevantSnippet($transcript, $question, 7000);
            $sources[] = 'lesson_transcript';
        }
        if ($content !== '') {
            $sections[] = "Conteúdo textual da aula:\n" . $this->extractRelevantSnippet($content, $question, 3500);
            $sources[] = 'conteudo_texto';
        }
        if ($description !== '') {
            $sections[] = "Descrição da aula:\n" . $description;
            $sources[] = 'description';
        }
        if ($title !== '') {
            $sections[] = "Título da aula:\n" . $title;
            $sources[] = 'titulo';
        }

        return [
            'context' => implode("\n\n", $sections),
            'sources' => $sources,
            'limited_mode' => $transcript === '',
        ];
    }

    private function callGemini(string $context, string $question): array
    {
        $apiKey = trim((string)env_value('GEMINI_API_KEY', ''));
        if ($apiKey === '') {
            return ['sucesso' => false, 'mensagem' => 'GEMINI_API_KEY não configurada.', 'status_code' => 503];
        }

        $model = trim((string)env_value('GEMINI_MODEL', 'gemini-1.5-flash'));
        $timeoutSeconds = 10;
        $prompt = "Você é um tutor profissional da plataforma EAD em Angola.\n\n"
            . "Responda APENAS com base no conteúdo abaixo.\n\n"
            . "Se a resposta não estiver no conteúdo, diga claramente:\n"
            . "'Essa informação não foi abordada nesta aula.'\n\n"
            . "NÃO invente informações.\n\n"
            . "Explique de forma simples, clara e didática.\n\n"
            . "Use exemplos práticos do dia a dia angolano.\n\n"
            . "Conteúdo da aula:\n"
            . $context
            . "\n\nPergunta do aluno:\n"
            . $question;

        $payload = json_encode([
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 700,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            return ['sucesso' => false, 'mensagem' => 'Falha ao preparar requisição Gemini.', 'status_code' => 500];
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);

        try {
            $response = $this->performGeminiRequest($url, $payload, $timeoutSeconds);
            $decoded = json_decode((string)($response['body'] ?? ''), true);
            if (!is_array($decoded)) {
                return ['sucesso' => false, 'mensagem' => 'Resposta inválida do Gemini.', 'status_code' => 502];
            }

            $statusCode = (int)($response['status_code'] ?? 0);
            if ($statusCode >= 400) {
                $errorMessage = (string)($decoded['error']['message'] ?? ('Erro HTTP ' . $statusCode . ' ao consultar Gemini.'));
                if (function_exists('registrar_log')) {
                    registrar_log('lesson_ai_api_error', $errorMessage);
                }
                return ['sucesso' => false, 'mensagem' => $errorMessage, 'status_code' => $statusCode];
            }

            if (!isset($decoded['candidates'][0]['content']['parts'][0]['text']) || !is_string($decoded['candidates'][0]['content']['parts'][0]['text'])) {
                return ['sucesso' => false, 'mensagem' => 'Não foi possível gerar resposta no momento. Tente novamente.', 'status_code' => 502];
            }

            $answer = trim((string)$decoded['candidates'][0]['content']['parts'][0]['text']);
            if ($answer === '') {
                return ['sucesso' => false, 'mensagem' => 'Não foi possível gerar resposta no momento. Tente novamente.', 'status_code' => 502];
            }

            return ['sucesso' => true, 'answer' => $answer];
        } catch (Throwable $e) {
            $this->logApiError($e->getMessage());
            return ['sucesso' => false, 'mensagem' => 'Erro temporário no tutor. Tente novamente.', 'status_code' => 503];
        }
    }

    private function performGeminiRequest(string $url, string $payload, int $timeoutSeconds): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL indisponível para consulta ao Gemini.');
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Não foi possível inicializar cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
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
                throw new RuntimeException('Timeout ao consultar Gemini.');
            }
            throw new RuntimeException('Falha ao consultar Gemini via cURL: ' . $curlError);
        }

        if ($statusCode === 0) {
            throw new RuntimeException('Resposta vazia ou sem status ao consultar Gemini.');
        }

        return [
            'body' => (string)$body,
            'status_code' => $statusCode,
        ];
    }

    private function logInteraction(
        int $userId,
        int $lessonId,
        string $question,
        ?string $answer,
        string $status,
        ?string $errorMessage,
        float $startedAt
    ): void {
        $durationMs = (int)round((microtime(true) - $startedAt) * 1000);
        $stmt = $this->pdo->prepare(
            'INSERT INTO lesson_ai_logs
             (user_id, lesson_id, question_text, answer_text, status, error_message, response_time_ms, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $userId,
            $lessonId,
            mb_substr($question, 0, 2000),
            $answer !== null ? mb_substr($answer, 0, 8000) : null,
            $status,
            $errorMessage !== null ? mb_substr($errorMessage, 0, 1000) : null,
            $durationMs,
        ]);

        if (function_exists('registrar_log')) {
            $message = 'Tutor IA aula=' . $lessonId . ' status=' . $status . ' tempo_ms=' . $durationMs;
            if ($errorMessage) {
                $message .= ' erro=' . $errorMessage;
            }
            registrar_log('lesson_ai', $message, $userId);
        }
    }

    private function sanitizeQuestion(string $question): string
    {
        $question = html_entity_decode(strip_tags($question), ENT_QUOTES, 'UTF-8');
        $question = preg_replace('/\s+/u', ' ', $question);
        return trim(mb_substr((string)$question, 0, self::MAX_QUESTION_LENGTH));
    }

    private function normalizeContent(string $content): string
    {
        $content = html_entity_decode(strip_tags($content), ENT_QUOTES, 'UTF-8');
        $content = preg_replace('/\R+/u', "\n", $content);
        $content = preg_replace('/[ \t]+/u', ' ', $content);
        return trim((string)$content);
    }

    private function extractRelevantSnippet(string $content, string $question, int $maxChars): string
    {
        if (mb_strlen($content) <= $maxChars) {
            return $content;
        }

        $segments = preg_split("/\n{2,}/u", $content) ?: [];
        $keywords = $this->extractKeywords($question);
        $ranked = [];

        foreach ($segments as $index => $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            $score = 0;
            $lower = mb_strtolower($segment, 'UTF-8');
            foreach ($keywords as $keyword) {
                if ($keyword !== '' && mb_stripos($lower, $keyword, 0, 'UTF-8') !== false) {
                    $score += 3;
                }
            }
            if ($index < 3) {
                $score += 1;
            }

            $ranked[] = ['segment' => $segment, 'score' => $score, 'index' => $index];
        }

        usort($ranked, static function (array $a, array $b): int {
            if ($a['score'] === $b['score']) {
                return $a['index'] <=> $b['index'];
            }
            return $b['score'] <=> $a['score'];
        });

        $selected = [];
        $length = 0;
        foreach ($ranked as $item) {
            $segmentLength = mb_strlen($item['segment']);
            if ($length > 0 && ($length + $segmentLength + 2) > $maxChars) {
                continue;
            }
            $selected[$item['index']] = $item['segment'];
            $length += $segmentLength + 2;
            if ($length >= $maxChars) {
                break;
            }
        }

        if ($selected === []) {
            return mb_substr($content, 0, $maxChars);
        }

        ksort($selected);
        return trim(implode("\n\n", $selected));
    }

    private function extractKeywords(string $question): array
    {
        $normalized = mb_strtolower($this->normalizeContent($question), 'UTF-8');
        $tokens = preg_split('/[^a-z0-9áàâãéèêíïóôõöúç]+/iu', $normalized) ?: [];
        $tokens = array_filter($tokens, static function ($token) {
            return mb_strlen((string)$token) >= 3;
        });
        return array_values(array_unique($tokens));
    }

    private function ensureSchema(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS lesson_ai_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                lesson_id INT NOT NULL,
                question_text TEXT NOT NULL,
                answer_text LONGTEXT NULL,
                status VARCHAR(32) NOT NULL DEFAULT "success",
                error_message VARCHAR(1000) NULL,
                response_time_ms INT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_lesson_ai_logs_user_created (user_id, created_at),
                INDEX idx_lesson_ai_logs_lesson_created (lesson_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private function logApiError(string $message): void
    {
        if (function_exists('registrar_log')) {
            registrar_log('lesson_ai_api_error', $message);
        }
    }
}
