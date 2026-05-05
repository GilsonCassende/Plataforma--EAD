<?php

class LessonAiTutorService
{
    private PDO $pdo;
    private Lesson $lessonModel;
    private Enrollment $enrollmentModel;
    private const FALLBACK_MESSAGE = 'O assistente está temporariamente indisponível. Tente novamente em instantes.';
    private const RATE_LIMIT_MESSAGE = 'Aguarde alguns segundos antes de perguntar novamente.';
    private const API_QUOTA_MESSAGE = 'O assistente de IA atingiu o limite temporário de uso da API. Aguarde cerca de 1 minuto e tente novamente.';
    private const MAX_QUESTION_LENGTH = 500;
    private const TRANSCRIPT_SNIPPET_LIMIT = 2800;
    private const CONTENT_SNIPPET_LIMIT = 1400;
    private const DESCRIPTION_LIMIT = 500;
    private const TITLE_LIMIT = 160;
    private const MAX_OUTPUT_TOKENS = 280;
    private const CACHE_TTL_SECONDS = 21600;
    private const DUPLICATE_QUESTION_WINDOW_SECONDS = 45;
    private const DUPLICATE_QUESTION_MESSAGE = 'Você acabou de enviar essa mesma pergunta. Aguarde alguns segundos ou reformule a dúvida.';
    private const GROQ_FAILOVER_STATUSES = [408, 429, 500, 502, 503, 504];
    private const EMPTY_ANSWER_MESSAGE = 'Não consegui gerar uma resposta clara. Tente reformular sua pergunta.';
    private const HISTORY_MESSAGE_LIMIT = 6;
    private const HISTORY_MESSAGE_MAX_LENGTH = 400;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->lessonModel = new Lesson($pdo);
        $this->enrollmentModel = new Enrollment($pdo);
        $this->ensureSchema();
    }

    public function answerQuestion(array $user, int $lessonId, string $question, array $history = []): array
    {
        $userId = (int)($user['id'] ?? 0);
        $startedAt = microtime(true);
        $question = $this->sanitizeQuestion($question);
        $history = $this->sanitizeHistory($history);

        if ($userId <= 0) {
            return ['sucesso' => false, 'mensagem' => 'Faça login para usar o assistente da aula.', 'status_code' => 401];
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

        $context = $this->buildContext($lesson, $question);
        $historyContext = $this->buildHistoryContext($history);

        $cacheKey = $this->buildCacheKey(
            $lessonId,
            $question,
            (string)($context['context'] ?? ''),
            $historyContext
        );

        $recentDuplicate = $this->findRecentDuplicateQuestion($userId, $lessonId, $question);
        if (is_array($recentDuplicate)) {
            $cachedAnswer = trim((string)($recentDuplicate['answer_text'] ?? ''));
            if ((string)($recentDuplicate['status'] ?? '') === 'success' && $cachedAnswer !== '') {
                $this->logInteraction($userId, $lessonId, $question, $cachedAnswer, 'cached_hit', null, $startedAt, $cacheKey);
                return [
                    'sucesso' => true,
                    'resposta' => $cachedAnswer,
                    'modo_limitado' => !empty($context['limited_mode']),
                    'fontes' => $context['sources'],
                    'cache' => true,
                ];
            }

            $this->logInteraction($userId, $lessonId, $question, null, 'duplicate_question', self::DUPLICATE_QUESTION_MESSAGE, $startedAt, $cacheKey);
            return [
                'sucesso' => false,
                'mensagem' => self::DUPLICATE_QUESTION_MESSAGE,
                'status_code' => 429,
                'modo_limitado' => !empty($context['limited_mode']),
            ];
        }

        $cachedResult = $this->findCachedAnswer($lessonId, $cacheKey);
        if (is_array($cachedResult)) {
            $cachedAnswer = trim((string)($cachedResult['answer_text'] ?? ''));
            if ($cachedAnswer !== '') {
                $this->logInteraction($userId, $lessonId, $question, $cachedAnswer, 'cached_hit', null, $startedAt, $cacheKey);
                return [
                    'sucesso' => true,
                    'resposta' => $cachedAnswer,
                    'modo_limitado' => !empty($context['limited_mode']),
                    'fontes' => $context['sources'],
                    'cache' => true,
                ];
            }
        }

        $rateLimit = $this->checkRateLimit($userId);
        if (empty($rateLimit['sucesso'])) {
            $this->logInteraction($userId, $lessonId, $question, null, 'rate_limited', $rateLimit['mensagem'], $startedAt, $cacheKey);
            return $rateLimit;
        }

        $providerResult = $this->callPreferredProvider($context['context'], $question, $historyContext);
        if (empty($providerResult['sucesso'])) {
            $this->logInteraction($userId, $lessonId, $question, null, 'api_error', $providerResult['mensagem'] ?? 'Falha IA', $startedAt, $cacheKey);
            $statusCode = (int)($providerResult['status_code'] ?? 503);
            return [
                'sucesso' => false,
                'mensagem' => $this->mapTutorErrorMessage($providerResult['mensagem'] ?? '', $statusCode),
                'status_code' => $statusCode,
                'modo_limitado' => !empty($context['limited_mode']),
            ];
        }

        $answer = $this->formatTutorAnswer((string)($providerResult['answer'] ?? ''));
        if ($answer === '') {
            $answer = self::EMPTY_ANSWER_MESSAGE;
        }

        $this->logInteraction($userId, $lessonId, $question, $answer, 'success', null, $startedAt, $cacheKey);

        return [
            'sucesso' => true,
            'resposta' => $answer,
            'modo_limitado' => !empty($context['limited_mode']),
            'fontes' => $context['sources'],
        ];
    }

    private function callPreferredProvider(string $context, string $question, string $historyContext = ''): array
    {
        return $this->callGroq($context, $question, $historyContext);
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
            $sections[] = "Transcrição da aula:\n" . $this->extractRelevantSnippet($transcript, $question, self::TRANSCRIPT_SNIPPET_LIMIT);
            $sources[] = 'lesson_transcript';
        }
        if ($content !== '') {
            $sections[] = "Conteúdo textual da aula:\n" . $this->extractRelevantSnippet($content, $question, self::CONTENT_SNIPPET_LIMIT);
            $sources[] = 'conteudo_texto';
        }
        if ($description !== '') {
            $sections[] = "Descrição da aula:\n" . mb_substr($description, 0, self::DESCRIPTION_LIMIT);
            $sources[] = 'description';
        }
        if ($title !== '') {
            $sections[] = "Título da aula:\n" . mb_substr($title, 0, self::TITLE_LIMIT);
            $sources[] = 'titulo';
        }

        return [
            'context' => implode("\n\n", $sections),
            'sources' => $sources,
            'limited_mode' => $transcript === '',
        ];
    }

    private function callGroq(string $context, string $question, string $historyContext = ''): array
    {
        $apiKeys = $this->resolveGroqApiKeys();
        if ($apiKeys === []) {
            return ['sucesso' => false, 'mensagem' => 'GROQ_API_KEY não configurada.', 'status_code' => 503];
        }

        $model = trim((string)env_value('GROQ_MODEL', 'llama-3.1-8b-instant'));
        $timeoutSeconds = max(5, env_int('GROQ_TIMEOUT', 10));
        $systemPrompt = $this->buildTutorSystemPrompt();

        $payload = json_encode([
            'model' => $model !== '' ? $model : 'llama-3.1-8b-instant',
            'temperature' => 0.2,
            'max_tokens' => self::MAX_OUTPUT_TOKENS,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildTutorUserPrompt($context, $question, $historyContext),
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            return ['sucesso' => false, 'mensagem' => 'Falha ao preparar requisição Groq.', 'status_code' => 500];
        }

        $lastError = null;
        foreach ($apiKeys as $keyIndex => $apiKey) {
            try {
                $response = $this->performJsonRequest(
                    'https://api.groq.com/openai/v1/chat/completions',
                    $payload,
                    [
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'Authorization: Bearer ' . $apiKey,
                    ],
                    $timeoutSeconds
                );
                $decoded = json_decode((string)($response['body'] ?? ''), true);
                if (!is_array($decoded)) {
                    return ['sucesso' => false, 'mensagem' => 'Resposta inválida da Groq.', 'status_code' => 502];
                }

                $statusCode = (int)($response['status_code'] ?? 0);
                if ($statusCode >= 400) {
                    $errorMessage = (string)($decoded['error']['message'] ?? ('Erro HTTP ' . $statusCode . ' ao consultar Groq.'));
                    $lastError = [
                        'sucesso' => false,
                        'mensagem' => $errorMessage,
                        'status_code' => $statusCode,
                        'model' => $model,
                        'key_index' => $keyIndex,
                        'provider' => 'groq',
                    ];

                    if ($this->shouldFailoverGroqKey($statusCode, $errorMessage) && isset($apiKeys[$keyIndex + 1])) {
                        $this->logApiError('Failover Groq para próxima chave após erro na chave #' . ($keyIndex + 1) . ' e modelo ' . $model . ': ' . $errorMessage);
                        continue;
                    }

                    if (function_exists('registrar_log')) {
                        registrar_log('lesson_ai_api_error', $errorMessage);
                    }
                    return $lastError;
                }

                $answer = $this->formatTutorAnswer((string)($decoded['choices'][0]['message']['content'] ?? ''));
                if ($answer === '') {
                    return ['sucesso' => false, 'mensagem' => self::EMPTY_ANSWER_MESSAGE, 'status_code' => 502];
                }

                return ['sucesso' => true, 'answer' => $answer, 'model' => $model, 'key_index' => $keyIndex, 'provider' => 'groq'];
            } catch (Throwable $e) {
                $lastError = [
                    'sucesso' => false,
                    'mensagem' => 'Erro temporário no assistente. Tente novamente.',
                    'status_code' => 503,
                    'model' => $model,
                    'key_index' => $keyIndex,
                    'provider' => 'groq',
                ];
                $shouldFailover = $this->shouldFailoverGroqKey(503, $e->getMessage());
                $this->logApiError('Exceção Groq na chave #' . ($keyIndex + 1) . ' e modelo ' . $model . ': ' . $e->getMessage());
                if ($shouldFailover && isset($apiKeys[$keyIndex + 1])) {
                    continue;
                }
                return $lastError;
            }
        }

        return is_array($lastError)
            ? $lastError
            : ['sucesso' => false, 'mensagem' => 'Não foi possível gerar resposta no momento. Tente novamente.', 'status_code' => 502];
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
                throw new RuntimeException('Timeout ao consultar Groq.');
            }
            throw new RuntimeException('Falha ao consultar Groq via cURL: ' . $curlError);
        }

        if ($statusCode === 0) {
            throw new RuntimeException('Resposta vazia ou sem status ao consultar Groq.');
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
        float $startedAt,
        ?string $cacheKey = null
    ): void {
        $durationMs = (int)round((microtime(true) - $startedAt) * 1000);
        $stmt = $this->pdo->prepare(
            'INSERT INTO lesson_ai_logs
             (user_id, lesson_id, question_text, answer_text, status, error_message, cache_key, response_time_ms, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $userId,
            $lessonId,
            mb_substr($question, 0, 2000),
            $answer !== null ? mb_substr($answer, 0, 8000) : null,
            $status,
            $errorMessage !== null ? mb_substr($errorMessage, 0, 1000) : null,
            $cacheKey !== null ? mb_substr($cacheKey, 0, 64) : null,
            $durationMs,
        ]);

        if (function_exists('registrar_log')) {
            $message = 'Assistente IA aula=' . $lessonId . ' status=' . $status . ' tempo_ms=' . $durationMs;
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

    private function formatTutorAnswer(string $answer): string
    {
        $answer = html_entity_decode(strip_tags($answer), ENT_QUOTES, 'UTF-8');
        $answer = preg_replace("/\r\n?/", "\n", $answer);
        $answer = preg_replace('/^\s*#{1,6}\s*/mu', '', $answer);
        $answer = str_replace(['**', '__'], '', $answer);
        $answer = preg_replace('/^\s*[*-]\s*[*-]\s*/mu', "- ", $answer);
        $answer = preg_replace("/[ \t]+\n/u", "\n", $answer);
        $answer = preg_replace("/\n{3,}/u", "\n\n", $answer);
        $answer = preg_replace("/(?<!\n)([-*])\s+/u", "\n$1 ", $answer);
        $answer = preg_replace("/(?<!\n)(\d+\.)\s+/u", "\n$1 ", $answer);
        $answer = preg_replace('/^\s*\*\s+/mu', "- ", $answer);
        $answer = preg_replace('/[ \t]{2,}/u', ' ', $answer);
        return $this->normalizeTutorSections(trim((string)$answer));
    }

    private function buildTutorSystemPrompt(): string
    {
        return "Você é um assistente educacional profissional de uma plataforma de ensino em Angola.\n\n"
            . "Seu objetivo é ajudar o aluno a entender melhor a aula e também orientar em dúvidas gerais de estudo, aprendizagem, organização e desenvolvimento académico.\n\n"
            . "REGRAS IMPORTANTES:\n"
            . "- Use o conteúdo da aula como base principal quando ele for relevante para a pergunta\n"
            . "- Se a pergunta for geral e não depender do conteúdo da aula, você PODE responder com orientação educacional útil, prática e segura\n"
            . "- Quando estiver indo além do conteúdo da aula, deixe isso claro com uma frase curta como: 'Isto é uma orientação geral de estudo.'\n"
            . "- Não invente fatos específicos sobre a aula quando eles não estiverem no contexto\n"
            . "- Se faltar contexto da aula para uma pergunta muito específica, diga isso claramente e depois ofereça a melhor orientação geral possível\n\n"
            . "ESTILO DE RESPOSTA:\n"
            . "- Seja didático (como um professor explicando)\n"
            . "- Use linguagem simples\n"
            . "- Evite respostas curtas, vagas ou genéricas demais\n"
            . "- Use exemplos práticos (preferencialmente do dia a dia)\n"
            . "- Se possível, explique passo a passo\n"
            . "- Em perguntas sobre estudo, inclua técnicas acionáveis, rotina, revisão, prática, descanso e foco quando fizer sentido\n\n"
            . "CONVERSA:\n"
            . "- Considere o histórico recente para manter continuidade\n"
            . "- Evite repetir toda a resposta anterior sem necessidade\n"
            . "- Se o aluno fizer uma continuação, responda levando em conta o que já foi dito\n\n"
            . "FORMATO:\n"
            . "- Use exatamente estes títulos quando fizer sentido:\n"
            . "  Explicação\n"
            . "  Exemplo\n"
            . "  Resumo\n"
            . "- Comece com Explicação\n"
            . "- Depois dê um Exemplo simples\n"
            . "- Finalize com Resumo curto, se necessário\n"
            . "- Não use Markdown com ** ou #";
    }

    private function buildTutorUserPrompt(string $context, string $question, string $historyContext = ''): string
    {
        $prompt = "CONTEXTO DA AULA:\n"
            . ($context !== '' ? $context : 'Nenhum conteúdo detalhado da aula foi fornecido.');

        if ($historyContext !== '') {
            $prompt .= "\n\n----------------------------------------\n\n"
                . "HISTÓRICO RECENTE DA CONVERSA:\n"
                . $historyContext;
        }

        return $prompt
            . "\n\n----------------------------------------\n\n"
            . "PERGUNTA DO ALUNO:\n"
            . $question;
    }

    private function normalizeTutorSections(string $answer): string
    {
        if ($answer === '') {
            return '';
        }

        $answer = preg_replace('/^\s*(explicação direta|explicacao direta)\s*:?/imu', "Explicação", $answer);
        $answer = preg_replace('/^\s*(o que vamos aprender|explicação|explicacao)\s*:?/imu', "Explicação", $answer);
        $answer = preg_replace('/^\s*(exemplo prático|exemplo pratico|exemplo)\s*:?/imu', "Exemplo", $answer);
        $answer = preg_replace('/^\s*(resumo curto|resumo)\s*:?/imu', "Resumo", $answer);
        $answer = preg_replace('/(Explicação)\s*\1+/u', '$1', $answer);
        $answer = preg_replace('/(Exemplo)\s*\1+/u', '$1', $answer);
        $answer = preg_replace('/(Resumo)\s*\1+/u', '$1', $answer);
        $answer = preg_replace('/^Explicação\s*\n+\s*Explicação\b/u', "Explicação", $answer);
        $answer = preg_replace('/^Explicação\s*Explicação\b/u', "Explicação", $answer);
        $answer = preg_replace('/\n\s*Exemplo\s*Exemplo\b/u', "\n\nExemplo", $answer);
        $answer = preg_replace('/\n\s*Resumo\s*Resumo\b/u', "\n\nResumo", $answer);
        $answer = preg_replace('/^Explicação(?=\S)/u', "Explicação\n\n", $answer);
        $answer = preg_replace('/(?<!\n)Exemplo(?=\S)/u', "\n\nExemplo\n\n", $answer);
        $answer = preg_replace('/(?<!\n)Resumo(?=\S)/u', "\n\nResumo\n\n", $answer);
        $answer = preg_replace('/(Explicação)(Exemplo|Resumo)/u', "$1\n\n$2", $answer);
        $answer = preg_replace('/(Exemplo)(Resumo)/u', "$1\n\n$2", $answer);
        $answer = preg_replace("/(?<!\n)(Explicação|Exemplo|Resumo)\b/u", "\n\n$1", $answer);
        $answer = preg_replace("/\n{3,}/u", "\n\n", $answer);
        $answer = preg_replace('/^(Explicação)\s*\n+\1\b/u', '$1', $answer);

        $hasExplanation = preg_match('/(^|\n)Explicação(\n|$)/u', $answer) === 1;
        if (!$hasExplanation) {
            $answer = "Explicação\n\n" . ltrim($answer);
        }

        return trim($answer);
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

        try {
            $this->pdo->exec('ALTER TABLE lesson_ai_logs ADD COLUMN cache_key VARCHAR(64) NULL AFTER error_message');
        } catch (Throwable $e) {
        }

        try {
            $this->pdo->exec('CREATE INDEX idx_lesson_ai_logs_cache_key ON lesson_ai_logs (lesson_id, cache_key, created_at)');
        } catch (Throwable $e) {
        }
    }

    private function logApiError(string $message): void
    {
        if (function_exists('registrar_log')) {
            registrar_log('lesson_ai_api_error', $message);
        }
    }

    private function mapTutorErrorMessage(string $message, int $statusCode): string
    {
        if ($statusCode === 429 || $this->isQuotaExceededError($message)) {
            return self::API_QUOTA_MESSAGE;
        }

        return self::FALLBACK_MESSAGE;
    }

    private function isQuotaExceededError(string $message): bool
    {
        $message = mb_strtolower(trim($message), 'UTF-8');
        return $message !== ''
            && (strpos($message, 'quota exceeded') !== false || strpos($message, 'rate limit') !== false);
    }

    private function shouldFailoverGroqKey(int $statusCode, string $message): bool
    {
        if (in_array($statusCode, self::GROQ_FAILOVER_STATUSES, true)) {
            return true;
        }

        $normalized = mb_strtolower(trim($message), 'UTF-8');
        return $normalized !== ''
            && (
                strpos($normalized, 'rate limit') !== false
                || strpos($normalized, 'quota') !== false
                || strpos($normalized, 'timeout') !== false
                || strpos($normalized, 'tempor') !== false
                || strpos($normalized, 'overloaded') !== false
            );
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

    private function buildCacheKey(int $lessonId, string $question, string $context, string $historyContext = ''): string
    {
        return hash('sha256', $lessonId . '|' . $question . '|' . $context . '|' . $historyContext);
    }

    private function sanitizeHistory(array $history): array
    {
        $normalized = [];

        foreach ($history as $item) {
            if (!is_array($item)) {
                continue;
            }

            $role = mb_strtolower(trim((string)($item['role'] ?? '')), 'UTF-8');
            $text = $this->normalizeContent((string)($item['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            if (!in_array($role, ['user', 'ai', 'assistant'], true)) {
                continue;
            }

            $normalized[] = [
                'role' => $role === 'assistant' ? 'ai' : $role,
                'text' => mb_substr($text, 0, self::HISTORY_MESSAGE_MAX_LENGTH),
            ];
        }

        if (count($normalized) > self::HISTORY_MESSAGE_LIMIT) {
            $normalized = array_slice($normalized, -self::HISTORY_MESSAGE_LIMIT);
        }

        return array_values($normalized);
    }

    private function buildHistoryContext(array $history): string
    {
        if ($history === []) {
            return '';
        }

        $lines = [];
        foreach ($history as $item) {
            $label = ($item['role'] ?? '') === 'user' ? 'Aluno' : 'Assistente';
            $lines[] = $label . ': ' . (string)($item['text'] ?? '');
        }

        return implode("\n", $lines);
    }

    private function findRecentDuplicateQuestion(int $userId, int $lessonId, string $question): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT status, answer_text, created_at
             FROM lesson_ai_logs
             WHERE user_id = ?
               AND lesson_id = ?
               AND question_text = ?
               AND created_at >= (NOW() - INTERVAL ? SECOND)
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([$userId, $lessonId, $question, self::DUPLICATE_QUESTION_WINDOW_SECONDS]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($result) ? $result : null;
    }

    private function findCachedAnswer(int $lessonId, string $cacheKey): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT answer_text, created_at
             FROM lesson_ai_logs
             WHERE lesson_id = ?
               AND cache_key = ?
               AND status IN ("success", "cached_hit")
               AND answer_text IS NOT NULL
               AND answer_text <> ""
               AND created_at >= (NOW() - INTERVAL ? SECOND)
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([$lessonId, $cacheKey, self::CACHE_TTL_SECONDS]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($result) ? $result : null;
    }
}
