<?php

/**
 * View/Partial: Criar Quiz
 */

$lesson_id = isset($lesson_id) ? (int)$lesson_id : (isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : (int)($_POST['lesson_id'] ?? 0));
$course_id = isset($course_id) ? (int)$course_id : (isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0);
$module_id = isset($module_id) ? (int)$module_id : (isset($_GET['module_id']) ? (int)$_GET['module_id'] : (int)($_POST['module_id'] ?? 0));
$lesson_options = is_array($lesson_options ?? null) ? $lesson_options : [];
$module_options = is_array($module_options ?? null) ? $module_options : [];
$course = $course ?? null;
$courseStructure = (string)($course['course_structure'] ?? 'single_module');
$defaultTipo = $lesson_id > 0 ? 'aula' : ($courseStructure === 'multi_module' && $module_id > 0 ? 'modulo' : 'final');
$isPartial = isset($_GET['partial']) && $_GET['partial'] == '1';
$wrapperTag = $isPartial ? 'div' : 'section';
$wrapperClass = $isPartial ? 'quiz-builder-shell editor-card create-quiz' : 'container quiz-builder-shell';
$formCardClass = $isPartial ? 'quiz-builder-card' : 'editor-card create-quiz quiz-builder-card';
$cancelHref = '?page=gerenciar-curso&id=' . htmlspecialchars((string)$course_id, ENT_QUOTES, 'UTF-8');
$courseLabel = htmlspecialchars((string)($course['titulo'] ?? ('Curso #' . $course_id)), ENT_QUOTES, 'UTF-8');
?>

