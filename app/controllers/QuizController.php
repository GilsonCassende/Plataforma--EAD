<?php
/**
 * Controller: QuizController
 * Gerencia criação, correção, nota final e análises.
 */

class QuizController
{
    private $pdo;
    private $quizModel;
    private $lessonModel;
    private $courseModel;
    private $moduleModel;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../models/Quiz.php';
        require_once __DIR__ . '/../models/Lesson.php';
        require_once __DIR__ . '/../models/Course.php';
        require_once __DIR__ . '/../models/Module.php';
        $this->quizModel = new Quiz($pdo);
        $this->lessonModel = new Lesson($pdo);
        $this->courseModel = new Course($pdo);
        $this->moduleModel = new Module($pdo);
    }

    public function criar($lesson_id, $titulo, $descricao, $tentativas_maximas = 3, $pontos_totais = 20, array $options = [])
    {
        $usuario = $_SESSION['usuario'] ?? null;
        $titulo = trim((string)$titulo);
        $descricao = trim((string)$descricao);
        $lesson_id = (int)$lesson_id;
        $course_id = (int)($options['course_id'] ?? 0);
        $module_id = (int)($options['module_id'] ?? 0);
        $tipo = (string)($options['tipo'] ?? 'aula');
        $dificuldade = $this->normalizarDificuldade($options['dificuldade'] ?? null, $tipo);
        $questions = is_array($options['questions'] ?? null) ? $options['questions'] : [];

        $aula = null;
        if ($lesson_id > 0) {
            $aula = $this->lessonModel->obterPorId($lesson_id);
            $course_id = (int)($aula['course_id'] ?? $course_id);
        }

        if ($course_id <= 0 && $aula) {
            $course_id = (int)$aula['course_id'];
        }

        if ($titulo === '') {
            return ['sucesso' => false, 'mensagem' => 'O título do quiz é obrigatório'];
        }

        $curso = $this->courseModel->obterPorId($course_id);
        if (!$curso || (int)($curso['teacher_id'] ?? 0) !== (int)($usuario['id'] ?? 0)) {
            return ['sucesso' => false, 'mensagem' => 'Você não tem permissão para criar avaliação neste curso'];
        }

        $this->moduleModel->sincronizarCurso($course_id, (string)($curso['titulo'] ?? ''), (string)($curso['course_structure'] ?? 'single_module'));

        if ($tipo === 'aula') {
            if (!$aula || (int)($aula['course_id'] ?? 0) !== $course_id) {
                return ['sucesso' => false, 'mensagem' => 'Selecione uma aula válida para o quiz da aula'];
            }

            $lesson_id = (int)($aula['id'] ?? $lesson_id);
            $module_id = (int)($aula['module_id'] ?? $module_id);
        } elseif ($tipo === 'modulo') {
            if (($curso['course_structure'] ?? 'single_module') !== 'multi_module') {
                return ['sucesso' => false, 'mensagem' => 'Quizzes de módulo só estão disponíveis em cursos com múltiplos módulos'];
            }

            $module = $this->moduleModel->obterPorId($module_id);
            if (!$module || (int)($module['course_id'] ?? 0) !== $course_id) {
                return ['sucesso' => false, 'mensagem' => 'Selecione um módulo válido para o quiz de módulo'];
            }

            $lesson_id = 0;
        } elseif ($tipo === 'final') {
            $lesson_id = 0;
            $module_id = 0;
        } else {
            return ['sucesso' => false, 'mensagem' => 'Tipo de quiz inválido para a nova estrutura do curso'];
        }

        if ($tipo === 'final') {
            $existingFinal = array_filter($this->quizModel->listarPorCurso($course_id), static function ($quiz) {
                return ($quiz['tipo'] ?? '') === 'final';
            });
            if (!empty($existingFinal)) {
                return ['sucesso' => false, 'mensagem' => 'Este curso já possui um quiz final. Edite o existente em vez de criar outro.'];
            }
        }

        if ($tipo === 'modulo') {
            $existingModuleQuiz = array_filter($this->quizModel->listarPorModulo($module_id), static function ($quiz) {
                return ($quiz['tipo'] ?? '') === 'modulo';
            });
            if (!empty($existingModuleQuiz)) {
                return ['sucesso' => false, 'mensagem' => 'Este módulo já possui um quiz de módulo. Edite o existente em vez de criar outro.'];
            }
        }

        if ($tipo === 'aula') {
            $existingLessonQuiz = array_filter($this->quizModel->listarPorAula($lesson_id), static function ($quiz) {
                return ($quiz['tipo'] ?? 'aula') === 'aula';
            });
            if (!empty($existingLessonQuiz)) {
                return ['sucesso' => false, 'mensagem' => 'Esta aula já possui um quiz. Edite o existente em vez de criar outro.'];
            }
        }

        $validatedQuestions = [];
        foreach ($questions as $index => $question) {
            $texto = trim((string)($question['texto'] ?? ''));
            $alternativas = array_values(array_filter(array_map('trim', (array)($question['alternativas'] ?? [])), static function ($item) {
                return $item !== '';
            }));
            $corretaIndex = (int)($question['correta'] ?? -1);
            $pontos = max(1, (int)($question['pontos'] ?? 1));

            if ($texto === '') {
                return ['sucesso' => false, 'mensagem' => 'Toda pergunta precisa de um enunciado'];
            }

            if (count($alternativas) < 2) {
                return ['sucesso' => false, 'mensagem' => 'Cada pergunta precisa ter pelo menos 2 alternativas'];
            }

            if (!array_key_exists($corretaIndex, $alternativas)) {
                return ['sucesso' => false, 'mensagem' => 'Selecione a resposta correta de cada pergunta'];
            }

            $validatedQuestions[] = [
                'texto' => $texto,
                'alternativas' => $alternativas,
                'resposta_correta' => $alternativas[$corretaIndex],
                'ordem' => $index + 1,
                'pontos' => $pontos
            ];
        }

        if (empty($validatedQuestions)) {
            return ['sucesso' => false, 'mensagem' => 'Adicione pelo menos uma pergunta ao quiz'];
        }

        $pontosTotaisQuiz = array_reduce($validatedQuestions, static function ($carry, $question) {
            return $carry + max(1, (float)($question['pontos'] ?? 1));
        }, 0.0);

        if (abs($pontosTotaisQuiz - 20.0) > 0.001) {
            return [
                'sucesso' => false,
                'mensagem' => $pontosTotaisQuiz < 20
                    ? 'A soma das perguntas precisa fechar exatamente 20 valores. Ainda faltam ' . number_format(20 - $pontosTotaisQuiz, 0, ',', '.') . ' valores.'
                    : 'A soma das perguntas precisa fechar exatamente 20 valores. Remova ' . number_format($pontosTotaisQuiz - 20, 0, ',', '.') . ' valores.'
            ];
        }

        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            $quiz = $this->quizModel->criar($lesson_id ?: null, $titulo, $descricao, $tentativas_maximas, 20, array_merge($options, [
                'course_id' => $course_id,
                'module_id' => $module_id,
                'tipo' => $tipo,
                'dificuldade' => $dificuldade,
                'nota_minima' => 10,
                'obrigatorio' => $tipo === 'final' ? 1 : ($options['obrigatorio'] ?? 0),
            ]));

            if (empty($quiz['sucesso']) || empty($quiz['id'])) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                return $quiz;
            }

            foreach ($validatedQuestions as $question) {
                $result = $this->quizModel->adicionarQuestao(
                    (int)$quiz['id'],
                    $question['texto'],
                    'multipla',
                    $question['alternativas'],
                    $question['resposta_correta'],
                    (int)$question['ordem'],
                    null,
                    $question['pontos']
                );

                if (empty($result['sucesso'])) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    return ['sucesso' => false, 'mensagem' => $result['mensagem'] ?? 'Erro ao salvar as perguntas do quiz'];
                }
            }

            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            return $quiz;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['sucesso' => false, 'mensagem' => $exception->getMessage()];
        }
    }

    public function adicionarQuestao($quiz_id, $texto, $tipo, $opcoes, $resposta_correta, $explicacao = null)
    {
        $texto = trim((string)$texto);
        if ($texto === '') {
            return ['sucesso' => false, 'mensagem' => 'O enunciado da questão é obrigatório'];
        }

        if ($tipo === 'multipla') {
            $opcoes = array_values(array_filter(array_map('trim', (array)$opcoes), static function ($item) {
                return $item !== '';
            }));
            if (count($opcoes) < 2) {
                return ['sucesso' => false, 'mensagem' => 'Informe pelo menos duas opções para a questão'];
            }
            if (!in_array($resposta_correta, $opcoes, true)) {
                return ['sucesso' => false, 'mensagem' => 'A resposta correta deve existir entre as opções'];
            }
        }

        return $this->quizModel->adicionarQuestao($quiz_id, $texto, $tipo, $opcoes, $resposta_correta, 1, $explicacao);
    }

    public function obter($quiz_id)
    {
        $quiz = $this->quizModel->obterComQuestoes($quiz_id);
        if (!$quiz) {
            return ['sucesso' => false, 'mensagem' => 'Quiz não encontrado'];
        }

        if (isset($quiz['questoes'])) {
            foreach ($quiz['questoes'] as &$questao) {
                $questao['opcoes'] = !empty($questao['opcoes']) ? (json_decode($questao['opcoes'], true) ?? []) : [];
            }
            unset($questao);
        }

        return ['sucesso' => true, 'quiz' => $quiz];
    }

    public function corrigirResposta($quiz_id, $respostas_usuario, array $options = [])
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            return ['sucesso' => false, 'mensagem' => 'Faça login'];
        }

        $quiz = $this->quizModel->obterComQuestoes($quiz_id);
        if (!$quiz || empty($quiz['questoes'])) {
            return ['sucesso' => false, 'mensagem' => 'Quiz inválido ou sem questões'];
        }

        $tentativas = $this->quizModel->contarTentativas((int)$usuario['id'], (int)$quiz_id);
        if ($tentativas >= (int)($quiz['tentativas_maximas'] ?? 0)) {
            return ['sucesso' => false, 'mensagem' => 'Você atingiu o número máximo de tentativas'];
        }

        $tempoGasto = max(0, (int)($options['tempo_gasto'] ?? 0));
        $tempoEsgotado = !empty($options['tempo_esgotado']);
        $permitirEmBranco = !empty($options['permitir_em_branco']) || $tempoEsgotado;
        $respostasCorrigidas = [];
        $totalCorreto = 0;
        $questoesValidas = 0;
        $pontosObtidos = 0.0;
        $pontosTotais = 0.0;

        foreach ($quiz['questoes'] as $questao) {
            $questionId = (int)$questao['id'];
            $tipo = (string)($questao['tipo'] ?? 'multipla');
            $respostaUsuario = trim((string)($respostas_usuario[$questionId] ?? ''));
            $opcoesQuestao = $this->normalizarOpcoesQuestao($questao['opcoes'] ?? []);
            $pontosQuestao = max(1, (float)($questao['pontos'] ?? 1));

            if ($respostaUsuario === '' && !$permitirEmBranco) {
                return ['sucesso' => false, 'mensagem' => 'Responda todas as questões antes de enviar o quiz'];
            }

            $questoesValidas++;
            $pontosTotais += $pontosQuestao;
            $correta = $respostaUsuario !== '' && $this->validarRespostaQuestao($questao, $respostaUsuario, $tipo);
            if ($correta) {
                $totalCorreto++;
                $pontosObtidos += $pontosQuestao;
            }

            $respostasCorrigidas[] = [
                'question_id' => $questionId,
                'texto' => $questao['texto'],
                'resposta_usuario' => $respostaUsuario !== '' ? $respostaUsuario : 'Não respondida',
                'resposta_correta' => $questao['resposta_correta'],
                'correta' => $correta,
                'explicacao' => $questao['explicacao'] ?? null,
                'opcoes' => $opcoesQuestao,
                'pontos' => $pontosQuestao
            ];
        }

        $percentual = $pontosTotais > 0 ? round(($pontosObtidos / $pontosTotais) * 100, 2) : 0;
        $score = round($pontosObtidos, 2);
        $notaMinimaQuiz = $this->normalizarNotaMinimaQuiz((float)($quiz['nota_minima'] ?? 10));
        $aprovado = $score >= $notaMinimaQuiz;

        $attempt = $this->quizModel->salvarTentativa(
            (int)$usuario['id'],
            (int)$quiz_id,
            $score,
            $percentual,
            $totalCorreto,
            $questoesValidas,
            $tentativas + 1,
            $tempoGasto,
            $aprovado
        );

        if (empty($attempt['sucesso'])) {
            return $attempt;
        }

        $this->quizModel->salvarRespostasTentativa((int)$attempt['id'], $respostasCorrigidas);

        $courseId = (int)($quiz['course_id'] ?? 0);
        $notaCurso = $this->quizModel->calcularNotaFinalCurso($courseId, (int)$usuario['id']);
        $certificateController = new CertificateController($this->pdo);
        $certificateSync = $certificateController->syncCourseCertificates((int)$usuario['id'], $courseId);

        return [
            'sucesso' => true,
            'attempt_id' => (int)$attempt['id'],
            'score' => $score,
            'percentual' => $percentual,
            'aprovado' => $aprovado,
            'nota_minima' => $notaMinimaQuiz,
            'total_correto' => $totalCorreto,
            'total_questoes' => $questoesValidas,
            'pontos_obtidos' => round($pontosObtidos, 2),
            'pontos_totais' => round($pontosTotais, 2),
            'respostas' => $respostasCorrigidas,
            'tentativas_usadas' => $tentativas + 1,
            'tentativas_restantes' => max(0, (int)($quiz['tentativas_maximas'] ?? 0) - ($tentativas + 1)),
            'nota_final_curso' => $notaCurso['nota_final'] ?? 0,
            'course_id' => $courseId,
            'tempo_gasto' => $tempoGasto,
            'tempo_esgotado' => $tempoEsgotado,
            'certificate_events' => $certificateSync['issued'] ?? [],
        ];
    }

    private function normalizarDificuldade($dificuldade, $tipo = 'aula')
    {
        $slug = strtolower(trim((string)$dificuldade));
        if (in_array($slug, ['normal', 'medio', 'dificil'], true)) {
            return $slug;
        }

        return $tipo === 'final' ? 'dificil' : 'normal';
    }

    private function normalizarNotaMinimaQuiz($notaMinima)
    {
        $notaMinima = (float)$notaMinima;
        if ($notaMinima > 20) {
            return round(($notaMinima / 100) * 20, 2);
        }

        return max(0.0, min(20.0, $notaMinima));
    }

    public function obterResultados($quiz_id)
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            return ['sucesso' => false, 'mensagem' => 'Faça login'];
        }

        return ['sucesso' => true, 'resultados' => $this->quizModel->obterResultados((int)$usuario['id'], (int)$quiz_id)];
    }

    public function obterMelhorResultado($quiz_id)
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            return ['sucesso' => false, 'mensagem' => 'Faça login'];
        }

        return ['sucesso' => true, 'resultado' => $this->quizModel->obterMelhorResultado((int)$usuario['id'], (int)$quiz_id)];
    }

    public function obterUltimaTentativa($quiz_id)
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            return ['sucesso' => false, 'mensagem' => 'Faça login'];
        }

        $attempt = $this->quizModel->obterUltimaTentativaCompleta((int)$usuario['id'], (int)$quiz_id);
        return ['sucesso' => true, 'tentativa' => $attempt];
    }

    public function obterDesempenhoCursoAluno($course_id, $user_id)
    {
        $nota = $this->quizModel->calcularNotaFinalCurso((int)$course_id, (int)$user_id);
        $progressoAvaliacao = $this->quizModel->calcularProgressoAvaliacaoCurso((int)$course_id, (int)$user_id);
        $quizzes = $this->quizModel->listarPorCursoParaAluno((int)$course_id, (int)$user_id);

        return [
            'nota' => $nota,
            'progresso_avaliacao' => $progressoAvaliacao,
            'quizzes' => $quizzes
        ];
    }

    public function obterAnaliseProfessorCurso($course_id)
    {
        return [
            'resumo' => $this->quizModel->obterResumoProfessorCurso((int)$course_id),
            'alunos' => $this->quizModel->listarDesempenhoCursoProfessor((int)$course_id)
        ];
    }

    public function deletar($quiz_id)
    {
        $quiz = $this->quizModel->obterComQuestoes((int)$quiz_id);
        if (!$quiz) {
            return ['sucesso' => false, 'mensagem' => 'Quiz não encontrado'];
        }

        $courseId = (int)($quiz['course_id'] ?? $quiz['lesson_course_id'] ?? 0);
        $curso = $this->courseModel->obterPorId($courseId);
        $usuario = $_SESSION['usuario'] ?? null;

        $ehAdmin = ($usuario['role'] ?? '') === 'admin';
        $ehDono = $curso && (int)($curso['teacher_id'] ?? 0) === (int)($usuario['id'] ?? 0);

        if ((!$ehAdmin && !$ehDono) || !$curso) {
            return ['sucesso' => false, 'mensagem' => 'Você não tem permissão'];
        }

        if ($this->quizModel->deletar((int)$quiz_id)) {
            return ['sucesso' => true, 'mensagem' => 'Quiz deletado'];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao deletar'];
    }

    public function deletarQuestao($question_id)
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) return ['sucesso' => false, 'mensagem' => 'Faça login'];

        $stmt = $this->pdo->prepare(
            'SELECT q.id, z.course_id, l.course_id as lesson_course_id
             FROM questions q
             JOIN quizzes z ON q.quiz_id = z.id
             LEFT JOIN lessons l ON z.lesson_id = l.id
             WHERE q.id = ?'
        );
        $stmt->execute([$question_id]);
        $row = $stmt->fetch();
        if (!$row) return ['sucesso' => false, 'mensagem' => 'Questão não encontrada'];

        $courseId = (int)($row['course_id'] ?? $row['lesson_course_id'] ?? 0);
        $curso = $this->courseModel->obterPorId($courseId);
        $ehAdmin = ($usuario['role'] ?? '') === 'admin';
        $ehDono = $curso && (int)($curso['teacher_id'] ?? 0) === (int)($usuario['id'] ?? 0);
        if ((!$ehAdmin && !$ehDono) || !$curso) {
            return ['sucesso' => false, 'mensagem' => 'Sem permissão'];
        }

        if ($this->quizModel->deletarQuestao((int)$question_id)) {
            return ['sucesso' => true, 'mensagem' => 'Questão deletada'];
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao deletar questão'];
    }

    private function validarRespostaQuestao(array $questao, $respostaUsuario, $tipo)
    {
        if ($tipo === 'dissertativa') {
            return mb_strtolower(trim((string)$respostaUsuario)) === mb_strtolower(trim((string)($questao['resposta_correta'] ?? '')));
        }

        return trim((string)$respostaUsuario) === trim((string)($questao['resposta_correta'] ?? ''));
    }

    private function normalizarOpcoesQuestao($opcoes)
    {
        if (is_string($opcoes)) {
            $opcoes = json_decode($opcoes, true) ?? [];
        }

        if (!is_array($opcoes)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($opcao) {
            return is_scalar($opcao) ? trim((string)$opcao) : '';
        }, $opcoes), static function ($opcao) {
            return $opcao !== '';
        }));
    }
}
?>
