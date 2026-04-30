<?php

/**
 * View: Página de Cursos (Listagem)
 */
?>

<section class="cursos-section">
    <div class="container">
        <h1>Todos os Cursos</h1>

        <div class="cursos-toolbar">
            <form method="GET" class="search-form">
                <input type="hidden" name="page" value="cursos">
                <input
                    type="text"
                    name="busca"
                    placeholder="Buscar cursos..."
                    value="<?php echo htmlspecialchars($busca ?? ''); ?>"
                    class="search-input">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </form>

            <?php if (tem_permissao('professor')): ?>
                <a
                    href="?page=criar-curso"
                    class="btn cursos-toolbar__create-btn"
                    data-fragment="?page=criar-curso&partial=1"
                    data-fragment-title="Criar novo curso"
                >+ Novo Curso</a>
            <?php endif; ?>
        </div>

        <div class="cursos-container">
            <div class="course-grid cursos-grid">
                <?php if (isset($cursos) && count($cursos) > 0): ?>
                    <?php foreach ($cursos as $curso): ?>
                        <?php
                        $teacherName = trim((string)preg_replace('/^\s*Prof\.?\s*/iu', '', (string)($curso['professor_nome'] ?? 'Equipe EAD')));
                        $isOwner = is_course_owner($curso);
                        $courseCard = [
                            'course' => $curso,
                            'title' => $curso['titulo'],
                            'title_href' => '?page=curso&id=' . urlencode((string)$curso['id']),
                            'thumbnail' => $curso['thumbnail'] ?? '',
                            'eyebrow' => $curso['categoria'] ?? 'Geral',
                            'instructor' => 'Prof. ' . ($teacherName !== '' ? $teacherName : 'Equipe EAD'),
                            'description' => $curso['descricao'] ?? '',
                            'meta' => [
                                ($curso['total_alunos'] ?? 0) . ' alunos',
                                !empty($curso['categoria']) ? $curso['categoria'] : 'Formação online',
                            ],
                            'primary_action' => $isOwner
                                ? [
                                    'label' => 'Gerenciar curso',
                                    'href' => '?page=gerenciar-curso&id=' . urlencode((string)$curso['id']),
                                    'class' => 'btn-info'
                                ]
                                : [
                                    'label' => 'Ver detalhes',
                                    'href' => '?page=curso&id=' . urlencode((string)$curso['id'])
                                ],
                        ];
                        include __DIR__ . '/course-card.php';
                        ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-results">
                        <p>Nenhum curso encontrado. Tente refinar sua busca.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Paginação -->
        <?php if (isset($total_paginas) && $total_paginas > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a
                        href="?page=cursos&p=<?php echo htmlspecialchars($i, ENT_QUOTES, 'UTF-8'); ?>"
                        class="pagination-link <?php echo (isset($pagina) && $pagina == $i) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($i, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
