<?php

/**
 * ARQUIVO PRINCIPAL - PLATAFORMA EAD
 * Roteador e controlador central
 */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Inicializar sessão
session_start();

// Incluir configurações
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/autoload.php';
// controllers podem ser carregados automaticamente pelo autoload quando instanciados
// mas manter requires diretos não é necessário mais
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/CourseController.php';
require_once __DIR__ . '/../app/controllers/LessonController.php';
require_once __DIR__ . '/../app/controllers/QuizController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/models/Lesson.php';

// Armazenar PDO globalmente para usar em views
$GLOBALS['pdo'] = $pdo;

// Suporte a URLs amigáveis públicas como /certificado/CODIGO
$prettyUrl = trim((string)($_GET['url'] ?? ''), '/');
if (!isset($_GET['page']) && preg_match('#^certificado/([A-Za-z0-9]+)$#', $prettyUrl, $matches)) {
    $_GET['page'] = 'certificado';
    $_GET['codigo'] = $matches[1];
    $_GET['public'] = '1';
}

// Obter página
$page = $_GET['page'] ?? 'home';
$titulo = 'Plataforma EAD';
$conteudo = '';
$pdfExport = false;

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    processarAcao($_POST, $pdo);
}

/**
 * Detectar se a requisição é AJAX (fetch/fetch wrapper define X-CSRF-Token or Accept: application/json)
 */
function isAjaxRequest()
{
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') return true;
    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) return true;
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) return true;
    return false;
}

function abortExportRequest($message, $isAjax, $statusCode = 400)
{
    http_response_code($statusCode);
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['sucesso' => false, 'mensagem' => $message]);
        exit;
    }

    $_SESSION['erro'] = $message;
    $redirect = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/index.php?page=perfil');
    header('Location: ' . $redirect);
    exit;
}

function validateRouteCsrfOrAbort($isAjax)
{
    $token = $_POST['csrf_token'] ?? '';
    if (!validar_csrf($token)) {
        abortExportRequest('Token CSRF inválido ou expirado. Atualize a página e tente novamente.', $isAjax, 419);
    }
}

function quizTimerSessionKey($quizId, $userId)
{
    return 'quiz_timer_' . (int)$userId . '_' . (int)$quizId;
}

function getQuizTimerState($quizId, $userId)
{
    $key = quizTimerSessionKey($quizId, $userId);
    $state = $_SESSION[$key] ?? null;
    return is_array($state) ? $state : null;
}

function clearQuizTimerState($quizId, $userId)
{
    unset($_SESSION[quizTimerSessionKey($quizId, $userId)]);
}

function ensureQuizTimerState(array $quiz, $userId, $forceRestart = false)
{
    $quizId = (int)($quiz['id'] ?? 0);
    $userId = (int)$userId;
    $timeLimitMinutes = max(0, (int)($quiz['tempo_limite'] ?? 0));

    if ($quizId <= 0 || $userId <= 0 || $timeLimitMinutes <= 0) {
        clearQuizTimerState($quizId, $userId);
        return null;
    }

    $key = quizTimerSessionKey($quizId, $userId);
    if ($forceRestart) {
        unset($_SESSION[$key]);
    }

    $current = $_SESSION[$key] ?? null;
    if (is_array($current) && (int)($current['expires_at'] ?? 0) > time()) {
        return $current;
    }

    $startedAt = time();
    $state = [
        'quiz_id' => $quizId,
        'user_id' => $userId,
        'started_at' => $startedAt,
        'expires_at' => $startedAt + ($timeLimitMinutes * 60),
        'time_limit_minutes' => $timeLimitMinutes
    ];
    $_SESSION[$key] = $state;

    return $state;
}

function quizViewStateSessionKey($quizId, $userId)
{
    return 'quiz_view_state_' . (int)$userId . '_' . (int)$quizId;
}

function clearQuizViewState($quizId, $userId)
{
    unset($_SESSION[quizViewStateSessionKey($quizId, $userId)]);
}

function applyQuizViewState(array $quiz, $userId, $forceRestart = false)
{
    $quizId = (int)($quiz['id'] ?? 0);
    $userId = (int)$userId;
    if ($quizId <= 0 || $userId <= 0 || empty($quiz['questoes']) || !is_array($quiz['questoes'])) {
        return $quiz;
    }

    $key = quizViewStateSessionKey($quizId, $userId);
    if ($forceRestart) {
        unset($_SESSION[$key]);
    }

    $state = $_SESSION[$key] ?? null;
    if (!is_array($state)) {
        $questionIds = array_map(static function ($questao) {
            return (int)($questao['id'] ?? 0);
        }, $quiz['questoes']);

        if (!empty($quiz['embaralhar_perguntas'])) {
            shuffle($questionIds);
        }

        $optionOrder = [];
        foreach ($quiz['questoes'] as $questao) {
            $questionId = (int)($questao['id'] ?? 0);
            $options = $questao['opcoes'] ?? [];
            if (!is_array($options)) {
                $options = json_decode((string)$options, true) ?? [];
            }
            $options = array_values($options);
            if (!empty($quiz['embaralhar_respostas'])) {
                shuffle($options);
            }
            $optionOrder[$questionId] = $options;
        }

        $state = [
            'question_ids' => $questionIds,
            'option_order' => $optionOrder
        ];
        $_SESSION[$key] = $state;
    }

    $questionsById = [];
    foreach ($quiz['questoes'] as $questao) {
        $questionId = (int)($questao['id'] ?? 0);
        if (isset($state['option_order'][$questionId]) && is_array($state['option_order'][$questionId])) {
            $questao['opcoes'] = $state['option_order'][$questionId];
        }
        $questionsById[$questionId] = $questao;
    }

    $orderedQuestions = [];
    foreach (($state['question_ids'] ?? []) as $questionId) {
        if (isset($questionsById[$questionId])) {
            $orderedQuestions[] = $questionsById[$questionId];
            unset($questionsById[$questionId]);
        }
    }

    foreach ($questionsById as $questao) {
        $orderedQuestions[] = $questao;
    }

    $quiz['questoes'] = $orderedQuestions;
    return $quiz;
}