<<?php echo $wrapperTag; ?> class="<?php echo $wrapperClass; ?>"<?php echo $isPartial ? ' data-modal-fragment="create-quiz"' : ''; ?>>
    <div class="quiz-builder-layout">
        <aside class="quiz-builder-showcase">
            <span class="quiz-builder-showcase__eyebrow">Experiência premium</span>
            <h2><?php echo $isPartial ? 'Monte um quiz claro e bonito' : 'Crie um quiz com aparência profissional'; ?></h2>
            <p>Organize a avaliação como um produto educacional de verdade: tipo correto, regras objetivas, perguntas bem distribuídas e uma experiência moderna para o aluno responder.</p>

            <div class="quiz-builder-showcase__stack">
                <article class="quiz-builder-showcase__card">
                    <strong>Fluxo editorial</strong>
                    <span>Defina o contexto, ajuste as regras e só depois construa as perguntas.</span>
                </article>
                <article class="quiz-builder-showcase__card">
                    <strong>Pontuação fechada</strong>
                    <span>O total sempre precisa fechar exatamente 20 valores para a correção ficar consistente.</span>
                </article>
                <article class="quiz-builder-showcase__card">
                    <strong>Leitura limpa</strong>
                    <span>O aluno recebe um quiz em etapas, com foco total em uma pergunta de cada vez.</span>
                </article>
            </div>

            <div class="quiz-builder-showcase__meta">
                <span class="quiz-builder-showcase__pill"><?php echo $courseLabel; ?></span>
                <span class="quiz-builder-showcase__pill"><?php echo htmlspecialchars(strtoupper($defaultTipo), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </aside>

        <div class="<?php echo $formCardClass; ?>">
            <div class="quiz-builder__header editor-card__header">
                <div class="quiz-builder__header-copy">
                    <span class="quiz-builder__eyebrow">Criação de avaliação</span>
                    <h1 class="editor-card__title"><?php echo $isPartial ? 'Criar Quiz' : 'Criar Quiz Profissional'; ?></h1>
                    <p class="editor-card__hint">Estruture o quiz com informações claras, configurações objetivas e perguntas prontas para uso.</p>
                </div>
            </div>

            <form id="form-criar-quiz" method="post" action="<?php echo BASE_URL; ?>/index.php" class="editor-card__body quiz-builder" data-quiz-builder>
            <input type="hidden" value="<?php echo htmlspecialchars('quiz_draft_' . $course_id . '_' . $lesson_id, ENT_QUOTES, 'UTF-8'); ?>" data-draft-key>
            <?php echo csrf_input(); ?>
            <input type="hidden" name="acao" value="criar_quiz">
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars((string)$course_id, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="lesson_id" value="<?php echo htmlspecialchars((string)$lesson_id, ENT_QUOTES, 'UTF-8'); ?>" data-lesson-hidden-input>

            <section class="quiz-builder__section">
                <div class="quiz-builder__section-head">
                    <div>
                        <span class="quiz-builder__section-step">1</span>
                        <h2>Informações do Quiz</h2>
                    </div>
                    <p>Defina o contexto principal da avaliação e o vínculo com o módulo ou com a etapa final do curso.</p>
                </div>

                <div class="quiz-builder__grid quiz-builder__grid--info">
                    <div class="form-group quiz-builder__field quiz-builder__field--full">
                        <label for="quiz-titulo">Título</label>
                        <input id="quiz-titulo" type="text" name="titulo" required maxlength="140" placeholder="Ex.: Quiz de revisão do módulo 1">
                    </div>

                    <div class="form-group quiz-builder__field quiz-builder__field--full">
                        <label for="quiz-descricao">Descrição</label>
                        <textarea id="quiz-descricao" name="descricao" rows="3" placeholder="Opcional. Descreva o objetivo pedagógico do quiz."></textarea>
                    </div>

                    <div class="form-group quiz-builder__field">
                        <label for="quiz-course-display">Curso</label>
                        <input id="quiz-course-display" type="text" value="<?php echo $courseLabel; ?>" readonly>
                    </div>

                    <div class="form-group quiz-builder__field">
                        <label for="quiz-type-select">Tipo</label>
                        <select name="tipo" id="quiz-type-select" data-quiz-type>
                            <option value="aula" <?php echo $defaultTipo === 'aula' ? 'selected' : ''; ?>>Quiz da Aula</option>
                            <?php if ($courseStructure === 'multi_module'): ?>
                                <option value="modulo" <?php echo $defaultTipo === 'modulo' ? 'selected' : ''; ?>>Quiz de Módulo</option>
                            <?php endif; ?>
                            <option value="final" <?php echo $defaultTipo === 'final' ? 'selected' : ''; ?>>Prova Final</option>
                        </select>
                    </div>

                    <div class="form-group quiz-builder__field quiz-builder__field--full" data-lesson-field>
                        <label for="quiz-lesson-select">Aula vinculada</label>
                        <?php if (!empty($lesson_options)): ?>
                            <select name="lesson_id" id="quiz-lesson-select" data-lesson-select>
                                <option value="">Selecione a aula</option>
                                <?php foreach ($lesson_options as $lessonOption): ?>
                                    <option value="<?php echo htmlspecialchars((string)($lessonOption['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (int)$lesson_id === (int)($lessonOption['id'] ?? 0) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lessonOption['titulo'] ?? 'Aula', ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" value="Nenhuma aula disponível" readonly>
                        <?php endif; ?>
                        <small>Obrigatório para o quiz da aula. Cada aula pode ter seu próprio quiz avaliativo.</small>
                    </div>

                    <div class="form-group quiz-builder__field quiz-builder__field--full" data-module-field <?php echo $courseStructure === 'multi_module' ? '' : 'hidden'; ?>>
                        <label for="quiz-module-select">Módulo vinculado</label>
                        <?php if (!empty($module_options)): ?>
                            <select name="module_id" id="quiz-module-select" data-module-select>
                                <option value="">Selecione o módulo</option>
                                <?php foreach ($module_options as $moduleOption): ?>
                                    <option value="<?php echo htmlspecialchars((string)($moduleOption['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (int)$module_id === (int)($moduleOption['id'] ?? 0) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($moduleOption['titulo'] ?? 'Módulo', ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="module_id" value="<?php echo htmlspecialchars((string)$module_id, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="text" value="Nenhum módulo disponível" readonly>
                        <?php endif; ?>
                        <small>Obrigatório para quizzes de módulo. A prova final fica no final do curso, sem vínculo com módulo.</small>
                    </div>
                </div>
            </section>

            <section class="quiz-builder__section">
                <div class="quiz-builder__section-head">
                    <div>
                        <span class="quiz-builder__section-step">2</span>
                        <h2>Configurações</h2>
                    </div>
                    <p>Ajuste regras de tentativa, aprovação e comportamento da experiência.</p>
                </div>

                <div class="quiz-builder__grid quiz-builder__grid--settings">
                    <div class="form-group quiz-builder__field">
                        <label for="quiz-tempo-limite">Tempo limite (minutos)</label>
                        <input id="quiz-tempo-limite" type="number" name="tempo_limite" min="0" max="240" step="1" value="0" placeholder="0 = sem limite">
                        <small class="quiz-builder__hint">`0` deixa o quiz sem cronômetro. Se definir um tempo, o envio acontece automaticamente ao terminar.</small>
                    </div>

                    <div class="form-group quiz-builder__field">
                        <label for="quiz-tentativas">Tentativas</label>
                        <input id="quiz-tentativas" type="number" name="tentativas_maximas" min="1" max="10" step="1" value="3" required>
                        <small class="quiz-builder__hint">Controla quantas vezes o aluno pode reenviar este quiz. O melhor resultado fica registrado.</small>
                    </div>

                    <div class="form-group quiz-builder__field">
                        <label for="quiz-dificuldade">Dificuldade</label>
                        <select id="quiz-dificuldade" name="dificuldade" data-difficulty-select>
                            <option value="normal" <?php echo $defaultTipo === 'final' ? '' : 'selected'; ?>>Normal · peso 20%</option>
                            <option value="medio">Médio · peso 30%</option>
                            <option value="dificil" <?php echo $defaultTipo === 'final' ? 'selected' : ''; ?>>Difícil · peso 50%</option>
                        </select>
                        <small class="quiz-builder__hint">A dificuldade define automaticamente o peso deste quiz na média final do curso.</small>
                    </div>

                    <div class="form-group quiz-builder__field">
                        <label for="quiz-nota-minima">Nota mínima para aprovação</label>
                        <input id="quiz-nota-minima" type="text" value="10 / 20 valores" readonly>
                        <small class="quiz-builder__hint">Todo quiz da plataforma exige no mínimo 10 valores para aprovação.</small>
                    </div>

                    <div class="form-group quiz-builder__field">
                        <label for="quiz-total-fixo">Total do quiz</label>
                        <input id="quiz-total-fixo" type="text" value="20 / 20 valores" readonly>
                        <small class="quiz-builder__hint">A soma das pontuações das perguntas deve fechar exatamente 20 valores antes de salvar.</small>
                    </div>
                </div>

                <div class="quiz-builder__toggles">
                    <label class="quiz-toggle">
                        <input type="checkbox" name="embaralhar_perguntas" value="1">
                        <span>
                            <strong>Embaralhar perguntas</strong>
                            <small>Muda a ordem das questões para cada tentativa, mantendo a mesma ordem durante aquele envio.</small>
                        </span>
                    </label>
                    <label class="quiz-toggle">
                        <input type="checkbox" name="embaralhar_respostas" value="1">
                        <span>
                            <strong>Embaralhar respostas</strong>
                            <small>Altera a ordem das alternativas para reduzir memorização mecânica.</small>
                        </span>
                    </label>
                    <label class="quiz-toggle">
                        <input type="checkbox" name="mostrar_respostas" value="1" checked>
                        <span>
                            <strong>Mostrar respostas após envio</strong>
                            <small>Se desligado, o aluno vê só o resultado geral, sem revisão pergunta por pergunta.</small>
                        </span>
                    </label>
                    <label class="quiz-toggle">
                        <input type="checkbox" name="mostrar_nota" value="1" checked>
                        <span>
                            <strong>Mostrar nota ao aluno</strong>
                            <small>Se desligado, o aluno não vê percentual, acertos nem nota final deste quiz.</small>
                        </span>
                    </label>
                    <label class="quiz-toggle">
                        <input type="checkbox" name="obrigatorio" value="1" checked>
                        <span>
                            <strong>Tornar obrigatório para progresso</strong>
                            <small>Quizzes obrigatórios entram nas exigências de conclusão do curso e certificado.</small>
                        </span>
                    </label>
                </div>

                <aside class="quiz-builder__explanation">
                    <h3>Como a nota final é calculada</h3>
                    <p>Cada quiz da plataforma vale exatamente 20 valores. O professor distribui esses 20 pelas perguntas.</p>
                    <p>Depois, o curso calcula uma média para cada grupo de dificuldade e combina essas médias com os pesos fixos normal, médio e difícil.</p>
                    <ul class="quiz-builder__formula-list">
                        <li>Resultado do quiz = pontos obtidos em 20 valores</li>
                        <li>Todo quiz aprova com no mínimo 10/20</li>
                        <li>Média final = média dos quizzes normais × 20% + médios × 30% + difíceis × 50%</li>
                        <li>Se um grupo não existir no curso, os pesos são redistribuídos entre os grupos existentes</li>
                    </ul>
                </aside>
            </section>

            <section class="quiz-builder__section">
                <div class="quiz-builder__section-head quiz-builder__section-head--actions">
                    <div>
                        <span class="quiz-builder__section-step">3</span>
                        <h2>Perguntas</h2>
                    </div>
                    <p>Monte perguntas em cards reutilizáveis, com alternativas, resposta correta e pontuação.</p>
                </div>

                <div class="quiz-builder__questions-summary">
                    <article class="quiz-builder__summary-card">
                        <span>Total atual do quiz</span>
                        <strong data-total-points>0 / 20</strong>
                        <small data-total-points-copy>Distribua os valores até fechar exatamente 20.</small>
                    </article>
                    <article class="quiz-builder__summary-card">
                        <span>Regra de aprovação</span>
                        <strong>10 / 20</strong>
                        <small>O aluno precisa de pelo menos 10 valores para passar neste quiz.</small>
                    </article>
                </div>

                <div class="quiz-builder__question-stage" data-builder-stage>
                    <header class="quiz-builder__question-stage-head">
                        <div class="quiz-builder__question-stage-copy">
                            <span class="quiz-builder__question-stage-label">Editor por etapas</span>
                            <strong data-builder-position-label>1 / 1</strong>
                        </div>
                        <div class="quiz-builder__question-stage-meta">
                            <span>Uma pergunta por vez</span>
                            <span>Visual limpo e focado</span>
                        </div>
                    </header>

                    <div class="quiz-builder__question-progress">
                        <div class="quiz-builder__question-progress-track" data-builder-progress></div>
                    </div>

                    <div class="quiz-builder__questions" data-question-list></div>

                    <div class="quiz-builder__question-nav">
                        <button type="button" class="btn btn-outline ui-btn" data-builder-prev disabled>Back</button>
                        <div class="quiz-builder__question-nav-center">
                            <strong data-builder-position-label>1 / 1</strong>
                            <span>Avance pergunta por pergunta para manter a edição organizada.</span>
                        </div>
                        <button type="button" class="btn btn-primary ui-btn ui-btn--primary" data-add-question>+ Adicionar pergunta</button>
                    </div>
                </div>

                <template id="quiz-question-template">
                    <article class="question-card" data-question-card draggable="true">
                        <div class="question-card__head">
                            <div>
                                <span class="question-card__index" data-question-number>Pergunta 1</span>
                                <h3 class="question-card__title">Questão de múltipla escolha</h3>
                            </div>
                            <div class="question-card__actions">
                                <button type="button" class="btn btn-outline btn-sm ui-btn ui-btn--small question-card__drag-handle" data-drag-question title="Arrastar para reordenar">Arrastar</button>
                                <button type="button" class="btn btn-outline btn-sm ui-btn ui-btn--small" data-move-question="up">Subir</button>
                                <button type="button" class="btn btn-outline btn-sm ui-btn ui-btn--small" data-move-question="down">Descer</button>
                                <button type="button" class="btn btn-danger btn-sm ui-btn ui-btn--small" data-remove-question>Remover</button>
                            </div>
                        </div>

                        <div class="question-card__body">
                            <div class="form-group quiz-builder__field quiz-builder__field--full">
                                <label>Enunciado</label>
                                <textarea rows="3" data-field="texto" placeholder="Digite o enunciado da pergunta"></textarea>
                            </div>

                            <div class="question-card__options" data-options-list></div>

                            <div class="question-card__footer">
                                <div class="form-group quiz-builder__field question-card__footer-action">
                                    <label>Alternativas</label>
                                    <button type="button" class="btn btn-outline ui-btn" data-add-option>+ Alternativa</button>
                                </div>
                                <div class="form-group quiz-builder__field">
                                    <label>Resposta correta</label>
                                    <select data-field="correta" data-correct-select></select>
                                    <small class="quiz-builder__hint">Escolhe qual alternativa será considerada certa na correção.</small>
                                </div>
                                <div class="form-group quiz-builder__field">
                                    <label>Pontuação</label>
                                    <input type="number" min="1" step="1" value="1" data-field="pontos">
                                    <small class="quiz-builder__hint">Distribua os valores das perguntas até a soma total fechar 20.</small>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>

                <template id="quiz-option-template">
                    <div class="question-option" data-option-item>
                        <span class="question-option__badge" data-option-label>A</span>
                        <input type="text" data-option-input placeholder="Digite a alternativa">
                        <button type="button" class="btn btn-outline btn-sm ui-btn ui-btn--small" data-remove-option>Remover</button>
                    </div>
                </template>
            </section>

            <div class="quiz-builder__alerts" data-quiz-builder-feedback aria-live="polite"></div>
            <div class="quiz-builder__draft-status" data-draft-status aria-live="polite"></div>

                <div class="panel-actions quiz-builder__footer">
                    <button class="btn btn-primary ui-btn ui-btn--primary" type="submit">Salvar Quiz</button>
                    <button type="button" class="btn btn-outline ui-btn" data-clear-draft>Limpar Rascunho</button>
                    <?php if ($isPartial): ?>
                        <button type="button" class="btn btn-secondary ui-btn" data-modal-close>Cancelar</button>
                    <?php else: ?>
                        <a href="<?php echo $cancelHref; ?>" class="btn btn-secondary ui-btn">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</<?php echo $wrapperTag; ?>>
