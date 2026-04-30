<?php

/**
 * View: Gerenciar Curso por Módulos
 */

$courseTitle = htmlspecialchars($curso['titulo'] ?? 'Curso', ENT_QUOTES, 'UTF-8');
$courseId = (int)($curso['id'] ?? 0);
$courseDescription = trim((string)($curso['descricao'] ?? ''));
$courseStructure = (string)($curso['course_structure'] ?? 'single_module');
$courseStructureLabel = htmlspecialchars((string)($curso['course_structure_label'] ?? ($courseStructure === 'multi_module' ? 'Múltiplos módulos' : 'Módulo único')), ENT_QUOTES, 'UTF-8');
$modules = is_array($curso['modulos'] ?? null) ? $curso['modulos'] : [];
$finalQuizzes = is_array($curso['quizzes_finais'] ?? null) ? $curso['quizzes_finais'] : [];
$renderFormattedDescription = static function (string $text): string {
    $normalized = trim(str_replace(["\r\n", "\r"], "\n", $text));
    if ($normalized === '') {
        return '';
    }

    $paragraphs = preg_split("/\n\s*\n/", $normalized) ?: [];
    $chunks = [];

    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph === '') {
            continue;
        }

        $chunks[] = '<p>' . nl2br(htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8')) . '</p>';
    }

    return implode('', $chunks);
};
$totalLessons = count($curso['aulas'] ?? []);
$totalStudents = (int)($curso['total_alunos'] ?? 0);
$totalModuleQuizzes = 0;
foreach ($modules as $module) {
    $totalModuleQuizzes += count($module['module_quizzes'] ?? []);
}
?>