// Roteamento
try {
    switch ($page) {
        // Autenticação
        case 'login':
            $titulo = 'Login - Plataforma EAD';
            $conteudo = renderizar('login');
            break;

        case 'registro':
            $titulo = 'Registrar - Plataforma EAD';
            $conteudo = renderizar('registro');
            break;

        case 'registro-professor':
            $titulo = 'Registrar como Professor - Plataforma EAD';
            $conteudo = renderizar('registro-professor');
            break;

        case 'confirmar-email':
            $titulo = 'Confirmar Email - Plataforma EAD';
            $conteudo = renderizar('email-verification', [
                'email' => (string)($_GET['email'] ?? ''),
                'context' => (string)($_GET['context'] ?? ''),
            ]);
            break;

        case 'verificacao-email':
            $titulo = 'Verificação de Email - Plataforma EAD';
            $conteudo = renderizar('email-verification', [
                'email' => (string)($_GET['email'] ?? ''),
                'context' => (string)($_GET['context'] ?? ''),
            ]);
            break;

        case 'esqueci-senha':
            $titulo = 'Recuperar Senha - Plataforma EAD';
            $conteudo = renderizar('forgot-password');
            break;

        case 'redefinir-senha':
            $titulo = 'Redefinir Senha - Plataforma EAD';
            $auth = new AuthController($pdo);
            $validacaoToken = $auth->validarResetToken($_GET['token'] ?? '');
            $conteudo = renderizar('reset-password', [
                'tokenValido' => !empty($validacaoToken['sucesso']),
                'token' => (string)($_GET['token'] ?? ''),
                'mensagemToken' => $validacaoToken['mensagem'] ?? '',
            ]);
            break;

        case 'logout':
            $auth = new AuthController($pdo);
            $auth->logout();
            header('Location: ' . BASE_URL . '/index.php');
            exit;

            // Cursos
        case 'cursos':
            if (AuthController::estaAutenticado() && (AuthController::obterUsuarioAtual()['role'] ?? '') === 'admin') {
                header('Location: ' . BASE_URL . '/index.php?page=admin-cursos');
                exit;
            }

            $courseController = new CourseController($pdo);
            $pagina = $_GET['p'] ?? 1;
            $busca = $_GET['busca'] ?? '';

            if ($busca) {
                $resultado = $courseController->buscar($busca);
                $cursos = $resultado['sucesso'] ? $resultado['cursos'] : [];
                $busca = htmlspecialchars($busca);
                // Quando há busca, não usamos paginação do listing padrão
                $total = count($cursos);
                $total_paginas = 1;
            } else {
                $lista = $courseController->listar($pagina);
                // Evitar extract() inseguro: atribuir explicitamente variáveis esperadas
                $cursos = $lista['cursos'] ?? [];
                $pagina = $lista['pagina'] ?? 1;
                $total_paginas = $lista['total_paginas'] ?? 1;
                $total = $lista['total'] ?? 0;
            }

            $titulo = 'Cursos - Plataforma EAD';
            $conteudo = renderizar('cursos', [
                'cursos' => $cursos ?? [],
                'pagina' => $pagina ?? 1,
                'total_paginas' => $total_paginas ?? 1,
                'total' => $total ?? 0,
                'busca' => $busca ?? ''
            ]);
            break;

        case 'curso':
            if (!isset($_GET['id'])) {
                throw new Exception('Curso não especificado');
            }
            $courseController = new CourseController($pdo);
            $resultado = $courseController->obter($_GET['id']);

            if (!$resultado['sucesso']) {
                throw new Exception($resultado['mensagem']);
            }

            $curso = $resultado['curso'];
            $titulo = $curso['titulo'] . ' - Plataforma EAD';

            // Obter progresso se autenticado
            $meu_progresso = 0;
            if (AuthController::estaAutenticado()) {
                $dashboardController = new DashboardController($pdo);
                $meu_progresso = $dashboardController->obterProgressoCurso($curso['id']);
            }

            $conteudo = renderizar('curso-detail', [
                'curso' => $curso ?? null,
                'meu_progresso' => $meu_progresso ?? 0
            ]);
            break;

        case 'aula':
            if (!isset($_GET['lesson_id'])) {
                throw new Exception('Aula não especificada');
            }
            AuthController::exigirAutenticacao();

            $lessonId = (int)($_GET['lesson_id'] ?? 0);
            $lessonController = new LessonController($pdo);
            $resultado = $lessonController->obter($lessonId);

            if (empty($resultado['sucesso']) || empty($resultado['aula'])) {
                throw new Exception($resultado['mensagem'] ?? 'Aula não encontrada');
            }

            $aula = $resultado['aula'];
            $course_id = (int)($aula['course_id'] ?? 0);
            if ($course_id <= 0) {
                throw new Exception('Curso da aula não encontrado');
            }

            $usuarioAtual = AuthController::obterUsuarioAtual();
            $concluida = false;
            $avaliacao = [
                'nota' => null,
                'progresso_avaliacao' => 0,
                'quizzes' => []
            ];
            $quizzes = [];
            $aulas_curso = [];
            $aulas_concluidas_ids = [];
            $aulas_concluidas_total = 0;
            $progresso_curso = 0;

            $courseController = new CourseController($pdo);
            $cursoResult = $courseController->obter($course_id);
            if (empty($cursoResult['sucesso']) || empty($cursoResult['curso'])) {
                throw new Exception('Curso não encontrado para esta aula');
            }
            $curso = $cursoResult['curso'];
            $modulos_curso = is_array($curso['modulos'] ?? null) ? $curso['modulos'] : [];
            $current_module = null;
            foreach ($modulos_curso as $moduleItem) {
                foreach (($moduleItem['lessons'] ?? []) as $moduleLesson) {
                    if ((int)($moduleLesson['id'] ?? 0) === (int)$aula['id']) {
                        $current_module = $moduleItem;
                        break 2;
                    }
                }
            }

            if (empty($current_module) && !empty($modulos_curso[0])) {
                $current_module = $modulos_curso[0];
            }

            $isOwner = (int)($curso['teacher_id'] ?? 0) === (int)($usuarioAtual['id'] ?? 0);
            if (!$isOwner && !empty($current_module) && empty($current_module['unlocked'])) {
                $_SESSION['erro'] = 'Este módulo ainda está bloqueado. Conclua e aprove o módulo anterior para avançar.';
                header('Location: ' . BASE_URL . '/index.php?page=curso&id=' . $course_id);
                exit;
            }

            try {
                $stmt = $pdo->prepare('SELECT concluida FROM lesson_progress WHERE user_id = ? AND lesson_id = ?');
                $stmt->execute([(int)($usuarioAtual['id'] ?? 0), (int)$aula['id']]);
                $row = $stmt->fetch();
                $concluida = !empty($row) && (int)($row['concluida'] ?? 0) === 1;
            } catch (Throwable $exception) {
                if (function_exists('registrar_log')) {
                    registrar_log('warning', 'lesson_progress_check_failed lesson=' . (int)$aula['id'] . ' erro=' . $exception->getMessage(), (int)($usuarioAtual['id'] ?? 0));
                }
            }

            try {
                require_once __DIR__ . '/../app/controllers/QuizController.php';
                $quizController = new QuizController($pdo);
                $avaliacao = $quizController->obterDesempenhoCursoAluno($course_id, (int)($usuarioAtual['id'] ?? 0));
                $currentModuleId = (int)($current_module['id'] ?? 0);
                $currentLessonId = (int)($aula['id'] ?? 0);
                $quizzes = $currentLessonId > 0 ? array_values(array_filter(($avaliacao['quizzes'] ?? []), static function ($quiz) use ($currentModuleId, $currentLessonId) {
                    $quizType = (string)($quiz['tipo'] ?? 'aula');
                    return (($quizType === 'aula' && (int)($quiz['lesson_id'] ?? 0) === $currentLessonId)
                        || ($quizType === 'modulo' && (int)($quiz['module_id'] ?? 0) === $currentModuleId)
                        || $quizType === 'final');
                })) : [];
            } catch (Throwable $exception) {
                $avaliacao = [
                    'nota' => null,
                    'progresso_avaliacao' => 0,
                    'quizzes' => []
                ];
                $quizzes = [];
                if (function_exists('registrar_log')) {
                    registrar_log('warning', 'lesson_quiz_fallback lesson=' . (int)$aula['id'] . ' course=' . $course_id . ' erro=' . $exception->getMessage(), (int)($usuarioAtual['id'] ?? 0));
                }
            }

            try {
                $aulas_curso = $lessonController->listarPorCurso($course_id);

                if (!empty($aulas_curso)) {
                    $lessonIds = array_map(static function ($lesson) {
                        return (int)($lesson['id'] ?? 0);
                    }, $aulas_curso);

                    if (!empty($lessonIds)) {
                        $placeholders = implode(',', array_fill(0, count($lessonIds), '?'));
                        $params = array_merge([(int)($usuarioAtual['id'] ?? 0)], $lessonIds);
                        $stmt = $pdo->prepare(
                            "SELECT lesson_id
                             FROM lesson_progress
                             WHERE user_id = ? AND concluida = 1 AND lesson_id IN ($placeholders)"
                        );
                        $stmt->execute($params);
                        $aulas_concluidas_ids = array_map('intval', array_column($stmt->fetchAll(), 'lesson_id'));
                    }
                }
            } catch (Throwable $exception) {
                $aulas_curso = [$aula];
                $aulas_concluidas_ids = [];
                if (function_exists('registrar_log')) {
                    registrar_log('warning', 'lesson_course_list_fallback lesson=' . (int)$aula['id'] . ' course=' . $course_id . ' erro=' . $exception->getMessage(), (int)($usuarioAtual['id'] ?? 0));
                }
            }

            if (empty($aulas_curso)) {
                $aulas_curso = [$aula];
            }

            foreach ($aulas_curso as $index => &$lessonCurso) {
                $lessonIdFromCourse = (int)($lessonCurso['id'] ?? 0);
                $lessonCurso['is_current'] = $lessonIdFromCourse === (int)$aula['id'];
                $lessonCurso['is_completed'] = in_array($lessonIdFromCourse, $aulas_concluidas_ids, true);
                $lessonCurso['position'] = $index + 1;

                if ($lessonCurso['is_completed']) {
                    $aulas_concluidas_total++;
                }
            }
            unset($lessonCurso);

            if (!empty($aulas_curso)) {
                $progresso_curso = (int)floor(($aulas_concluidas_total / count($aulas_curso)) * 100);
            }

            $titulo = $aula['titulo'] . ' - Plataforma EAD';
            $conteudo = renderizar('aula', [
                'aula' => $aula,
                'curso' => $curso,
                'modulos_curso' => $modulos_curso,
                'current_module' => $current_module,
                'quizzes' => $quizzes,
                'avaliacao' => $avaliacao,
                'aulas_curso' => $aulas_curso,
                'course_id' => $course_id,
                'concluida' => $concluida,
                'progresso_curso' => $progresso_curso,
                'aulas_concluidas_total' => $aulas_concluidas_total
            ]);
            break;

        case 'admin-cursos':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['admin']);

            $adminController = new AdminController($pdo);
            $pagina = max(1, (int)($_GET['p'] ?? 1));
            $busca = trim((string)($_GET['busca'] ?? ''));
            $status = trim((string)($_GET['status'] ?? ''));
            $statusPermitido = in_array($status, ['ativo', 'inativo', 'rascunho'], true) ? $status : null;

            $lista = $adminController->listarCursosPaginados($pagina, 12, $busca, $statusPermitido);
            $stats = $adminController->obterEstatisticas();

            $titulo = 'Cursos Admin - Plataforma EAD';
            $conteudo = renderizar('admin-cursos', [
                'cursos' => $lista['cursos'] ?? [],
                'pagina' => $lista['pagina'] ?? 1,
                'total_paginas' => $lista['total_paginas'] ?? 1,
                'total' => $lista['total'] ?? 0,
                'busca' => $busca,
                'status' => $status,
                'stats' => $stats
            ]);
            break;

        case 'admin-quizzes':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['admin']);

            $adminController = new AdminController($pdo);
            $pagina = max(1, (int)($_GET['p'] ?? 1));
            $busca = trim((string)($_GET['busca'] ?? ''));
            $tipo = trim((string)($_GET['tipo'] ?? ''));
            $tipoPermitido = in_array($tipo, ['aula', 'modulo', 'final'], true) ? $tipo : null;

            $lista = $adminController->listarQuizzesPaginados($pagina, 12, $busca, $tipoPermitido);
            $stats = $adminController->obterEstatisticas();
            $quizStats = $adminController->obterResumoQuizzes();

            $titulo = 'Quizzes Admin - Plataforma EAD';
            $conteudo = renderizar('admin-quizzes', [
                'quizzes' => $lista['quizzes'] ?? [],
                'pagina' => $lista['pagina'] ?? 1,
                'total_paginas' => $lista['total_paginas'] ?? 1,
                'total' => $lista['total'] ?? 0,
                'busca' => $busca,
                'tipo' => $tipo,
                'stats' => $stats,
                'quiz_stats' => $quizStats
            ]);
            break;

        case 'quiz':
            if (!isset($_GET['quiz_id'])) {
                throw new Exception('Quiz não especificado');
            }
            AuthController::exigirAutenticacao();

            $quizController = new QuizController($pdo);
            $resultado = $quizController->obter($_GET['quiz_id']);

            if (!$resultado['sucesso']) {
                throw new Exception($resultado['mensagem']);
            }

            $quiz = $resultado['quiz'];
            $usuarioAtual = AuthController::obterUsuarioAtual();
            if (!tem_permissao('professor') && !empty($quiz['course_id'])) {
                $courseController = new CourseController($pdo);
                $courseResult = $courseController->obter((int)$quiz['course_id']);
                if (!empty($courseResult['sucesso'])) {
                    $courseQuiz = $courseResult['curso'] ?? [];
                    $moduleStates = $courseQuiz['modulos'] ?? [];
                    if (($quiz['tipo'] ?? '') === 'modulo') {
                        foreach ($moduleStates as $moduleState) {
                            if ((int)($moduleState['id'] ?? 0) === (int)($quiz['module_id'] ?? 0)) {
                                if (empty($moduleState['unlocked'])) {
                                    throw new Exception('Este módulo ainda está bloqueado.');
                                }

                                if (empty($moduleState['quiz_unlocked'])) {
                                    throw new Exception('Conclua as aulas deste módulo antes de abrir o quiz.');
                                }
                            }
                        }
                    }

                    if (($quiz['tipo'] ?? '') === 'final') {
                        foreach ($moduleStates as $moduleState) {
                            if (empty($moduleState['completed'])) {
                                throw new Exception('Conclua e aprove os módulos anteriores antes de acessar o quiz final.');
                            }
                        }
                    }
                }
            }
            $historico = $quizController->obterResultados($_GET['quiz_id']);
            $melhorResultado = $quizController->obterMelhorResultado($_GET['quiz_id']);
            $ultimoResultado = null;
            if (!empty($_SESSION['quiz_resultado']) && (int)($_SESSION['quiz_resultado']['quiz_id'] ?? 0) === (int)$quiz['id']) {
                $ultimoResultado = $_SESSION['quiz_resultado'];
                unset($_SESSION['quiz_resultado']);
            }

            $forceRestart = !empty($_GET['restart']);
            if ($forceRestart) {
                $ultimoResultado = null;
                clearQuizViewState((int)$quiz['id'], (int)($usuarioAtual['id'] ?? 0));
            }

            $quiz = applyQuizViewState($quiz, (int)($usuarioAtual['id'] ?? 0), $forceRestart);

            $tentativasUsadas = count($historico['resultados'] ?? []);
            $tentativasMaximas = (int)($quiz['tentativas_maximas'] ?? 0);
            $tentativasEncerradas = $tentativasMaximas > 0 && $tentativasUsadas >= $tentativasMaximas && !tem_permissao('professor');

            if (!$ultimoResultado && $tentativasEncerradas) {
                $ultimaTentativa = $quizController->obterUltimaTentativa((int)$quiz['id']);
                $ultimoResultado = $ultimaTentativa['tentativa'] ?? null;
            }

            $desempenhoCurso = [];
            if (!empty($quiz['course_id'])) {
                $desempenhoCurso = $quizController->obterDesempenhoCursoAluno((int)$quiz['course_id'], (int)($usuarioAtual['id'] ?? 0));
            }

            $quizTimer = null;
            if (empty($ultimoResultado['respostas']) && !tem_permissao('professor')) {
                $quizTimer = ensureQuizTimerState($quiz, (int)($usuarioAtual['id'] ?? 0), $forceRestart);
            } else {
                clearQuizTimerState((int)$quiz['id'], (int)($usuarioAtual['id'] ?? 0));
            }

            $titulo = $quiz['titulo'] . ' - Plataforma EAD';
            $conteudo = renderizar('quiz', [
                'quiz' => $quiz ?? null,
                'historico' => $historico['resultados'] ?? [],
                'melhor_resultado' => $melhorResultado['resultado'] ?? null,
                'ultimo_resultado' => $ultimoResultado,
                'desempenho_curso' => $desempenhoCurso ?? [],
                'quiz_timer' => $quizTimer
            ]);
            break;

        case 'certificado':
            try {
                $certificateController = new CertificateController($pdo);

                if (!empty($_GET['codigo'])) {
                    $verification = $certificateController->getPublicVerificationData((string)$_GET['codigo']);
                    $titulo = 'Verificação de Certificado - Plataforma EAD';
                    $conteudo = renderizar('certificado', [
                        'mode' => 'public',
                        'verification' => $verification,
                        'certificado' => $verification['certificate'] ?? null,
                    ]);
                    break;
                }

                if (!isset($_GET['course_id'])) {
                    throw new Exception('Curso não especificado para o certificado');
                }

                $courseId = (int)$_GET['course_id'];
                $type = (!empty($_GET['module_id']) || ($_GET['type'] ?? '') === 'module') ? 'module' : 'course';
                $moduleId = $type === 'module' ? (int)($_GET['module_id'] ?? 0) : null;

                if (!empty($_GET['pdf_render'])) {
                    $pdfUserId = (int)($_GET['pdf_user_id'] ?? 0);
                    $pdfExpires = (int)($_GET['pdf_expires'] ?? 0);
                    $pdfToken = (string)($_GET['pdf_token'] ?? '');
                    $ownedCertificate = $certificateController->validatePdfRenderToken($pdfUserId, $courseId, $type, $moduleId, $pdfExpires, $pdfToken);

                    if (!$ownedCertificate) {
                        throw new Exception('Acesso negado');
                    }

                    $certificateController->syncCourseCertificates($pdfUserId, $courseId);
                    $pageData = $certificateController->getStudentCertificatePageData($pdfUserId, $courseId, $type, $moduleId);
                    $titulo = 'Certificado - PDF';
                    $pdfExport = true;
                    $conteudo = renderizar('certificado', [
                        'mode' => 'owner',
                        'certificado' => $pageData['certificate'] ?? null,
                        'snapshot' => $pageData['snapshot'] ?? [],
                        'requested_type' => $type,
                        'requested_module_id' => $moduleId,
                        'course_id' => $courseId,
                        'pdf_export' => true
                    ]);
                    break;
                }

                AuthController::exigirAutenticacao();
                $userId = (int)(AuthController::obterUsuarioAtual()['id'] ?? 0);

                $certificateController->syncCourseCertificates($userId, $courseId);
                $pageData = $certificateController->getStudentCertificatePageData($userId, $courseId, $type, $moduleId);

                if (($_GET['download'] ?? '') === 'pdf') {
                    $pdf = $certificateController->downloadOwnedCertificatePdf($userId, $courseId, $type, $moduleId);
                    if (!$pdf) {
                        $_SESSION['erro'] = 'Certificado ainda não disponível para download.';
                        header('Location: ' . BASE_URL . '/index.php?page=curso&id=' . $courseId);
                        exit;
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="' . basename($pdf['filename']) . '"');
                    header('Content-Length: ' . strlen($pdf['content']));
                    echo $pdf['content'];
                    exit;
                }

                $titulo = 'Certificado - Plataforma EAD';
                $conteudo = renderizar('certificado', [
                    'mode' => 'owner',
                    'certificado' => $pageData['certificate'] ?? null,
                    'snapshot' => $pageData['snapshot'] ?? [],
                    'requested_type' => $type,
                    'requested_module_id' => $moduleId,
                    'course_id' => $courseId,
                    'download_pdf_url' => $certificateController->buildOwnedCertificatePdfDownloadUrl($userId, $courseId, $type, $moduleId),
                ]);
            } catch (Throwable $certificateException) {
                if (function_exists('registrar_log')) {
                    registrar_log('exception', 'certificado_route: ' . $certificateException->getMessage());
                }

                $titulo = 'Certificado - Plataforma EAD';
                $conteudo = '<div class="alert alert-error">Não foi possível carregar o certificado agora. Tente novamente em instantes.</div>';
            }
            break;

        case 'certificado-pdf':
            try {
                AuthController::exigirAutenticacao();
                $certificateController = new CertificateController($pdo);
                $downloadUserId = (int)(AuthController::obterUsuarioAtual()['id'] ?? 0);

                $renderUrl = trim((string)($_GET['url'] ?? ''));
                if ($renderUrl === '') {
                    throw new Exception('URL do certificado não informada para geração do PDF.');
                }

                $query = [];
                parse_str((string)parse_url($renderUrl, PHP_URL_QUERY), $query);
                if ((int)($query['pdf_user_id'] ?? 0) !== $downloadUserId) {
                    throw new Exception('Acesso negado ao PDF do certificado.');
                }

                $downloadCourseId = (int)($query['course_id'] ?? 0);
                $downloadType = (!empty($query['module_id']) || (($query['type'] ?? '') === 'module')) ? 'module' : 'course';
                $downloadModuleId = $downloadType === 'module' ? (int)($query['module_id'] ?? 0) : null;
                $metadata = $certificateController->getOwnedCertificatePdfMetadata($downloadUserId, $downloadCourseId, $downloadType, $downloadModuleId);

                if (!$metadata) {
                    throw new Exception('Certificado indisponível para download.');
                }

                $pdfContent = $certificateController->downloadCertificatePdfFromRenderUrl($renderUrl);

                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($metadata['filename']) . '"');
                header('Content-Length: ' . strlen($pdfContent));
                echo $pdfContent;
                exit;
            } catch (Throwable $certificatePdfException) {
                if (function_exists('registrar_log')) {
                    registrar_log('exception', 'certificado_pdf_route: ' . $certificatePdfException->getMessage());
                }
                error_log('certificado_pdf_route: ' . $certificatePdfException->getMessage());

                $_SESSION['erro'] = 'Não foi possível gerar o PDF do certificado no momento.';
                $fallback = BASE_URL . '/index.php?page=dashboard';
                if (!empty($query['course_id'])) {
                    $fallback = BASE_URL . '/index.php?page=certificado&course_id=' . urlencode((string)$query['course_id']);
                    if ($downloadType === 'module' && !empty($downloadModuleId)) {
                        $fallback .= '&type=module&module_id=' . urlencode((string)$downloadModuleId);
                    }
                }
                header('Location: ' . $fallback);
                exit;
            }

        // Dashboard
        case 'dashboard':
            try {
                AuthController::exigirAutenticacao();
                $usuario = AuthController::obterUsuarioAtual();
                $dashboardController = new DashboardController($pdo);

                $titulo = 'Dashboard - Plataforma EAD';

                if ($usuario['role'] === 'aluno') {
                    $dados = $dashboardController->dashboardAluno();
                    $conteudo = renderizar('dashboard-aluno', $dados);
                } elseif ($usuario['role'] === 'professor') {
                    $dados = $dashboardController->dashboardProfessor();
                    $conteudo = renderizar('dashboard-professor', $dados);
                } elseif ($usuario['role'] === 'admin') {
                    $dados = $dashboardController->dashboardAdmin();
                    $conteudo = renderizar('dashboard-admin', $dados);
                } else {
                    $conteudo = '<div class="alert alert-error">Perfil de usuário não reconhecido para o dashboard.</div>';
                }
            } catch (Throwable $dashboardException) {
                if (function_exists('registrar_log')) {
                    registrar_log('exception', 'dashboard_route: ' . $dashboardException->getMessage());
                }

                $titulo = 'Dashboard - Plataforma EAD';
                $conteudo = '<div class="alert alert-error">Não foi possível carregar o dashboard agora. Tente novamente em instantes.</div>';
            }
            break;

        // Partial views para professor (abertas via modal)
        case 'meus-cursos':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $courseController = new CourseController($pdo);
            $all = $courseController->listarMeusCursos();

            // Filtragem e paginação simples via query string
            $q = trim($_GET['q'] ?? '');
            $per = max(1, (int)($_GET['per'] ?? 6));
            $pagina = max(1, (int)($_GET['p'] ?? 1));

            if ($q !== '') {
                $filtered = array_filter($all, function ($c) use ($q) {
                    return stripos($c['titulo'], $q) !== false || stripos($c['descricao'], $q) !== false;
                });
                $all = array_values($filtered);
            }

            $total = count($all);
            $total_paginas = $total ? (int)ceil($total / $per) : 1;
            $offset = ($pagina - 1) * $per;
            $cursos = array_slice($all, $offset, $per);

            $titulo = 'Meus Cursos - Plataforma EAD';
            $conteudo = renderizar('meus-cursos', ['cursos' => $cursos ?? [], 'pagina' => $pagina, 'total_paginas' => $total_paginas, 'q' => $q, 'per' => $per]);
            break;

        case 'meus-alunos':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $courseController = new CourseController($pdo);
            $cursos = $courseController->listarMeusCursos();

            // Paginação e busca via DB (mais eficiente)
            $q = trim($_GET['q'] ?? '');
            $per = max(1, (int)($_GET['per'] ?? 12));
            $pagina = max(1, (int)($_GET['p'] ?? 1));
            $offset = ($pagina - 1) * $per;

            $enrollmentModel = new Enrollment($pdo);
            $page_students = $enrollmentModel->obterAlunosPorProfessor($_SESSION['usuario']['id'], $per, $offset, $q);
            $total = $enrollmentModel->contarAlunosPorProfessor($_SESSION['usuario']['id'], $q);
            $total_paginas = $total ? (int)ceil($total / $per) : 1;

            $titulo = 'Alunos - Plataforma EAD';
            $conteudo = renderizar('meus-alunos', ['students' => $page_students, 'pagina' => $pagina, 'total_paginas' => $total_paginas, 'q' => $q, 'per' => $per]);
            break;

        case 'minhas-aulas':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $courseController = new CourseController($pdo);
            $cursos = $courseController->listarMeusCursos();
            require_once __DIR__ . '/../app/models/Lesson.php';
            $lessonModel = new Lesson($pdo);

            // Agregar todas as aulas
            $all_lessons = [];
            foreach ($cursos as $c) {
                $aulas = $lessonModel->listarPorCurso($c['id']);
                foreach ($aulas as $a) {
                    $a['course_id'] = $c['id'];
                    $a['course_title'] = $c['titulo'];
                    $all_lessons[] = $a;
                }
            }

            // Filtragem e paginação
            $q = trim($_GET['q'] ?? '');
            $per = max(1, (int)($_GET['per'] ?? 12));
            $pagina = max(1, (int)($_GET['p'] ?? 1));

            if ($q !== '') {
                $filtered = array_filter($all_lessons, function ($l) use ($q) {
                    return stripos($l['titulo'], $q) !== false || stripos($l['descricao'], $q) !== false;
                });
                $all_lessons = array_values($filtered);
            }

            $total = count($all_lessons);
            $total_paginas = $total ? (int)ceil($total / $per) : 1;
            $offset = ($pagina - 1) * $per;
            $page_lessons = array_slice($all_lessons, $offset, $per);

            $titulo = 'Minhas Aulas - Plataforma EAD';
            $conteudo = renderizar('minhas-aulas', ['lessons' => $page_lessons, 'pagina' => $pagina, 'total_paginas' => $total_paginas, 'q' => $q, 'per' => $per]);
            break;

        case 'atividades':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $courseController = new CourseController($pdo);
            $cursos = $courseController->listarMeusCursos();
            require_once __DIR__ . '/../app/models/Enrollment.php';
            require_once __DIR__ . '/../app/models/Lesson.php';
            $enrollmentModel = new Enrollment($pdo);
            $lessonModel = new Lesson($pdo);

            $recent_courses = [];
            $total_alunos_atividade = 0;
            $total_aulas_atividade = 0;

            foreach ($cursos as $curso) {
                $cursoId = (int)($curso['id'] ?? 0);
                $curso['total_alunos'] = $enrollmentModel->contarAlunos($cursoId);
                $curso['total_aulas'] = count($lessonModel->listarPorCurso($cursoId));

                $total_alunos_atividade += (int)$curso['total_alunos'];
                $total_aulas_atividade += (int)$curso['total_aulas'];
                $recent_courses[] = $curso;
            }

            $recent_courses = array_slice($recent_courses, 0, 8);

            $titulo = 'Atividades - Plataforma EAD';
            $conteudo = renderizar('atividades', [
                'recent_courses' => $recent_courses,
                'total_cursos_atividade' => count($cursos),
                'total_alunos_atividade' => $total_alunos_atividade,
                'total_aulas_atividade' => $total_aulas_atividade
            ]);
            break;

        case 'perfil':
            AuthController::exigirAutenticacao();
            $titulo = 'Meu Perfil - Plataforma EAD';
            require_once __DIR__ . '/../app/models/User.php';
            $usuarioSessao = AuthController::obterUsuarioAtual();
            $userModel = new User($pdo);
            $usuario = $userModel->obterPorId($usuarioSessao['id'] ?? 0);
            $backupRestorePreview = null;
            $backupPreferences = null;

            if (class_exists('ImportController')) {
                try {
                    $backupRestorePreview = (new ImportController($pdo))->getPreview();
                } catch (Throwable $exception) {
                    $backupRestorePreview = null;
                }
            }
            if (class_exists('BackupLogService')) {
                try {
                    $backupPreferences = (new BackupLogService($pdo))->getPreference((int)($usuarioSessao['id'] ?? 0));
                } catch (Throwable $exception) {
                    $backupPreferences = null;
                }
            }

            if ($usuario) {
                $_SESSION['usuario']['nome'] = $usuario['nome'];
                $_SESSION['usuario']['email'] = $usuario['email'];
                $_SESSION['usuario']['role'] = $usuario['role'];
                $_SESSION['usuario']['fotografia'] = $usuario['fotografia'] ?? null;
            }

            $conteudo = renderizar('perfil', [
                'usuario' => $usuario ?? null,
                'backupRestorePreview' => $backupRestorePreview,
                'backupPreferences' => $backupPreferences,
            ]);
            break;

        case 'exportar-dados-aluno':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['aluno']);
            $isAjax = isAjaxRequest();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                abortExportRequest('Método inválido para exportação.', $isAjax, 405);
            }

            validateRouteCsrfOrAbort($isAjax);

            try {
                $usuarioAtual = AuthController::obterUsuarioAtual();
                $exportController = new ExportController($pdo);
                $backupPassword = (string)($_POST['backup_password'] ?? '');
                $package = $exportController->exportarAluno((int)($usuarioAtual['id'] ?? 0), $backupPassword !== '' ? $backupPassword : null);
                $downloadUrl = (string)($package['persistent_download_url'] ?? $exportController->buildDownloadUrl((string)($package['token'] ?? '')));

                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'sucesso' => true,
                        'mensagem' => 'Seu backup está pronto para download.',
                        'download_url' => $downloadUrl,
                        'filename' => $package['filename'] ?? 'backup.zip',
                    ]);
                    exit;
                }

                $_SESSION['mensagem'] = 'Seu backup está pronto para download.';
                header('Location: ' . $downloadUrl);
                exit;
            } catch (Throwable $exception) {
                if (function_exists('registrar_log')) {
                    registrar_log('EXPORT', 'Falha exportar-dados-aluno: ' . $exception->getMessage(), (int)(AuthController::obterUsuarioAtual()['id'] ?? 0));
                }
                abortExportRequest('Não foi possível gerar o backup agora.', $isAjax, 500);
            }

        case 'exportar-dados-professor':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $isAjax = isAjaxRequest();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                abortExportRequest('Método inválido para exportação.', $isAjax, 405);
            }

            validateRouteCsrfOrAbort($isAjax);

            try {
                $usuarioAtual = AuthController::obterUsuarioAtual();
                $courseId = isset($_POST['course_id']) && $_POST['course_id'] !== '' ? (int)$_POST['course_id'] : null;
                $scope = (string)($_POST['scope'] ?? 'all');
                $backupPassword = (string)($_POST['backup_password'] ?? '');

                $exportController = new ExportController($pdo);
                $package = $exportController->exportarProfessor((int)($usuarioAtual['id'] ?? 0), $courseId, $scope, $backupPassword !== '' ? $backupPassword : null);
                $downloadUrl = (string)($package['persistent_download_url'] ?? $exportController->buildDownloadUrl((string)($package['token'] ?? '')));

                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'sucesso' => true,
                        'mensagem' => 'Seu backup está pronto para download.',
                        'download_url' => $downloadUrl,
                        'filename' => $package['filename'] ?? 'backup.zip',
                    ]);
                    exit;
                }

                $_SESSION['mensagem'] = 'Seu backup está pronto para download.';
                header('Location: ' . $downloadUrl);
                exit;
            } catch (Throwable $exception) {
                if (function_exists('registrar_log')) {
                    registrar_log('EXPORT', 'Falha exportar-dados-professor: ' . $exception->getMessage(), (int)(AuthController::obterUsuarioAtual()['id'] ?? 0));
                }
                abortExportRequest('Não foi possível gerar o backup agora.', $isAjax, 500);
            }

        case 'download-backup':
            AuthController::exigirAutenticacao();

            try {
                $usuarioAtual = AuthController::obterUsuarioAtual();
                $token = (string)($_GET['token'] ?? '');
                $exportController = new ExportController($pdo);
                $exportController->streamDownload($token, (int)($usuarioAtual['id'] ?? 0));
            } catch (Throwable $exception) {
                if (function_exists('registrar_log')) {
                    registrar_log('EXPORT', 'Falha download-backup: ' . $exception->getMessage(), (int)(AuthController::obterUsuarioAtual()['id'] ?? 0));
                }

                $_SESSION['erro'] = $exception->getMessage();
                header('Location: ' . BASE_URL . '/index.php?page=perfil');
                exit;
            }

        case 'download-backup-log':
            AuthController::exigirAutenticacao();

            try {
                $usuarioAtual = AuthController::obterUsuarioAtual();
                $token = (string)($_GET['token'] ?? '');
                $logService = new BackupLogService($pdo);
                $record = $logService->findByToken($token);
                if (!$record) {
                    throw new RuntimeException('Link de backup inválido ou expirado.');
                }
                if ((int)($record['user_id'] ?? 0) > 0 && (int)($record['user_id'] ?? 0) !== (int)($usuarioAtual['id'] ?? 0)) {
                    throw new RuntimeException('Você não tem permissão para baixar este backup.');
                }

                $storage = new StorageService((string)($record['storage_disk'] ?? 'local'));
                $descriptor = $storage->getDescriptor((string)($record['file_path'] ?? ''));
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }

                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . basename((string)($record['meta_json']['filename'] ?? 'backup.zip')) . '"');
                header('Content-Length: ' . (string)($descriptor['size'] ?? 0));
                readfile((string)$descriptor['path']);
                exit;
            } catch (Throwable $exception) {
                $_SESSION['erro'] = $exception->getMessage();
                header('Location: ' . BASE_URL . '/index.php?page=perfil');
                exit;
            }

        case 'validar-backup':
            AuthController::exigirAutenticacao();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                abortExportRequest('Método inválido para validação do backup.', false, 405);
            }

            validateRouteCsrfOrAbort(false);

            try {
                $usuarioAtual = AuthController::obterUsuarioAtual();
                $importController = new ImportController($pdo);
                $backupPassword = (string)($_POST['backup_password'] ?? '');
                $preview = $importController->uploadBackup($_FILES['backup_zip'] ?? [], (int)($usuarioAtual['id'] ?? 0), $backupPassword !== '' ? $backupPassword : null);
                $_SESSION['mensagem'] = 'Backup validado com sucesso. Revise o resumo e confirme a restauração.';
                header('Location: ' . BASE_URL . '/index.php?page=perfil');
                exit;
            } catch (Throwable $exception) {
                if (function_exists('registrar_log')) {
                    registrar_log('IMPORT', 'Falha validar-backup: ' . $exception->getMessage(), (int)(AuthController::obterUsuarioAtual()['id'] ?? 0));
                }

                $_SESSION['erro'] = $exception->getMessage();
                header('Location: ' . BASE_URL . '/index.php?page=perfil');
                exit;
            }

        case 'restaurar-backup':
            AuthController::exigirAutenticacao();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                abortExportRequest('Método inválido para restauração do backup.', false, 405);
            }

            validateRouteCsrfOrAbort(false);

            try {
                $usuarioAtual = AuthController::obterUsuarioAtual();
                $token = (string)($_POST['backup_token'] ?? '');
                $backupPassword = (string)($_POST['backup_password'] ?? '');
                $restoreScope = (string)($_POST['restore_scope'] ?? 'full');
                $sourceCourseId = isset($_POST['restore_course_source_id']) && $_POST['restore_course_source_id'] !== '' ? (int)$_POST['restore_course_source_id'] : null;
                $sourceModuleId = isset($_POST['restore_module_source_id']) && $_POST['restore_module_source_id'] !== '' ? (int)$_POST['restore_module_source_id'] : null;
                $importController = new ImportController($pdo);
                if ($restoreScope === 'course' && $sourceCourseId) {
                    $result = $importController->restoreCourse($token, (int)($usuarioAtual['id'] ?? 0), $sourceCourseId, $backupPassword !== '' ? $backupPassword : null);
                } elseif ($restoreScope === 'module' && $sourceModuleId) {
                    $result = $importController->restoreModule($token, (int)($usuarioAtual['id'] ?? 0), $sourceModuleId, $backupPassword !== '' ? $backupPassword : null);
                } else {
                    $result = $importController->restoreBackup($token, (int)($usuarioAtual['id'] ?? 0), $backupPassword !== '' ? $backupPassword : null, $restoreScope, $sourceCourseId, $sourceModuleId);
                }
                $_SESSION['mensagem'] = $result['mensagem'] ?? 'Backup restaurado com sucesso.';
                header('Location: ' . BASE_URL . '/index.php?page=perfil');
                exit;
            } catch (Throwable $exception) {
                if (function_exists('registrar_log')) {
                    registrar_log('IMPORT', 'Falha restaurar-backup: ' . $exception->getMessage(), (int)(AuthController::obterUsuarioAtual()['id'] ?? 0));
                }

                $_SESSION['erro'] = $exception->getMessage();
                header('Location: ' . BASE_URL . '/index.php?page=perfil');
                exit;
            }

        // Painel do Professor - páginas de gestão
        case 'criar-curso':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $titulo = 'Criar Curso - Plataforma EAD';
            $conteudo = renderizar('criar-curso');
            break;

        case 'criar-modulo':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $course_id = (int)($_GET['course_id'] ?? 0);
            $titulo = 'Criar Módulo - Plataforma EAD';
            $conteudo = renderizar('criar-modulo', ['course_id' => $course_id]);
            break;

        case 'editar-modulo':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            require_once __DIR__ . '/../app/controllers/ModuleController.php';
            $moduleController = new ModuleController($pdo);
            $moduleResult = $moduleController->obter((int)($_GET['module_id'] ?? 0));
            if (empty($moduleResult['sucesso'])) {
                throw new Exception('Módulo não encontrado');
            }
            $titulo = 'Editar Módulo - Plataforma EAD';
            $conteudo = renderizar('editar-modulo', ['modulo' => $moduleResult['modulo'] ?? null]);
            break;

        case 'criar-aula':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $course_id = $_GET['course_id'] ?? null;
            require_once __DIR__ . '/../app/models/Module.php';
            $moduleModel = new Module($pdo);
            $module_options = $course_id ? $moduleModel->listarPorCurso((int)$course_id) : [];
            $module_id = (int)($_GET['module_id'] ?? 0);
            $titulo = 'Adicionar Aula - Plataforma EAD';
            $conteudo = renderizar('criar-aula', ['course_id' => $course_id, 'module_options' => $module_options, 'module_id' => $module_id]);
            break;

        case 'criar-quiz':
        case 'criar_quiz':
            AuthController::exigirAutenticacao();
            $lesson_id = (int)($_GET['lesson_id'] ?? 0);
            $course_id = (int)($_GET['course_id'] ?? 0);
            $module_id = (int)($_GET['module_id'] ?? 0);
            $course = null;

            if ($course_id <= 0 && $lesson_id > 0) {
                $lessonModel = new Lesson($pdo);
                $lesson = $lessonModel->obterPorId($lesson_id);
                $course_id = (int)($lesson['course_id'] ?? 0);
                $module_id = (int)($lesson['module_id'] ?? 0);
            }

            if ($course_id <= 0 && $module_id > 0) {
                require_once __DIR__ . '/../app/models/Module.php';
                $moduleModel = new Module($pdo);
                $module = $moduleModel->obterPorId($module_id);
                $course_id = (int)($module['course_id'] ?? 0);
            }

            if ($course_id > 0) {
                $courseController = new CourseController($pdo);
                $courseResult = $courseController->obter($course_id);
                if (!empty($courseResult['sucesso'])) {
                    $course = $courseResult['curso'] ?? null;
                }
            }

            $usuarioAtual = AuthController::obterUsuarioAtual();
            $ehAdmin = ($usuarioAtual['role'] ?? '') === 'admin';
            $ehDono = $course && (int)($course['teacher_id'] ?? 0) === (int)($usuarioAtual['id'] ?? 0);
            if (!$ehAdmin && !$ehDono) {
                throw new Exception('Você não tem permissão para criar quiz neste curso');
            }

            $lessonModel = new Lesson($pdo);
            $lesson_options = $course_id > 0 ? $lessonModel->listarPorCurso($course_id) : [];
            require_once __DIR__ . '/../app/models/Module.php';
            $moduleModel = new Module($pdo);
            $module_options = $course_id > 0 ? $moduleModel->listarPorCurso($course_id) : [];
            $existing_quizzes = [];

            if ($course_id > 0) {
                require_once __DIR__ . '/../app/models/Quiz.php';
                $quizModel = new Quiz($pdo);
                $existing_quizzes = $quizModel->listarPorCurso($course_id);
            }

            $titulo = 'Criar Quiz - Plataforma EAD';
            $conteudo = renderizar('criar-quiz', [
                'lesson_id' => $lesson_id,
                'course_id' => $course_id,
                'module_id' => $module_id,
                'lesson_options' => $lesson_options,
                'module_options' => $module_options,
                'course' => $course,
                'existing_quizzes' => $existing_quizzes
            ]);
            break;

        case 'editar-curso':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor', 'admin']);
            $course_id = $_GET['id'] ?? null;
            $courseController = new CourseController($pdo);
            $cursoResult = $courseController->obter($course_id);
            if (!$cursoResult['sucesso']) throw new Exception('Curso não encontrado');
            $curso = $cursoResult['curso'];
            $usuarioAtual = AuthController::obterUsuarioAtual();
            $ehAdmin = ($usuarioAtual['role'] ?? '') === 'admin';
            $ehDono = (int)($curso['teacher_id'] ?? 0) === (int)($usuarioAtual['id'] ?? 0);
            if (!$ehAdmin && !$ehDono) {
                throw new Exception('Você não tem permissão para editar este curso');
            }
            $titulo = 'Editar Curso - ' . htmlspecialchars($curso['titulo']);
            $conteudo = renderizar('editar-curso', ['curso' => $curso ?? null]);
            break;

        case 'editar-aula':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $lesson_id = (int)($_GET['lesson_id'] ?? 0);
            $lessonController = new LessonController($pdo);
            $lessonResult = $lessonController->obter($lesson_id);
            if (!$lessonResult['sucesso']) throw new Exception('Aula não encontrada');
            $aula = $lessonResult['aula'];

            $courseController = new CourseController($pdo);
            $cursoResult = $courseController->obter($aula['course_id'] ?? 0);
            if (!$cursoResult['sucesso']) throw new Exception('Curso não encontrado');
            $curso = $cursoResult['curso'];

            $usuarioAtual = AuthController::obterUsuarioAtual();
            $ehDono = (int)($curso['teacher_id'] ?? 0) === (int)($usuarioAtual['id'] ?? 0);
            if (!$ehDono) {
                throw new Exception('Você não tem permissão para editar esta aula');
            }

            $titulo = 'Editar Aula - ' . htmlspecialchars($aula['titulo'] ?? 'Aula');
            require_once __DIR__ . '/../app/models/Module.php';
            $moduleModel = new Module($pdo);
            $conteudo = renderizar('editar-aula', [
                'aula' => $aula ?? null,
                'curso' => $curso ?? null,
                'module_options' => $moduleModel->listarPorCurso((int)($curso['id'] ?? 0))
            ]);
            break;

        case 'gerenciar-curso':
            AuthController::exigirAutenticacao();
            $course_id = $_GET['id'] ?? null;
            $courseController = new CourseController($pdo);
            $cursoResult = $courseController->obter($course_id);
            if (!$cursoResult['sucesso']) throw new Exception('Curso não encontrado');
            $curso = $cursoResult['curso'];
            $usuarioAtual = AuthController::obterUsuarioAtual();
            $ehAdmin = ($usuarioAtual['role'] ?? '') === 'admin';
            $ehDono = (int)($curso['teacher_id'] ?? 0) === (int)($usuarioAtual['id'] ?? 0);
            if (!$ehAdmin && !$ehDono) {
                throw new Exception('Você não tem permissão para gerenciar este curso');
            }
            $quizAnalytics = ['media_turma' => 0, 'perguntas_criticas' => []];
            $quizStudents = [];
            $quizzes = [];
            try {
                require_once __DIR__ . '/../app/models/Quiz.php';
                $quizModel = new Quiz($pdo);
                $quizAnalytics = $quizModel->obterResumoProfessorCurso((int)$curso['id']);
                $quizStudents = $quizModel->listarDesempenhoCursoProfessor((int)$curso['id']);
                $quizzes = $quizModel->listarPorCurso((int)$curso['id']);
            } catch (Throwable $exception) {
                if (function_exists('registrar_log')) {
                    registrar_log('warning', 'gerenciar_curso_quiz_fallback curso=' . (int)$curso['id'] . ' erro=' . $exception->getMessage(), (int)(AuthController::obterUsuarioAtual()['id'] ?? 0));
                }
            }
            $titulo = 'Gerenciar Curso - ' . htmlspecialchars($curso['titulo']);
            $conteudo = renderizar('gerenciar-curso', [
                'curso' => $curso ?? null,
                'quizAnalytics' => $quizAnalytics ?? [],
                'quizStudents' => $quizStudents ?? [],
                'quizzes' => $quizzes ?? []
            ]);
            break;

        case 'alunos-curso':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $course_id = $_GET['id'] ?? null;
            $courseController = new CourseController($pdo);
            $cursoResult = $courseController->obter($course_id);
            if (!$cursoResult['sucesso']) throw new Exception('Curso não encontrado');
            $curso = $cursoResult['curso'];
            $usuarioAtual = AuthController::obterUsuarioAtual();
            if ((int)($curso['teacher_id'] ?? 0) !== (int)($usuarioAtual['id'] ?? 0)) {
                throw new Exception('Você não tem permissão para visualizar os alunos deste curso.');
            }
            $enrollmentModel = new Enrollment($pdo);
            $alunos = $enrollmentModel->obterAlunosCurso($course_id);
            $titulo = 'Alunos - ' . htmlspecialchars($curso['titulo']);
            $conteudo = renderizar('alunos-curso', ['curso' => $curso ?? null, 'alunos' => $alunos ?? []]);
            break;

        // Home
        case 'home':
        default:
            $titulo = 'Bem-vindo - Plataforma EAD';
            $courseController = new CourseController($pdo);
            $lista = $courseController->listar(1, 8);
            $cursos_destaque = $lista['cursos'] ?? [];
            $featuredDesktopColumns = 4;
            $featuredCount = count($cursos_destaque);
            if ($featuredCount >= $featuredDesktopColumns) {
                $featuredCompleteRows = intdiv($featuredCount, $featuredDesktopColumns) * $featuredDesktopColumns;
                $cursos_destaque = array_slice($cursos_destaque, 0, $featuredCompleteRows);
            }
            $conteudo = renderizar('home', ['cursos_destaque' => $cursos_destaque ?? []]);
            break;
    }
} catch (Throwable $e) {
    // Logar exceção e mostrar mensagem genérica ao usuário
    if (function_exists('registrar_log')) {
        registrar_log('exception', $e->getMessage());
    }
    $conteudo = '<div class="alert alert-error">Ocorreu um erro interno. Por favor, tente novamente mais tarde.</div>';
}

