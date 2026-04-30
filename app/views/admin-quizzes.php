<?php

$adminName = $_SESSION['usuario']['nome'] ?? 'Administrador';
$csrf = function_exists('gerar_csrf') ? gerar_csrf() : '';
$quizzes = $quizzes ?? [];
$busca = $busca ?? '';
$tipo = $tipo ?? '';
$pagina = (int)($pagina ?? 1);
$total_paginas = (int)($total_paginas ?? 1);
$total = (int)($total ?? 0);
$stats = $stats ?? [];
$quizStats = $quiz_stats ?? [];
?>

<section class="admin-shell">
    <div class="container">
        <div class="admin-layout">
            <aside class="admin-sidebar" aria-label="Navegação administrativa">
                <div class="admin-sidebar__brand">
                    <span class="admin-sidebar__eyebrow">Admin Console</span>
                    <strong>Painel Central</strong>
                    <p>Audite avaliações, tentativas e qualidade pedagógica em um fluxo administrativo próprio.</p>
                </div>

                <nav class="admin-nav">
                    <a href="<?php echo BASE_URL; ?>/index.php?page=dashboard" class="admin-nav__item">
                        <span class="admin-nav__icon">◫</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=admin-cursos" class="admin-nav__item">
                        <span class="admin-nav__icon">▣</span>
                        <span>Cursos</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=admin-quizzes" class="admin-nav__item is-active">
                        <span class="admin-nav__icon">◌</span>
                        <span>Quizzes</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=dashboard#admin-users" class="admin-nav__item">
                        <span class="admin-nav__icon">◉</span>
                        <span>Alunos</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=dashboard#admin-teachers" class="admin-nav__item">
                        <span class="admin-nav__icon">◎</span>
                        <span>Professores</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=dashboard#admin-settings" class="admin-nav__item">
                        <span class="admin-nav__icon">⚙</span>
                        <span>Configurações</span>
                    </a>
                </nav>

                <div class="admin-sidebar__meta">
                    <span class="admin-sidebar__pill">Quizzes: <?php echo htmlspecialchars($quizStats['total_quizzes'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="admin-sidebar__pill">Tentativas: <?php echo htmlspecialchars($quizStats['total_tentativas'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </aside>

            <div class="admin-main">
                <header class="admin-topbar">
                    <div class="admin-topbar__copy">
                        <span class="admin-topbar__eyebrow">Auditoria de avaliações</span>
                        <h1>Governança administrativa dos quizzes</h1>
                        <p>Revise avaliações do catálogo, acompanhe tentativas, identifique provas finais e mantenha a operação acadêmica consistente.</p>
                    </div>

                    <div class="admin-topbar__actions">
                        <div class="admin-topbar__quickstats">
                            <span class="admin-topbar__chip">Admin: <?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="admin-topbar__chip">Resultados: <?php echo htmlspecialchars($total, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="admin-topbar__buttons">
                            <a href="<?php echo BASE_URL; ?>/index.php?page=dashboard" class="btn btn-secondary">Voltar ao painel</a>
                        </div>
                    </div>
                </header>

                <section class="admin-metrics">
                    <article class="admin-metric-card">
                        <span class="admin-metric-card__label">Quizzes totais</span>
                        <strong class="admin-metric-card__value"><?php echo htmlspecialchars($quizStats['total_quizzes'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="admin-metric-card__note">Avaliações cadastradas em todo o catálogo.</p>
                    </article>

                    <article class="admin-metric-card">
                        <span class="admin-metric-card__label">Provas finais</span>
                        <strong class="admin-metric-card__value"><?php echo htmlspecialchars($quizStats['quizzes_finais'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="admin-metric-card__note">Avaliações finais com maior peso acadêmico.</p>
                    </article>

                    <article class="admin-metric-card">
                        <span class="admin-metric-card__label">Tentativas totais</span>
                        <strong class="admin-metric-card__value"><?php echo htmlspecialchars($quizStats['total_tentativas'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="admin-metric-card__note">Envios realizados pelos alunos.</p>
                    </article>

                    <article class="admin-metric-card">
                        <span class="admin-metric-card__label">Média geral</span>
                        <strong class="admin-metric-card__value"><?php echo htmlspecialchars(number_format((float)($quizStats['media_geral'] ?? 0), 1), ENT_QUOTES, 'UTF-8'); ?>%</strong>
                        <p class="admin-metric-card__note">Aproveitamento médio global dos quizzes.</p>
                    </article>
                </section>

                <section class="admin-panel admin-panel--table">
                    <div class="admin-panel__header">
                        <div>
                            <span class="admin-panel__eyebrow">Filtros</span>
                            <h2>Busca e tipo de avaliação</h2>
                            <p>Encontre quizzes por título, curso, aula ou professor e filtre por categoria pedagógica.</p>
                        </div>
                    </div>

                    <form method="get" action="<?php echo BASE_URL; ?>/index.php" class="admin-toolbar">
                        <input type="hidden" name="page" value="admin-quizzes">
                        <div class="admin-toolbar__field">
                            <label for="admin-quiz-search">Buscar quiz</label>
                            <input id="admin-quiz-search" type="search" name="busca" value="<?php echo htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Título, curso, aula ou professor">
                        </div>

                        <div class="admin-toolbar__field">
                            <label for="admin-quiz-type">Tipo</label>
                            <select id="admin-quiz-type" name="tipo">
                                <option value="">Todos</option>
                                <option value="aula" <?php echo $tipo === 'aula' ? 'selected' : ''; ?>>Aula</option>
                                <option value="modulo" <?php echo $tipo === 'modulo' ? 'selected' : ''; ?>>Módulo</option>
                                <option value="final" <?php echo $tipo === 'final' ? 'selected' : ''; ?>>Final</option>
                            </select>
                        </div>

                        <div class="admin-toolbar__actions">
                            <button type="submit" class="btn btn-primary">Aplicar filtros</button>
                            <a href="<?php echo BASE_URL; ?>/index.php?page=admin-quizzes" class="btn btn-outline">Limpar</a>
                        </div>
                    </form>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <span class="admin-panel__eyebrow">Qualidade pedagógica</span>
                            <h2>Perguntas com maior incidência de erro</h2>
                            <p>Use este bloco para localizar lacunas de conteúdo ou questões mal calibradas.</p>
                        </div>
                    </div>

                    <?php if (!empty($quizStats['perguntas_criticas'])): ?>
                        <div class="admin-audit-list">
                            <?php foreach ($quizStats['perguntas_criticas'] as $pergunta): ?>
                                <article class="admin-audit-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($pergunta['course_title'] ?? 'Curso sem título', ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <p><?php echo htmlspecialchars(mb_strimwidth($pergunta['texto'] ?? '', 0, 130, '...'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                    <div class="admin-audit-item__meta">
                                        <span><?php echo htmlspecialchars($pergunta['erros'] ?? 0, ENT_QUOTES, 'UTF-8'); ?> erros</span>
                                        <small><?php echo htmlspecialchars($pergunta['total_respostas'] ?? 0, ENT_QUOTES, 'UTF-8'); ?> respostas</small>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty-state">As perguntas críticas aparecerão aqui quando houver tentativas suficientes para análise.</div>
                    <?php endif; ?>
                </section>

                <section class="admin-panel admin-panel--table">
                    <div class="admin-panel__header">
                        <div>
                            <span class="admin-panel__eyebrow">Tabela administrativa</span>
                            <h2>Quizzes cadastrados</h2>
                            <p>Audite quizzes, acompanhe desempenho e remova avaliações inconsistentes sem entrar no fluxo do professor.</p>
                        </div>
                    </div>

                    <?php if (!empty($quizzes)): ?>
                        <div class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Quiz</th>
                                        <th>Curso</th>
                                        <th>Professor</th>
                                        <th>Tipo</th>
                                        <th>Questões</th>
                                        <th>Tentativas</th>
                                        <th>Média</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quizzes as $quiz): ?>
                                        <tr>
                                            <td>
                                                <div class="admin-course-cell">
                                                    <strong><?php echo htmlspecialchars($quiz['titulo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($quiz['course_title'] ?? 'Curso removido', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($quiz['teacher_name'] ?? 'Sem professor', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="admin-status-pill admin-status-pill--<?php echo htmlspecialchars(($quiz['tipo'] ?? 'aula') === 'final' ? 'draft' : (($quiz['tipo'] ?? 'aula') === 'modulo' ? 'inactive' : 'active'), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($quiz['tipo'] ?? 'aula'), ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($quiz['total_questoes'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($quiz['total_tentativas'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars(number_format((float)($quiz['media_percentual'] ?? 0), 1), ENT_QUOTES, 'UTF-8'); ?>%</td>
                                            <td>
                                                <div class="admin-actions-stack">
                                                    <div class="admin-table__actions admin-table__actions--compact">
                                                        <a href="<?php echo BASE_URL; ?>/index.php?page=quiz&quiz_id=<?php echo htmlspecialchars($quiz['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary btn-sm">Abrir</a>
                                                        <form method="post" action="<?php echo BASE_URL; ?>/index.php" class="admin-inline-form">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="acao" value="admin_deletar_quiz">
                                                            <input type="hidden" name="quiz_id" value="<?php echo htmlspecialchars($quiz['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_paginas > 1): ?>
                            <nav class="admin-pagination" aria-label="Paginação de quizzes administrativos">
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <a
                                        href="<?php echo BASE_URL; ?>/index.php?page=admin-quizzes&p=<?php echo $i; ?>&busca=<?php echo urlencode($busca); ?>&tipo=<?php echo urlencode($tipo); ?>"
                                        class="admin-pagination__item <?php echo $i === $pagina ? 'is-active' : ''; ?>"
                                    >
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="admin-empty-state">Nenhum quiz encontrado com os filtros atuais.</div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</section>