<section class="container manage-course-page">
    <header class="manage-course-hero card">
        <div class="manage-course-hero__media">
            <?php if (!empty($curso['thumbnail'])): ?>
                <img src="<?php echo htmlspecialchars(thumbnail_url($curso['thumbnail'])); ?>" alt="<?php echo $courseTitle; ?>" class="course-thumb">
            <?php else: ?>
                <div class="course-thumb-placeholder" aria-hidden="true">📘</div>
            <?php endif; ?>
        </div>

        <div class="manage-course-hero__content">
            <div class="manage-course-hero__top">
                <span class="manage-course-hero__eyebrow">Gestão modular do curso</span>
                <h1><?php echo $courseTitle; ?></h1>
                <p>
                    <?php if ($courseDescription !== ''): ?>
                        <?php echo htmlspecialchars(mb_substr($courseDescription, 0, 220, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?><?php echo mb_strlen($courseDescription, 'UTF-8') > 220 ? '...' : ''; ?>
                    <?php else: ?>
                        Organize conteúdo profissionalmente em módulos, aulas e avaliações com progressão clara.
                    <?php endif; ?>
                </p>
            </div>

            <div class="manage-course-hero__meta">
                <span class="manage-course-hero__pill"><?php echo htmlspecialchars((string)count($modules), ENT_QUOTES, 'UTF-8'); ?> módulos</span>
                <span class="manage-course-hero__pill"><?php echo htmlspecialchars((string)$totalLessons, ENT_QUOTES, 'UTF-8'); ?> aulas</span>
                <span class="manage-course-hero__pill"><?php echo htmlspecialchars((string)$totalModuleQuizzes, ENT_QUOTES, 'UTF-8'); ?> quizzes de módulo</span>
                <span class="manage-course-hero__pill"><?php echo htmlspecialchars((string)count($finalQuizzes), ENT_QUOTES, 'UTF-8'); ?> quiz final</span>
                <span class="manage-course-hero__pill"><?php echo $courseStructureLabel; ?></span>
                <span class="manage-course-hero__pill"><?php echo htmlspecialchars((string)$totalStudents, ENT_QUOTES, 'UTF-8'); ?> alunos</span>
            </div>

            <div class="manage-course-hero__actions">
                <a href="?page=editar-curso&id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline ui-btn">Editar Curso</a>
                <?php if ($courseStructure === 'multi_module'): ?>
                    <a href="?page=criar-modulo&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary ui-btn ui-btn--secondary" data-fragment="?page=criar-modulo&partial=1&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" data-fragment-title="Criar Módulo">+ Criar Módulo</a>
                <?php endif; ?>
                <a href="?page=criar-aula&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary ui-btn ui-btn--primary" data-fragment="?page=criar-aula&partial=1&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" data-fragment-title="Adicionar Aula">+ Adicionar Aula</a>
                <a href="?page=criar-quiz&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary ui-btn ui-btn--secondary" data-fragment="?page=criar-quiz&partial=1&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" data-fragment-title="Criar Quiz Final">+ Quiz Final</a>

                <form method="post" action="<?php echo BASE_URL; ?>/index.php?page=exportar-dados-professor" class="manage-course-hero__export" data-export-form>
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="scope" value="course">
                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="field-with-toggle">
                        <input type="password" name="backup_password" placeholder="Senha opcional">
                    </div>
                    <button type="submit" class="btn btn-outline ui-btn" data-loading-text="Gerando backup...">Exportar curso</button>
                </form>

                <form method="post" action="<?php echo BASE_URL; ?>/index.php?page=exportar-dados-professor" class="manage-course-hero__export" data-export-form>
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="scope" value="students">
                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="field-with-toggle">
                        <input type="password" name="backup_password" placeholder="Senha opcional">
                    </div>
                    <button type="submit" class="btn btn-outline ui-btn" data-loading-text="Gerando backup...">Exportar alunos</button>
                </form>
            </div>
        </div>
    </header>

    <div class="manage-course-panels manage-course-panels--modules">
        <section class="card manage-course-panel manage-course-panel--modules">
            <div class="manage-course-panel__header">
                <div class="panel-heading">
                    <h3>Estrutura por módulos</h3>
                    <span class="panel-count">Cada módulo organiza aulas e o quiz que libera a próxima etapa.</span>
                </div>
            </div>

            <div class="manage-course-panel__body">
                <div class="module-stack scrollable">
                    <?php foreach ($modules as $module): ?>
                        <?php
                        $moduleId = (int)($module['id'] ?? 0);
                        $moduleLessons = is_array($module['lessons'] ?? null) ? $module['lessons'] : [];
                        $moduleQuizzes = is_array($module['module_quizzes'] ?? null) ? $module['module_quizzes'] : [];
                        $moduleLessonQuizzesCount = (int)($module['lesson_quizzes_count'] ?? 0);
                        $moduleDescription = trim((string)($module['descricao'] ?? ''));
                        $hasLongDescription = mb_strlen($moduleDescription, 'UTF-8') > 220;
                        ?>
                        <article class="module-card">
                            <header class="module-card__header">
                                <div class="module-card__copy">
                                    <span class="module-card__eyebrow">Módulo <?php echo htmlspecialchars((string)($module['position'] ?? 1), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <h4><?php echo htmlspecialchars((string)($module['titulo'] ?? 'Módulo'), ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <?php if ($moduleDescription !== ''): ?>
                                        <div class="expandable-copy<?php echo $hasLongDescription ? ' is-collapsed' : ''; ?>" data-expandable-copy>
                                            <div class="expandable-copy__content" data-expandable-content>
                                                <?php echo $renderFormattedDescription($moduleDescription); ?>
                                            </div>
                                            <?php if ($hasLongDescription): ?>
                                                <button type="button" class="expandable-copy__toggle" data-expandable-trigger aria-expanded="false">Expandir descrição</button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="module-card__actions">
                                    <?php if ($courseStructure === 'multi_module'): ?>
                                        <form method="post" action="<?php echo BASE_URL; ?>/index.php" class="inline-form">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="acao" value="mover_modulo">
                                            <input type="hidden" name="module_id" value="<?php echo htmlspecialchars((string)$moduleId, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="direction" value="up">
                                            <button class="btn btn-outline btn-sm ui-btn ui-btn--small" type="submit">Subir</button>
                                        </form>
                                        <form method="post" action="<?php echo BASE_URL; ?>/index.php" class="inline-form">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="acao" value="mover_modulo">
                                            <input type="hidden" name="module_id" value="<?php echo htmlspecialchars((string)$moduleId, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="direction" value="down">
                                            <button class="btn btn-outline btn-sm ui-btn ui-btn--small" type="submit">Descer</button>
                                        </form>
                                        <a href="?page=editar-modulo&module_id=<?php echo htmlspecialchars((string)$moduleId, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline btn-sm ui-btn ui-btn--small" data-fragment="?page=editar-modulo&partial=1&module_id=<?php echo htmlspecialchars((string)$moduleId, ENT_QUOTES, 'UTF-8'); ?>" data-fragment-title="Editar Módulo">Editar módulo</a>
                                    <?php endif; ?>
                                    <a href="?page=criar-aula&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>&module_id=<?php echo htmlspecialchars((string)$moduleId, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-sm ui-btn ui-btn--small ui-btn--primary" data-fragment="?page=criar-aula&partial=1&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>&module_id=<?php echo htmlspecialchars((string)$moduleId, ENT_QUOTES, 'UTF-8'); ?>" data-fragment-title="Adicionar Aula">+ Aula</a>
                                    <?php if ($courseStructure === 'multi_module'): ?>
                                        <a href="?page=criar-quiz&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>&module_id=<?php echo htmlspecialchars((string)$moduleId, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary btn-sm ui-btn ui-btn--small ui-btn--secondary" data-fragment="?page=criar-quiz&partial=1&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>&module_id=<?php echo htmlspecialchars((string)$moduleId, ENT_QUOTES, 'UTF-8'); ?>" data-fragment-title="Criar Quiz de Módulo">+ Quiz do módulo</a>
                                    <?php endif; ?>
                                </div>
                            </header>

                            <div class="module-card__body">
                                <div class="module-card__section">
                                    <div class="module-card__section-head">
                                        <h5>Aulas</h5>
                                        <span><?php echo htmlspecialchars((string)count($moduleLessons), ENT_QUOTES, 'UTF-8'); ?> item(ns) · <?php echo htmlspecialchars((string)$moduleLessonQuizzesCount, ENT_QUOTES, 'UTF-8'); ?> quiz(es) de aula</span>
                                    </div>
                                    <div class="module-list">
                                        <?php if (!empty($moduleLessons)): ?>
                                            <?php foreach ($moduleLessons as $lesson): ?>
                                                <?php
                                                $lessonQuizzes = is_array($lesson['lesson_quizzes'] ?? null) ? $lesson['lesson_quizzes'] : [];
                                                $lessonQuiz = $lessonQuizzes[0] ?? null;
                                                ?>
                                                <article class="lesson-card" draggable="false" data-lesson-id="<?php echo htmlspecialchars((string)($lesson['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <div class="lesson-main">
                                                        <h4 class="lesson-title"><?php echo htmlspecialchars((string)($lesson['titulo'] ?? 'Aula'), ENT_QUOTES, 'UTF-8'); ?></h4>
                                                        <div class="quiz-meta">
                                                            <span><?php echo !empty($lessonQuiz) ? 'Quiz da aula criado' : 'Sem quiz da aula'; ?></span>
                                                            <?php if (!empty($lessonQuiz)): ?>
                                                                <span><?php echo htmlspecialchars((string)($lessonQuiz['dificuldade_label'] ?? 'Normal'), ENT_QUOTES, 'UTF-8'); ?></span>
                                                                <span><?php echo htmlspecialchars((string)($lessonQuiz['peso_percentual'] ?? $lessonQuiz['peso'] ?? 20), ENT_QUOTES, 'UTF-8'); ?>%</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="lesson-actions">
                                                        <?php if (!empty($lessonQuiz)): ?>
                                                            <a class="btn btn-secondary btn-sm ui-btn ui-btn--small ui-btn--secondary" href="?page=quiz&quiz_id=<?php echo htmlspecialchars((string)($lessonQuiz['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">Abrir Quiz</a>
                                                        <?php else: ?>
                                                            <a class="btn btn-secondary btn-sm ui-btn ui-btn--small ui-btn--secondary" href="?page=criar-quiz&lesson_id=<?php echo htmlspecialchars((string)($lesson['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" data-fragment="?page=criar-quiz&partial=1&lesson_id=<?php echo htmlspecialchars((string)($lesson['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" data-fragment-title="Criar Quiz da Aula">Quiz</a>
                                                        <?php endif; ?>
                                                        <a class="btn btn-outline btn-sm ui-btn ui-btn--small" href="?page=editar-aula&lesson_id=<?php echo htmlspecialchars((string)($lesson['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" data-fragment="?page=editar-aula&partial=1&lesson_id=<?php echo htmlspecialchars((string)($lesson['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" data-fragment-title="Editar Aula">Editar</a>
                                                        <button type="button" class="btn btn-danger btn-sm btn-delete-lesson ui-btn ui-btn--small" data-lesson-id="<?php echo htmlspecialchars((string)($lesson['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">Excluir</button>
                                                    </div>
                                                </article>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="no-content">
                                                Nenhuma aula neste módulo ainda.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="module-card__section">
                                    <div class="module-card__section-head">
                                        <h5>Quiz do módulo</h5>
                                        <span><?php echo htmlspecialchars((string)count($moduleQuizzes), ENT_QUOTES, 'UTF-8'); ?> item(ns)</span>
                                    </div>
                                    <div class="module-list">
                                        <?php if (!empty($moduleQuizzes)): ?>
                                            <?php foreach ($moduleQuizzes as $quiz): ?>
                                                <article class="quiz-card">
                                                    <div class="quiz-main">
                                                        <div class="quiz-title"><?php echo htmlspecialchars((string)($quiz['titulo'] ?? 'Quiz'), ENT_QUOTES, 'UTF-8'); ?></div>
                                                        <div class="quiz-meta">
                                                            <span><?php echo htmlspecialchars((string)($quiz['dificuldade_label'] ?? 'Normal'), ENT_QUOTES, 'UTF-8'); ?></span>
                                                            <span><?php echo htmlspecialchars((string)($quiz['peso_percentual'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>%</span>
                                                        </div>
                                                    </div>
                                                    <div class="quiz-actions">
                                                        <a class="btn btn-outline btn-sm ui-btn ui-btn--small" href="?page=quiz&quiz_id=<?php echo htmlspecialchars((string)($quiz['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">Abrir</a>
                                                    </div>
                                                </article>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="no-content">
                                                Este módulo ainda não tem quiz de progressão.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($courseStructure === 'multi_module'): ?>
                    <div class="manage-course-panel__footer">
                        <a href="?page=criar-modulo&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary ui-btn ui-btn--secondary" data-fragment="?page=criar-modulo&partial=1&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" data-fragment-title="Criar Módulo">+ Criar Módulo</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card manage-course-panel">
            <div class="manage-course-panel__header">
                <div class="panel-heading">
                    <h3>Quiz final do curso</h3>
                    <span class="panel-count">Obrigatório para certificação e fechamento da trilha.</span>
                </div>
            </div>

            <div class="manage-course-panel__body">
                <div class="quiz-summary">
                    <div class="quiz-summary__item">
                        <span>Estrutura</span>
                        <strong><?php echo $courseStructureLabel; ?></strong>
                    </div>
                    <div class="quiz-summary__item">
                        <span>Módulos</span>
                        <strong><?php echo htmlspecialchars((string)count($modules), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                    <div class="quiz-summary__item">
                        <span>Status do final</span>
                        <strong><?php echo !empty($finalQuizzes) ? 'Configurado' : 'Pendente'; ?></strong>
                    </div>
                </div>

                <div class="quiz-list scrollable">
                    <?php if (!empty($finalQuizzes)): ?>
                        <?php foreach ($finalQuizzes as $quiz): ?>
                            <article class="quiz-card">
                                <div class="quiz-main">
                                    <div class="quiz-title"><?php echo htmlspecialchars((string)($quiz['titulo'] ?? 'Quiz final'), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="quiz-meta">
                                        <span>Final do curso</span>
                                        <span><?php echo htmlspecialchars((string)($quiz['dificuldade_label'] ?? 'Difícil'), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span><?php echo htmlspecialchars((string)($quiz['peso_percentual'] ?? 50), ENT_QUOTES, 'UTF-8'); ?>%</span>
                                    </div>
                                </div>
                                <div class="quiz-actions">
                                    <a class="btn btn-outline btn-sm ui-btn ui-btn--small" href="?page=quiz&quiz_id=<?php echo htmlspecialchars((string)($quiz['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">Abrir</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-content no-content--rich">
                            <h4>Quiz final ainda não criado</h4>
                            <p>Todo curso profissional precisa de uma avaliação final para fechar a certificação com clareza.</p>
                            <a href="?page=criar-quiz&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary ui-btn ui-btn--secondary" data-fragment="?page=criar-quiz&partial=1&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" data-fragment-title="Criar Quiz Final">+ Criar Quiz Final</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="manage-course-panel__footer">
                    <a href="?page=criar-quiz&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary ui-btn ui-btn--secondary" data-fragment="?page=criar-quiz&partial=1&course_id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" data-fragment-title="Criar Quiz Final">+ Quiz Final</a>
                </div>
            </div>
        </section>
    </div>
</section>