// If a partial fragment was requested, return only the view content (no layout)
if (isset($_GET['partial']) && $_GET['partial'] == '1') {
    // Return raw content (useful for modal fragments / AJAX)
    echo $conteudo;
    exit;
}

// Simple API endpoints
if (isset($_GET['api']) && $_GET['api'] === 'dashboard_counts') {
    AuthController::exigirAutenticacao();
    $dashboardController = new DashboardController($pdo);
    $dados = $dashboardController->dashboardAluno();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'total_cursos' => $dados['total_cursos'] ?? 0,
        'em_progresso' => $dados['em_progresso'] ?? 0,
        'concluidos' => $dados['concluidos'] ?? 0
    ]);
    exit;
}

// Renderizar layout
try {
    echo renderizarLayout($titulo, $conteudo, $pdo, $pdfExport);
} catch (Throwable $layoutException) {
    if (function_exists('registrar_log')) {
        registrar_log('exception', 'layout_render: ' . $layoutException->getMessage());
    }

    http_response_code(500);
    echo renderizarLayoutFallback($titulo, '<div class="alert alert-error">Não foi possível renderizar a página completa. O sistema ativou o modo de recuperação.</div>' . $conteudo);
}

/**
 * Renderizar uma view
 */
function renderizar($view, $data = [])
{
    // Validar nome da view para prevenir Local File Inclusion (LFI)
    $base = realpath(__DIR__ . '/../app/views');
    $view = preg_replace('/[^a-z0-9_\-]/i', '', $view);
    $path = $base . '/' . $view . '.php';
    $real = realpath($path);

    if ($real === false || strpos($real, $base) !== 0) {
        throw new Exception('Página inválida');
    }

    // Extração controlada: extrair apenas chaves com nomes válidos para evitar sobrescrita inesperada
    if (is_array($data)) {
        foreach ($data as $k => $v) {
            if (preg_match('/^[a-z_][a-z0-9_]*$/i', $k)) {
                ${$k} = $v;
            }
        }
    }

    ob_start();
    include $real;
    return ob_get_clean();
}

