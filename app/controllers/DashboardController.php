<?php

/**
 * Controller: DashboardController
 * Gerencia painéis de aluno, professor e admin
 */

class DashboardController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Dashboard do Aluno
     */
    public function dashboardAluno()
    {
        try {
            $usuario = $_SESSION['usuario'] ?? null;
            $userId = (int)($usuario['id'] ?? 0);

            require_once __DIR__ . '/../models/Enrollment.php';
            $enrollmentModel = new Enrollment($this->pdo);
            $cursos = $enrollmentModel->obterCursosAluno($userId);

            $em_progresso = 0;
            $concluidos = 0;

            require_once __DIR__ . '/../models/Lesson.php';
            $lessonModel = new Lesson($this->pdo);

            foreach ($cursos as &$curso) {
                try {
                    $progressState = $this->calcularEstadoProgressoCurso($userId, (int)$curso['id'], $lessonModel);
                } catch (Throwable $exception) {
                    if (function_exists('registrar_log')) {
                        registrar_log('warning', 'dashboard_aluno fallback curso=' . (int)($curso['id'] ?? 0) . ' erro=' . $exception->getMessage(), $userId);
                    }
                    $progressState = $this->calcularEstadoProgressoSomenteAulas($userId, (int)$curso['id'], $lessonModel);
                }

                $curso['progress'] = $progressState['progress'];
                $curso['next_lesson_id'] = $progressState['next_lesson_id'];
                $curso['aulas_totais'] = $progressState['total_lessons'];
                $curso['aulas_concluidas'] = $progressState['completed_lessons'];

                if ((int)$curso['progress'] === 100) {
                    $concluidos++;
                } elseif ((int)$curso['progress'] > 0) {
                    $em_progresso++;
                }
            }
            unset($curso);

            return [
                'cursos' => $cursos,
                'total_cursos' => count($cursos),
                'em_progresso' => $em_progresso,
                'concluidos' => $concluidos,
                'usuario' => $usuario
            ];
        } catch (Throwable $exception) {
            $this->logDashboardError('dashboardAluno', $exception);
            return [
                'cursos' => [],
                'total_cursos' => 0,
                'em_progresso' => 0,
                'concluidos' => 0,
                'usuario' => $_SESSION['usuario'] ?? null
            ];
        }
    }

    /**
     * Dashboard do Professor
     */
    public function dashboardProfessor()
    {
        try {
            $usuario = $_SESSION['usuario'] ?? null;

            require_once __DIR__ . '/../models/Course.php';
            require_once __DIR__ . '/../models/Enrollment.php';
            require_once __DIR__ . '/../models/Lesson.php';
            $courseModel = new Course($this->pdo);
            $enrollmentModel = new Enrollment($this->pdo);
            $lessonModel = new Lesson($this->pdo);

            $cursos = $courseModel->listarPorProfessor($usuario['id']);
            $total_alunos = 0;
            $total_aulas = 0;
            $total_quizzes = 0;
            $media_quiz_soma = 0.0;
            $media_quiz_count = 0;
            $total_alunos_avaliados = 0;
            $total_alunos_aprovados = 0;
            $desempenho_cursos = [];
            $perguntas_criticas = [];

            foreach ($cursos as &$curso) {
                $curso['total_alunos'] = $enrollmentModel->contarAlunos($curso['id']);
                $total_alunos += $curso['total_alunos'];
                $aulas = $lessonModel->listarPorCurso($curso['id']);
                $curso['total_aulas'] = count($aulas);
                $total_aulas += $curso['total_aulas'];

                $curso['total_quizzes'] = 0;
                $curso['media_quiz'] = 0;
                $curso['alunos_avaliados'] = 0;
                $curso['taxa_aprovacao_quiz'] = 0;

                try {
                    require_once __DIR__ . '/../models/Quiz.php';
                    $quizModel = new Quiz($this->pdo);

                    $quizzes = $quizModel->listarPorCurso((int)$curso['id']);
                    $curso['total_quizzes'] = count($quizzes);
                    $total_quizzes += $curso['total_quizzes'];

                    $resumoQuiz = $quizModel->obterResumoProfessorCurso((int)$curso['id']);
                    $curso['media_quiz'] = (float)($resumoQuiz['media_turma'] ?? 0);
                    if ($curso['media_quiz'] > 0) {
                        $media_quiz_soma += $curso['media_quiz'];
                        $media_quiz_count++;
                    }

                    $desempenhoAlunos = $quizModel->listarDesempenhoCursoProfessor((int)$curso['id']);
                    $alunosAvaliadosCurso = 0;
                    $alunosAprovadosCurso = 0;

                    foreach ($desempenhoAlunos as $aluno) {
                        $melhorPercentual = (float)($aluno['melhor_percentual'] ?? 0);
                        if ($melhorPercentual <= 0) {
                            continue;
                        }

                        $alunosAvaliadosCurso++;
                        $total_alunos_avaliados++;

                        if ($melhorPercentual >= 70) {
                            $alunosAprovadosCurso++;
                            $total_alunos_aprovados++;
                        }
                    }

                    $curso['alunos_avaliados'] = $alunosAvaliadosCurso;
                    $curso['taxa_aprovacao_quiz'] = $alunosAvaliadosCurso > 0
                        ? (int)round(($alunosAprovadosCurso / $alunosAvaliadosCurso) * 100)
                        : 0;

                    foreach (($resumoQuiz['perguntas_criticas'] ?? []) as $pergunta) {
                        $perguntas_criticas[] = [
                            'curso_titulo' => (string)($curso['titulo'] ?? 'Curso'),
                            'texto' => (string)($pergunta['texto'] ?? ''),
                            'erros' => (int)($pergunta['erros'] ?? 0),
                            'total_respostas' => (int)($pergunta['total_respostas'] ?? 0)
                        ];
                    }
                } catch (Throwable $exception) {
                    $curso['total_quizzes'] = 0;
                    $curso['media_quiz'] = 0;
                    $curso['alunos_avaliados'] = 0;
                    $curso['taxa_aprovacao_quiz'] = 0;
                }

                $desempenho_cursos[] = [
                    'id' => (int)$curso['id'],
                    'titulo' => (string)($curso['titulo'] ?? 'Curso sem título'),
                    'media_quiz' => (float)$curso['media_quiz'],
                    'total_quizzes' => (int)$curso['total_quizzes'],
                    'alunos_avaliados' => (int)$curso['alunos_avaliados'],
                    'taxa_aprovacao_quiz' => (int)$curso['taxa_aprovacao_quiz']
                ];
            }
            unset($curso);

            usort($desempenho_cursos, static function ($a, $b) {
                return [$b['media_quiz'], $b['alunos_avaliados']] <=> [$a['media_quiz'], $a['alunos_avaliados']];
            });

            usort($perguntas_criticas, static function ($a, $b) {
                return [$b['erros'], $b['total_respostas']] <=> [$a['erros'], $a['total_respostas']];
            });

            return [
                'cursos' => $cursos,
                'total_cursos' => count($cursos),
                'total_alunos' => $total_alunos,
                'total_aulas' => $total_aulas,
                'total_atividades' => min(count($cursos), 8),
                'total_quizzes' => $total_quizzes,
                'media_quiz_geral' => $media_quiz_count > 0 ? round($media_quiz_soma / $media_quiz_count, 1) : 0,
                'taxa_aprovacao_quiz_geral' => $total_alunos_avaliados > 0 ? (int)round(($total_alunos_aprovados / $total_alunos_avaliados) * 100) : 0,
                'desempenho_cursos' => array_slice($desempenho_cursos, 0, 4),
                'perguntas_criticas' => array_slice($perguntas_criticas, 0, 4),
                'usuario' => $usuario
            ];
        } catch (Throwable $exception) {
            $this->logDashboardError('dashboardProfessor', $exception);
            return [
                'cursos' => [],
                'total_cursos' => 0,
                'total_alunos' => 0,
                'total_aulas' => 0,
                'total_atividades' => 0,
                'total_quizzes' => 0,
                'media_quiz_geral' => 0,
                'taxa_aprovacao_quiz_geral' => 0,
                'desempenho_cursos' => [],
                'perguntas_criticas' => [],
                'usuario' => $_SESSION['usuario'] ?? null
            ];
        }
    }

    /**
     * Dashboard do Admin
     */
    public function dashboardAdmin()
    {
        try {
            require_once __DIR__ . '/../controllers/AdminController.php';
            $adminController = new AdminController($this->pdo);

            $stats = $adminController->obterEstatisticas();
            $usuarios = $adminController->listarUsuarios();
            $cursos = $adminController->listarCursos();

            return [
                'stats' => $stats,
                'usuarios' => $usuarios,
                'cursos' => $cursos,
                'usuario' => $_SESSION['usuario'] ?? null
            ];
        } catch (Throwable $exception) {
            $this->logDashboardError('dashboardAdmin', $exception);
            return [
                'stats' => [],
                'usuarios' => [],
                'cursos' => [],
                'usuario' => $_SESSION['usuario'] ?? null
            ];
        }
    }

    /**
     * Obter progresso no curso
     */
    public function obterProgressoCurso($course_id)
    {
        $usuario = $_SESSION['usuario'] ?? null;
        $userId = (int)($usuario['id'] ?? 0);

        try {
            require_once __DIR__ . '/../models/Lesson.php';
            $lessonModel = new Lesson($this->pdo);
            $progressState = $this->calcularEstadoProgressoCurso($userId, (int)$course_id, $lessonModel);
            return $progressState['progress'];
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Atualizar progresso
     */
    public function atualizarProgresso($course_id)
    {
        try {
            $usuario = $_SESSION['usuario'] ?? null;
            $userId = (int)($usuario['id'] ?? 0);

            require_once __DIR__ . '/../models/Lesson.php';
            $lessonModel = new Lesson($this->pdo);
            $progressState = $this->calcularEstadoProgressoCurso($userId, (int)$course_id, $lessonModel);

            return $progressState['progress'];
        } catch (Throwable $exception) {
            $this->logDashboardError('atualizarProgresso', $exception);
            return 0;
        }
    }

    private function logDashboardError($context, Throwable $exception)
    {
        if (function_exists('registrar_log')) {
            registrar_log('exception', 'DashboardController::' . $context . ' ' . $exception->getMessage(), (int)(($_SESSION['usuario']['id'] ?? 0)));
        }
    }

    private function calcularEstadoProgressoCurso($userId, $courseId, Lesson $lessonModel)
    {
        $lessons = $lessonModel->listarPorCurso($courseId);
        $lessonIds = array_map(static function ($lesson) {
            return (int)$lesson['id'];
        }, $lessons);

        $totalLessons = count($lessonIds);
        if ($totalLessons === 0) {
            $this->persistirProgressoMatricula($userId, $courseId, 0);
            return [
                'progress' => 0,
                'completed_lessons' => 0,
                'total_lessons' => 0,
                'next_lesson_id' => null,
            ];
        }

        $placeholders = implode(',', array_fill(0, $totalLessons, '?'));
        $params = array_merge([$userId], $lessonIds);
        $stmt = $this->pdo->prepare(
            "SELECT lesson_id, concluida
             FROM lesson_progress
             WHERE user_id = ? AND lesson_id IN ($placeholders)"
        );
        $stmt->execute($params);

        $progressMap = [];
        foreach ($stmt->fetchAll() as $row) {
            $progressMap[(int)$row['lesson_id']] = (int)$row['concluida'] === 1;
        }

        $completedLessons = 0;
        $nextLessonId = null;

        foreach ($lessons as $lesson) {
            $lessonId = (int)$lesson['id'];
            $isCompleted = !empty($progressMap[$lessonId]);

            if ($isCompleted) {
                $completedLessons++;
                continue;
            }

            if ($nextLessonId === null) {
                $nextLessonId = $lessonId;
            }
        }

        if ($nextLessonId === null) {
            $nextLessonId = (int)$lessons[0]['id'];
        }

        $nextLessonId = $this->resolverProximaAulaDesbloqueada($courseId, $nextLessonId);

        $lessonProgress = (int)floor(($completedLessons / $totalLessons) * 100);
        $baseState = [
            'progress' => $lessonProgress,
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
            'next_lesson_id' => $nextLessonId,
            'lesson_progress' => $lessonProgress,
            'quiz_progress' => 100,
            'nota_final' => 0,
            'aprovado' => $lessonProgress >= 100,
        ];

        try {
            require_once __DIR__ . '/../models/Quiz.php';
            $quizModel = new Quiz($this->pdo);
            $quizProgress = $quizModel->calcularProgressoAvaliacaoCurso($courseId, $userId);
            $eligibilidade = $quizModel->alunoAptoConclusao($courseId, $userId, $lessonProgress);

            $hasQuizzes = count($quizModel->listarPorCurso($courseId)) > 0;
            $progress = $hasQuizzes
                ? (int)floor((($lessonProgress * 0.6) + ($quizProgress * 0.4)))
                : $lessonProgress;

            if (!empty($eligibilidade['aprovado'])) {
                $progress = 100;
            }

            $baseState['progress'] = $progress;
            $baseState['quiz_progress'] = $quizProgress;
            $baseState['nota_final'] = $eligibilidade['nota_final'] ?? 0;
            $baseState['aprovado'] = !empty($eligibilidade['aprovado']);
        } catch (Throwable $exception) {
            if (function_exists('registrar_log')) {
                registrar_log('warning', 'quiz_progress_fallback curso=' . $courseId . ' erro=' . $exception->getMessage(), $userId);
            }
        }

        $this->persistirProgressoMatricula($userId, $courseId, $baseState['progress']);

        return $baseState;
    }

    private function persistirProgressoMatricula($userId, $courseId, $progress)
    {
        try {
            require_once __DIR__ . '/../models/Enrollment.php';
            $enrollmentModel = new Enrollment($this->pdo);
            $enrollmentModel->atualizarProgresso($userId, $courseId, $progress);
        } catch (Throwable $exception) {
            if (function_exists('registrar_log')) {
                registrar_log('warning', 'persistir_progresso_falhou curso=' . $courseId . ' erro=' . $exception->getMessage(), $userId);
            }
        }
    }

    private function calcularEstadoProgressoSomenteAulas($userId, $courseId, Lesson $lessonModel)
    {
        $lessons = $lessonModel->listarPorCurso($courseId);
        $lessonIds = array_map(static function ($lesson) {
            return (int)$lesson['id'];
        }, $lessons);

        $totalLessons = count($lessonIds);
        if ($totalLessons === 0) {
            $this->persistirProgressoMatricula($userId, $courseId, 0);
            return [
                'progress' => 0,
                'completed_lessons' => 0,
                'total_lessons' => 0,
                'next_lesson_id' => null,
                'lesson_progress' => 0,
                'quiz_progress' => 100,
                'nota_final' => 0,
                'aprovado' => false,
            ];
        }

        $placeholders = implode(',', array_fill(0, $totalLessons, '?'));
        $params = array_merge([$userId], $lessonIds);
        $stmt = $this->pdo->prepare(
            "SELECT lesson_id, concluida
             FROM lesson_progress
             WHERE user_id = ? AND lesson_id IN ($placeholders)"
        );
        $stmt->execute($params);

        $progressMap = [];
        foreach ($stmt->fetchAll() as $row) {
            $progressMap[(int)$row['lesson_id']] = (int)$row['concluida'] === 1;
        }

        $completedLessons = 0;
        $nextLessonId = null;

        foreach ($lessons as $lesson) {
            $lessonId = (int)$lesson['id'];
            $isCompleted = !empty($progressMap[$lessonId]);

            if ($isCompleted) {
                $completedLessons++;
                continue;
            }

            if ($nextLessonId === null) {
                $nextLessonId = $lessonId;
            }
        }

        if ($nextLessonId === null) {
            $nextLessonId = (int)$lessons[0]['id'];
        }

        $nextLessonId = $this->resolverProximaAulaDesbloqueada($courseId, $nextLessonId);

        $lessonProgress = (int)floor(($completedLessons / $totalLessons) * 100);
        $this->persistirProgressoMatricula($userId, $courseId, $lessonProgress);

        return [
            'progress' => $lessonProgress,
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
            'next_lesson_id' => $nextLessonId,
            'lesson_progress' => $lessonProgress,
            'quiz_progress' => 100,
            'nota_final' => 0,
            'aprovado' => $lessonProgress >= 100,
        ];
    }

    private function resolverProximaAulaDesbloqueada($courseId, $fallbackLessonId)
    {
        try {
            require_once __DIR__ . '/CourseController.php';
            $courseController = new CourseController($this->pdo);
            $courseResult = $courseController->obter($courseId);
            if (empty($courseResult['sucesso'])) {
                return $fallbackLessonId;
            }

            foreach (($courseResult['curso']['modulos'] ?? []) as $module) {
                if (empty($module['unlocked'])) {
                    continue;
                }
                foreach (($module['lessons'] ?? []) as $lesson) {
                    if (empty($lesson['is_completed'])) {
                        return (int)($lesson['id'] ?? $fallbackLessonId);
                    }
                }
            }
        } catch (Throwable $exception) {
            return $fallbackLessonId;
        }

        return $fallbackLessonId;
    }
}
