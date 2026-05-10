<?php

/**
 * View: Dashboard do Professor
 */
$coursePickerOptions = array_map(static function ($curso) {
    return [
        'id' => (int)($curso['id'] ?? 0),
        'titulo' => (string)($curso['titulo'] ?? 'Curso sem título'),
        'status' => (string)($curso['status'] ?? ''),
        'total_aulas' => (int)($curso['total_aulas'] ?? 0),
        'total_alunos' => (int)($curso['total_alunos'] ?? 0)
    ];
}, $cursos ?? []);
?>
<section
    class="dashboard container professor-dashboard"
    data-cursos="<?php echo (int)($total_cursos ?? count($cursos ?? [])); ?>"
    data-alunos="<?php echo (int)($total_alunos ?? 0); ?>"
    data-aulas="<?php echo (int)($total_aulas ?? 0); ?>"
    data-atividades="<?php echo (int)($total_atividades ?? 0); ?>"
    data-course-options="<?php echo htmlspecialchars(json_encode($coursePickerOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>">
    <header class="professor-panel-header">
        <div class="dashboard__hero">
            <div class="dashboard__hero-copy">
                <span class="dashboard__eyebrow">Painel do instrutor</span>
                <h1>Você está no comando da próxima turma, <?php echo htmlspecialchars($_SESSION['usuario']['nome']); ?>.</h1>
                <p class="welcome">Gerencie cursos, publique aulas e acompanhe a evolução dos seus alunos com mais clareza, ritmo e presença profissional.</p>
            </div>
            <div class="dashboard__hero-badges" aria-label="Resumo rápido do professor">
                <span class="dashboard__hero-badge">Cursos publicados: <?php echo (int)($total_cursos ?? count($cursos ?? [])); ?></span>
                <span class="dashboard__hero-badge">Alunos alcançados: <?php echo (int)($total_alunos ?? 0); ?></span>
                <span class="dashboard__hero-badge">Aulas disponíveis: <?php echo (int)($total_aulas ?? 0); ?></span>
            </div>
        </div>
    </header>

    <div class="stats-row">
        <a href="?page=meus-cursos" class="stat-card card" aria-label="Abrir Meus Cursos">
            <div class="stat-icon">🏫</div>
            <div class="stat-body">
                <div class="stat-number"><span id="counter-cursos">0</span></div>
                <div class="stat-label">Meus Cursos</div>
            </div>
        </a>

        <a href="?page=meus-alunos" class="stat-card card" aria-label="Abrir Alunos">
            <div class="stat-icon">👥</div>
            <div class="stat-body">
                <div class="stat-number"><span id="counter-alunos">0</span></div>
                <div class="stat-label">Total de Alunos</div>
            </div>
        </a>

        <a href="?page=minhas-aulas" class="stat-card card" aria-label="Abrir Aulas">
            <div class="stat-icon">🎬</div>
            <div class="stat-body">
                <div class="stat-number"><span id="counter-aulas">0</span></div>
                <div class="stat-label">Total de Aulas</div>
            </div>
        </a>

        <a
            href="?page=atividades"
            class="stat-card card"
            aria-label="Abrir Atividades"
            data-fragment="?page=atividades&partial=1"
            data-fragment-title="Atividades Recentes">
            <div class="stat-icon">📝</div>
            <div class="stat-body">
                <div class="stat-number"><span id="counter-atividades">0</span></div>
                <div class="stat-label">Atividades Recentes</div>
            </div>
        </a>
    </div>

    <div class="dashboard-grid">
        <main class="main-col">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3>Comparativo Rápido</h3>
                        <div class="card-title">Cursos vs Alunos vs Aulas vs Atividades</div>
                    </div>
                </div>
                <div class="chart-card">
                    <canvas id="cmpChart" aria-label="Comparativo Cursos vs Alunos vs Aulas vs Atividades" role="img"></canvas>
                </div>
            </div>

            <section class="card professor-evaluation-panel mt-20">
                <div class="card-header">
                    <div>
                        <h3>Desempenho das Avaliações</h3>
                        <div class="card-title">Acompanhe média, aprovação e pontos que precisam de reforço</div>
                    </div>
                </div>

                <div class="professor-evaluation-metrics">
                    <article class="professor-evaluation-metric">
                        <span class="professor-evaluation-metric__label">Média geral da turma</span>
                        <strong><?php echo htmlspecialchars(number_format((float)($media_quiz_geral ?? 0), 1), ENT_QUOTES, 'UTF-8'); ?>%</strong>
                    </article>
                    <article class="professor-evaluation-metric">
                        <span class="professor-evaluation-metric__label">Taxa de aprovação</span>
                        <strong><?php echo htmlspecialchars((string)(int)($taxa_aprovacao_quiz_geral ?? 0), ENT_QUOTES, 'UTF-8'); ?>%</strong>
                    </article>
                    <article class="professor-evaluation-metric">
                        <span class="professor-evaluation-metric__label">Quizzes publicados</span>
                        <strong><?php echo htmlspecialchars((string)(int)($total_quizzes ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </article>
                </div>

                <div class="professor-evaluation-grid">
                    <div class="professor-evaluation-block">
                        <h4>Cursos com avaliação ativa</h4>
                        <?php if (!empty($desempenho_cursos)): ?>
                            <div class="professor-evaluation-list">
                                <?php foreach ($desempenho_cursos as $cursoAvaliacao): ?>
                                    <article class="professor-evaluation-item">
                                        <div>
                                            <strong><?php echo htmlspecialchars($cursoAvaliacao['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <small><?php echo htmlspecialchars((string)$cursoAvaliacao['total_quizzes'], ENT_QUOTES, 'UTF-8'); ?> quizzes • <?php echo htmlspecialchars((string)$cursoAvaliacao['alunos_avaliados'], ENT_QUOTES, 'UTF-8'); ?> alunos avaliados</small>
                                        </div>
                                        <div class="professor-evaluation-item__meta">
                                            <span><?php echo htmlspecialchars(number_format((float)$cursoAvaliacao['media_quiz'], 1), ENT_QUOTES, 'UTF-8'); ?>%</span>
                                            <small><?php echo htmlspecialchars((string)$cursoAvaliacao['taxa_aprovacao_quiz'], ENT_QUOTES, 'UTF-8'); ?>% aprov.</small>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="muted">Publique quizzes para começar a acompanhar o desempenho da turma.</p>
                        <?php endif; ?>
                    </div>

                    <div class="professor-evaluation-block">
                        <h4>Perguntas com mais erros</h4>
                        <?php if (!empty($perguntas_criticas)): ?>
                            <div class="professor-evaluation-list">
                                <?php foreach ($perguntas_criticas as $pergunta): ?>
                                    <article class="professor-evaluation-item is-critical">
                                        <div>
                                            <strong><?php echo htmlspecialchars($pergunta['curso_titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <small><?php echo htmlspecialchars(strlen((string)($pergunta['texto'] ?? '')) > 96 ? substr((string)$pergunta['texto'], 0, 96) . '...' : (string)($pergunta['texto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                                        </div>
                                        <div class="professor-evaluation-item__meta">
                                            <span><?php echo htmlspecialchars((string)$pergunta['erros'], ENT_QUOTES, 'UTF-8'); ?> erros</span>
                                            <small><?php echo htmlspecialchars((string)$pergunta['total_respostas'], ENT_QUOTES, 'UTF-8'); ?> respostas</small>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="muted">As perguntas com maior taxa de erro aparecerão aqui após as primeiras tentativas.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="meus-cursos mt-20">
                <h2>Meus Cursos</h2>

                <?php if (isset($cursos) && count($cursos) > 0): ?>
                    <div class="course-grid professor-course-grid">
                        <?php foreach ($cursos as $curso): ?>
                            <?php
                            $courseCard = [
                                'course' => $curso,
                                'class' => 'course-card--instructor',
                                'title' => $curso['titulo'],
                                'title_href' => '?page=gerenciar-curso&id=' . urlencode((string)$curso['id']),
                                'thumbnail' => $curso['thumbnail'] ?? '',
                                'eyebrow' => 'Curso publicado',
                                'description' => $curso['descricao'] ?? '',
                                'meta' => [
                                    ($curso['total_alunos'] ?? 0) . ' alunos',
                                    ($curso['total_aulas'] ?? 0) . ' aulas',
                                    ucfirst((string)($curso['status'] ?? 'ativo')),
                                ],
                                'status' => [
                                    'label' => ucfirst((string)($curso['status'] ?? 'ativo')),
                                    'class' => 'badge-ghost'
                                ],
                                'primary_action' => [
                                    'label' => 'Gerenciar curso',
                                    'href' => '?page=gerenciar-curso&id=' . urlencode((string)$curso['id']),
                                    'class' => 'btn-info'
                                ],
                                'secondary_actions' => [
                                    [
                                        'label' => 'Editar',
                                        'href' => '?page=editar-curso&id=' . urlencode((string)$curso['id']),
                                        'class' => 'btn-outline btn-sm',
                                        'attributes' => [
                                            'data-fragment' => '?page=editar-curso&partial=1&id=' . (string)$curso['id'],
                                            'data-fragment-title' => 'Editar Curso'
                                        ]
                                    ],
                                    [
                                        'label' => 'Alunos',
                                        'href' => '?page=alunos-curso&id=' . urlencode((string)$curso['id']),
                                        'class' => 'btn-outline btn-sm'
                                    ]
                                ]
                            ];
                            include __DIR__ . '/course-card.php';
                            ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <?php
                    $courseCard = [
                        'class' => 'course-card--empty',
                        'eyebrow' => 'Primeiro passo',
                        'title' => 'Você ainda não criou nenhum curso',
                        'description' => 'Monte sua primeira oferta e organize a experiência dos seus alunos com um painel profissional.',
                        'primary_action' => [
                            'label' => 'Criar primeiro curso',
                            'href' => '?page=criar-curso',
                            'attributes' => [
                                'data-fragment' => '?page=criar-curso&partial=1',
                                'data-fragment-title' => 'Criar novo curso'
                            ]
                        ]
                    ];
                    include __DIR__ . '/course-card.php';
                    ?>
                <?php endif; ?>
            </section>
        </main>

        <aside class="side-col">
            <div class="card quick-actions">
                <div class="quick-actions__header">
                    <h3>Ações Rápidas</h3>
                    <p>Atalhos diretos para criar, publicar e movimentar seu catálogo com mais agilidade.</p>
                </div>
                <div class="actions-list">
                    <a class="btn btn-primary btn-block" href="?page=criar-curso" data-fragment="?page=criar-curso&partial=1" data-fragment-title="Criar novo curso">Novo Curso</a>
                    <button class="btn btn-primary btn-block" type="button" data-course-picker="lesson">Adicionar Aula</button>
                    <button class="btn btn-primary btn-block" type="button" data-course-picker="quiz">Novo Quiz</button>
                </div>
            </div>
        </aside>
    </div>
</section>
