<?php
/**
 * Model: Quiz
 * Gerencia avaliações, tentativas, desempenho e aprovação do curso.
 */

class Quiz
{
    private const QUIZ_TOTAL_VALORES = 20.0;
    private const QUIZ_NOTA_MINIMA = 10.0;
    private const DIFICULDADE_PESOS = [
        'normal' => 20.0,
        'medio' => 30.0,
        'dificil' => 50.0,
    ];

    private $pdo;
    private $columnCache = [];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->ensureSchema();
    }

    public function criar($lesson_id, $titulo, $descricao, $tentativas_maximas = 3, $pontos_totais = 10, array $options = [])
    {
        try {
            $courseId = (int)($options['course_id'] ?? 0);
            $moduleId = (int)($options['module_id'] ?? 0);
            $tipo = (string)($options['tipo'] ?? 'aula');
            $dificuldade = $this->normalizarDificuldade($options['dificuldade'] ?? null, $options['peso'] ?? null, $tipo);
            $peso = $this->obterPesoPorDificuldade($dificuldade);
            $notaMinima = self::QUIZ_NOTA_MINIMA;
            $obrigatorio = !empty($options['obrigatorio']) ? 1 : 0;
            $tempoLimite = isset($options['tempo_limite']) && $options['tempo_limite'] !== ''
                ? max(0, (int)$options['tempo_limite'])
                : null;
            $embaralharPerguntas = !empty($options['embaralhar_perguntas']) ? 1 : 0;
            $embaralharRespostas = !empty($options['embaralhar_respostas']) ? 1 : 0;
            $mostrarRespostas = !empty($options['mostrar_respostas']) ? 1 : 0;
            $mostrarNota = !empty($options['mostrar_nota']) ? 1 : 0;

            $payload = [
                'lesson_id' => $lesson_id ?: null,
                'course_id' => $courseId ?: null,
                'module_id' => $moduleId ?: null,
                'titulo' => $titulo,
                'descricao' => $descricao,
                'tipo' => $tipo,
                'dificuldade' => $dificuldade,
                'peso' => $peso,
                'nota_minima' => $notaMinima,
                'obrigatorio' => $obrigatorio,
                'tentativas_maximas' => $tentativas_maximas,
                'pontos_totais' => $pontos_totais,
                'tempo_limite' => $tempoLimite,
                'embaralhar_perguntas' => $embaralharPerguntas,
                'embaralhar_respostas' => $embaralharRespostas,
                'mostrar_respostas' => $mostrarRespostas,
                'mostrar_nota' => $mostrarNota
            ];

            $availableColumns = [];
            $values = [];
            foreach ($payload as $column => $value) {
                if ($this->hasColumn('quizzes', $column)) {
                    $availableColumns[] = $column;
                    $values[] = $value;
                }
            }

            if (empty($availableColumns)) {
                throw new RuntimeException('Tabela de quizzes sem colunas compatíveis para criação.');
            }

            $columnsSql = implode(', ', array_map(static function ($column) {
                return "`$column`";
            }, $availableColumns));
            $placeholders = implode(', ', array_fill(0, count($availableColumns), '?'));
            $stmt = $this->pdo->prepare("INSERT INTO quizzes ($columnsSql) VALUES ($placeholders)");
            $stmt->execute($values);

            return ['sucesso' => true, 'id' => (int)$this->pdo->lastInsertId(), 'mensagem' => 'Quiz criado com sucesso'];
        } catch (Exception $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

    public function adicionarQuestao($quiz_id, $texto, $tipo, $opcoes, $resposta_correta, $ordem = 1, $explicacao = null, $pontos = 1)
    {
        try {
            $opcoes_json = json_encode($opcoes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $payload = [
                'quiz_id' => $quiz_id,
                'texto' => $texto,
                'tipo' => $tipo,
                'opcoes' => $opcoes_json,
                'resposta_correta' => $resposta_correta,
                'ordem' => $ordem,
                'explicacao' => $explicacao,
                'pontos' => $pontos
            ];

            $availableColumns = [];
            $values = [];
            foreach ($payload as $column => $value) {
                if ($this->hasColumn('questions', $column)) {
                    $availableColumns[] = $column;
                    $values[] = $value;
                }
            }

            $columnsSql = implode(', ', array_map(static function ($column) {
                return "`$column`";
            }, $availableColumns));
            $placeholders = implode(', ', array_fill(0, count($availableColumns), '?'));
            $stmt = $this->pdo->prepare("INSERT INTO questions ($columnsSql) VALUES ($placeholders)");
            $stmt->execute($values);
            return ['sucesso' => true, 'id' => (int)$this->pdo->lastInsertId()];
        } catch (Exception $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

    public function obterComQuestoes($quiz_id)
    {
        $stmt = $this->pdo->prepare(
            'SELECT q.*, l.course_id as lesson_course_id, l.titulo as lesson_title,
                    m.titulo as module_title, m.ordem as module_ordem
             FROM quizzes q
             LEFT JOIN lessons l ON l.id = q.lesson_id
             LEFT JOIN course_modules m ON m.id = q.module_id
             WHERE q.id = ?'
        );
        $stmt->execute([$quiz_id]);
        $quiz = $stmt->fetch();

        if ($quiz) {
            if (empty($quiz['course_id']) && !empty($quiz['lesson_course_id'])) {
                $quiz['course_id'] = (int)$quiz['lesson_course_id'];
            }
            $stmt = $this->pdo->prepare('SELECT * FROM questions WHERE quiz_id = ? ORDER BY ordem, id');
            $stmt->execute([$quiz_id]);
            $quiz['questoes'] = $stmt->fetchAll();
            $quiz['pontos_totais'] = array_reduce($quiz['questoes'], static function ($carry, $questao) {
                return $carry + max(1, (float)($questao['pontos'] ?? 1));
            }, 0.0);
            $quiz = $this->enriquecerQuiz($quiz);
        }

        return $quiz;
    }

    public function listarPorAula($lesson_id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM quizzes WHERE lesson_id = ? ORDER BY created_at DESC');
        $stmt->execute([$lesson_id]);
        return $stmt->fetchAll();
    }

    public function listarPorCurso($course_id)
    {
        $courseFilter = $this->buildCourseFilterSql('q', 'q.lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)');
        $params = $this->buildCourseFilterParams((int)$course_id);

        $stmt = $this->pdo->prepare(
            "SELECT q.*, l.titulo as lesson_title, m.titulo as module_title, m.ordem as module_ordem
             FROM quizzes q
             LEFT JOIN lessons l ON l.id = q.lesson_id
             LEFT JOIN course_modules m ON m.id = q.module_id
             WHERE $courseFilter
             ORDER BY FIELD(q.tipo, 'final', 'modulo', 'aula'), COALESCE(m.ordem, 999999) ASC, q.created_at DESC"
        );
        $stmt->execute($params);
        return array_map(function ($quiz) {
            return $this->enriquecerQuiz($quiz);
        }, $stmt->fetchAll());
    }

    public function listarPorModulo($module_id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT q.*, l.titulo as lesson_title, m.titulo as module_title, m.ordem as module_ordem
             FROM quizzes q
             LEFT JOIN lessons l ON l.id = q.lesson_id
             LEFT JOIN course_modules m ON m.id = q.module_id
             WHERE q.module_id = ?
             ORDER BY FIELD(q.tipo, 'modulo', 'aula', 'final'), q.created_at DESC"
        );
        $stmt->execute([(int)$module_id]);
        return array_map(function ($quiz) {
            return $this->enriquecerQuiz($quiz);
        }, $stmt->fetchAll());
    }

    public function listarPorCursoParaAluno($course_id, $user_id)
    {
        $quizzes = $this->listarPorCurso($course_id);
        foreach ($quizzes as &$quiz) {
            $quiz['melhor_resultado'] = $this->obterMelhorResultado($user_id, (int)$quiz['id']);
            $quiz['tentativas_usadas'] = $this->contarTentativas($user_id, (int)$quiz['id']);
            $quiz['tentativas_restantes'] = max(0, (int)($quiz['tentativas_maximas'] ?? 0) - (int)$quiz['tentativas_usadas']);
            $quiz['aprovado'] = !empty($quiz['melhor_resultado']) && (float)($quiz['melhor_resultado']['pontuacao'] ?? 0) >= (float)($quiz['nota_minima'] ?? self::QUIZ_NOTA_MINIMA);
        }
        unset($quiz);

        return $quizzes;
    }

    public function salvarTentativa($user_id, $quiz_id, $score, $percentual, $total_correto, $total_questoes, $tentativa = 1, $tempo_gasto = 0, $aprovado = 0)
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'INSERT INTO quiz_attempts (user_id, quiz_id, pontuacao, percentual, total_correto, total_questoes, tentativa_numero, tempo_gasto, aprovado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $user_id,
                $quiz_id,
                $score,
                $percentual,
                $total_correto,
                $total_questoes,
                $tentativa,
                $tempo_gasto,
                $aprovado ? 1 : 0
            ]);

            $attemptId = (int)$this->pdo->lastInsertId();

            $legacyStmt = $this->pdo->prepare(
                'INSERT INTO quiz_results (user_id, quiz_id, score, resposta_usuario, tentativa, tempo_gasto)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $legacyStmt->execute([$user_id, $quiz_id, $score, json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $tentativa, $tempo_gasto]);

            $this->pdo->commit();
            return ['sucesso' => true, 'id' => $attemptId];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

    public function salvarRespostasTentativa($attempt_id, array $answers)
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO quiz_attempt_answers (attempt_id, question_id, resposta_usuario, correta)
             VALUES (?, ?, ?, ?)'
        );

        foreach ($answers as $answer) {
            $stmt->execute([
                $attempt_id,
                (int)($answer['question_id'] ?? 0),
                (string)($answer['resposta_usuario'] ?? ''),
                !empty($answer['correta']) ? 1 : 0
            ]);
        }
    }

    public function contarTentativas($user_id, $quiz_id)
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as tentativas FROM quiz_attempts WHERE user_id = ? AND quiz_id = ?');
        $stmt->execute([$user_id, $quiz_id]);
        $result = $stmt->fetch();
        return (int)($result['tentativas'] ?? 0);
    }

    public function obterResultados($user_id, $quiz_id)
    {
        $quiz = $this->obterComQuestoes($quiz_id);
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM quiz_attempts
             WHERE user_id = ? AND quiz_id = ?
             ORDER BY tentativa_numero DESC, data_realizacao DESC'
        );
        $stmt->execute([$user_id, $quiz_id]);
        return array_map(function ($attempt) use ($quiz) {
            return $this->enriquecerTentativa($attempt, $quiz ?: []);
        }, $stmt->fetchAll());
    }

    public function obterTentativaPorId($attempt_id, $user_id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM quiz_attempts WHERE id = ? AND user_id = ?');
        $stmt->execute([$attempt_id, $user_id]);
        $attempt = $stmt->fetch();

        if ($attempt) {
            $quiz = $this->obterComQuestoes((int)($attempt['quiz_id'] ?? 0));
            $stmt = $this->pdo->prepare(
                'SELECT a.*, q.texto, q.resposta_correta, q.explicacao, q.opcoes, q.tipo
                 FROM quiz_attempt_answers a
                 JOIN questions q ON q.id = a.question_id
                 WHERE a.attempt_id = ?
                 ORDER BY q.ordem, q.id'
            );
            $stmt->execute([$attempt_id]);
            $attempt['respostas'] = $stmt->fetchAll();
            $attempt = $this->enriquecerTentativa($attempt, $quiz ?: []);
        }

        return $attempt;
    }

    public function obterMelhorResultado($user_id, $quiz_id)
    {
        $quiz = $this->obterComQuestoes($quiz_id);
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM quiz_attempts
             WHERE user_id = ? AND quiz_id = ?
             ORDER BY pontuacao DESC, percentual DESC, tentativa_numero ASC
             LIMIT 1'
        );
        $stmt->execute([$user_id, $quiz_id]);
        $attempt = $stmt->fetch();
        return $attempt ? $this->enriquecerTentativa($attempt, $quiz ?: []) : null;
    }

    public function getMandatoryLessonQuizStatus($lessonId, $userId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT q.id, q.titulo, q.nota_minima,
                    COALESCE(best_attempt.best_score, -1) AS best_score
             FROM quizzes q
             LEFT JOIN (
                SELECT quiz_id, MAX(pontuacao) AS best_score
                FROM quiz_attempts
                WHERE user_id = ?
                GROUP BY quiz_id
             ) best_attempt ON best_attempt.quiz_id = q.id
             WHERE q.lesson_id = ?
               AND q.tipo = 'aula'
               AND COALESCE(q.obrigatorio, 0) = 1
             ORDER BY q.id ASC"
        );
        $stmt->execute([(int)$userId, (int)$lessonId]);
        $rows = $stmt->fetchAll();

        $approved = 0;
        $pendingTitles = [];

        foreach ($rows as $row) {
            $minimumGrade = (float)($row['nota_minima'] ?? self::QUIZ_NOTA_MINIMA);
            $bestScore = (float)($row['best_score'] ?? -1);
            if ($bestScore >= $minimumGrade) {
                $approved++;
                continue;
            }

            $pendingTitles[] = trim((string)($row['titulo'] ?? 'Quiz da aula'));
        }

        return [
            'required_total' => count($rows),
            'approved_total' => $approved,
            'all_passed' => count($rows) === 0 || $approved === count($rows),
            'pending_titles' => array_values(array_filter(array_unique($pendingTitles))),
        ];
    }

    public function getMandatoryLessonQuizApprovalMap($courseId, $userId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                q.lesson_id,
                COUNT(*) AS required_total,
                SUM(
                    CASE
                        WHEN COALESCE(best_attempt.best_score, -1) >= COALESCE(q.nota_minima, ?)
                        THEN 1
                        ELSE 0
                    END
                ) AS approved_total
             FROM quizzes q
             LEFT JOIN lessons l ON l.id = q.lesson_id
             LEFT JOIN (
                SELECT quiz_id, MAX(pontuacao) AS best_score
                FROM quiz_attempts
                WHERE user_id = ?
                GROUP BY quiz_id
             ) best_attempt ON best_attempt.quiz_id = q.id
             WHERE q.tipo = 'aula'
               AND COALESCE(q.obrigatorio, 0) = 1
               AND COALESCE(q.course_id, l.course_id) = ?
               AND q.lesson_id IS NOT NULL
             GROUP BY q.lesson_id"
        );
        $stmt->execute([self::QUIZ_NOTA_MINIMA, (int)$userId, (int)$courseId]);

        $approvalMap = [];
        foreach ($stmt->fetchAll() as $row) {
            $lessonId = (int)($row['lesson_id'] ?? 0);
            if ($lessonId <= 0) {
                continue;
            }

            $approvalMap[$lessonId] = (int)($row['required_total'] ?? 0) > 0
                && (int)($row['approved_total'] ?? 0) >= (int)($row['required_total'] ?? 0);
        }

        return $approvalMap;
    }

    public function calculateLessonProgressForCourse($courseId, $userId)
    {
        $stmt = $this->pdo->prepare('SELECT id FROM lessons WHERE course_id = ? ORDER BY ordem ASC, id ASC');
        $stmt->execute([(int)$courseId]);
        $lessonIds = array_map('intval', array_column($stmt->fetchAll(), 'id'));
        $totalLessons = count($lessonIds);

        if ($totalLessons === 0) {
            return [
                'total_lessons' => 0,
                'raw_completed_ids' => [],
                'completed_ids' => [],
                'completed_total' => 0,
                'progress' => 0,
                'mandatory_quiz_approval_map' => [],
            ];
        }

        $placeholders = implode(',', array_fill(0, $totalLessons, '?'));
        $params = array_merge([(int)$userId], $lessonIds);
        $stmt = $this->pdo->prepare(
            "SELECT lesson_id
             FROM lesson_progress
             WHERE user_id = ?
               AND concluida = 1
               AND lesson_id IN ($placeholders)"
        );
        $stmt->execute($params);
        $rawCompletedIds = array_map('intval', array_column($stmt->fetchAll(), 'lesson_id'));
        $approvalMap = $this->getMandatoryLessonQuizApprovalMap((int)$courseId, (int)$userId);

        $effectiveCompletedIds = array_values(array_filter($rawCompletedIds, static function ($lessonId) use ($approvalMap) {
            return !array_key_exists((int)$lessonId, $approvalMap) || !empty($approvalMap[(int)$lessonId]);
        }));

        $completedTotal = count($effectiveCompletedIds);
        $progress = (int)floor(($completedTotal / $totalLessons) * 100);

        return [
            'total_lessons' => $totalLessons,
            'raw_completed_ids' => $rawCompletedIds,
            'completed_ids' => $effectiveCompletedIds,
            'completed_total' => $completedTotal,
            'progress' => $progress,
            'mandatory_quiz_approval_map' => $approvalMap,
        ];
    }

    public function recalculateEnrollmentProgress($courseId, $userId)
    {
        $lessonProgress = $this->calculateLessonProgressForCourse((int)$courseId, (int)$userId);
        $lessonPercent = (int)($lessonProgress['progress'] ?? 0);
        $hasQuizzes = count($this->listarPorCurso((int)$courseId)) > 0;
        $quizProgress = $this->calcularProgressoAvaliacaoCurso((int)$courseId, (int)$userId);
        $eligibilidade = $this->alunoAptoConclusao((int)$courseId, (int)$userId, $lessonPercent);

        $novoProgresso = $hasQuizzes
            ? (int)floor((($lessonPercent * 0.6) + ($quizProgress * 0.4)))
            : $lessonPercent;

        if (!empty($eligibilidade['aprovado'])) {
            $novoProgresso = 100;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE enrollments
             SET progress = ?,
                 data_conclusao = CASE WHEN ? = 100 THEN COALESCE(data_conclusao, NOW()) ELSE NULL END
             WHERE user_id = ? AND course_id = ?'
        );
        $stmt->execute([$novoProgresso, $novoProgresso, (int)$userId, (int)$courseId]);

        return $novoProgresso;
    }

    public function obterUltimaTentativaCompleta($user_id, $quiz_id)
    {
        $stmt = $this->pdo->prepare(
            'SELECT id
             FROM quiz_attempts
             WHERE user_id = ? AND quiz_id = ?
             ORDER BY tentativa_numero DESC, data_realizacao DESC
             LIMIT 1'
        );
        $stmt->execute([$user_id, $quiz_id]);
        $attemptId = (int)($stmt->fetchColumn() ?: 0);

        if ($attemptId <= 0) {
            return null;
        }

        return $this->obterTentativaPorId($attemptId, $user_id);
    }

    public function calcularNotaFinalCurso($course_id, $user_id)
    {
        $quizzes = $this->listarPorCurso($course_id);
        $grupos = $this->inicializarGruposDificuldade();
        $quizzesObrigatorios = 0;
        $quizzesObrigatoriosAprovados = 0;
        $quizzesRespondidos = 0;
        $notaMinimaCurso = self::QUIZ_NOTA_MINIMA;
        $temFinal = false;
        $finalAprovada = false;

        foreach ($quizzes as $quiz) {
            $dificuldade = $this->normalizarDificuldade($quiz['dificuldade'] ?? null, $quiz['peso'] ?? null, $quiz['tipo'] ?? 'aula');
            $melhor = $this->obterMelhorResultado($user_id, (int)$quiz['id']);
            $pontuacao = (float)($melhor['pontuacao'] ?? 0);
            $notaMinimaQuiz = (float)($quiz['nota_minima'] ?? self::QUIZ_NOTA_MINIMA);

            $grupos[$dificuldade]['total'] += $pontuacao;
            $grupos[$dificuldade]['count']++;

            if ($melhor) {
                $quizzesRespondidos++;
            }

            if (!empty($quiz['obrigatorio'])) {
                $quizzesObrigatorios++;
                if ($melhor && $pontuacao >= $notaMinimaQuiz) {
                    $quizzesObrigatoriosAprovados++;
                }
            }

            if (($quiz['tipo'] ?? '') === 'final') {
                $temFinal = true;
                $finalAprovada = $melhor && $pontuacao >= $notaMinimaQuiz;
            }
        }

        $gruposAtivos = array_filter($grupos, static function ($grupo) {
            return (int)($grupo['count'] ?? 0) > 0;
        });
        $pesoTotal = array_reduce($gruposAtivos, static function ($carry, $grupo) {
            return $carry + (float)($grupo['peso'] ?? 0);
        }, 0.0);
        $notaPonderada = 0.0;

        foreach ($grupos as $slug => &$grupo) {
            $grupo['media'] = (int)$grupo['count'] > 0 ? round($grupo['total'] / $grupo['count'], 2) : null;
            $grupo['peso_normalizado'] = ((int)$grupo['count'] > 0 && $pesoTotal > 0)
                ? round(((float)$grupo['peso'] / $pesoTotal) * 100, 2)
                : 0.0;
            if ((int)$grupo['count'] > 0 && $grupo['media'] !== null) {
                $notaPonderada += $grupo['media'] * ((float)$grupo['peso_normalizado'] / 100);
            }
        }
        unset($grupo);

        $notaFinal = $pesoTotal > 0 ? round($notaPonderada, 2) : 0.0;
        $aprovado = $notaFinal >= $notaMinimaCurso;

        return [
            'nota_final' => $notaFinal,
            'nota_final_percentual' => round(($notaFinal / self::QUIZ_TOTAL_VALORES) * 100, 2),
            'peso_total' => $pesoTotal,
            'quizzes_total' => count($quizzes),
            'quizzes_respondidos' => $quizzesRespondidos,
            'quizzes_obrigatorios' => $quizzesObrigatorios,
            'quizzes_obrigatorios_aprovados' => $quizzesObrigatoriosAprovados,
            'nota_minima' => $notaMinimaCurso,
            'tem_prova_final' => $temFinal,
            'prova_final_aprovada' => !$temFinal || $finalAprovada,
            'aprovado' => $aprovado,
            'grupos' => $grupos,
        ];
    }

    public function calcularProgressoAvaliacaoCurso($course_id, $user_id)
    {
        $quizzes = $this->listarPorCurso($course_id);
        if (empty($quizzes)) {
            return 100;
        }

        $grupos = $this->inicializarGruposDificuldade();

        foreach ($quizzes as $quiz) {
            $dificuldade = $this->normalizarDificuldade($quiz['dificuldade'] ?? null, $quiz['peso'] ?? null, $quiz['tipo'] ?? 'aula');
            $grupos[$dificuldade]['count']++;
            $melhor = $this->obterMelhorResultado($user_id, (int)$quiz['id']);
            if ($melhor && (float)($melhor['pontuacao'] ?? 0) >= (float)($quiz['nota_minima'] ?? self::QUIZ_NOTA_MINIMA)) {
                $grupos[$dificuldade]['approved']++;
            }
        }

        $gruposAtivos = array_filter($grupos, static function ($grupo) {
            return (int)($grupo['count'] ?? 0) > 0;
        });
        $pesoTotal = array_reduce($gruposAtivos, static function ($carry, $grupo) {
            return $carry + (float)($grupo['peso'] ?? 0);
        }, 0.0);
        $progresso = 0.0;

        foreach ($gruposAtivos as $grupo) {
            $taxaAprovacao = (int)$grupo['count'] > 0 ? ((int)$grupo['approved'] / (int)$grupo['count']) : 0;
            $pesoNormalizado = $pesoTotal > 0 ? ((float)$grupo['peso'] / $pesoTotal) : 0;
            $progresso += $taxaAprovacao * $pesoNormalizado;
        }

        return (int)floor($progresso * 100);
    }

    public function alunoAptoConclusao($course_id, $user_id, $lessonProgress = 0)
    {
        $nota = $this->calcularNotaFinalCurso($course_id, $user_id);
        $quizzes = $this->listarPorCurso($course_id);
        $aprovadoEmQuizzes = $nota['aprovado']
            && $nota['quizzes_obrigatorios'] === $nota['quizzes_obrigatorios_aprovados']
            && $nota['prova_final_aprovada'];

        return [
            'concluiu_aulas' => (int)$lessonProgress >= 100,
            'concluiu_quizzes' => empty($quizzes) ? true : $aprovadoEmQuizzes,
            'nota_final' => $nota['nota_final'],
            'nota_minima' => $nota['nota_minima'],
            'aprovado' => ((int)$lessonProgress >= 100) && (empty($quizzes) ? true : $aprovadoEmQuizzes)
        ];
    }

    public function emitirCertificadoSeElegivel($course_id, $user_id)
    {
        require_once __DIR__ . '/Certificate.php';
        $certificateModel = new Certificate($this->pdo);
        $sync = $certificateModel->syncCourseCertificates((int)$user_id, (int)$course_id);
        return !empty($sync['issued']) || !empty($sync['state']['course']['certificate']);
    }

    public function listarDesempenhoCursoProfessor($course_id)
    {
        $subFilter = $this->buildCourseFilterSql('q', 'q.lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)');
        $subParams = $this->buildCourseFilterParams((int)$course_id);
        $stmt = $this->pdo->prepare(
            "SELECT u.id as user_id, u.nome, u.email,
                    MAX(qa.percentual) as melhor_percentual,
                    ROUND(AVG(qa.percentual), 2) as media_percentual,
                    COUNT(DISTINCT qa.quiz_id) as quizzes_realizados
             FROM enrollments e
             JOIN users u ON u.id = e.user_id
             LEFT JOIN quiz_attempts qa
               ON qa.user_id = e.user_id
               AND qa.quiz_id IN (
                    SELECT q.id
                    FROM quizzes q
                    WHERE $subFilter
               )
             WHERE e.course_id = ?
             GROUP BY u.id, u.nome, u.email
             ORDER BY media_percentual DESC, u.nome ASC"
        );
        $stmt->execute(array_merge($subParams, [(int)$course_id]));
        return $stmt->fetchAll();
    }

    public function listarAdministrativo($limit = null, $offset = null, $busca = '', $tipo = null)
    {
        $sql = 'SELECT q.*,
                       c.titulo as course_title,
                       c.status as course_status,
                       l.titulo as lesson_title,
                       u.nome as teacher_name,
                       COUNT(DISTINCT qs.id) as total_questoes,
                       COUNT(DISTINCT qa.id) as total_tentativas,
                       ROUND(AVG(qa.percentual), 2) as media_percentual,
                       ROUND(MAX(qa.percentual), 2) as melhor_percentual
                FROM quizzes q
                LEFT JOIN courses c ON c.id = q.course_id
                LEFT JOIN lessons l ON l.id = q.lesson_id
                LEFT JOIN users u ON u.id = c.teacher_id
                LEFT JOIN questions qs ON qs.quiz_id = q.id
                LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.id';

        $conditions = [];
        $params = [];
        $busca = trim((string)$busca);

        if ($busca !== '') {
            $conditions[] = '(q.titulo LIKE ? OR q.descricao LIKE ? OR c.titulo LIKE ? OR l.titulo LIKE ? OR u.nome LIKE ?)';
            $termo = '%' . $busca . '%';
            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
        }

        if ($tipo !== null && $tipo !== '') {
            $conditions[] = 'q.tipo = ?';
            $params[] = $tipo;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' GROUP BY q.id, c.titulo, c.status, l.titulo, u.nome
                  ORDER BY q.created_at DESC';

        if ($limit !== null && $offset !== null) {
            $sql .= ' LIMIT ? OFFSET ?';
            $params[] = (int)$limit;
            $params[] = (int)$offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function contarAdministrativo($busca = '', $tipo = null)
    {
        $sql = 'SELECT COUNT(*) as total
                FROM quizzes q
                LEFT JOIN courses c ON c.id = q.course_id
                LEFT JOIN lessons l ON l.id = q.lesson_id
                LEFT JOIN users u ON u.id = c.teacher_id';
        $conditions = [];
        $params = [];
        $busca = trim((string)$busca);

        if ($busca !== '') {
            $conditions[] = '(q.titulo LIKE ? OR q.descricao LIKE ? OR c.titulo LIKE ? OR l.titulo LIKE ? OR u.nome LIKE ?)';
            $termo = '%' . $busca . '%';
            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
        }

        if ($tipo !== null && $tipo !== '') {
            $conditions[] = 'q.tipo = ?';
            $params[] = $tipo;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int)($result['total'] ?? 0);
    }

    public function obterResumoAdministrativo()
    {
        $totalQuizzes = (int)($this->pdo->query('SELECT COUNT(*) FROM quizzes')->fetchColumn() ?: 0);
        $quizzesFinais = (int)($this->pdo->query("SELECT COUNT(*) FROM quizzes WHERE tipo = 'final'")->fetchColumn() ?: 0);
        $totalTentativas = (int)($this->pdo->query('SELECT COUNT(*) FROM quiz_attempts')->fetchColumn() ?: 0);

        $mediaStmt = $this->pdo->query('SELECT ROUND(AVG(percentual), 2) as media FROM quiz_attempts');
        $mediaGeral = (float)(($mediaStmt ? $mediaStmt->fetch()['media'] : 0) ?? 0);

        $taxaStmt = $this->pdo->query('SELECT ROUND(AVG(CASE WHEN aprovado = 1 THEN 100 ELSE 0 END), 2) as taxa FROM quiz_attempts');
        $taxaAprovacao = (float)(($taxaStmt ? $taxaStmt->fetch()['taxa'] : 0) ?? 0);

        $perguntasStmt = $this->pdo->query(
            'SELECT c.titulo as course_title,
                    q.texto,
                    COUNT(a.id) as total_respostas,
                    SUM(CASE WHEN a.correta = 0 THEN 1 ELSE 0 END) as erros
             FROM questions q
             JOIN quizzes z ON z.id = q.quiz_id
             LEFT JOIN courses c ON c.id = z.course_id
             LEFT JOIN quiz_attempt_answers a ON a.question_id = q.id
             GROUP BY c.titulo, q.texto
             HAVING total_respostas > 0
             ORDER BY erros DESC, total_respostas DESC
             LIMIT 5'
        );

        return [
            'total_quizzes' => $totalQuizzes,
            'quizzes_finais' => $quizzesFinais,
            'total_tentativas' => $totalTentativas,
            'media_geral' => $mediaGeral,
            'taxa_aprovacao' => $taxaAprovacao,
            'perguntas_criticas' => $perguntasStmt ? $perguntasStmt->fetchAll() : []
        ];
    }

    public function obterResumoProfessorCurso($course_id)
    {
        $quizFilter = $this->buildCourseFilterSql('q', 'q.lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)');
        $quizFilterParams = $this->buildCourseFilterParams((int)$course_id);
        $questionFilter = $this->buildCourseFilterSql('z', 'z.lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)');
        $questionFilterParams = $this->buildCourseFilterParams((int)$course_id);

        $notaStmt = $this->pdo->prepare(
            "SELECT ROUND(AVG(best_attempt.percentual), 2) as media_turma
             FROM (
                SELECT qa.user_id, qa.quiz_id, MAX(qa.percentual) as percentual
                FROM quiz_attempts qa
                JOIN quizzes q ON q.id = qa.quiz_id
                WHERE $quizFilter
                GROUP BY qa.user_id, qa.quiz_id
             ) best_attempt"
        );
        $notaStmt->execute($quizFilterParams);
        $mediaTurma = (float)($notaStmt->fetch()['media_turma'] ?? 0);

        $perguntasStmt = $this->pdo->prepare(
            "SELECT q.id, q.texto,
                    COUNT(a.id) as total_respostas,
                    SUM(CASE WHEN a.correta = 0 THEN 1 ELSE 0 END) as erros
             FROM questions q
             JOIN quizzes z ON z.id = q.quiz_id
             LEFT JOIN quiz_attempt_answers a ON a.question_id = q.id
             WHERE $questionFilter
             GROUP BY q.id, q.texto
             HAVING total_respostas > 0
             ORDER BY erros DESC, total_respostas DESC
             LIMIT 5"
        );
        $perguntasStmt->execute($questionFilterParams);

        return [
            'media_turma' => $mediaTurma,
            'perguntas_criticas' => $perguntasStmt->fetchAll()
        ];
    }

    public function deletar($id)
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM quizzes WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function deletarQuestao($question_id)
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM questions WHERE id = ?');
            return $stmt->execute([$question_id]);
        } catch (Exception $e) {
            return false;
        }
    }

    private function obterPercentualAulasConcluidas($course_id, $user_id)
    {
        $stmt = $this->pdo->prepare('SELECT progress FROM enrollments WHERE course_id = ? AND user_id = ?');
        $stmt->execute([$course_id, $user_id]);
        $row = $stmt->fetch();
        return (int)($row['progress'] ?? 0);
    }

    private function ensureSchema()
    {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS quiz_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                quiz_id INT NOT NULL,
                pontuacao DECIMAL(8,2) DEFAULT 0,
                percentual DECIMAL(5,2) DEFAULT 0,
                total_correto INT DEFAULT 0,
                total_questoes INT DEFAULT 0,
                tentativa_numero INT DEFAULT 1,
                tempo_gasto INT DEFAULT 0,
                aprovado TINYINT(1) DEFAULT 0,
                data_realizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id),
                INDEX (quiz_id),
                INDEX (data_realizacao)
            )");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS quiz_attempt_answers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                attempt_id INT NOT NULL,
                question_id INT NOT NULL,
                resposta_usuario TEXT,
                correta TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (attempt_id),
                INDEX (question_id)
            )");

            $this->addColumnIfMissing('quizzes', 'course_id', 'INT NULL AFTER lesson_id');
            $this->addColumnIfMissing('quizzes', 'module_id', 'INT NULL AFTER course_id');
            $this->addColumnIfMissing('quizzes', 'tipo', "VARCHAR(20) NOT NULL DEFAULT 'aula' AFTER descricao");
            $this->addColumnIfMissing('quizzes', 'dificuldade', "VARCHAR(20) NOT NULL DEFAULT 'normal' AFTER tipo");
            $this->addColumnIfMissing('quizzes', 'peso', 'DECIMAL(8,2) NOT NULL DEFAULT 20 AFTER dificuldade');
            $this->addColumnIfMissing('quizzes', 'nota_minima', 'DECIMAL(5,2) NOT NULL DEFAULT 10 AFTER peso');
            $this->addColumnIfMissing('quizzes', 'obrigatorio', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER nota_minima');
            $this->addColumnIfMissing('quizzes', 'tempo_limite', 'INT NULL AFTER tentativas_maximas');
            $this->addColumnIfMissing('quizzes', 'embaralhar_perguntas', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER tempo_limite');
            $this->addColumnIfMissing('quizzes', 'embaralhar_respostas', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER embaralhar_perguntas');
            $this->addColumnIfMissing('quizzes', 'mostrar_respostas', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER embaralhar_respostas');
            $this->addColumnIfMissing('quizzes', 'mostrar_nota', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER mostrar_respostas');
            $this->addColumnIfMissing('questions', 'explicacao', 'TEXT NULL AFTER resposta_correta');
            $this->addColumnIfMissing('questions', 'pontos', 'DECIMAL(8,2) NOT NULL DEFAULT 1 AFTER explicacao');
            $this->addColumnIfMissing('certificates', 'nota_final', 'DECIMAL(5,2) NULL AFTER codigo_certificado');

            // Compatibilidade com quizzes de curso/final sem aula.
            $this->allowNullableLessonId();

            $this->pdo->exec('UPDATE quizzes q
                LEFT JOIN lessons l ON l.id = q.lesson_id
                SET q.course_id = COALESCE(q.course_id, l.course_id)
                WHERE q.course_id IS NULL');

            if ($this->hasColumn('quizzes', 'dificuldade')) {
                $this->pdo->exec("UPDATE quizzes
                    SET dificuldade = CASE
                        WHEN tipo = 'final' THEN 'dificil'
                        WHEN peso >= 50 THEN 'dificil'
                        WHEN peso >= 30 THEN 'medio'
                        ELSE 'normal'
                    END
                    WHERE dificuldade IS NULL OR dificuldade = ''");
            }
        } catch (Exception $e) {
            if (function_exists('registrar_log')) {
                registrar_log('warning', 'quiz_ensure_schema_failed: ' . $e->getMessage());
            }
        }
    }

    private function addColumnIfMissing($table, $column, $definition)
    {
        if (!$this->hasColumn($table, $column)) {
            try {
                $this->pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            } catch (Throwable $exception) {
                $fallbackDefinition = preg_replace('/\s+AFTER\s+`?[a-z0-9_]+`?$/i', '', trim($definition));
                if ($fallbackDefinition === trim($definition)) {
                    throw $exception;
                }
                $this->pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $fallbackDefinition");
            }
            $this->columnCache[$table][$column] = true;
        }
    }

    private function allowNullableLessonId()
    {
        $stmt = $this->pdo->prepare(
            'SELECT IS_NULLABLE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1'
        );
        $stmt->execute(['quizzes', 'lesson_id']);
        $column = $stmt->fetch();
        if ($column && strtoupper((string)($column['IS_NULLABLE'] ?? 'NO')) !== 'YES') {
            $this->pdo->exec('ALTER TABLE quizzes MODIFY lesson_id INT NULL');
        }
    }

    private function hasColumn($table, $column)
    {
        if (isset($this->columnCache[$table][$column])) {
            return $this->columnCache[$table][$column];
        }

        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1'
        );
        $stmt->execute([$table, $column]);
        $exists = (bool)$stmt->fetchColumn();
        $this->columnCache[$table][$column] = $exists;
        return $exists;
    }

    private function buildCourseFilterSql($alias, $lessonFallbackSql)
    {
        if ($this->hasColumn('quizzes', 'course_id')) {
            return sprintf('%s.course_id = ? OR %s', $alias, $lessonFallbackSql);
        }

        return $lessonFallbackSql;
    }

    private function buildCourseFilterParams($courseId)
    {
        if ($this->hasColumn('quizzes', 'course_id')) {
            return [$courseId, $courseId];
        }

        return [$courseId];
    }

    private function enriquecerQuiz(array $quiz)
    {
        $dificuldade = $this->normalizarDificuldade($quiz['dificuldade'] ?? null, $quiz['peso'] ?? null, $quiz['tipo'] ?? 'aula');
        $quiz['dificuldade'] = $dificuldade;
        $quiz['dificuldade_label'] = $this->obterLabelDificuldade($dificuldade);
        $quiz['peso'] = $this->obterPesoPorDificuldade($dificuldade);
        $quiz['peso_percentual'] = $quiz['peso'];
        $quiz['nota_minima'] = $this->normalizarNotaMinima($quiz['nota_minima'] ?? self::QUIZ_NOTA_MINIMA);
        $quiz['nota_minima_percentual'] = round(($quiz['nota_minima'] / self::QUIZ_TOTAL_VALORES) * 100, 2);
        $quiz['pontos_totais'] = round((float)($quiz['pontos_totais'] ?? self::QUIZ_TOTAL_VALORES), 2);
        return $quiz;
    }

    private function enriquecerTentativa(array $attempt, array $quiz = [])
    {
        $pontosTotais = (float)($quiz['pontos_totais'] ?? self::QUIZ_TOTAL_VALORES);
        $pontuacao = $this->normalizarPontuacao((float)($attempt['pontuacao'] ?? 0), $attempt['percentual'] ?? null, $pontosTotais);
        $attempt['pontuacao'] = round($pontuacao, 2);
        $attempt['percentual'] = round(($pontuacao / self::QUIZ_TOTAL_VALORES) * 100, 2);

        if (!array_key_exists('aprovado', $attempt) && !empty($quiz)) {
            $attempt['aprovado'] = $pontuacao >= (float)($quiz['nota_minima'] ?? self::QUIZ_NOTA_MINIMA);
        }

        return $attempt;
    }

    private function normalizarDificuldade($dificuldade, $peso = null, $tipo = 'aula')
    {
        $slug = strtolower(trim((string)$dificuldade));
        if (isset(self::DIFICULDADE_PESOS[$slug])) {
            return $slug;
        }

        $peso = (float)$peso;
        if ($peso >= 50) {
            return 'dificil';
        }
        if ($peso >= 30) {
            return 'medio';
        }
        if ((string)$tipo === 'final') {
            return 'dificil';
        }

        return 'normal';
    }

    private function obterPesoPorDificuldade($dificuldade)
    {
        return self::DIFICULDADE_PESOS[$dificuldade] ?? self::DIFICULDADE_PESOS['normal'];
    }

    private function obterLabelDificuldade($dificuldade)
    {
        switch ($dificuldade) {
            case 'medio':
                return 'Médio';
            case 'dificil':
                return 'Difícil';
            default:
                return 'Normal';
        }
    }

    private function normalizarNotaMinima($notaMinima)
    {
        $notaMinima = (float)$notaMinima;
        if ($notaMinima > self::QUIZ_TOTAL_VALORES) {
            return round(($notaMinima / 100) * self::QUIZ_TOTAL_VALORES, 2);
        }

        return max(0.0, min(self::QUIZ_TOTAL_VALORES, $notaMinima));
    }

    private function normalizarPontuacao($pontuacao, $percentual = null, $pontosTotais = self::QUIZ_TOTAL_VALORES)
    {
        $pontuacao = (float)$pontuacao;
        $pontosTotais = max(1.0, (float)$pontosTotais);

        if (abs($pontosTotais - self::QUIZ_TOTAL_VALORES) < 0.01) {
            return max(0.0, min(self::QUIZ_TOTAL_VALORES, $pontuacao));
        }

        if ($percentual !== null && $percentual !== '') {
            return max(0.0, min(self::QUIZ_TOTAL_VALORES, (((float)$percentual) / 100) * self::QUIZ_TOTAL_VALORES));
        }

        return max(0.0, min(self::QUIZ_TOTAL_VALORES, ($pontuacao / $pontosTotais) * self::QUIZ_TOTAL_VALORES));
    }

    private function inicializarGruposDificuldade()
    {
        $grupos = [];
        foreach (self::DIFICULDADE_PESOS as $slug => $peso) {
            $grupos[$slug] = [
                'slug' => $slug,
                'label' => $this->obterLabelDificuldade($slug),
                'peso' => $peso,
                'count' => 0,
                'approved' => 0,
                'total' => 0.0,
                'media' => null,
                'peso_normalizado' => 0.0,
            ];
        }

        return $grupos;
    }
}
?>