/**
 * Renderizar layout completo
 */
function renderizarLayout($titulo, $conteudo, $pdo, $pdfExport = false)
{
    ob_start();
    $layoutPath = __DIR__ . '/../app/views/layout.php';

    if (!is_file($layoutPath)) {
        ob_end_clean();
        return renderizarLayoutFallback($titulo, $conteudo);
    }

    include $layoutPath;
    return ob_get_clean();
}

function renderizarLayoutFallback($titulo, $conteudo)
{
    $tituloSeguro = htmlspecialchars((string)$titulo, ENT_QUOTES, 'UTF-8');
    $bodyClass = 'app-shell page-' . preg_replace('/[^a-z0-9\-]/i', '-', (string)($_GET['page'] ?? 'home'));
    $csrfToken = function_exists('gerar_csrf') ? gerar_csrf() : '';

    return '<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">
    <title>' . $tituloSeguro . '</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="' . BASE_URL . '/css/system.css">
    <link rel="stylesheet" href="' . BASE_URL . '/css/style.css">
</head>
<body class="' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') . '">
    <header class="navbar">
        <div class="navbar-container">
            <a class="navbar-logo" href="' . BASE_URL . '/index.php">
                <span class="navbar-logo-mark">E</span>
                <span class="navbar-logo-copy">
                    <strong>Plataforma EAD</strong>
                    <small>Ambiente de aprendizagem</small>
                </span>
            </a>
        </div>
    </header>
    <main class="main-container">' . $conteudo . '</main>
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">
                <a class="footer-logo" href="' . BASE_URL . '/index.php">Plataforma EAD</a>
                <p>Modo de recuperação ativo para manter o sistema acessível mesmo quando o layout principal falhar.</p>
            </div>
            <div class="footer-bottom">
                <p>&copy; ' . date('Y') . ' Plataforma EAD</p>
            </div>
        </div>
    </footer>
    <script src="' . BASE_URL . '/js/main.js"></script>
</body>
</html>';
}

