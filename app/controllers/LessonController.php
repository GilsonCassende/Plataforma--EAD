<?php

/**
 * Controller: LessonController
 * Gerencia operações com aulas
 */

class LessonController
{
    private $pdo;
    private $lessonModel;
    private $courseModel;
    private $moduleModel;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../models/Lesson.php';
        require_once __DIR__ . '/../models/Course.php';
        require_once __DIR__ . '/../models/Module.php';
        $this->lessonModel = new Lesson($pdo);
        $this->courseModel = new Course($pdo);
        $this->moduleModel = new Module($pdo);
    }

    /**
     * Criar aula (professor dono do curso)
     */
    public function criar($course_id, $titulo, $descricao, $tipo, $conteudo, $url_arquivo = null, $video_id = null, $module_id = null, $resumo = null, $audio_url = null, $audio_storage_disk = null, $audio_storage_key = null)
    {
        $usuario = $_SESSION['usuario'] ?? null;
        $curso = $this->courseModel->obterPorId($course_id);

        if (!$curso || $curso['teacher_id'] != $usuario['id']) {
            return ['sucesso' => false, 'mensagem' => 'Você não tem permissão'];
        }

        if (empty($titulo) || empty($conteudo)) {
            return ['sucesso' => false, 'mensagem' => 'Título e conteúdo obrigatórios'];
        }

        $this->moduleModel->sincronizarCurso((int)$course_id, (string)($curso['titulo'] ?? ''), (string)($curso['course_structure'] ?? 'single_module'));
        $module_id = $this->resolverModuloAula((int)$course_id, $module_id, $curso);
        if ($module_id <= 0) {
            return ['sucesso' => false, 'mensagem' => 'Selecione um módulo válido para a aula'];
        }

        // Obter próxima ordem
        $aulas = $this->lessonModel->listarPorModulo($module_id);
        $ordem = count($aulas) + 1;

        return $this->lessonModel->criar($course_id, $titulo, $descricao, $tipo, $conteudo, $url_arquivo, $video_id, $ordem, $module_id, $resumo, $audio_url, $audio_storage_disk, $audio_storage_key);
    }

    /**
     * Obter aula
     */
    public function obter($lesson_id)
    {
        $lesson_id = (int)$lesson_id;
        if ($lesson_id <= 0) {
            return ['sucesso' => false, 'mensagem' => 'Aula inválida'];
        }

        $aula = $this->lessonModel->obterPorId($lesson_id);

        if (!$aula) {
            return ['sucesso' => false, 'mensagem' => 'Aula não encontrada'];
        }

        return ['sucesso' => true, 'aula' => $aula];
    }

    /**
     * Listar aulas de um curso
     */
    public function listarPorCurso($course_id)
    {
        return $this->lessonModel->listarPorCurso($course_id);
    }

    /**
     * Marcar aula como assistida
     */
    public function marcarConcluida($lesson_id)
    {
        $usuario = $_SESSION['usuario'] ?? null;
        $userId = (int)($usuario['id'] ?? 0);

        if (!$usuario) {
            return ['sucesso' => false, 'mensagem' => 'Faça login'];
        }

        try {
            // Obter a aula e o curso
            $aula = $this->lessonModel->obterPorId($lesson_id);
            if (!$aula) {
                return ['sucesso' => false, 'mensagem' => 'Aula não encontrada'];
            }

            $course_id = $aula['course_id'];

            // Marcar aula como concluída
            $stmt = $this->pdo->prepare(
                'INSERT INTO lesson_progress (user_id, lesson_id, concluida, data_conclusao) 
                 VALUES (?, ?, 1, NOW())
                 ON DUPLICATE KEY UPDATE concluida = 1, data_conclusao = NOW()'
            );
            $stmt->execute([$usuario['id'], $lesson_id]);

            $novo_progresso = $this->recalcularProgressoCurso($userId, (int)$course_id);
            $certificateController = new CertificateController($this->pdo);
            $certificateSync = $certificateController->syncCourseCertificates($userId, (int)$course_id);

            return [
                'sucesso' => true,
                'mensagem' => 'Aula marcada como concluída',
                'progress' => $novo_progresso,
                'certificate_events' => $certificateSync['issued'] ?? [],
            ];
        } catch (Exception $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

    /**
     * Desmarcar aula como concluída (toggle)
     */
    public function desmarcarConcluida($lesson_id)
    {
        $usuario = $_SESSION['usuario'] ?? null;
        $userId = (int)($usuario['id'] ?? 0);

        if (!$usuario) {
            return ['sucesso' => false, 'mensagem' => 'Faça login'];
        }

        try {
            $aula = $this->lessonModel->obterPorId($lesson_id);
            if (!$aula) return ['sucesso' => false, 'mensagem' => 'Aula não encontrada'];

            $course_id = $aula['course_id'];

            // Inserir ou atualizar para concluida = 0
            $stmt = $this->pdo->prepare(
                'INSERT INTO lesson_progress (user_id, lesson_id, concluida, data_conclusao) 
                 VALUES (?, ?, 0, NULL)
                 ON DUPLICATE KEY UPDATE concluida = 0, data_conclusao = NULL'
            );
            $stmt->execute([$usuario['id'], $lesson_id]);

            $novo_progresso = $this->recalcularProgressoCurso($userId, (int)$course_id);
            $certificateController = new CertificateController($this->pdo);
            $certificateSync = $certificateController->syncCourseCertificates($userId, (int)$course_id);

            return [
                'sucesso' => true,
                'mensagem' => 'Aula desmarcada como concluída',
                'progress' => $novo_progresso,
                'certificate_events' => $certificateSync['issued'] ?? [],
            ];
        } catch (Exception $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

    /**
     * Atualizar aula
     */
    public function atualizar($lesson_id, $titulo, $descricao, $tipo, $conteudo, $url_arquivo = null, $video_id = null, $module_id = null, $resumo = null, $audio_url = null, $audio_storage_disk = null, $audio_storage_key = null)
    {
        $aula = $this->lessonModel->obterPorId($lesson_id);
        if (!$aula) {
            return ['sucesso' => false, 'mensagem' => 'Aula não encontrada'];
        }

        $curso = $this->courseModel->obterPorId($aula['course_id']);
        $usuario = $_SESSION['usuario'] ?? null;

        if (!$curso || $curso['teacher_id'] != $usuario['id']) {
            return ['sucesso' => false, 'mensagem' => 'Você não tem permissão'];
        }

        $this->moduleModel->sincronizarCurso((int)$aula['course_id'], (string)($curso['titulo'] ?? ''), (string)($curso['course_structure'] ?? 'single_module'));
        $module_id = $this->resolverModuloAula((int)$aula['course_id'], $module_id ?? ($aula['module_id'] ?? 0), $curso);
        $resolvedResumo = $resumo !== null ? $resumo : ($aula['resumo'] ?? null);
        $resolvedAudioUrl = $audio_url !== null ? $audio_url : ($aula['audio_url'] ?? null);
        $resolvedAudioStorageDisk = $audio_storage_disk !== null ? $audio_storage_disk : ($aula['audio_storage_disk'] ?? null);
        $resolvedAudioStorageKey = $audio_storage_key !== null ? $audio_storage_key : ($aula['audio_storage_key'] ?? null);
        $audioChanged = $audio_url !== null || $audio_storage_key !== null;

        if ($this->lessonModel->atualizar($lesson_id, $titulo, $descricao, $tipo, $conteudo, $url_arquivo, $video_id, null, $module_id, $resolvedResumo, $resolvedAudioUrl, $resolvedAudioStorageDisk, $resolvedAudioStorageKey)) {
            if ($audioChanged) {
                $mediaService = new LessonMediaService();
                $mediaService->cleanupLessonMedia($aula, ['remove_audio' => true]);
            }
            return ['sucesso' => true, 'mensagem' => 'Aula atualizada com sucesso'];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao atualizar aula'];
    }

    /**
     * Deletar aula
     */
    public function deletar($lesson_id)
    {
        $aula = $this->lessonModel->obterPorId($lesson_id);
        if (!$aula) {
            return ['sucesso' => false, 'mensagem' => 'Aula não encontrada'];
        }

        $curso = $this->courseModel->obterPorId($aula['course_id']);
        $usuario = $_SESSION['usuario'] ?? null;

        if (!$curso || $curso['teacher_id'] != $usuario['id']) {
            return ['sucesso' => false, 'mensagem' => 'Você não tem permissão'];
        }

        if ($this->lessonModel->deletar($lesson_id)) {
            $mediaService = new LessonMediaService();
            $mediaService->cleanupLessonMedia($aula, ['remove_audio' => true]);
            return ['sucesso' => true, 'mensagem' => 'Aula deletada com sucesso'];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao deletar aula'];
    }

    private function resolverModuloAula($courseId, $moduleId, array $curso)
    {
        $moduleId = (int)$moduleId;
        if ($moduleId > 0) {
            $module = $this->moduleModel->obterPorId($moduleId);
            if ($module && (int)($module['course_id'] ?? 0) === (int)$courseId) {
                return $moduleId;
            }
        }

        $defaultModule = $this->moduleModel->obterOuCriarModuloPadrao((int)$courseId, (string)($curso['titulo'] ?? ''));
        return (int)($defaultModule['id'] ?? 0);
    }

    private function recalcularProgressoCurso($userId, $courseId)
    {
        $aulas = $this->lessonModel->listarPorCurso($courseId);
        $aulasTotais = count($aulas);

        if ($aulasTotais === 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE enrollments SET progress = 0, data_conclusao = NULL WHERE user_id = ? AND course_id = ?'
            );
            $stmt->execute([$userId, $courseId]);
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) as concluidas FROM lesson_progress
             WHERE user_id = ? AND concluida = 1
             AND lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)'
        );
        $stmt->execute([$userId, $courseId]);
        $resultado = $stmt->fetch();
        $aulasConcluidas = (int)($resultado['concluidas'] ?? 0);
        $lessonProgress = (int)floor(($aulasConcluidas / $aulasTotais) * 100);

        require_once __DIR__ . '/../models/Quiz.php';
        $quizModel = new Quiz($this->pdo);
        $hasQuizzes = count($quizModel->listarPorCurso($courseId)) > 0;
        $quizProgress = $quizModel->calcularProgressoAvaliacaoCurso($courseId, $userId);
        $eligibilidade = $quizModel->alunoAptoConclusao($courseId, $userId, $lessonProgress);

        $novoProgresso = $hasQuizzes
            ? (int)floor((($lessonProgress * 0.6) + ($quizProgress * 0.4)))
            : $lessonProgress;

        if (!empty($eligibilidade['aprovado'])) {
            $novoProgresso = 100;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE enrollments
             SET progress = ?,
                 data_conclusao = CASE WHEN ? = 100 THEN NOW() ELSE NULL END
             WHERE user_id = ? AND course_id = ?'
        );
        $stmt->execute([$novoProgresso, $novoProgresso, $userId, $courseId]);

        return $novoProgresso;
    }
}
