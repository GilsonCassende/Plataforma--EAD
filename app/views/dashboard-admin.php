<?php

/**
 * View: Dashboard do Administrador
 */

$adminName = $_SESSION['usuario']['nome'] ?? 'Administrador';
$adminId = (int)($_SESSION['usuario']['id'] ?? 0);
$stats = $stats ?? [];
$usuarios = $usuarios ?? [];
$cursos = $cursos ?? [];
$csrf = function_exists('gerar_csrf') ? gerar_csrf() : '';
$alunos = array_values(array_filter($usuarios, static function ($usuario) {
    return (string)($usuario['role'] ?? '') === 'aluno';
}));
$professores = array_values(array_filter($usuarios, static function ($usuario) {
    return (string)($usuario['role'] ?? '') === 'professor';
}));
?>

<section class="admin-shell">
    <div class="container">
        <div class="admin-layout">
            <aside class="admin-sidebar" aria-label="Navegação administrativa">
                <div class="admin-sidebar__brand">
                    <span class="admin-sidebar__eyebrow">Admin Console</span>
                    <strong>Painel Central</strong>
                    <p>Operação, gestão e visão estratégica da plataforma em um único fluxo.</p>
                </div>

                <nav class="admin-nav">
                    <a href="<?php echo BASE_URL; ?>/index.php?page=dashboard" class="admin-nav__item is-active">
                        <span class="admin-nav__icon">◫</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=admin-cursos" class="admin-nav__item">
                        <span class="admin-nav__icon">▣</span>
                        <span>Cursos</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=admin-quizzes" class="admin-nav__item">
                        <span class="admin-nav__icon">◌</span>
                        <span>Quizzes</span>
                    </a>
                    <a href="#admin-users" class="admin-nav__item">
                        <span class="admin-nav__icon">◉</span>
                        <span>Alunos</span>
                    </a>
                    <a href="#admin-teachers" class="admin-nav__item">
                        <span class="admin-nav__icon">◎</span>
                        <span>Professores</span>
                    </a>
                    <a href="#admin-settings" class="admin-nav__item">
                        <span class="admin-nav__icon">⚙</span>
                        <span>Configurações</span>
                    </a>
                </nav>

                <div class="admin-sidebar__meta">
                    <span class="admin-sidebar__pill">Usuários: <?php echo htmlspecialchars($stats['total_usuarios'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="admin-sidebar__pill">Cursos ativos: <?php echo htmlspecialchars($stats['total_cursos_ativos'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </aside>

            <div class="admin-main">
                <header class="admin-topbar" id="admin-overview">
                    <div class="admin-topbar__copy">
                        <span class="admin-topbar__eyebrow">Painel administrativo</span>
                        <h1>Gestão executiva da plataforma</h1>
                        <p>Monitore crescimento, organize operações e revise usuários e cursos com uma interface mais clara e eficiente.</p>
                    </div>

                    <div class="admin-topbar__actions">
                        <div class="admin-topbar__quickstats">
                            <span class="admin-topbar__chip">Admin: <?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="admin-topbar__chip">Cursos totais: <?php echo htmlspecialchars($stats['total_cursos'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="admin-topbar__buttons">
                            <a href="<?php echo BASE_URL; ?>/index.php?page=admin-cursos" class="btn btn-primary">Gerenciar Cursos</a>
                            <a href="#admin-users" class="btn btn-secondary">Ver Usuários</a>
                        </div>
                    </div>
                </header>

                <section class="admin-metrics">
                    <article class="admin-metric-card">
                        <span class="admin-metric-card__label">Total de usuários</span>
                        <strong class="admin-metric-card__value"><?php echo htmlspecialchars($stats['total_usuarios'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="admin-metric-card__note">Base total de contas na plataforma.</p>
                    </article>

                    <article class="admin-metric-card">
                        <span class="admin-metric-card__label">Alunos ativos</span>
                        <strong class="admin-metric-card__value"><?php echo htmlspecialchars($stats['total_alunos'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="admin-metric-card__note">Contas com perfil de aprendizagem.</p>
                    </article>

                    <article class="admin-metric-card">
                        <span class="admin-metric-card__label">Professores</span>
                        <strong class="admin-metric-card__value"><?php echo htmlspecialchars($stats['total_professores'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="admin-metric-card__note">Instrutores responsáveis pelo catálogo.</p>
                    </article>

                    <article class="admin-metric-card">
                        <span class="admin-metric-card__label">Cursos ativos</span>
                        <strong class="admin-metric-card__value"><?php echo htmlspecialchars($stats['total_cursos_ativos'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="admin-metric-card__note">Oferta disponível para matrícula agora.</p>
                    </article>

                    <article class="admin-metric-card">
                        <span class="admin-metric-card__label">Quizzes</span>
                        <strong class="admin-metric-card__value"><?php echo htmlspecialchars($stats['total_quizzes'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="admin-metric-card__note">Avaliações publicadas em toda a plataforma.</p>
                    </article>
                </section>

                <section class="admin-panel admin-panel--summary admin-panel--compact" id="admin-settings">
                    <div class="admin-panel__header">
                        <div>
                            <span class="admin-panel__eyebrow">Resumo executivo</span>
                            <h2>Indicadores operacionais</h2>
                            <p>Leitura rápida dos principais sinais do sistema, com menos ruído e melhor densidade visual.</p>
                        </div>
                    </div>

                    <div class="admin-summary-grid">
                        <div class="admin-summary-item">
                            <span>Usuários por professor</span>
                            <strong>
                                <?php
                                $teachers = max(1, (int)($stats['total_professores'] ?? 1));
                                echo htmlspecialchars((string)ceil(((int)($stats['total_usuarios'] ?? 0)) / $teachers), ENT_QUOTES, 'UTF-8');
                                ?>
                            </strong>
                        </div>
                        <div class="admin-summary-item">
                            <span>Cursos por professor</span>
                            <strong>
                                <?php
                                echo htmlspecialchars((string)ceil(((int)($stats['total_cursos'] ?? 0)) / $teachers), ENT_QUOTES, 'UTF-8');
                                ?>
                            </strong>
                        </div>
                        <div class="admin-summary-item">
                            <span>Taxa de catálogo ativo</span>
                            <strong>
                                <?php
                                $totalCourses = max(1, (int)($stats['total_cursos'] ?? 1));
                                echo htmlspecialchars((string)round((((int)($stats['total_cursos_ativos'] ?? 0)) / $totalCourses) * 100), ENT_QUOTES, 'UTF-8');
                                ?>%
                            </strong>
                        </div>
                    </div>
                </section>

                <section class="admin-panel admin-panel--table" id="admin-users">
                    <div class="admin-panel__header">
                        <div>
                            <span class="admin-panel__eyebrow">Alunos</span>
                            <h2>Base de alunos</h2>
                            <p>Lista restrita aos utilizadores com perfil de aluno.</p>
                        </div>
                    </div>

                    <?php if (!empty($alunos)): ?>
                        <div class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Email</th>
                                        <th>Função</th>
                                        <th>Cadastro</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alunos as $usuario): ?>
                                        <tr>
                                            <td>
                                                <div class="admin-entity">
                                                    <span class="admin-entity__avatar"><?php echo htmlspecialchars(strtoupper(substr($usuario['nome'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <div class="admin-entity__copy">
                                                        <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                                        <span>ID #<?php echo htmlspecialchars($usuario['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                            <td>
                                                <span class="admin-role-pill admin-role-pill--<?php echo htmlspecialchars($usuario['role'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo ucfirst($usuario['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?></td>
                                            <td>
                                                <div class="admin-table__actions admin-table__actions--compact">
                                                    <button type="button" class="btn btn-outline btn-sm admin-contact-link" data-copy-text="<?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?>">Copiar email</button>
                                                    <?php if ((int)($usuario['id'] ?? 0) !== $adminId): ?>
                                                        <form method="post" action="<?php echo BASE_URL; ?>/index.php" class="admin-inline-form">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="acao" value="admin_deletar_usuario">
                                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($usuario['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="admin-self-badge">Conta atual</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty-state">Nenhum aluno cadastrado até o momento.</div>
                    <?php endif; ?>
                </section>

                <section class="admin-panel admin-panel--table" id="admin-teachers">
                    <div class="admin-panel__header">
                        <div>
                            <span class="admin-panel__eyebrow">Professores</span>
                            <h2>Base de professores</h2>
                            <p>Lista restrita aos utilizadores com perfil de professor.</p>
                        </div>
                    </div>

                    <?php if (!empty($professores)): ?>
                        <div class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Email</th>
                                        <th>Função</th>
                                        <th>Cadastro</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($professores as $usuario): ?>
                                        <tr>
                                            <td>
                                                <div class="admin-entity">
                                                    <span class="admin-entity__avatar"><?php echo htmlspecialchars(strtoupper(substr($usuario['nome'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <div class="admin-entity__copy">
                                                        <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                                        <span>ID #<?php echo htmlspecialchars($usuario['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                            <td>
                                                <span class="admin-role-pill admin-role-pill--<?php echo htmlspecialchars($usuario['role'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo ucfirst($usuario['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?></td>
                                            <td>
                                                <div class="admin-table__actions admin-table__actions--compact">
                                                    <button type="button" class="btn btn-outline btn-sm admin-contact-link" data-copy-text="<?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?>">Copiar email</button>
                                                    <?php if ((int)($usuario['id'] ?? 0) !== $adminId): ?>
                                                        <form method="post" action="<?php echo BASE_URL; ?>/index.php" class="admin-inline-form">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="acao" value="admin_deletar_usuario">
                                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($usuario['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="admin-self-badge">Conta atual</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty-state">Nenhum professor cadastrado até o momento.</div>
                    <?php endif; ?>
                </section>

                <section class="admin-panel admin-panel--table" id="admin-courses">
                    <div class="admin-panel__header">
                        <div>
                            <span class="admin-panel__eyebrow">Cursos</span>
                            <h2>Catálogo administrativo</h2>
                            <p>Revise status, autoria e manutenção do catálogo em uma única tabela.</p>
                        </div>
                    </div>

                    <?php if (!empty($cursos)): ?>
                        <div class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Curso</th>
                                        <th>Professor</th>
                                        <th>Status</th>
                                        <th>Criação</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cursos as $curso): ?>
                                        <tr>
                                            <td>
                                                <div class="admin-course-cell">
                                                    <strong><?php echo htmlspecialchars(substr($curso['titulo'], 0, 48)); ?></strong>
                                                    <span><?php echo htmlspecialchars($curso['categoria'] ?? 'Sem categoria', ENT_QUOTES, 'UTF-8'); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($curso['professor_nome']); ?></td>
                                            <td>
                                                <?php $statusClass = ($curso['status'] ?? '') === 'ativo' ? 'active' : ((($curso['status'] ?? '') === 'rascunho') ? 'draft' : 'inactive'); ?>
                                                <span class="admin-status-pill admin-status-pill--<?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo ucfirst($curso['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($curso['created_at'])); ?></td>
                                            <td>
                                                <div class="admin-actions-stack">
                                                    <div class="admin-table__actions admin-table__actions--compact">
                                                        <a href="<?php echo BASE_URL; ?>/index.php?page=editar-curso&id=<?php echo htmlspecialchars($curso['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary btn-sm">Editar</a>
                                                        <form method="post" action="<?php echo BASE_URL; ?>/index.php" class="admin-inline-form">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="acao" value="admin_alterar_status_curso">
                                                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($curso['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="status" value="<?php echo ($curso['status'] ?? '') === 'ativo' ? 'inativo' : 'ativo'; ?>">
                                                            <button type="submit" class="btn btn-outline btn-sm">
                                                                <?php echo ($curso['status'] ?? '') === 'ativo' ? 'Pausar' : 'Ativar'; ?>
                                                            </button>
                                                        </form>
                                                        <form method="post" action="<?php echo BASE_URL; ?>/index.php" class="admin-inline-form">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="acao" value="deletar_curso">
                                                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($curso['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
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
                    <?php else: ?>
                        <div class="admin-empty-state">Nenhum curso cadastrado no sistema.</div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</section>