/**
 * Processar ações POST
 */
function processarAcao($post, $pdo)
{
    // Usar apenas 'acao' como nome de ação (removida compatibilidade com 'action')
    $action = $post['acao'] ?? null;
    // Validar CSRF para ações que mudam estado
    $acoesProtegidas = [
        'login',
        'registrar',
        'solicitar_recuperacao_senha',
        'redefinir_senha',
        'matricular_curso',
        'marcar_concluida',
        'desmarcar_concluida',
        'responder_quiz',
        'atualizar_perfil',
        'alterar_senha',
        'atualizar_backup_preferencias',
        // professor/admin actions
        'criar_curso',
        'criar_modulo',
        'atualizar_modulo',
        'mover_modulo',
        'atualizar_curso',
        'deletar_curso',
        'criar_aula',
        'atualizar_aula',
        'deletar_aula',
        'criar_quiz',
        'deletar_quiz',
        'adicionar_questao',
        // enrollment management
        'remover_matricula',
        'atualizar_progresso',
        'restaurar_matricula',
        'admin_alterar_status_curso',
        'admin_deletar_usuario',
        'admin_deletar_quiz'
    ];
    $isAjax = isAjaxRequest();
    // Se for uma chamada AJAX, padronizar Content-Type para JSON com charset
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
    }
    if (in_array($action, $acoesProtegidas)) {
        $token = $post['csrf_token'] ?? '';
        if (!validar_csrf($token)) {
            $msg = 'Token CSRF inválido ou expirado. Por favor, atualize a página e tente novamente.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'mensagem' => $msg]);
            } else {
                $_SESSION['erro'] = $msg;
                $redirect = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php';
                header('Location: ' . $redirect);
            }
            exit;
        }
    }

    switch ($action) {
        // --- Ações de professor: cursos, aulas, quizzes ---
        case 'criar_curso':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $titulo_in = $post['titulo'] ?? '';
            $descricao_in = $post['descricao'] ?? '';
            $categoria_in = $post['categoria'] ?? '';
            $course_structure_in = $post['course_structure'] ?? 'single_module';

            $thumbnail_name = null;
            if (!empty($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $upload = fazer_upload($_FILES['thumbnail'], __DIR__ . '/uploads', ['jpg', 'jpeg', 'png']);
                if ($upload['sucesso']) {
                    $thumbnail_name = basename($upload['nome']);
                }
            }

            $courseController = new CourseController($pdo);
            $result = $courseController->criar($titulo_in, $descricao_in, $categoria_in, $thumbnail_name, $course_structure_in);
            // fallback: CourseController expects (titulo, descricao, categoria, thumbnail)
            // registrar_log pode ser usado para auditoria, mas evitar logs debug em produção
            if ($result['sucesso']) {
                $redirect = BASE_URL . '/index.php?page=gerenciar-curso&id=' . $result['id'];
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['sucesso' => true, 'redirect' => $redirect]);
                } else header('Location: ' . $redirect);
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['sucesso' => false, 'mensagem' => $result['mensagem']]);
                } else {
                    $_SESSION['erro'] = $result['mensagem'];
                    header('Location: ' . BASE_URL . '/index.php?page=criar-curso');
                }
            }
            exit;

        case 'atualizar_curso':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor', 'admin']);
            $course_id = $post['course_id'] ?? 0;
            $titulo_in = $post['titulo'] ?? '';
            $descricao_in = $post['descricao'] ?? '';
            $categoria_in = $post['categoria'] ?? '';

            $thumbnail_name = null;
            if (!empty($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $upload = fazer_upload($_FILES['thumbnail'], __DIR__ . '/uploads', ['jpg', 'jpeg', 'png']);
                if ($upload['sucesso']) {
                    $thumbnail_name = basename($upload['nome']);
                }
            }

            $courseController = new CourseController($pdo);
            $result = $courseController->atualizar($course_id, $titulo_in, $descricao_in, $categoria_in, null);
            // If thumbnail provided, call model directly to update thumbnail
            if ($thumbnail_name) {
                require_once __DIR__ . '/../app/models/Course.php';
                $courseModel = new Course($pdo);
                $courseModel->atualizar($course_id, $titulo_in, $descricao_in, $categoria_in, null, $thumbnail_name);
            }

            if ($result['sucesso']) {
                $usuarioAtual = AuthController::obterUsuarioAtual();
                $redirect = ($usuarioAtual['role'] ?? '') === 'admin'
                    ? BASE_URL . '/index.php?page=admin-cursos'
                    : BASE_URL . '/index.php?page=gerenciar-curso&id=' . $course_id;

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['sucesso' => true, 'redirect' => $redirect]);
                } else {
                    $_SESSION['mensagem'] = $result['mensagem'];
                    header('Location: ' . $redirect);
                }
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['sucesso' => false, 'mensagem' => $result['mensagem']]);
                } else {
                    $_SESSION['erro'] = $result['mensagem'];
                    header('Location: ' . BASE_URL . '/index.php?page=editar-curso&id=' . $course_id);
                }
            }
            exit;

        case 'deletar_curso':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor', 'admin']);
            $course_id = $post['course_id'] ?? 0;
            $courseController = new CourseController($pdo);
            $result = $courseController->deletar($course_id);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                $_SESSION[$result['sucesso'] ? 'mensagem' : 'erro'] = $result['mensagem'];
                $redirect = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php?page=dashboard';
                header('Location: ' . $redirect);
            }
            exit;

        case 'criar_modulo':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            require_once __DIR__ . '/../app/controllers/ModuleController.php';
            $moduleController = new ModuleController($pdo);
            $course_id = (int)($post['course_id'] ?? 0);
            $result = $moduleController->criar($course_id, trim((string)($post['titulo'] ?? '')), trim((string)($post['descricao'] ?? '')));
            if ($isAjax) {
                if (!empty($result['sucesso'])) {
                    $result['redirect'] = BASE_URL . '/index.php?page=gerenciar-curso&id=' . $course_id;
                }
                echo json_encode($result);
            } else {
                $_SESSION[$result['sucesso'] ? 'mensagem' : 'erro'] = $result['mensagem'] ?? ($result['sucesso'] ? 'Módulo criado com sucesso.' : 'Erro ao criar módulo.');
                header('Location: ' . BASE_URL . '/index.php?page=gerenciar-curso&id=' . $course_id);
            }
            exit;

        case 'atualizar_modulo':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            require_once __DIR__ . '/../app/controllers/ModuleController.php';
            $moduleController = new ModuleController($pdo);
            $module_id = (int)($post['module_id'] ?? 0);
            $course_id = (int)($post['course_id'] ?? 0);
            $result = $moduleController->atualizar($module_id, trim((string)($post['titulo'] ?? '')), trim((string)($post['descricao'] ?? '')));
            if ($isAjax) {
                if (!empty($result['sucesso'])) {
                    $result['redirect'] = BASE_URL . '/index.php?page=gerenciar-curso&id=' . $course_id;
                }
                echo json_encode($result);
            } else {
                $_SESSION[$result['sucesso'] ? 'mensagem' : 'erro'] = $result['mensagem'] ?? ($result['sucesso'] ? 'Módulo atualizado com sucesso.' : 'Erro ao atualizar módulo.');
                header('Location: ' . BASE_URL . '/index.php?page=gerenciar-curso&id=' . $course_id);
            }
            exit;

        case 'mover_modulo':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            require_once __DIR__ . '/../app/controllers/ModuleController.php';
            $moduleController = new ModuleController($pdo);
            $module_id = (int)($post['module_id'] ?? 0);
            $course_id = (int)($post['course_id'] ?? 0);
            $direction = (string)($post['direction'] ?? 'up');
            $result = $moduleController->mover($module_id, $direction);
            if ($isAjax) {
                echo json_encode($result);
            } else {
                $_SESSION[$result['sucesso'] ? 'mensagem' : 'erro'] = $result['mensagem'] ?? ($result['sucesso'] ? 'Módulo reordenado.' : 'Erro ao reordenar módulo.');
                header('Location: ' . BASE_URL . '/index.php?page=gerenciar-curso&id=' . $course_id);
            }
            exit;

        case 'admin_alterar_status_curso':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['admin']);
            $adminController = new AdminController($pdo);
            $result = $adminController->alterarStatusCurso((int)($post['course_id'] ?? 0), (string)($post['status'] ?? ''));
            if ($isAjax) {
                echo json_encode($result);
            } else {
                $_SESSION[$result['sucesso'] ? 'mensagem' : 'erro'] = $result['mensagem'];
                $redirect = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php?page=admin-cursos';
                header('Location: ' . $redirect);
            }
            exit;

        case 'admin_deletar_usuario':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['admin']);
            $adminController = new AdminController($pdo);
            $result = $adminController->deletarUsuario((int)($post['user_id'] ?? 0));
            if ($isAjax) {
                echo json_encode($result);
            } else {
                $_SESSION[$result['sucesso'] ? 'mensagem' : 'erro'] = $result['mensagem'];
                $redirect = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php?page=dashboard';
                header('Location: ' . $redirect);
            }
            exit;
        case 'login':
            $auth = new AuthController($pdo);
            $result = $auth->login(
                $post['email'] ?? '',
                $post['senha'] ?? ''
            );

            if ($result['sucesso']) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['sucesso' => true, 'mensagem' => 'Login realizado com sucesso!', 'redirect' => BASE_URL . '/index.php?page=dashboard']);
                } else {
                    $_SESSION['mensagem'] = 'Login realizado com sucesso!';
                    header('Location: ' . BASE_URL . '/index.php?page=dashboard');
                }
            } else {
                if ($isAjax) {
                    $payload = ['sucesso' => false, 'mensagem' => $result['mensagem']];
                    if (($result['mensagem'] ?? '') === 'Verifique seu email para ativar sua conta antes de entrar.') {
                        $payload['redirect'] = BASE_URL . '/index.php?page=confirmar-email&email=' . urlencode((string)($post['email'] ?? '')) . '&context=login';
                    }
                    header('Content-Type: application/json');
                    echo json_encode($payload);
                } else {
                    if (($result['mensagem'] ?? '') === 'Verifique seu email para ativar sua conta antes de entrar.') {
                        $_SESSION['erro'] = $result['mensagem'];
                        header('Location: ' . BASE_URL . '/index.php?page=confirmar-email&email=' . urlencode((string)($post['email'] ?? '')) . '&context=login');
                    } else {
                        $_SESSION['erro'] = $result['mensagem'];
                        header('Location: ' . BASE_URL . '/index.php?page=login');
                    }
                }
            }
            exit;

        case 'registrar':
            $auth = new AuthController($pdo);
            $role = $post['role'] ?? 'aluno';
            $result = $auth->registrar(
                $post['nome'] ?? '',
                $post['email'] ?? '',
                $post['senha'] ?? '',
                $post['confirm_senha'] ?? '',
                $role
            );

            if ($result['sucesso']) {
                if ($isAjax) {
                    echo json_encode([
                        'sucesso' => true,
                        'mensagem' => $result['mensagem'],
                        'redirect' => BASE_URL . '/index.php?page=confirmar-email&email=' . urlencode((string)($post['email'] ?? '')) . '&context=signup'
                    ]);
                } else {
                    $_SESSION['mensagem'] = $result['mensagem'];
                    header('Location: ' . BASE_URL . '/index.php?page=confirmar-email&email=' . urlencode((string)($post['email'] ?? '')) . '&context=signup');
                }
            } else {
                $erroMsg = implode(', ', $result['erros'] ?? [$result['mensagem']]);
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => $erroMsg]);
                } else {
                    $_SESSION['erro'] = $erroMsg;
                    header('Location: ' . BASE_URL . '/index.php?page=' . (($role === 'professor') ? 'registro-professor' : 'registro'));
                }
            }
            exit;

        case 'reenviar_confirmacao_email':
            $auth = new AuthController($pdo);
            $result = $auth->solicitarReenvioConfirmacaoEmail($post['email'] ?? '');

            if ($isAjax) {
                echo json_encode([
                    'sucesso' => !empty($result['sucesso']),
                    'mensagem' => $result['mensagem'] ?? 'Processamos sua solicitação.',
                ]);
            } else {
                $_SESSION[!empty($result['sucesso']) ? 'mensagem' : 'erro'] = $result['mensagem'] ?? 'Processamos sua solicitação.';
                header('Location: ' . BASE_URL . '/index.php?page=confirmar-email&email=' . urlencode((string)($post['email'] ?? '')));
            }
            exit;

        case 'confirmar_email_codigo':
            $auth = new AuthController($pdo);
            $result = $auth->confirmarEmailPorCodigo(
                $post['email'] ?? '',
                $post['codigo'] ?? ''
            );

            if ($result['sucesso']) {
                if ($isAjax) {
                    echo json_encode([
                        'sucesso' => true,
                        'mensagem' => $result['mensagem'],
                        'redirect' => BASE_URL . '/index.php?page=login'
                    ]);
                } else {
                    $_SESSION['mensagem'] = $result['mensagem'];
                    header('Location: ' . BASE_URL . '/index.php?page=login');
                }
            } else {
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => $result['mensagem']]);
                } else {
                    $_SESSION['erro'] = $result['mensagem'];
                    header('Location: ' . BASE_URL . '/index.php?page=confirmar-email&email=' . urlencode((string)($post['email'] ?? '')));
                }
            }
            exit;

        case 'solicitar_recuperacao_senha':
            $auth = new AuthController($pdo);
            $result = $auth->solicitarRecuperacaoSenha($post['email'] ?? '');

            if ($isAjax) {
                echo json_encode(['sucesso' => true, 'mensagem' => $result['mensagem']]);
            } else {
                $_SESSION['mensagem'] = $result['mensagem'];
                header('Location: ' . BASE_URL . '/index.php?page=esqueci-senha');
            }
            exit;

        case 'redefinir_senha':
            $auth = new AuthController($pdo);
            $result = $auth->redefinirSenha(
                $post['token'] ?? '',
                $post['senha'] ?? '',
                $post['confirm_senha'] ?? ''
            );

            if ($result['sucesso']) {
                if ($isAjax) {
                    echo json_encode([
                        'sucesso' => true,
                        'mensagem' => $result['mensagem'],
                        'redirect' => BASE_URL . '/index.php?page=login'
                    ]);
                } else {
                    $_SESSION['mensagem'] = $result['mensagem'];
                    header('Location: ' . BASE_URL . '/index.php?page=login');
                }
            } else {
                $erroMsg = implode(', ', $result['erros'] ?? [$result['mensagem']]);
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => $erroMsg]);
                } else {
                    $_SESSION['erro'] = $erroMsg;
                    header('Location: ' . BASE_URL . '/index.php?page=redefinir-senha&token=' . urlencode((string)($post['token'] ?? '')));
                }
            }
            exit;

        case 'matricular_curso':
            AuthController::exigirAutenticacao();
            $courseController = new CourseController($pdo);
            $result = $courseController->matricular($_POST['course_id'] ?? 0);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => $result['sucesso'], 'mensagem' => $result['mensagem'] ?? '', 'redirect' => BASE_URL . '/index.php?page=curso&id=' . ($_POST['course_id'] ?? 0)]);
            } else {
                if ($result['sucesso']) {
                    $_SESSION['mensagem'] = 'Matrícula realizada com sucesso!';
                } else {
                    $_SESSION['erro'] = $result['mensagem'];
                }

                header('Location: ' . BASE_URL . '/index.php?page=curso&id=' . ($_POST['course_id'] ?? 0));
            }
            exit;

        case 'atualizar_perfil':
            AuthController::exigirAutenticacao();
            require_once __DIR__ . '/../app/models/User.php';

            $usuarioAtual = AuthController::obterUsuarioAtual();
            $userModel = new User($pdo);
            $nome = trim($post['nome'] ?? '');
            $email = trim($post['email'] ?? '');

            if ($nome === '' || mb_strlen($nome) < 3) {
                $_SESSION['erro'] = 'O nome deve ter pelo menos 3 caracteres.';
                header('Location: ' . BASE_URL . '/index.php?page=perfil');
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['erro'] = 'Informe um email válido.';
                header('Location: ' . BASE_URL . '/index.php?page=perfil');
                exit;
            }

            if (!$userModel->emailDisponivel($email, (int)$usuarioAtual['id'])) {
                $_SESSION['erro'] = 'Este email já está em uso por outra conta.';
                header('Location: ' . BASE_URL . '/index.php?page=perfil');
                exit;
            }

            $fotografia = null;
            if (!empty($_FILES['fotografia']) && ($_FILES['fotografia']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['fotografia']['error'] === UPLOAD_ERR_OK) {
                    $upload = fazer_upload($_FILES['fotografia'], __DIR__ . '/uploads', ['jpg', 'jpeg', 'png', 'gif']);
                    if (!($upload['sucesso'] ?? false)) {
                        $_SESSION['erro'] = $upload['mensagem'] ?? 'Erro ao enviar a foto de perfil.';
                        header('Location: ' . BASE_URL . '/index.php?page=perfil');
                        exit;
                    }
                    $fotografia = basename($upload['nome']);
                } else {
                    $_SESSION['erro'] = 'Erro ao enviar a foto de perfil.';
                    header('Location: ' . BASE_URL . '/index.php?page=perfil');
                    exit;
                }
            }

            $ok = $userModel->atualizar((int)$usuarioAtual['id'], $nome, $email, $fotografia);
            if (!$ok) {
                $_SESSION['erro'] = 'Não foi possível atualizar o perfil.';
                header('Location: ' . BASE_URL . '/index.php?page=perfil');
                exit;
            }

            $usuarioAtualizado = $userModel->obterPorId((int)$usuarioAtual['id']);
            if ($usuarioAtualizado) {
                $_SESSION['usuario']['nome'] = $usuarioAtualizado['nome'];
                $_SESSION['usuario']['email'] = $usuarioAtualizado['email'];
                $_SESSION['usuario']['role'] = $usuarioAtualizado['role'];
                $_SESSION['usuario']['fotografia'] = $usuarioAtualizado['fotografia'] ?? null;
            }

            $_SESSION['mensagem'] = 'Perfil atualizado com sucesso.';
            header('Location: ' . BASE_URL . '/index.php?page=perfil');
            exit;

        case 'alterar_senha':
            AuthController::exigirAutenticacao();
            require_once __DIR__ . '/../app/models/User.php';

            $usuarioAtual = AuthController::obterUsuarioAtual();
            $userModel = new User($pdo);
            $senhaAtual = (string)($post['senha_atual'] ?? '');
            $novaSenha = (string)($post['nova_senha'] ?? '');
            $confirmarSenha = (string)($post['confirmar_senha'] ?? '');

            if ($novaSenha !== $confirmarSenha) {
                $_SESSION['erro'] = 'A confirmação da nova senha não confere.';
                header('Location: ' . BASE_URL . '/index.php?page=perfil');
                exit;
            }

            if (strlen($novaSenha) < 6) {
                $_SESSION['erro'] = 'A nova senha deve ter pelo menos 6 caracteres.';
                header('Location: ' . BASE_URL . '/index.php?page=perfil');
                exit;
            }

            $usuarioCompleto = $userModel->obterComSenhaPorId((int)$usuarioAtual['id']);
            if (!$usuarioCompleto || !password_verify($senhaAtual, $usuarioCompleto['senha_hash'] ?? '')) {
                $_SESSION['erro'] = 'A senha atual está incorreta.';
                header('Location: ' . BASE_URL . '/index.php?page=perfil');
                exit;
            }

            $ok = $userModel->atualizarSenha((int)$usuarioAtual['id'], password_hash($novaSenha, PASSWORD_BCRYPT));
            if (!$ok) {
                $_SESSION['erro'] = 'Não foi possível alterar a senha.';
                header('Location: ' . BASE_URL . '/index.php?page=perfil');
                exit;
            }

            $_SESSION['mensagem'] = 'Senha alterada com sucesso.';
            header('Location: ' . BASE_URL . '/index.php?page=perfil');
            exit;

        case 'atualizar_backup_preferencias':
            AuthController::exigirAutenticacao();
            $usuarioAtual = AuthController::obterUsuarioAtual();
            $backupLogService = new BackupLogService($pdo);
            $backupLogService->savePreference((int)($usuarioAtual['id'] ?? 0), [
                'auto_enabled' => !empty($post['backup_auto_enabled']),
                'frequency' => (string)($post['backup_frequency'] ?? 'daily'),
                'receive_email' => !empty($post['backup_receive_email']),
            ]);
            $_SESSION['mensagem'] = 'Preferências de backup atualizadas com sucesso.';
            header('Location: ' . BASE_URL . '/index.php?page=perfil');
            exit;

        case 'criar_aula':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $course_id = $_POST['course_id'] ?? 0;
            $module_id = (int)($_POST['module_id'] ?? 0);
            $titulo_a = trim($_POST['titulo'] ?? '');
            $descricao_a = trim($_POST['descricao'] ?? '');
            $conteudo_a = trim($_POST['conteudo'] ?? '');
            $tipo = $_POST['tipo'] ?? 'texto';
            $url_arquivo = null;
            if (!empty($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['pdf', 'mp4', 'jpg', 'jpeg', 'png'];
                $upload = fazer_upload($_FILES['arquivo'], __DIR__ . '/uploads', $allowed);
                if ($upload['sucesso']) {
                    $url_arquivo = basename($upload['nome']);
                }
            }

            // aceitar URL do YouTube (campo youtube_url) e extrair ID
            $youtube_url = trim($post['youtube_url'] ?? '');
            $video_id = null;
            if (!empty($youtube_url) && function_exists('youtube_id_from_url')) {
                $video_id = youtube_id_from_url($youtube_url);
            }

            $lessonController = new LessonController($pdo);
            $result = $lessonController->criar($course_id, $titulo_a, $descricao_a, $tipo, $conteudo_a, $url_arquivo, $video_id, $module_id);
            if ($isAjax) {
                if (!empty($result['sucesso'])) {
                    $result['redirect'] = BASE_URL . '/index.php?page=gerenciar-curso&id=' . (int)$course_id;
                }
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                $_SESSION[$result['sucesso'] ? 'mensagem' : 'erro'] = $result['mensagem'];
                header('Location: ' . BASE_URL . '/index.php?page=gerenciar-curso&id=' . $course_id);
            }
            exit;

        case 'deletar_aula':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $lesson_id = $_POST['lesson_id'] ?? 0;
            $lessonController = new LessonController($pdo);
            $result = $lessonController->deletar($lesson_id);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                $_SESSION[$result['sucesso'] ? 'mensagem' : 'erro'] = $result['mensagem'];
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            }
            exit;

        case 'atualizar_aula':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $lesson_id = (int)($_POST['lesson_id'] ?? 0);
            $course_id = (int)($_POST['course_id'] ?? 0);
            $module_id = (int)($_POST['module_id'] ?? 0);
            $titulo_a = trim($_POST['titulo'] ?? '');
            $descricao_a = trim($_POST['descricao'] ?? '');
            $conteudo_a = trim($_POST['conteudo'] ?? '');
            $tipo = $_POST['tipo'] ?? 'texto';
            $url_arquivo = null;

            if (!empty($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['pdf', 'mp4', 'jpg', 'jpeg', 'png'];
                $upload = fazer_upload($_FILES['arquivo'], __DIR__ . '/uploads', $allowed);
                if ($upload['sucesso']) {
                    $url_arquivo = basename($upload['nome']);
                }
            }

            $youtube_url = trim($post['youtube_url'] ?? '');
            $video_id = null;
            if (!empty($youtube_url) && function_exists('youtube_id_from_url')) {
                $video_id = youtube_id_from_url($youtube_url);
            }

            $lessonController = new LessonController($pdo);
            $result = $lessonController->atualizar($lesson_id, $titulo_a, $descricao_a, $tipo, $conteudo_a, $url_arquivo, $video_id, $module_id);
            if ($isAjax) {
                if (!empty($result['sucesso'])) {
                    $result['redirect'] = BASE_URL . '/index.php?page=gerenciar-curso&id=' . (int)$course_id;
                }
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                $_SESSION[$result['sucesso'] ? 'mensagem' : 'erro'] = $result['mensagem'];
                header('Location: ' . BASE_URL . '/index.php?page=gerenciar-curso&id=' . $course_id);
            }
            exit;

        case 'reordenar_aulas':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $course_id = $_POST['course_id'] ?? 0;
            $orderRaw = $_POST['order'] ?? '';
            $ids = [];
            if (is_string($orderRaw)) {
                $decoded = json_decode($orderRaw, true);
                if (is_array($decoded)) $ids = $decoded;
                else $ids = array_filter(array_map('intval', explode(',', $orderRaw)));
            } elseif (is_array($orderRaw)) {
                $ids = $orderRaw;
            }

            // Validar que o professor é dono do curso
            $courseController = new CourseController($pdo);
            $cursoResult = $courseController->obter($course_id);
            if (!$cursoResult['sucesso']) {
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Curso não encontrado']);
                } else {
                    $_SESSION['erro'] = 'Curso não encontrado';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
                exit;
            }

            $curso = $cursoResult['curso'];
            $usuarioAtual = AuthController::obterUsuarioAtual();
            if (!$usuarioAtual || $curso['teacher_id'] != $usuarioAtual['id']) {
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Você não tem permissão']);
                } else {
                    $_SESSION['erro'] = 'Você não tem permissão';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
                exit;
            }

            require_once __DIR__ . '/../app/models/Lesson.php';
            $lessonModel = new Lesson($pdo);
            $ok = $lessonModel->reordenar($ids);

            if ($isAjax) {
                echo json_encode(['sucesso' => $ok, 'mensagem' => $ok ? 'Ordem de aulas atualizada' : 'Erro ao atualizar ordem']);
            } else {
                $_SESSION[$ok ? 'mensagem' : 'erro'] = $ok ? 'Ordem atualizada' : 'Erro ao atualizar ordem';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            }
            exit;

        case 'criar_quiz':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $lesson_id = (int)($_POST['lesson_id'] ?? 0);
            $module_id = (int)($_POST['module_id'] ?? 0);
            $titulo_q = trim($_POST['titulo'] ?? '');
            $descricao_q = trim($_POST['descricao'] ?? '');
            $tipo_q = $_POST['tipo'] ?? 'final';
            $dificuldade_q = trim((string)($_POST['dificuldade'] ?? ($tipo_q === 'final' ? 'dificil' : 'normal')));
            $tentativas_q = (int)($_POST['tentativas_maximas'] ?? 3);
            $obrigatorio_q = !empty($_POST['obrigatorio']) ? 1 : 0;
            $course_id_q = (int)($_POST['course_id'] ?? 0);
            $tempo_limite_q = isset($_POST['tempo_limite']) ? (int)$_POST['tempo_limite'] : null;
            $embaralhar_perguntas_q = !empty($_POST['embaralhar_perguntas']) ? 1 : 0;
            $embaralhar_respostas_q = !empty($_POST['embaralhar_respostas']) ? 1 : 0;
            $mostrar_respostas_q = !empty($_POST['mostrar_respostas']) ? 1 : 0;
            $mostrar_nota_q = !empty($_POST['mostrar_nota']) ? 1 : 0;
            $questions_q = is_array($_POST['questions'] ?? null) ? $_POST['questions'] : [];
            $quizController = new QuizController($pdo);
            $result = $quizController->criar($lesson_id, $titulo_q, $descricao_q, $tentativas_q, 20, [
                'course_id' => $course_id_q,
                'module_id' => $module_id,
                'tipo' => $tipo_q,
                'dificuldade' => $dificuldade_q,
                'obrigatorio' => $obrigatorio_q,
                'tempo_limite' => $tempo_limite_q,
                'embaralhar_perguntas' => $embaralhar_perguntas_q,
                'embaralhar_respostas' => $embaralhar_respostas_q,
                'mostrar_respostas' => $mostrar_respostas_q,
                'mostrar_nota' => $mostrar_nota_q,
                'questions' => $questions_q
            ]);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                $_SESSION[$result['sucesso'] ? 'mensagem' : 'erro'] = $result['mensagem'];
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            }
            exit;

        case 'deletar_quiz':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor', 'admin']);
            $quiz_id = $_POST['quiz_id'] ?? 0;
            $quizController = new QuizController($pdo);
            $result = $quizController->deletar($quiz_id);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                $_SESSION[$result['sucesso'] ? 'mensagem' : 'erro'] = $result['mensagem'];
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            }
            exit;

        case 'admin_deletar_quiz':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['admin']);
            $quiz_id = $_POST['quiz_id'] ?? 0;
            $quizController = new QuizController($pdo);
            $result = $quizController->deletar($quiz_id);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                $_SESSION[$result['sucesso'] ? 'mensagem' : 'erro'] = $result['mensagem'];
                $redirect = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php?page=admin-quizzes';
                header('Location: ' . $redirect);
            }
            exit;

        case 'adicionar_questao':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $quiz_id = $_POST['quiz_id'] ?? 0;
            $texto = trim($_POST['texto'] ?? '');
            $tipo = $_POST['tipo'] ?? 'multipla';
            $opcoes = [];
            $resposta = $_POST['resposta_correta'] ?? '';
            $explicacao = trim($_POST['explicacao'] ?? '');

            if ($tipo === 'multipla') {
                // coletar opcoes enviadas como opcao_0, opcao_1, ...
                foreach ($_POST as $k => $v) {
                    if (strpos($k, 'opcao_') === 0) {
                        $opcoes[] = trim($v);
                    }
                }
            }

            $quizController = new QuizController($pdo);
            $result = $quizController->adicionarQuestao($quiz_id, $texto, $tipo, $opcoes, $resposta, $explicacao);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                $_SESSION[$result['sucesso'] ? 'mensagem' : 'erro'] = $result['mensagem'];
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            }
            exit;

        case 'deletar_questao':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $question_id = $_POST['question_id'] ?? 0;
            $quizController = new QuizController($pdo);
            $result = $quizController->deletarQuestao($question_id);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                $_SESSION[$result['sucesso'] ? 'mensagem' : 'erro'] = $result['mensagem'];
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            }
            exit;

        case 'marcar_concluida':
            AuthController::exigirAutenticacao();
            $lessonController = new LessonController($pdo);
            $result = $lessonController->marcarConcluida($_POST['lesson_id'] ?? 0);

            if ($isAjax) {
                header('Content-Type: application/json');
                // Obter o course_id da aula para retornar
                if ($result['sucesso']) {
                    $lessonModel = new Lesson($pdo);
                    $aula = $lessonModel->obterPorId($_POST['lesson_id'] ?? 0);
                    $result['course_id'] = $aula['course_id'] ?? null;
                }
                echo json_encode($result);
            } else {
                if ($result['sucesso']) {
                    $_SESSION['mensagem'] = $result['mensagem'];
                } else {
                    $_SESSION['erro'] = $result['mensagem'];
                }

                header('Location: ' . $_SERVER['HTTP_REFERER']);
            }
            exit;

        case 'desmarcar_concluida':
            AuthController::exigirAutenticacao();
            $lessonController = new LessonController($pdo);
            $result = $lessonController->desmarcarConcluida($_POST['lesson_id'] ?? 0);

            if ($isAjax) {
                header('Content-Type: application/json');
                if ($result['sucesso']) {
                    $lessonModel = new Lesson($pdo);
                    $aula = $lessonModel->obterPorId($_POST['lesson_id'] ?? 0);
                    $result['course_id'] = $aula['course_id'] ?? null;
                }
                echo json_encode($result);
            } else {
                if ($result['sucesso']) {
                    $_SESSION['mensagem'] = $result['mensagem'];
                } else {
                    $_SESSION['erro'] = $result['mensagem'];
                }

                header('Location: ' . $_SERVER['HTTP_REFERER']);
            }
            exit;

        case 'responder_quiz':
            AuthController::exigirAutenticacao();
            $quizController = new QuizController($pdo);
            $quizId = (int)($_POST['quiz_id'] ?? 0);
            $usuarioAtual = AuthController::obterUsuarioAtual();
            $timerState = getQuizTimerState($quizId, (int)($usuarioAtual['id'] ?? 0));
            $quizMeta = $quizController->obter($quizId);
            $quizData = (!empty($quizMeta['sucesso']) && !empty($quizMeta['quiz'])) ? $quizMeta['quiz'] : null;
            $tempoLimiteConfigurado = (int)($quizData['tempo_limite'] ?? 0);
            $agora = time();
            $tempoGasto = max(0, (int)($_POST['tempo_gasto'] ?? 0));
            $tempoEsgotado = !empty($_POST['tempo_esgotado']);

            if ($tempoLimiteConfigurado > 0 && !$timerState) {
                $result = ['sucesso' => false, 'mensagem' => 'A sessão do quiz expirou. Abra o quiz novamente para iniciar uma nova tentativa.'];
                if ($isAjax) {
                    echo json_encode($result);
                } else {
                    $_SESSION['erro'] = $result['mensagem'];
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
                exit;
            }

            if ($timerState) {
                $tempoGasto = max(0, $agora - (int)($timerState['started_at'] ?? $agora));
                $tempoEsgotado = $tempoEsgotado || ((int)($timerState['expires_at'] ?? 0) > 0 && $agora >= (int)$timerState['expires_at']);
            }

            // Preparar respostas
            $respostas = [];
            foreach ($post as $key => $value) {
                if (strpos($key, 'questao_') === 0) {
                    $question_id = str_replace('questao_', '', $key);
                    $respostas[$question_id] = $value;
                }
            }

            $result = $quizController->corrigirResposta(
                $quizId,
                $respostas,
                [
                    'tempo_gasto' => $tempoGasto,
                    'tempo_esgotado' => $tempoEsgotado,
                    'permitir_em_branco' => $tempoEsgotado
                ]
            );

            if (!empty($result['sucesso']) || $tempoEsgotado) {
                clearQuizTimerState($quizId, (int)($usuarioAtual['id'] ?? 0));
                clearQuizViewState($quizId, (int)($usuarioAtual['id'] ?? 0));
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                if ($result['sucesso']) {
                    $mensagem = !empty($result['tempo_esgotado'])
                        ? 'Tempo esgotado. O quiz foi enviado automaticamente.'
                        : 'Quiz respondido! Sua pontuação: ' . number_format((float)($result['score'] ?? 0), 1, ',', '.') . '/20';
                    echo json_encode(['sucesso' => true, 'mensagem' => $mensagem, 'resultado' => $result]);
                } else {
                    echo json_encode(['sucesso' => false, 'mensagem' => $result['mensagem'] ?? 'Erro ao processar quiz']);
                }
            } else {
                if ($result['sucesso']) {
                    $result['quiz_id'] = $quizId;
                    $_SESSION['quiz_resultado'] = $result;
                    $baseMessage = !empty($result['tempo_esgotado'])
                        ? 'Tempo esgotado. O quiz foi enviado automaticamente.'
                        : 'Quiz respondido! Sua pontuação: ' . round($result['score'], 2) . ' pontos';
                    $certificateEvents = is_array($result['certificate_events'] ?? null) ? $result['certificate_events'] : [];
                    if (!empty($certificateEvents)) {
                        $labels = array_map(static function ($event) {
                            $certificate = $event['certificate'] ?? [];
                            return ($certificate['type'] ?? 'course') === 'module'
                                ? 'Certificado do módulo liberado'
                                : 'Certificado final do curso liberado';
                        }, $certificateEvents);
                        $baseMessage .= ' ' . implode(' · ', array_unique($labels));
                    }
                    $_SESSION['mensagem'] = $baseMessage;
                } else {
                    $_SESSION['erro'] = $result['mensagem'];
                }

                header('Location: ' . $_SERVER['HTTP_REFERER']);
            }
            exit;

        case 'atualizar_progresso':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $course_id = $post['course_id'] ?? 0;
            $user_id = $post['user_id'] ?? 0;
            $progress = isset($post['progress']) ? (int)$post['progress'] : null;

            if ($progress === null || $progress < 0 || $progress > 100) {
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Valor de progresso inválido']);
                } else {
                    $_SESSION['erro'] = 'Valor de progresso inválido';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
                exit;
            }

            $courseController = new CourseController($pdo);
            $cursoResult = $courseController->obter($course_id);
            if (!$cursoResult['sucesso']) {
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Curso não encontrado']);
                } else {
                    $_SESSION['erro'] = 'Curso não encontrado';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
                exit;
            }

            $curso = $cursoResult['curso'];
            $usuarioAtual = AuthController::obterUsuarioAtual();
            if (!$usuarioAtual || $curso['teacher_id'] != $usuarioAtual['id']) {
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Você não tem permissão para atualizar progresso deste curso']);
                } else {
                    $_SESSION['erro'] = 'Você não tem permissão para atualizar progresso deste curso';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
                exit;
            }

            $enrollmentModel = new Enrollment($pdo);
            $ok = $enrollmentModel->atualizarProgresso($user_id, $course_id, $progress);
            if ($ok) {
                registrar_log('atualizar_progresso', "Progresso atualizado: user_id={$user_id}, course_id={$course_id}, progress={$progress}", $usuarioAtual['id']);
                if ($isAjax) {
                    echo json_encode(['sucesso' => true, 'mensagem' => 'Progresso atualizado com sucesso', 'progress' => $progress]);
                } else {
                    $_SESSION['mensagem'] = 'Progresso atualizado com sucesso';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
            } else {
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar progresso']);
                } else {
                    $_SESSION['erro'] = 'Erro ao atualizar progresso';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
            }
            exit;

        case 'restaurar_matricula':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $course_id = $post['course_id'] ?? 0;
            $user_id = $post['user_id'] ?? 0;

            $courseController = new CourseController($pdo);
            $cursoResult = $courseController->obter($course_id);
            if (!$cursoResult['sucesso']) {
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Curso não encontrado']);
                } else {
                    $_SESSION['erro'] = 'Curso não encontrado';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
                exit;
            }

            $curso = $cursoResult['curso'];
            $usuarioAtual = AuthController::obterUsuarioAtual();
            if (!$usuarioAtual || $curso['teacher_id'] != $usuarioAtual['id']) {
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Você não tem permissão para restaurar matrícula deste curso']);
                } else {
                    $_SESSION['erro'] = 'Você não tem permissão para restaurar matrícula deste curso';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
                exit;
            }

            $enrollmentModel = new Enrollment($pdo);
            $res = $enrollmentModel->matricular($user_id, $course_id);
            if ($res['sucesso'] ?? $res) {
                registrar_log('restaurar_matricula', "Matrícula restaurada: user_id={$user_id}, course_id={$course_id}", $usuarioAtual['id']);
                if ($isAjax) {
                    echo json_encode(['sucesso' => true, 'mensagem' => 'Matrícula restaurada com sucesso']);
                } else {
                    $_SESSION['mensagem'] = 'Matrícula restaurada com sucesso';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
            } else {
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao restaurar matrícula']);
                } else {
                    $_SESSION['erro'] = 'Erro ao restaurar matrícula';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
            }
            exit;

        case 'remover_matricula':
            AuthController::exigirAutenticacao();
            AuthController::exigirPermissao(['professor']);
            $course_id = $post['course_id'] ?? 0;
            $user_id = $post['user_id'] ?? 0;

            $courseController = new CourseController($pdo);
            $cursoResult = $courseController->obter($course_id);
            if (!$cursoResult['sucesso']) {
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Curso não encontrado']);
                } else {
                    $_SESSION['erro'] = 'Curso não encontrado';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
                exit;
            }

            $curso = $cursoResult['curso'];
            $usuarioAtual = AuthController::obterUsuarioAtual();
            if (!$usuarioAtual || $curso['teacher_id'] != $usuarioAtual['id']) {
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Você não tem permissão para remover alunos deste curso']);
                } else {
                    $_SESSION['erro'] = 'Você não tem permissão para remover alunos deste curso';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
                exit;
            }

            $enrollmentModel = new Enrollment($pdo);
            $rem = $enrollmentModel->remover($user_id, $course_id);
            if ($rem) {
                registrar_log('remover_matricula', "Matricula removida: user_id={$user_id}, course_id={$course_id}", $usuarioAtual['id']);
                if ($isAjax) {
                    echo json_encode(['sucesso' => true, 'mensagem' => 'Matrícula removida com sucesso']);
                } else {
                    $_SESSION['mensagem'] = 'Matrícula removida com sucesso';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
            } else {
                if ($isAjax) {
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao remover matrícula']);
                } else {
                    $_SESSION['erro'] = 'Erro ao remover matrícula';
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
            }
            exit;
    }
}
