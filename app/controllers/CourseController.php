<?php
/**
 * Controller: CourseController
 * Gerencia operações com cursos
 */

class CourseController {
    private $pdo;
    private $courseModel;
    private $enrollmentModel;
    private $moduleModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../models/Course.php';
        require_once __DIR__ . '/../models/Enrollment.php';
        require_once __DIR__ . '/../models/Module.php';
        $this->courseModel = new Course($pdo);
        $this->enrollmentModel = new Enrollment($pdo);
        $this->moduleModel = new Module($pdo);
    }

    /**
     * Criar curso (professor)
     */
    public function criar($titulo, $descricao, $categoria, $thumbnail = null, $courseStructure = 'single_module') {
        $usuario = $_SESSION['usuario'] ?? null;

        if (!$usuario || $usuario['role'] !== 'professor') {
            return ['sucesso' => false, 'mensagem' => 'Apenas professores podem criar cursos'];
        }

        if (empty($titulo) || empty($descricao)) {
            return ['sucesso' => false, 'mensagem' => 'Título e descrição são obrigatórios'];
        }

        $courseStructure = trim((string)$courseStructure) === 'multi_module' ? 'multi_module' : 'single_module';
        $result = $this->courseModel->criar($titulo, $descricao, $usuario['id'], $categoria, $thumbnail, 'ativo', $courseStructure);

        if (!empty($result['sucesso']) && !empty($result['id'])) {
            $courseId = (int)$result['id'];
            $this->moduleModel->sincronizarCurso($courseId, $titulo, $courseStructure);
            if ($courseStructure === 'single_module') {
                $this->moduleModel->obterOuCriarModuloPadrao($courseId, $titulo);
            }
        }

        return $result;
    }

    /**
     * Listar todos os cursos (página inicial)
     */
    public function listar($pagina = 1, $por_pagina = 12) {
        $offset = ($pagina - 1) * $por_pagina;
        $cursos = $this->courseModel->listar('ativo', $por_pagina, $offset);
        $total = $this->courseModel->contar('ativo');
        $total_paginas = ceil($total / $por_pagina);

        return [
            'cursos' => $cursos,
            'pagina' => $pagina,
            'total_paginas' => $total_paginas,
            'total' => $total
        ];
    }

    /**
     * Buscar cursos
     */
    public function buscar($termo) {
        if (strlen($termo) < 3) {
            return ['sucesso' => false, 'mensagem' => 'Digite pelo menos 3 caracteres'];
        }

        $cursos = $this->courseModel->buscar($termo);
        return ['sucesso' => true, 'cursos' => $cursos];
    }

    /**
     * Obter detalhes do curso
     */
    public function obter($course_id) {
        $curso = $this->courseModel->obterPorId($course_id);

        if (!$curso) {
            return ['sucesso' => false, 'mensagem' => 'Curso não encontrado'];
        }

        require_once __DIR__ . '/../models/Lesson.php';
        require_once __DIR__ . '/../models/Quiz.php';
        $lessonModel = new Lesson($this->pdo);
        $quizModel = new Quiz($this->pdo);

        $courseStructure = trim((string)($curso['course_structure'] ?? 'single_module'));
        if ($courseStructure !== 'multi_module') {
            $courseStructure = 'single_module';
        }

        $this->moduleModel->sincronizarCurso((int)$course_id, (string)($curso['titulo'] ?? ''), $courseStructure);

        $curso['aulas'] = $lessonModel->listarPorCurso($course_id);
        $curso['quizzes'] = $quizModel->listarPorCurso((int)$course_id);
        $curso['modulos'] = $this->montarEstruturaModular($curso, $curso['aulas'], $curso['quizzes']);
        $curso['quizzes_finais'] = array_values(array_filter($curso['quizzes'], static function ($quiz) {
            return ($quiz['tipo'] ?? '') === 'final';
        }));
        $curso['course_structure'] = $courseStructure;
        $curso['course_structure_label'] = $courseStructure === 'multi_module' ? 'Múltiplos módulos' : 'Módulo único';

        $sessionUser = $_SESSION['usuario'] ?? null;
        $currentUserId = (int)($sessionUser['id'] ?? 0);
        if ($currentUserId > 0) {
            require_once __DIR__ . '/../models/Certificate.php';
            $certificateModel = new Certificate($this->pdo);
            $certificateModel->syncCourseCertificates($currentUserId, (int)$course_id);
            $curso['certificate_summary'] = $certificateModel->buildEligibilitySnapshot($currentUserId, (int)$course_id);
            $curso['certificates'] = $certificateModel->listUserCertificatesForCourse($currentUserId, (int)$course_id);
        } else {
            $curso['certificate_summary'] = null;
            $curso['certificates'] = ['course' => null, 'modules' => []];
        }

        // Contar alunos
        $curso['total_alunos'] = $this->enrollmentModel->contarAlunos($course_id);

        return ['sucesso' => true, 'curso' => $curso];
    }

    /**
     * Matricular aluno em curso
     */
    public function matricular($course_id) {
        $usuario = $_SESSION['usuario'] ?? null;

        if (!$usuario) {
            return ['sucesso' => false, 'mensagem' => 'Faça login para se matricular'];
        }

        $curso = $this->courseModel->obterPorId($course_id);
        if (!$curso) {
            return ['sucesso' => false, 'mensagem' => 'Curso não encontrado'];
        }

        return $this->enrollmentModel->matricular($usuario['id'], $course_id);
    }

    /**
     * Atualizar curso (professor dono)
     */
    public function atualizar($course_id, $titulo, $descricao, $categoria, $status = null) {
        $usuario = $_SESSION['usuario'] ?? null;
        $curso = $this->courseModel->obterPorId($course_id);

        if (!$curso) {
            return ['sucesso' => false, 'mensagem' => 'Curso não encontrado'];
        }

        $ehAdmin = ($usuario['role'] ?? '') === 'admin';
        $ehDono = (int)($curso['teacher_id'] ?? 0) === (int)($usuario['id'] ?? 0);

        if (!$ehAdmin && !$ehDono) {
            return ['sucesso' => false, 'mensagem' => 'Você não tem permissão para atualizar este curso'];
        }

        if ($this->courseModel->atualizar($course_id, $titulo, $descricao, $categoria, $status)) {
            return ['sucesso' => true, 'mensagem' => 'Curso atualizado com sucesso'];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao atualizar curso'];
    }

    /**
     * Deletar curso (professor dono ou admin)
     */
    public function deletar($course_id) {
        $usuario = $_SESSION['usuario'] ?? null;
        $curso = $this->courseModel->obterPorId($course_id);

        if (!$curso) {
            return ['sucesso' => false, 'mensagem' => 'Curso não encontrado'];
        }

        $ehDono = $curso['teacher_id'] == $usuario['id'];
        $ehAdmin = $usuario['role'] === 'admin';

        if (!$ehDono && !$ehAdmin) {
            return ['sucesso' => false, 'mensagem' => 'Você não tem permissão para deletar este curso'];
        }

        if ($this->courseModel->deletar($course_id)) {
            return ['sucesso' => true, 'mensagem' => 'Curso deletado com sucesso'];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao deletar curso'];
    }

    /**
     * Listar cursos do professor
     */
    public function listarMeusCursos() {
        $usuario = $_SESSION['usuario'] ?? null;

        if (!$usuario || $usuario['role'] !== 'professor') {
            return ['sucesso' => false, 'mensagem' => 'Apenas professores podem acessar'];
        }

        return $this->courseModel->listarPorProfessor($usuario['id']);
    }

    private function montarEstruturaModular(array $curso, array $aulas, array $quizzes)
    {
        $modules = $this->moduleModel->listarPorCurso((int)($curso['id'] ?? 0));
        $sessionUser = $_SESSION['usuario'] ?? [];
        $userId = (int)($sessionUser['id'] ?? 0);
        $isOwner = $userId > 0 && (int)($curso['teacher_id'] ?? 0) === $userId;
        $isStudentContext = $userId > 0 && !$isOwner && (($sessionUser['role'] ?? '') === 'aluno');
        $quizModel = null;
        $mandatoryLessonQuizApprovalMap = [];

        $completedLessonIds = [];
        if ($isStudentContext && !empty($aulas)) {
            $quizModel = new Quiz($this->pdo);
            $lessonIds = array_map(static function ($lesson) {
                return (int)($lesson['id'] ?? 0);
            }, $aulas);
            $placeholders = implode(',', array_fill(0, count($lessonIds), '?'));
            $stmt = $this->pdo->prepare(
                "SELECT lesson_id
                 FROM lesson_progress
                 WHERE user_id = ? AND concluida = 1 AND lesson_id IN ($placeholders)"
            );
            $stmt->execute(array_merge([$userId], $lessonIds));
            $completedLessonIds = array_map('intval', array_column($stmt->fetchAll(), 'lesson_id'));
            $mandatoryLessonQuizApprovalMap = $quizModel->getMandatoryLessonQuizApprovalMap((int)($curso['id'] ?? 0), $userId);
        }

        $moduleMap = [];
        $lessonIndexMap = [];
        foreach ($modules as $index => $module) {
            $moduleId = (int)$module['id'];
            $moduleMap[$moduleId] = $module;
            $moduleMap[$moduleId]['position'] = $index + 1;
            $moduleMap[$moduleId]['lessons'] = [];
            $moduleMap[$moduleId]['module_quizzes'] = [];
            $moduleMap[$moduleId]['lesson_quizzes_count'] = 0;
            $moduleMap[$moduleId]['completed_lessons'] = 0;
            $moduleMap[$moduleId]['total_lessons'] = 0;
            $moduleMap[$moduleId]['unlocked'] = $index === 0 || !$isStudentContext;
            $moduleMap[$moduleId]['completed'] = false;
        }

        foreach ($aulas as $index => $lesson) {
            $moduleId = (int)($lesson['module_id'] ?? 0);
            if (!isset($moduleMap[$moduleId])) {
                continue;
            }
            $lesson['position'] = $index + 1;
            $lessonId = (int)($lesson['id'] ?? 0);
            $lesson['is_completed'] = in_array($lessonId, $completedLessonIds, true)
                && (!array_key_exists($lessonId, $mandatoryLessonQuizApprovalMap) || !empty($mandatoryLessonQuizApprovalMap[$lessonId]));
            $lesson['lesson_quizzes'] = [];
            $moduleMap[$moduleId]['lessons'][] = $lesson;
            $lessonIndexMap[$lessonId] = [
                'module_id' => $moduleId,
                'lesson_index' => count($moduleMap[$moduleId]['lessons']) - 1,
            ];
            $moduleMap[$moduleId]['total_lessons']++;
            if ($lesson['is_completed']) {
                $moduleMap[$moduleId]['completed_lessons']++;
            }
        }

        foreach ($quizzes as $quiz) {
            if (($quiz['tipo'] ?? '') === 'final') {
                continue;
            }
            if ($isStudentContext) {
                if ($quizModel === null) {
                    require_once __DIR__ . '/../models/Quiz.php';
                    $quizModel = new Quiz($this->pdo);
                }
                $quiz['melhor_resultado'] = $quizModel->obterMelhorResultado($userId, (int)($quiz['id'] ?? 0));
                $quiz['aprovado_aluno'] = !empty($quiz['melhor_resultado']) &&
                    (float)($quiz['melhor_resultado']['pontuacao'] ?? 0) >= (float)($quiz['nota_minima'] ?? 10);
            }

            if (($quiz['tipo'] ?? '') === 'aula') {
                $lessonId = (int)($quiz['lesson_id'] ?? 0);
                if (!isset($lessonIndexMap[$lessonId])) {
                    continue;
                }

                $lessonRef = $lessonIndexMap[$lessonId];
                $moduleMap[$lessonRef['module_id']]['lessons'][$lessonRef['lesson_index']]['lesson_quizzes'][] = $quiz;
                $moduleMap[$lessonRef['module_id']]['lesson_quizzes_count']++;
                continue;
            }

            $moduleId = (int)($quiz['module_id'] ?? 0);
            if (!isset($moduleMap[$moduleId])) {
                continue;
            }
            $moduleMap[$moduleId]['module_quizzes'][] = $quiz;
        }

        $previousCompleted = true;
        foreach ($moduleMap as $moduleId => &$module) {
            if ($isStudentContext) {
                $module['unlocked'] = $previousCompleted;
            }

            $lessonsCompleted = (int)$module['total_lessons'] > 0
                && (int)$module['completed_lessons'] === (int)$module['total_lessons'];
            $module['quiz_unlocked'] = !$isStudentContext || !empty($module['unlocked']) && ($lessonsCompleted || (int)$module['total_lessons'] === 0);

            $moduleQuizPassed = true;
            if (!empty($module['module_quizzes']) && $isStudentContext) {
                foreach ($module['module_quizzes'] as $quiz) {
                    if (empty($quiz['aprovado_aluno'])) {
                        $moduleQuizPassed = false;
                        break;
                    }
                }
            }

            $module['completed'] = $lessonsCompleted && $moduleQuizPassed;
            $module['progress_percent'] = (int)$module['total_lessons'] > 0
                ? (int)floor(((int)$module['completed_lessons'] / (int)$module['total_lessons']) * 100)
                : 0;
            $previousCompleted = $module['completed'];
        }
        unset($module);

        return array_values($moduleMap);
    }
}
?>
