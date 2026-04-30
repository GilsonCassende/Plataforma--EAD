<?php

/**
 * View: Página Inicial (Home)
 */
?>

<div class="hero-section hero-carousel" aria-roledescription="carousel">
    <div class="carousel-track" id="carouselTrack">
        <section class="carousel-slide" role="group" aria-roledescription="slide" aria-label="1 de 3">
            <figure class="slide-figure">
                <img class="slide-img" loading="lazy" src="<?php echo BASE_URL; ?>/uploads/slide_1_custom.png" alt="Instrutor demonstrando conteúdo para grupo de alunos em ambiente moderno">
            </figure>
            <div class="slide-overlay"></div>
            <div class="slide-content container">
                <div class="slide-panel">
                    <h1>Aprenda com Especialistas do Mercado</h1>
                    <p class="slide-subtitle">Cursos práticos e mentorias exclusivas para acelerar sua evolução profissional.</p>
                    <a href="?page=cursos" class="btn btn-hero ui-btn ui-btn--primary">Explorar Cursos</a>
                </div>
            </div>
        </section>

        <section class="carousel-slide" role="group" aria-roledescription="slide" aria-label="2 de 3">
            <figure class="slide-figure">
                <img class="slide-img" loading="lazy" src="<?php echo BASE_URL; ?>/uploads/slide_2_custom.png" alt="Profissional estudando em casa com laptop, caderno e café, luz natural">
            </figure>
            <div class="slide-overlay"></div>
            <div class="slide-content container">
                <div class="slide-panel">
                    <h1>Estude no Seu Ritmo, Onde Estiver</h1>
                    <p class="slide-subtitle">Uma plataforma flexível que se adapta à sua rotina e acelera o seu aprendizado.</p>
                    <a href="?page=registro" class="btn btn-hero ui-btn ui-btn--primary">Começar Agora</a>
                </div>
            </div>
        </section>

        <section class="carousel-slide" role="group" aria-roledescription="slide" aria-label="3 de 3">
            <figure class="slide-figure">
                <img class="slide-img" loading="lazy" src="<?php echo BASE_URL; ?>/uploads/slide_3_custom.png" alt="Profissional com certificado e dashboard de performance em monitor grande">
            </figure>
            <div class="slide-overlay"></div>
            <div class="slide-content container">
                <div class="slide-panel">
                    <h1>Conquiste sua Certificação Profissional</h1>
                    <p class="slide-subtitle">Valide suas habilidades com certificados reconhecidos e impulsione sua carreira no mercado.</p>
                    <a href="?page=cursos" class="btn btn-hero ui-btn ui-btn--primary">Saiba Mais</a>
                </div>
            </div>
        </section>
    </div>

    <div class="carousel-controls container">
        <button class="carousel-btn" id="prevBtn" aria-label="Slide anterior">‹</button>
        <div class="carousel-indicators" id="carouselIndicators" role="tablist" aria-label="Indicadores do carrossel"></div>
        <button class="carousel-btn" id="nextBtn" aria-label="Próximo slide">›</button>
    </div>
</div>

<section class="search-section">
    <div class="container">
        <div class="search-bar">
            <form id="form-busca" method="GET">
                <input type="hidden" name="page" value="cursos">
                <input
                    type="text"
                    name="busca"
                    placeholder="Buscar cursos..."
                    class="search-input">
                <button type="submit" class="btn btn-primary ui-btn ui-btn--primary">Buscar</button>
            </form>
        </div>
    </div>
</section>

<section class="featured-courses">
    <div class="container">
        <div class="section-heading">
            <span class="section-eyebrow">Curadoria Premium</span>
            <h2>Cursos em Destaque</h2>
            <p>Seleção com foco em aplicação prática, progressão clara e experiência de aprendizagem mais envolvente.</p>
        </div>
        <div class="course-grid courses-grid">
            <?php if (isset($cursos_destaque) && count($cursos_destaque) > 0): ?>
                <?php foreach ($cursos_destaque as $curso): ?>
                    <?php
                    $teacherName = trim((string)preg_replace('/^\s*Prof\.?\s*/iu', '', (string)($curso['professor_nome'] ?? 'Equipe EAD')));
                    $isOwner = is_course_owner($curso);
                    $courseCard = [
                        'course' => $curso,
                        'class' => 'course-card--featured',
                        'title' => $curso['titulo'],
                        'title_href' => '?page=curso&id=' . urlencode((string)$curso['id']),
                        'thumbnail' => $curso['thumbnail'] ?? '',
                        'eyebrow' => $curso['categoria'] ?? 'Curso em destaque',
                        'instructor' => 'Prof. ' . ($teacherName !== '' ? $teacherName : 'Equipe EAD'),
                        'description' => $curso['descricao'] ?? '',
                        'meta' => [
                            ($curso['total_alunos'] ?? 0) . ' alunos',
                            !empty($curso['categoria']) ? $curso['categoria'] : 'Aprendizado guiado',
                        ],
                        'primary_action' => $isOwner
                            ? [
                                'label' => 'Gerenciar curso',
                                'href' => '?page=gerenciar-curso&id=' . urlencode((string)$curso['id']),
                                'class' => 'btn-info'
                            ]
                            : [
                                'label' => 'Ver curso',
                                'href' => '?page=curso&id=' . urlencode((string)$curso['id'])
                            ],
                    ];
                    include __DIR__ . '/course-card.php';
                    ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-courses">Nenhum curso disponível no momento.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="container">
        <div class="section-heading">
            <span class="section-eyebrow">Prova Social</span>
            <h2>Aprendizado com escala e confiança</h2>
            <p>Indicadores que reforçam consistência, credibilidade e resultado para quem busca evolução profissional.</p>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">500+</div>
                <div class="stat-label">Alunos Ativos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">50+</div>
                <div class="stat-label">Cursos Disponíveis</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">30+</div>
                <div class="stat-label">Instrutores</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">98%</div>
                <div class="stat-label">Satisfação</div>
            </div>
        </div>
    </div>
</section>
