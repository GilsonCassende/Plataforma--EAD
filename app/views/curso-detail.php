<?php

/**
 * View: Detalhes do Curso
 */

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
?>

<section class="curso-detail">
    <div class="container">
        <?php if (isset($curso) && $curso): ?>
            <?php
            $certificateSummary = is_array($curso['certificate_summary'] ?? null) ? $curso['certificate_summary'] : [];
            $courseCertificates = is_array($curso['certificates'] ?? null) ? $curso['certificates'] : ['course' => null, 'modules' => []];
            $moduleCertificateStates = [];
            foreach (($certificateSummary['modules'] ?? []) as $moduleCertificateState) {
                $moduleCertificateStates[(int)($moduleCertificateState['module_id'] ?? 0)] = $moduleCertificateState;
            }
            $courseCertificateState = is_array($certificateSummary['course'] ?? null) ? $certificateSummary['course'] : [];
            ?>
            <div class="curso-header">
                <?php if ($curso['thumbnail']): ?>
                    <img src="<?php echo htmlspecialchars(thumbnail_url($curso['thumbnail'])); ?>" alt="<?php echo htmlspecialchars($curso['titulo']); ?>" class="curso-banner" loading="eager" width="1200" height="400">
                <?php else: ?>
                    <div class="curso-banner-placeholder">
                        <i class="icon-book"></i>
                    </div>
                <?php endif; ?>

                <div class="curso-info">
                    <h1><?php echo htmlspecialchars($curso['titulo']); ?></h1>
                    <p class="professor">Prof. <?php echo htmlspecialchars($curso['professor_nome']); ?></p>
                    <p class="alunos">👥 <?php echo htmlspecialchars($curso['total_alunos'] ?? 0, ENT_QUOTES, 'UTF-8'); ?> alunos matriculados</p>
                    <div class="curso-buttons">
                        <?php
                        $estaMatriculado = false;
                        $usuario = usuario_atual();
                        $isOwner = is_course_owner($curso);

                        if (!$isOwner && $usuario) {
                            $stmt = $GLOBALS['pdo']->prepare('SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?');
                            $stmt->execute([$usuario['id'], $curso['id']]);
                            $estaMatriculado = $stmt->fetch() ? true : false;
                        }
                        ?>

                        <?php if (!$usuario): ?>
                            <a href="?page=login" class="btn btn-primary btn-lg">Fazer Login para Matricular</a>
                        <?php elseif ($isOwner): ?>
                            <a href="?page=editar-curso&id=<?php echo htmlspecialchars($curso['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Editar Curso</a>
                            <a href="?page=criar-aula&course_id=<?php echo htmlspecialchars($curso['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Adicionar Aula</a>
                            <a href="?page=alunos-curso&course_id=<?php echo htmlspecialchars($curso['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Gerenciar Alunos</a>
                        <?php elseif ($estaMatriculado): ?>
                            <?php
                            $next_lesson_id = null;
                            foreach (($curso['modulos'] ?? []) as $moduloItem) {
                                if (empty($moduloItem['unlocked'])) {
                                    continue;
                                }
                                foreach (($moduloItem['lessons'] ?? []) as $aulaItem) {
                                    if (empty($aulaItem['is_completed'])) {
                                        $next_lesson_id = $aulaItem['id'];
                                        break 2;
                                    }
                                }
                            }
                            if ($next_lesson_id === null && !empty($curso['aulas'][0]['id'])) {
                                $next_lesson_id = $curso['aulas'][0]['id'];
                            }
                            ?>
                            <?php if ($next_lesson_id): ?>
                                <a href="?page=aula&lesson_id=<?php echo htmlspecialchars($next_lesson_id, ENT_QUOTES, 'UTF-8'); ?>&course_id=<?php echo htmlspecialchars($curso['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success btn-lg">Ir para o Curso</a>
                            <?php else: ?>
                                <a href="?page=curso&id=<?php echo htmlspecialchars($curso['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success btn-lg">Ver Aulas</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="acao" value="matricular_curso">
                                <?php echo csrf_input(); ?>
                                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($curso['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="btn btn-primary btn-lg">Se Matricular Agora</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="curso-content">
                <div class="curso-main">
                    <section class="descricao-section">
                        <h2>Sobre o Curso</h2>
                        <?php
                        $courseDescription = trim((string)($curso['descricao'] ?? ''));
                        $hasLongCourseDescription = mb_strlen($courseDescription, 'UTF-8') > 260;
                        ?>
                        <div class="expandable-copy<?php echo $hasLongCourseDescription ? ' is-collapsed' : ''; ?>" data-expandable-copy>
                            <div class="expandable-copy__content" data-expandable-content>
                                <?php echo $renderFormattedDescription($courseDescription); ?>
                            </div>
                            <?php if ($hasLongCourseDescription): ?>
                                <button type="button" class="expandable-copy__toggle" data-expandable-trigger aria-expanded="false">Expandir descrição</button>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="aulas-section">
                        <h2>Estrutura do Curso</h2>
                        <div class="aulas-list aulas-list--modules">
                            <?php if (!empty($curso['modulos'])): ?>
                                <?php foreach ($curso['modulos'] as $moduleIndex => $modulo): ?>
                                    <?php
                                    $moduleDescription = trim((string)($modulo['descricao'] ?? ''));
                                    $hasLongModuleDescription = mb_strlen($moduleDescription, 'UTF-8') > 220;
                                    $moduleLessons = $modulo['lessons'] ?? [];
                                    $moduleQuizzes = $modulo['module_quizzes'] ?? [];
                                    $moduleItemCount = count($moduleLessons) + count($moduleQuizzes);
                                    $moduleLessonCount = (int)($modulo['total_lessons'] ?? count($moduleLessons));
                                    $moduleCompletedLessons = (int)($modulo['completed_lessons'] ?? 0);
                                    $moduleProgressPercent = (int)($modulo['progress_percent'] ?? 0);
                                    $modulePanelId = 'module-panel-' . (int)($modulo['id'] ?? ($moduleIndex + 1));
                                    $moduleCertificateState = $moduleCertificateStates[(int)($modulo['id'] ?? 0)] ?? [];
                                    $moduleCertificate = $courseCertificates['modules'][(int)($modulo['id'] ?? 0)] ?? null;
                                    ?>
                                    <section class="module-block is-collapsed <?php echo !empty($modulo['unlocked']) ? '' : 'is-locked'; ?>" data-module-collapsible>
                                        <header class="module-block__header">
                                            <div class="module-block__summary">
                                                <span class="module-block__status-dot" aria-hidden="true"></span>
                                                <div class="module-block__summary-copy">
                                                    <h4><?php echo htmlspecialchars((string)($modulo['titulo'] ?? 'Módulo'), ENT_QUOTES, 'UTF-8'); ?></h4>
                                                    <p class="module-block__count">
                                                        <?php echo htmlspecialchars((string)$moduleLessonCount, ENT_QUOTES, 'UTF-8'); ?> aula<?php echo $moduleLessonCount === 1 ? '' : 's'; ?>
                                                        <?php if (count($moduleQuizzes) > 0): ?>
                                                            · <?php echo htmlspecialchars((string)count($moduleQuizzes), ENT_QUOTES, 'UTF-8'); ?> quiz<?php echo count($moduleQuizzes) === 1 ? '' : 'zes'; ?>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="module-block__meta">
                                                <?php if ($moduleItemCount > 0): ?>
                                                    <button
                                                        type="button"
                                                        class="module-block__toggle"
                                                        data-module-toggle
                                                        aria-expanded="false"
                                                        aria-controls="<?php echo htmlspecialchars($modulePanelId, ENT_QUOTES, 'UTF-8'); ?>"
                                                    >
                                                        <span class="module-block__toggle-icon" aria-hidden="true"></span>
                                                        <span class="module-block__toggle-label">Expandir</span>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </header>

                                        <?php if ($moduleDescription !== ''): ?>
                                            <div class="module-block__description">
                                                <div class="expandable-copy<?php echo $hasLongModuleDescription ? ' is-collapsed' : ''; ?>" data-expandable-copy>
                                                    <div class="expandable-copy__content" data-expandable-content>
                                                        <?php echo $renderFormattedDescription($moduleDescription); ?>
                                                    </div>
                                                    <?php if ($hasLongModuleDescription): ?>
                                                        <button type="button" class="expandable-copy__toggle" data-expandable-trigger aria-expanded="false">Expandir descrição</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="module-block__body" id="<?php echo htmlspecialchars($modulePanelId, ENT_QUOTES, 'UTF-8'); ?>" data-module-panel>
                                            <div class="module-block__content-header">
                                                <div>
                                                    <strong>Conteúdo do Módulo</strong>
                                                </div>
                                                <div class="module-block__content-meta">
                                                    <span>
                                                        <?php echo htmlspecialchars((string)$moduleProgressPercent, ENT_QUOTES, 'UTF-8'); ?>% completo
                                                        <?php if ($moduleLessonCount > 0): ?>
                                                            · <?php echo htmlspecialchars((string)$moduleCompletedLessons, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars((string)$moduleLessonCount, ENT_QUOTES, 'UTF-8'); ?> aulas
                                                        <?php endif; ?>
                                                    </span>
                                                    <?php if (!empty($moduleCertificateState['eligible']) && !empty($moduleCertificate) && ($estaMatriculado || $isOwner)): ?>
                                                        <div class="certificate-inline-actions">
                                                            <a href="?page=certificado&course_id=<?php echo htmlspecialchars((string)($curso['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>&type=module&module_id=<?php echo htmlspecialchars((string)($modulo['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light">Ver certificado</a>
                                                            <a href="?page=certificado&course_id=<?php echo htmlspecialchars((string)($curso['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>&type=module&module_id=<?php echo htmlspecialchars((string)($modulo['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>&download=pdf" class="btn btn-outline-light">Baixar PDF</a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if ($moduleItemCount === 0): ?>
                                                <p class="module-block__empty">Nenhuma aula disponível neste módulo ainda.</p>
                                            <?php endif; ?>

                                            <?php foreach ($moduleLessons as $lesson): ?>
                                                <div class="aula-item">
                                                    <div class="aula-info">
                                                        <h4><?php echo htmlspecialchars((string)($lesson['titulo'] ?? 'Aula'), ENT_QUOTES, 'UTF-8'); ?></h4>
                                                        <span class="aula-tipo"><?php echo htmlspecialchars(strtoupper((string)($lesson['tipo'] ?? 'AULA')), ENT_QUOTES, 'UTF-8'); ?></span>
                                                    </div>
                                                    <div class="aula-item-actions">
                                                        <?php if (($estaMatriculado || is_course_owner($curso)) && !empty($modulo['unlocked'])): ?>
                                                            <a href="?page=aula&lesson_id=<?php echo htmlspecialchars((string)($lesson['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>&course_id=<?php echo htmlspecialchars((string)($curso['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Acessar</a>
                                                        <?php else: ?>
                                                            <span class="locked">🔒 Bloqueado</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>

                                            <?php foreach ($moduleQuizzes as $quizModulo): ?>
                                                <div class="aula-item aula-item--quiz">
                                                    <div class="aula-info">
                                                        <h4><?php echo htmlspecialchars((string)($quizModulo['titulo'] ?? 'Quiz do módulo'), ENT_QUOTES, 'UTF-8'); ?></h4>
                                                        <span class="aula-tipo">QUIZ DO MÓDULO</span>
                                                    </div>
                                                    <div class="aula-item-actions">
                                                        <?php if (($estaMatriculado || is_course_owner($curso)) && (!empty($modulo['quiz_unlocked']) || is_course_owner($curso))): ?>
                                                            <a href="?page=quiz&quiz_id=<?php echo htmlspecialchars((string)($quizModulo['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Abrir quiz</a>
                                                        <?php else: ?>
                                                            <span class="locked">🔒 Conclua as aulas do módulo</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                <?php endforeach; ?>

                                <?php
                                $allModulesCompleted = !empty($curso['modulos']);
                                foreach (($curso['modulos'] ?? []) as $moduleState) {
                                    if (empty($moduleState['completed'])) {
                                        $allModulesCompleted = false;
                                        break;
                                    }
                                }
                                ?>
                                <?php if (!empty($curso['quizzes_finais'])): ?>
                                    <section class="module-block module-block--final">
                                        <header class="module-block__header">
                                            <div>
                                                <span class="aula-numero">F</span>
                                                <h4>Quiz final do curso</h4>
                                                <p>Etapa obrigatória para concluir a trilha e liberar certificação.</p>
                                            </div>
                                        </header>
                                        <div class="module-block__body">
                                            <?php if (!empty($courseCertificateState['eligible']) && !empty($courseCertificates['course']) && ($estaMatriculado || $isOwner)): ?>
                                                <div class="certificate-course-banner">
                                                    <div>
                                                        <strong>Certificado final disponível</strong>
                                                        <span>Sua aprovação final já foi validada pelo sistema.</span>
                                                    </div>
                                                    <div class="certificate-inline-actions">
                                                        <a href="?page=certificado&course_id=<?php echo htmlspecialchars((string)($curso['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Ver certificado</a>
                                                        <a href="?page=certificado&course_id=<?php echo htmlspecialchars((string)($curso['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>&download=pdf" class="btn btn-outline-secondary">Baixar PDF</a>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <?php foreach ($curso['quizzes_finais'] as $quizFinal): ?>
                                                <div class="aula-item aula-item--quiz">
                                                    <div class="aula-info">
                                                        <h4><?php echo htmlspecialchars((string)($quizFinal['titulo'] ?? 'Quiz final'), ENT_QUOTES, 'UTF-8'); ?></h4>
                                                        <span class="aula-tipo">FINAL DO CURSO</span>
                                                    </div>
                                                    <div class="aula-item-actions">
                                                        <?php if (($estaMatriculado && $allModulesCompleted) || is_course_owner($curso)): ?>
                                                            <a href="?page=quiz&quiz_id=<?php echo htmlspecialchars((string)($quizFinal['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Abrir quiz</a>
                                                        <?php else: ?>
                                                            <span class="locked">🔒 Conclua os módulos</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="no-aulas">Nenhuma aula disponível ainda.</p>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <aside class="curso-sidebar">
                    <div class="info-card">
                        <h3>Informações do Curso</h3>
                        <ul>
                            <li>
                                <strong>Categoria:</strong>
                                <span><?php echo htmlspecialchars($curso['categoria'] ?? 'Não especificada'); ?></span>
                            </li>
                            <li>
                                <strong>Aulas:</strong>
                                <span><?php echo count($curso['aulas'] ?? []); ?></span>
                            </li>
                            <li>
                                <strong>Módulos:</strong>
                                <span><?php echo count($curso['modulos'] ?? []); ?></span>
                            </li>
                            <li>
                                <strong>Alunos:</strong>
                                <span><?php echo htmlspecialchars($curso['total_alunos'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                            <li>
                                <strong>Status:</strong>
                                <span class="badge badge-<?php echo ($curso['status'] === 'ativo' ? 'success' : 'warning'); ?>">
                                    <?php echo ucfirst($curso['status']); ?>
                                </span>
                            </li>
                        </ul>
                    </div>

                    <?php if ($estaMatriculado): ?>
                        <?php $meu_width = intval($meu_progresso ?? 0); ?>
                        <div class="progresso-card">
                            <h3>Seu Progresso</h3>
                            <div class="progresso-bar" role="progressbar" aria-label="Progresso do curso" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo htmlspecialchars($meu_width, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="progresso-fill" data-progress="<?php echo htmlspecialchars($meu_width, ENT_QUOTES, 'UTF-8'); ?>"></div>
                            </div>
                            <p class="progresso-text"><?php echo $meu_progresso ?? 0; ?>% completo</p>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>
        <?php else: ?>
            <div class="alert alert-error">
                Curso não encontrado.
            </div>
        <?php endif; ?>
    </div>
</section>
