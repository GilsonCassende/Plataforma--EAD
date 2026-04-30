<?php

/**
 * View: Dashboard do Aluno
 */
?>

<section class="dashboard">
    <div class="container">
        <header class="dashboard__header">
            <div class="dashboard__hero">
                <div class="dashboard__hero-copy">
                    <span class="dashboard__eyebrow">Jornada do aluno</span>
                    <h1 class="dashboard__title">Seu progresso está em movimento, <?php echo htmlspecialchars($_SESSION['usuario']['nome']); ?>.</h1>
                    <p class="dashboard__subtitle">Acompanhe cursos, conclua aulas e avance com clareza em uma rotina de estudo mais objetiva e motivadora.</p>
                </div>
                <div class="dashboard__hero-badges" aria-label="Resumo rápido do aluno">
                    <span class="dashboard__hero-badge">Cursos ativos: <?php echo htmlspecialchars($total_cursos ?? 0, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="dashboard__hero-badge">Conquistas: <?php echo htmlspecialchars($concluidos ?? 0, ENT_QUOTES, 'UTF-8'); ?> concluídos</span>
                    <span class="dashboard__hero-badge">Próximo passo: continuar aprendendo</span>
                </div>
            </div>
        </header>

        <div class="dashboard__stats stats-grid" aria-label="Resumo do aluno">
            <div class="stat-card">
                <div id="stat-total-cursos" class="stat-number"><?php echo htmlspecialchars($total_cursos ?? 0, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="stat-label">Cursos Matriculado</div>
            </div>
            <div class="stat-card">
                <div id="stat-em-progresso" class="stat-number"><?php echo htmlspecialchars($em_progresso ?? 0, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="stat-label">Em Progresso</div>
            </div>
            <div class="stat-card">
                <div id="stat-concluidos" class="stat-number"><?php echo htmlspecialchars($concluidos ?? 0, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="stat-label">Concluídos</div>
            </div>
        </div>

        <section class="dashboard__courses meus-cursos" aria-labelledby="meus-cursos-title">
            <div class="section-heading">
                <span class="section-eyebrow">Área do Aluno</span>
                <h2 id="meus-cursos-title">Meus Cursos</h2>
                <p>Acompanhe progresso, status e próximos passos sem ruído visual.</p>
            </div>

            <?php if (!empty($cursos) && is_array($cursos)): ?>
                <div class="course-grid cursos-grid">
                    <?php foreach ($cursos as $curso): ?>
                        <?php $width = intval($curso['progress'] ?? 0); ?>
                        <?php
                        $teacherName = trim((string)preg_replace('/^\s*Prof\.?\s*/iu', '', (string)($curso['professor_nome'] ?? 'Equipe EAD')));
                        $courseCard = [
                            'course' => $curso,
                            'title' => $curso['titulo'],
                            'title_href' => '?page=curso&id=' . urlencode((string)$curso['id']),
                            'thumbnail' => $curso['thumbnail'] ?? '',
                            'eyebrow' => $width === 100 ? 'Concluído' : 'Em andamento',
                            'instructor' => 'Prof. ' . ($teacherName !== '' ? $teacherName : 'Equipe EAD'),
                            'description' => $curso['descricao'] ?? '',
                            'meta' => [
                                !empty($curso['categoria']) ? $curso['categoria'] : 'Curso ativo',
                                !empty($curso['next_lesson_id']) ? 'Próxima aula disponível' : 'Revisar conteúdo',
                            ],
                            'progress' => $width,
                            'progress_label' => ($curso['progress'] ?? 0) . '%',
                            'status' => $width === 100
                                ? ['label' => 'Curso concluído', 'class' => 'badge-success']
                                : ['label' => 'Continuar trilha', 'class' => 'badge-warning'],
                            'primary_action' => [
                                'label' => 'Continuar estudando',
                                'href' => !empty($curso['next_lesson_id'])
                                    ? '?page=aula&lesson_id=' . urlencode((string)$curso['next_lesson_id']) . '&course_id=' . urlencode((string)$curso['id'])
                                    : '?page=curso&id=' . urlencode((string)$curso['id'])
                            ],
                        ];
                        include __DIR__ . '/course-card.php';
                        ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?php
                $courseCard = [
                    'class' => 'course-card--empty',
                    'eyebrow' => 'Nova jornada',
                    'title' => 'Nenhum curso matriculado',
                    'description' => 'Explore o catálogo para começar sua próxima jornada de aprendizado.',
                    'meta' => ['Escolha um curso para começar hoje'],
                    'primary_action' => [
                        'label' => 'Explorar cursos',
                        'href' => '?page=cursos'
                    ]
                ];
                include __DIR__ . '/course-card.php';
                ?>
            <?php endif; ?>
        </section>
    </div>
</section>
