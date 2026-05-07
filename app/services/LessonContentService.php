<?php

class LessonContentService
{
    private PDO $pdo;
    private Lesson $lessonModel;
    private Enrollment $enrollmentModel;
    private LessonAiTutorService $lessonAiTutorService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->lessonModel = new Lesson($pdo);
        $this->enrollmentModel = new Enrollment($pdo);
        $this->lessonAiTutorService = new LessonAiTutorService($pdo);
    }

    public function getLessonContent(array $user, int $lessonId, bool $generateIfMissing = false): array
    {
        if ($lessonId <= 0) {
            return ['sucesso' => false, 'mensagem' => 'Aula inválida.', 'status_code' => 400];
        }

        $lesson = $this->lessonModel->obterPorId($lessonId);
        if (!$lesson) {
            return ['sucesso' => false, 'mensagem' => 'Aula não encontrada.', 'status_code' => 404];
        }

        $access = $this->validateLessonAccess($user, $lesson);
        if (empty($access['sucesso'])) {
            return $access;
        }

        $cachedContent = trim((string)($lesson['lesson_ai_content'] ?? ''));
        if ($cachedContent !== '') {
            return [
                'sucesso' => true,
                'conteudo' => $cachedContent,
                'cache' => true,
                'generated_at' => $lesson['lesson_ai_generated_at'] ?? null,
                'aula' => $lesson,
            ];
        }

        if (!$generateIfMissing) {
            return [
                'sucesso' => true,
                'conteudo' => '',
                'cache' => false,
                'generated_at' => null,
                'aula' => $lesson,
            ];
        }

        $generationResult = $this->lessonAiTutorService->generateStructuredLessonContent($lesson);
        if (empty($generationResult['sucesso'])) {
            return [
                'sucesso' => false,
                'mensagem' => 'Não foi possível gerar o conteúdo inteligente desta aula no momento.',
                'status_code' => (int)($generationResult['status_code'] ?? 503),
                'detalhe' => $generationResult['mensagem'] ?? null,
            ];
        }

        $content = trim((string)($generationResult['answer'] ?? ''));
        $generatedAt = date('Y-m-d H:i:s');
        $saved = $this->lessonModel->atualizarConteudoInteligente($lessonId, $content, $generatedAt);
        if (!$saved) {
            return [
                'sucesso' => false,
                'mensagem' => 'O conteúdo foi gerado, mas não pôde ser salvo no momento.',
                'status_code' => 500,
            ];
        }

        $lesson['lesson_ai_content'] = $content;
        $lesson['lesson_ai_generated_at'] = $generatedAt;

        if (function_exists('registrar_log')) {
            registrar_log('lesson_ai_content_generated', 'Conteúdo inteligente gerado para aula ' . $lessonId, (int)($user['id'] ?? 0));
        }

        return [
            'sucesso' => true,
            'conteudo' => $content,
            'cache' => false,
            'generated' => true,
            'generated_at' => $generatedAt,
            'aula' => $lesson,
        ];
    }

    private function validateLessonAccess(array $user, array $lesson): array
    {
        $userId = (int)($user['id'] ?? 0);
        $role = (string)($user['role'] ?? '');
        $courseId = (int)($lesson['course_id'] ?? 0);

        if ($userId <= 0) {
            return ['sucesso' => false, 'mensagem' => 'Faça login para acessar esta aula.', 'status_code' => 401];
        }

        if ($role === 'admin') {
            return ['sucesso' => true];
        }

        if ($role === 'professor') {
            $stmt = $this->pdo->prepare('SELECT teacher_id FROM courses WHERE id = ? LIMIT 1');
            $stmt->execute([$courseId]);
            if ((int)$stmt->fetchColumn() === $userId) {
                return ['sucesso' => true];
            }
        }

        if ($this->enrollmentModel->estaMatriculado($userId, $courseId)) {
            return ['sucesso' => true];
        }

        return ['sucesso' => false, 'mensagem' => 'Você não tem acesso a esta aula.', 'status_code' => 403];
    }
}
