<?php

$adminName = $_SESSION['usuario']['nome'] ?? 'Administrador';
$csrf = function_exists('gerar_csrf') ? gerar_csrf() : '';
$cursos = $cursos ?? [];
$busca = $busca ?? '';
$status = $status ?? '';
$pagina = (int)($pagina ?? 1);
$total_paginas = (int)($total_paginas ?? 1);
$total = (int)($total ?? 0);
$stats = $stats ?? [];
?>

<section class="admin-shell">
    <div class="container">
        <div class="admin-layout">
            <aside class="admin-sidebar" aria-label="Navegação administrativa">
                <div class="admin-sidebar__brand">
                    <span class="admin-sidebar__eyebrow">Admin Console</span>
                    <strong>Painel Central</strong>
                    <p>Gerencie catálogo, usuários e operação sem misturar interface pública com fluxo administrativo.</p>
                </div>

                <nav class="admin-nav">
                    <a href="<?php echo BASE_URL; ?>/index.php?page=dashboard" class="admin-nav__item">
                        <span class="admin-nav__icon">◫</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=admin-cursos" class="admin-nav__item is-active">
                        <span class="admin-nav__icon">▣</span>
                        <span>Cursos</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=admin-quizzes" class="admin-nav__item">
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
                    <span class="admin-sidebar__pill">Cursos totais: <?php echo htmlspecialchars($stats['total_cursos'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="admin-sidebar__pill">Ativos: <?php echo htmlspecialchars($stats['total_cursos_ativos'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </aside>

            <div class="admin-main">
                <header class="admin-topbar">
                    <div class="admin-topbar__copy">
                        <span class="admin-topbar__eyebrow">Catálogo administrativo</span>
                        <h1>Controle administrativo dos cursos</h1>
                        <p>Revise status, autoria e manutenção do catálogo em um fluxo próprio do admin, sem ações de aluno ou professor.</p>
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
                        <span class="admin-metric-card__label">Catálogo total</span>
                        <strong class="admin-metric-card__value"><?php echo htmlspecialchars($stats['total_cursos'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="admin-metric-card__note">Todos os cursos cadastrados no sistema.</p>
                    </article>

                    <article class="admin-metric-card">
                        <span class="admin-metric-card__label">Cursos ativos</span>
                        <strong class="admin-metric-card__value"><?php echo htmlspecialchars($stats['total_cursos_ativos'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="admin-metric-card__note">Cursos disponíveis para matrícula.</p>
                    </article>

                    <article class="admin-metric-card">
                        <span class="admin-metric-card__label">Resultados filtrados</span>
                        <strong class="admin-metric-card__value"><?php echo htmlspecialchars($total, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="admin-metric-card__note">Itens retornados pela busca atual.</p>
                    </article>

                    <article class="admin-metric-card">
                        <span class="admin-metric-card__label">Cursos não ativos</span>
                        <strong class="admin-metric-card__value"><?php echo htmlspecialchars(max(0, (int)($stats['total_cursos'] ?? 0) - (int)($stats['total_cursos_ativos'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="admin-metric-card__note">Itens em pausa ou rascunho para revisão.</p>
                    </article>
                </section>

                <section class="admin-panel admin-panel--table">
                    <div class="admin-panel__header">
                        <div>
                            <span class="admin-panel__eyebrow">Filtros</span>
                            <h2>Busca e status</h2>
                            <p>Encontre rapidamente um curso e ajuste o status diretamente pela tabela administrativa.</p>
                        </div>
                    </div>

                    <form method="get" action="<?php echo BASE_URL; ?>/index.php" class="admin-toolbar">
                        <input type="hidden" name="page" value="admin-cursos">
                        <div class="admin-toolbar__field">
                            <label for="admin-course-search">Buscar curso</label>
                            <input id="admin-course-search" type="search" name="busca" value="<?php echo htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Título, descrição, categoria ou professor">
                        </div>

                        <div class="admin-toolbar__field">
                            <label for="admin-course-status">Status</label>
                            <select id="admin-course-status" name="status">
                                <option value="">Todos</option>
                                <option value="ativo" <?php echo $status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                <option value="inativo" <?php echo $status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                <option value="rascunho" <?php echo $status === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                            </select>
                        </div>

                        <div class="admin-toolbar__actions">
                            <button type="submit" class="btn btn-primary">Aplicar filtros</button>
                            <a href="<?php echo BASE_URL; ?>/index.php?page=admin-cursos" class="btn btn-outline">Limpar</a>
                        </div>
                    </form>
                </section>

                <section class="admin-panel admin-panel--table">
                    <div class="admin-panel__header">
                        <div>
                            <span class="admin-panel__eyebrow">Tabela administrativa</span>
                            <h2>Cursos cadastrados</h2>
                            <p>Edite, altere status e exclua cursos sem exibir botões de catálogo público.</p>
                        </div>
                    </div>

                    <?php if (!empty($cursos)): ?>
                        <div class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Curso</th>
                                        <th>Professor</th>
                                        <th>Alunos</th>
                                        <th>Status</th>
                                        <th>Criação</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cursos as $curso): ?>
                                        <?php $statusClass = ($curso['status'] ?? '') === 'ativo' ? 'active' : ((($curso['status'] ?? '') === 'rascunho') ? 'draft' : 'inactive'); ?>
                                        <tr>
                                            <td>
                                                <div class="admin-course-cell">
                                                    <strong><?php echo htmlspecialchars($curso['titulo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                                                    <span><?php echo htmlspecialchars($curso['categoria'] ?? 'Sem categoria', ENT_QUOTES, 'UTF-8'); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($curso['professor_nome'] ?? 'Sem professor', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($curso['total_alunos'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="admin-status-pill admin-status-pill--<?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($curso['status'] ?? 'inativo'), ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo !empty($curso['created_at']) ? date('d/m/Y', strtotime($curso['created_at'])) : '-'; ?></td>
                                            <td>
                                                <div class="admin-actions-stack">
                                                    <div class="admin-table__actions admin-table__actions--compact">
                                                        <a href="<?php echo BASE_URL; ?>/index.php?page=editar-curso&id=<?php echo htmlspecialchars($curso['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary btn-sm">Editar</a>

                                                        <form method="post" action="<?php echo BASE_URL; ?>/index.php" class="admin-inline-form">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="acao" value="deletar_curso">
                                                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($curso['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                                                        </form>
                                                    </div>

                                                    <form method="post" action="<?php echo BASE_URL; ?>/index.php" class="admin-inline-form admin-status-form">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <input type="hidden" name="acao" value="admin_alterar_status_curso">
                                                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($curso['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                        <select name="status" class="admin-select">
                                                            <option value="ativo" <?php echo ($curso['status'] ?? '') === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                                            <option value="inativo" <?php echo ($curso['status'] ?? '') === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                                            <option value="rascunho" <?php echo ($curso['status'] ?? '') === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                                                        </select>
                                                        <button type="submit" class="btn btn-outline btn-sm">Salvar status</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_paginas > 1): ?>
                            <nav class="admin-pagination" aria-label="Paginação de cursos administrativos">
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <a
                                        href="<?php echo BASE_URL; ?>/index.php?page=admin-cursos&p=<?php echo $i; ?>&busca=<?php echo urlencode($busca); ?>&status=<?php echo urlencode($status); ?>"
                                        class="admin-pagination__item <?php echo $i === $pagina ? 'is-active' : ''; ?>"
                                    >
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="admin-empty-state">Nenhum curso encontrado com os filtros atuais.</div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</section>
