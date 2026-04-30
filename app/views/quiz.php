<?php

/**
 * View: Página de Quiz
 */
?>

<section class="quiz-view">
    <div class="container">
        <?php if (isset($quiz) && $quiz): ?>
            <?php
            $historico = $historico ?? [];
            $melhorResultado = $melhor_resultado ?? null;
            $ultimoResultado = $ultimo_resultado ?? null;
            $desempenhoCurso = $desempenho_curso ?? [];
            $quizTimer = $quiz_timer ?? null;
            $notaCurso = $desempenhoCurso['nota'] ?? [];
            $gruposNota = is_array($notaCurso['grupos'] ?? null) ? $notaCurso['grupos'] : [];
            $podeVerNota = tem_permissao('professor') || !empty($quiz['mostrar_nota']);
            $podeVerRespostas = tem_permissao('professor') || !empty($quiz['mostrar_respostas']);
            $formatValor = static function ($value) {
                return number_format((float)$value, 1, ',', '.');
            };
            $formatNota = static function ($value) use ($formatValor) {
                return $formatValor($value) . ' / 20';
            };
            $tentativasUsadas = count($historico);
            $tentativasMaximas = (int)($quiz['tentativas_maximas'] ?? 0);
            $tentativasRestantes = max(0, $tentativasMaximas - $tentativasUsadas);
            $tentativasEncerradas = $tentativasRestantes <= 0 && !tem_permissao('professor');
            $mostrarResultado = !empty($ultimoResultado['respostas']);
            $tempoLimiteAtivo = !$mostrarResultado
                && !$tentativasEncerradas
                && !tem_permissao('professor')
                && (int)($quiz['tempo_limite'] ?? 0) > 0
                && !empty($quizTimer['expires_at']);
            $questionCount = count($quiz['questoes'] ?? []);
            $tipoQuizLabel = match ((string)($quiz['tipo'] ?? 'aula')) {
                'final' => 'Quiz Final',
                'modulo' => 'Quiz do Módulo',
                default => 'Quiz da Aula',
            };
            $voltarHref = !empty($quiz['lesson_id'])
                ? '?page=aula&lesson_id=' . urlencode((string)$quiz['lesson_id'])
                : (!empty($quiz['course_id']) ? '?page=curso&id=' . urlencode((string)$quiz['course_id']) : 'javascript:history.back()');
            ?>
            <div class="quiz-header">
                <div class="quiz-header__main">
                    <div>
                        <h1><?php echo htmlspecialchars($quiz['titulo']); ?></h1>
                        <p class="quiz-description"><?php echo htmlspecialchars($quiz['descricao'] ?? ''); ?></p>
                        <div class="quiz-info">
                            <span>📝 <?php echo count($quiz['questoes'] ?? []); ?> questões</span>
                            <span>⭐ <?php echo htmlspecialchars($formatNota($quiz['pontos_totais'] ?? 20), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span>🔄 <?php echo htmlspecialchars($quiz['tentativas_maximas'], ENT_QUOTES, 'UTF-8'); ?> tentativas</span>
                            <span>⏳ Restantes <?php echo htmlspecialchars($tentativasRestantes, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span>🏷 <?php echo htmlspecialchars(strtoupper($quiz['tipo'] ?? 'aula'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span>📚 <?php echo htmlspecialchars($quiz['dificuldade_label'] ?? 'Normal', ENT_QUOTES, 'UTF-8'); ?> · peso <?php echo htmlspecialchars((string)($quiz['peso_percentual'] ?? $quiz['peso'] ?? 20), ENT_QUOTES, 'UTF-8'); ?>%</span>
                            <span>✅ Mínimo <?php echo htmlspecialchars($formatNota($quiz['nota_minima'] ?? 10), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>

                    <?php if ($tempoLimiteAtivo): ?>
                        <aside
                            class="quiz-timer"
                            data-quiz-timer
                            data-started-at="<?php echo htmlspecialchars((string)($quizTimer['started_at'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                            data-expires-at="<?php echo htmlspecialchars((string)($quizTimer['expires_at'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="quiz-timer__label">Tempo restante</span>
                            <strong class="quiz-timer__value" data-quiz-timer-value>00:00</strong>
                            <small class="quiz-timer__hint">O envio será automático quando o tempo acabar.</small>
                        </aside>
                    <?php endif; ?>
                </div>
            </div>

            <section class="quiz-course-performance">
                <article class="quiz-performance-card">
                    <span class="quiz-performance-label">Melhor resultado</span>
                    <strong><?php echo $podeVerNota ? htmlspecialchars($formatNota($melhorResultado['pontuacao'] ?? $melhorResultado['score'] ?? 0), ENT_QUOTES, 'UTF-8') : 'Oculta'; ?></strong>
                </article>
                <article class="quiz-performance-card">
                    <span class="quiz-performance-label">Nota final do curso</span>
                    <strong><?php echo $podeVerNota ? htmlspecialchars($formatNota($notaCurso['nota_final'] ?? 0), ENT_QUOTES, 'UTF-8') : 'Oculta'; ?></strong>
                </article>
                <article class="quiz-performance-card">
                    <span class="quiz-performance-label">Quizzes respondidos</span>
                    <strong><?php echo htmlspecialchars(($notaCurso['quizzes_respondidos'] ?? 0) . '/' . ($notaCurso['quizzes_total'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
                </article>
                <article class="quiz-performance-card">
                    <span class="quiz-performance-label">Status de aprovação</span>
                    <strong><?php echo !empty($notaCurso['aprovado']) ? 'Aprovado' : 'Pendente'; ?></strong>
                </article>
            </section>

            <?php if (!empty($gruposNota)): ?>
                <section class="quiz-group-summary">
                    <div class="quiz-feedback-panel__header">
                        <div>
                            <span class="quiz-feedback-panel__eyebrow">Composição da média</span>
                            <h2>Como a sua nota final está sendo montada</h2>
                            <p class="quiz-description">Cada grupo calcula sua própria média em valores e depois entra na nota final com o peso acadêmico do curso.</p>
                        </div>
                    </div>
                    <div class="quiz-group-summary__grid">
                        <?php foreach ($gruposNota as $grupo): ?>
                            <?php if (($grupo['count'] ?? 0) <= 0) { continue; } ?>
                            <article class="quiz-group-summary__card">
                                <span class="quiz-group-summary__label"><?php echo htmlspecialchars($grupo['label'] ?? 'Grupo', ENT_QUOTES, 'UTF-8'); ?></span>
                                <strong><?php echo htmlspecialchars($formatNota($grupo['media'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small>
                                    <?php echo htmlspecialchars((string)($grupo['count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> quiz(es)
                                    · peso real <?php echo htmlspecialchars(number_format((float)($grupo['peso_normalizado'] ?? 0), 1, ',', '.'), ENT_QUOTES, 'UTF-8'); ?>%
                                </small>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (($quiz['tipo'] ?? '') === 'modulo' && !empty($melhorResultado['aprovado']) && !empty($quiz['course_id']) && !empty($quiz['module_id'])): ?>
                <div class="quiz-actions quiz-actions--certificate">
                    <a href="?page=certificado&course_id=<?php echo htmlspecialchars((string)$quiz['course_id'], ENT_QUOTES, 'UTF-8'); ?>&type=module&module_id=<?php echo htmlspecialchars((string)$quiz['module_id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">Ver certificado</a>
                    <a href="?page=certificado&course_id=<?php echo htmlspecialchars((string)$quiz['course_id'], ENT_QUOTES, 'UTF-8'); ?>&type=module&module_id=<?php echo htmlspecialchars((string)$quiz['module_id'], ENT_QUOTES, 'UTF-8'); ?>&download=pdf" class="btn btn-outline-secondary">Baixar PDF</a>
                </div>
            <?php endif; ?>

            <?php if (($quiz['tipo'] ?? '') === 'final' && !empty($notaCurso['aprovado']) && !empty($quiz['course_id'])): ?>
                <div class="quiz-actions quiz-actions--certificate">
                    <a href="?page=certificado&course_id=<?php echo htmlspecialchars((string)$quiz['course_id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">Ver certificado</a>
                    <a href="?page=certificado&course_id=<?php echo htmlspecialchars((string)$quiz['course_id'], ENT_QUOTES, 'UTF-8'); ?>&download=pdf" class="btn btn-outline-secondary">Baixar PDF</a>
                </div>
            <?php endif; ?>

            <?php if ($mostrarResultado): ?>
                <section class="quiz-feedback-panel">
                    <div class="quiz-feedback-panel__header">
                        <div>
                            <span class="quiz-feedback-panel__eyebrow"><?php echo !empty($ultimoResultado['tempo_esgotado']) ? 'Resultado automático' : 'Resultado mais recente'; ?></span>
                            <h2>Seu desempenho nesta tentativa</h2>
                            <?php if (!empty($ultimoResultado['tempo_esgotado'])): ?>
                                <p class="quiz-feedback-explanation">Tempo esgotado. O quiz foi encerrado automaticamente e as respostas em branco foram consideradas incorretas.</p>
                            <?php endif; ?>
                        </div>
                        <div class="quiz-feedback-summary <?php echo !empty($ultimoResultado['aprovado']) ? 'is-success' : 'is-warning'; ?>">
                            <?php echo $podeVerNota ? htmlspecialchars($formatNota($ultimoResultado['pontuacao'] ?? $ultimoResultado['score'] ?? 0), ENT_QUOTES, 'UTF-8') : (!empty($ultimoResultado['aprovado']) ? 'Aprovado' : 'Reprovado'); ?>
                        </div>
                    </div>

                    <div class="quiz-result-stats">
                        <article class="quiz-result-stat">
                            <span>Nota final</span>
                            <strong><?php echo $podeVerNota ? htmlspecialchars($formatNota($ultimoResultado['pontuacao'] ?? $ultimoResultado['score'] ?? 0), ENT_QUOTES, 'UTF-8') : 'Oculta'; ?></strong>
                        </article>
                        <article class="quiz-result-stat">
                            <span>Status</span>
                            <strong><?php echo !empty($ultimoResultado['aprovado']) ? 'Aprovado' : 'Reprovado'; ?></strong>
                        </article>
                        <article class="quiz-result-stat">
                            <span>Acertos</span>
                            <strong><?php echo $podeVerNota ? htmlspecialchars((string)(($ultimoResultado['total_correto'] ?? 0) . '/' . ($ultimoResultado['total_questoes'] ?? 0)), ENT_QUOTES, 'UTF-8') : 'Ocultos'; ?></strong>
                        </article>
                        <article class="quiz-result-stat">
                            <span>Tempo gasto</span>
                            <strong><?php echo htmlspecialchars(gmdate('i:s', max(0, (int)($ultimoResultado['tempo_gasto'] ?? 0))), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                    </div>

                    <?php if ($podeVerRespostas): ?>
                        <div class="quiz-feedback-grid">
                            <?php foreach ($ultimoResultado['respostas'] as $feedback): ?>
                                <article class="quiz-feedback-item <?php echo !empty($feedback['correta']) ? 'is-correct' : 'is-wrong'; ?>">
                                    <h3><?php echo htmlspecialchars($feedback['texto'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p><strong>Sua resposta:</strong> <?php echo htmlspecialchars($feedback['resposta_usuario'] ?? 'Não respondida', ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p><strong>Resposta correta:</strong> <?php echo htmlspecialchars($feedback['resposta_correta'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php if (!empty($feedback['explicacao'])): ?>
                                        <p class="quiz-feedback-explanation"><?php echo htmlspecialchars($feedback['explicacao'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php else: ?>
                                        <p class="quiz-feedback-explanation"><?php echo !empty($feedback['correta']) ? 'Boa resposta. Você pode avançar para a próxima etapa.' : 'Revise a aula e tente novamente para reforçar este conceito.'; ?></p>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="quiz-feedback-locked">
                            <strong>Revisão oculta neste quiz.</strong>
                            <span>O professor desativou a visualização das respostas corretas após o envio.</span>
                        </div>
                    <?php endif; ?>

                    <div class="quiz-actions quiz-actions--result">
                        <?php if (!$tentativasEncerradas && !tem_permissao('professor')): ?>
                            <a href="?page=quiz&quiz_id=<?php echo htmlspecialchars((string)$quiz['id'], ENT_QUOTES, 'UTF-8'); ?>&restart=1" class="btn btn-primary">Refazer quiz</a>
                        <?php endif; ?>
                        <a href="<?php echo htmlspecialchars($voltarHref, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Voltar ao curso</a>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!$mostrarResultado): ?>
            <form method="POST" class="quiz-form" id="quiz-form" data-quiz-runtime>
                <input type="hidden" name="acao" value="responder_quiz">
                <input type="hidden" name="quiz_id" value="<?php echo htmlspecialchars($quiz['id'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="tempo_gasto" value="0" data-quiz-elapsed-input>
                <input type="hidden" name="tempo_esgotado" value="0" data-quiz-timeout-input>
                <?php echo csrf_input(); ?>

                <?php if (isset($quiz['questoes']) && count($quiz['questoes']) > 0): ?>
                    <?php if ($tentativasEncerradas): ?>
                        <section class="quiz-status-banner quiz-status-banner--locked">
                            <div class="quiz-status-banner__icon" aria-hidden="true">!</div>
                            <div class="quiz-status-banner__content">
                                <span class="quiz-status-banner__eyebrow">Tentativas encerradas</span>
                                <h2>Limite de tentativas atingido</h2>
                                <p>Este quiz já consumiu todas as tentativas permitidas. Revise o feedback e o histórico abaixo antes de continuar na trilha.</p>
                            </div>
                        </section>
                    <?php endif; ?>
                    <div class="quiz-player" data-quiz-player>
                        <aside class="quiz-player__aside">
                            <span class="quiz-player__eyebrow"><?php echo htmlspecialchars($tipoQuizLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                            <h2><?php echo htmlspecialchars($quiz['titulo'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p><?php echo htmlspecialchars($quiz['descricao'] ?? 'Responda com calma. O quiz foi organizado para focar uma pergunta de cada vez e manter a leitura limpa.', ENT_QUOTES, 'UTF-8'); ?></p>

                            <div class="quiz-player__summary">
                                <article class="quiz-player__summary-card">
                                    <span>Questões</span>
                                    <strong><?php echo htmlspecialchars((string)$questionCount, ENT_QUOTES, 'UTF-8'); ?></strong>
                                </article>
                                <article class="quiz-player__summary-card">
                                    <span>Tentativas</span>
                                    <strong><?php echo htmlspecialchars((string)$tentativasRestantes, ENT_QUOTES, 'UTF-8'); ?> restantes</strong>
                                </article>
                                <article class="quiz-player__summary-card">
                                    <span>Nota mínima</span>
                                    <strong><?php echo htmlspecialchars($formatNota($quiz['nota_minima'] ?? 10), ENT_QUOTES, 'UTF-8'); ?></strong>
                                </article>
                            </div>
                        </aside>

                        <section class="quiz-player__panel">
                            <header class="quiz-player__panel-head">
                                <div class="quiz-player__panel-copy">
                                    <span class="quiz-player__panel-label"><?php echo htmlspecialchars($tipoQuizLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <strong><?php echo htmlspecialchars($course_id ?? $quiz['course_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                                <div class="quiz-player__panel-status">
                                    <span data-quiz-position-label>1 / <?php echo htmlspecialchars((string)$questionCount, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span><?php echo htmlspecialchars((string)$tentativasUsadas, ENT_QUOTES, 'UTF-8'); ?> usadas</span>
                                </div>
                            </header>

                            <div class="quiz-player__progress" role="progressbar" aria-valuemin="1" aria-valuemax="<?php echo htmlspecialchars((string)$questionCount, ENT_QUOTES, 'UTF-8'); ?>" aria-valuenow="1">
                                <div class="quiz-player__progress-track">
                                    <?php foreach ($quiz['questoes'] as $index => $questao): ?>
                                        <button type="button" class="quiz-player__progress-step<?php echo $index === 0 ? ' is-active' : ''; ?>" data-quiz-step data-step-index="<?php echo htmlspecialchars((string)$index, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Ir para questão <?php echo htmlspecialchars((string)($index + 1), ENT_QUOTES, 'UTF-8'); ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="quiz-runtime-message" data-quiz-timeout-message hidden></div>

                            <div class="questoes-container quiz-player__questions">
                                <?php foreach ($quiz['questoes'] as $index => $questao): ?>
                                    <article class="questao-card<?php echo $index === 0 ? ' is-active' : ''; ?>" data-quiz-question data-question-index="<?php echo htmlspecialchars((string)$index, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $index === 0 ? '' : 'hidden'; ?>>
                                        <div class="questao-numero"><?php echo htmlspecialchars((string)($index + 1), ENT_QUOTES, 'UTF-8'); ?>.</div>
                                        <h3><?php echo htmlspecialchars($questao['texto']); ?></h3>

                                        <?php if ($questao['tipo'] === 'multipla'): ?>
                                            <?php
                                            $opcoesRaw = $questao['opcoes'] ?? [];
                                            if (is_string($opcoesRaw)) {
                                                $opcoes = json_decode($opcoesRaw, true) ?? [];
                                            } elseif (is_array($opcoesRaw)) {
                                                $opcoes = $opcoesRaw;
                                            } else {
                                                $opcoes = [];
                                            }
                                            $opcoes = array_values(array_filter(array_map(static function ($opcao) {
                                                return is_scalar($opcao) ? trim((string)$opcao) : '';
                                            }, $opcoes), static function ($opcao) {
                                                return $opcao !== '';
                                            }));
                                            ?>
                                            <div class="opcoes-container opcoes-container--stacked">
                                                <?php if (!empty($opcoes)): ?>
                                                    <?php foreach ($opcoes as $optionIndex => $opcao): ?>
                                                        <label class="opcao-radio opcao-radio--card">
                                                            <input
                                                                type="radio"
                                                                name="questao_<?php echo $questao['id']; ?>"
                                                                value="<?php echo htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8'); ?>"
                                                                <?php echo $tentativasEncerradas ? 'disabled' : ''; ?>>
                                                            <span class="opcao-radio__marker"><?php echo htmlspecialchars(chr(65 + $optionIndex), ENT_QUOTES, 'UTF-8'); ?></span>
                                                            <span class="opcao-radio__content"><?php echo htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8'); ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="alert alert-warning">Esta questão está sem alternativas válidas. Volte mais tarde ou contacte o professor.</div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($questao['tipo'] === 'verdadeiro_falso'): ?>
                                            <div class="opcoes-container opcoes-container--stacked">
                                                <label class="opcao-radio opcao-radio--card">
                                                    <input type="radio" name="questao_<?php echo $questao['id']; ?>" value="verdadeiro" <?php echo $tentativasEncerradas ? 'disabled' : ''; ?>>
                                                    <span class="opcao-radio__marker">A</span>
                                                    <span class="opcao-radio__content">Verdadeiro</span>
                                                </label>
                                                <label class="opcao-radio opcao-radio--card">
                                                    <input type="radio" name="questao_<?php echo $questao['id']; ?>" value="falso" <?php echo $tentativasEncerradas ? 'disabled' : ''; ?>>
                                                    <span class="opcao-radio__marker">B</span>
                                                    <span class="opcao-radio__content">Falso</span>
                                                </label>
                                            </div>
                                        <?php elseif ($questao['tipo'] === 'dissertativa'): ?>
                                            <textarea
                                                name="questao_<?php echo $questao['id']; ?>"
                                                class="resposta-texto"
                                                placeholder="Digite sua resposta aqui..."
                                                <?php echo $tentativasEncerradas ? 'disabled' : ''; ?>></textarea>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>

                            <div class="quiz-player__footer">
                                <button type="button" class="btn btn-outline-secondary" data-quiz-prev disabled>Back</button>
                                <div class="quiz-player__footer-center">
                                    <strong data-quiz-position-label>1 / <?php echo htmlspecialchars((string)$questionCount, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span>Responda no seu ritmo e avance.</span>
                                </div>
                                <div class="quiz-player__footer-actions">
                                    <button type="button" class="btn btn-primary" data-quiz-next>Next</button>
                                    <button type="submit" class="btn btn-primary btn-lg" data-quiz-submit hidden>Enviar Respostas</button>
                                </div>
                            </div>
                        </section>
                    </div>

                    <?php if (tem_permissao('professor')): ?>
                        <div class="professor-quiz-panel">
                            <h3>Gerenciar Questões</h3>

                            <div class="existing-questions">
                                <h4>Questões existentes</h4>
                                <?php if (!empty($quiz['questoes'])): ?>
                                    <ul class="question-admin-list">
                                        <?php foreach ($quiz['questoes'] as $q): ?>
                                            <li class="question-admin-item">
                                                <strong><?php echo htmlspecialchars($q['texto']); ?></strong>
                                                <form method="post" class="btn-inline inline-form" data-ajax="true" data-confirm="Deletar esta questão?">
                                                    <?php echo csrf_input(); ?>
                                                    <input type="hidden" name="acao" value="deletar_questao">
                                                    <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                                                    <button class="btn btn-danger btn-sm delete-question" type="submit">Deletar</button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p>Sem questões.</p>
                                <?php endif; ?>
                            </div>

                            <div class="add-question">
                                <h4>Adicionar Questão</h4>
                                <form id="form-adicionar-questao" method="post" action="<?php echo BASE_URL; ?>/index.php" data-ajax="true">
                                    <?php echo csrf_input(); ?>
                                    <input type="hidden" name="acao" value="adicionar_questao">
                                    <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">

                                    <div class="form-group">
                                        <label for="q-tipo">Tipo</label>
                                        <select name="tipo" id="q-tipo">
                                            <option value="multipla">Múltipla Escolha</option>
                                            <option value="verdadeiro_falso">Verdadeiro / Falso</option>
                                            <option value="dissertativa">Dissertativa</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="q-texto">Enunciado</label>
                                        <input type="text" name="texto" id="q-texto" required>
                                    </div>

                                    <div id="q-opcoes-container" class="question-options-builder">
                                        <label>Opções (para Múltipla Escolha)</label>
                                        <div id="q-opcoes-list" class="question-options-list">
                                            <div class="opcao-row">
                                                <input type="text" name="opcao_0" placeholder="Opção 1">
                                            </div>
                                            <div class="opcao-row">
                                                <input type="text" name="opcao_1" placeholder="Opção 2">
                                            </div>
                                        </div>
                                        <button type="button" id="add-opcao" class="btn btn-sm">+ Adicionar Opção</button>
                                    </div>

                                    <div class="form-group">
                                        <label for="resposta-correta">Resposta correta</label>
                                        <input type="text" id="resposta-correta" name="resposta_correta" placeholder="Resposta correta (valor/identificador)">
                                        <small class="muted">Para múltipla escolha, insira o texto exato da opção correta.</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="q-explicacao">Explicação da resposta (opcional)</label>
                                        <textarea id="q-explicacao" name="explicacao" placeholder="Explique por que a resposta está correta e o que o aluno deve revisar."></textarea>
                                    </div>

                                    <div class="form-actions">
                                        <button class="btn btn-primary" type="submit">Adicionar Questão</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="quiz-actions <?php echo $tentativasEncerradas && !tem_permissao('professor') ? 'quiz-actions--locked' : ''; ?>">
                        <?php if ($tentativasRestantes > 0 || tem_permissao('professor')): ?>
                            <button type="submit" class="btn btn-primary btn-lg">✓ Enviar Respostas</button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">Nenhuma questão disponível.</div>
                <?php endif; ?>
            </form>
            <?php endif; ?>

            <?php if (!empty($historico)): ?>
                <section class="quiz-history-panel">
                    <div class="quiz-feedback-panel__header">
                        <div>
                            <span class="quiz-feedback-panel__eyebrow">Histórico</span>
                            <h2>Tentativas anteriores</h2>
                        </div>
                    </div>
                    <div class="quiz-history-list">
                        <?php foreach ($historico as $attempt): ?>
                            <article class="quiz-history-item">
                                <strong>Tentativa <?php echo htmlspecialchars($attempt['tentativa_numero'] ?? $attempt['tentativa'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span><?php echo htmlspecialchars($formatNota($attempt['pontuacao'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></span>
                                <small><?php echo !empty($attempt['data_realizacao']) ? date('d/m/Y H:i', strtotime($attempt['data_realizacao'])) : '-'; ?></small>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-error">Quiz não encontrado.</div>
        <?php endif; ?>
    </div>
</section>
