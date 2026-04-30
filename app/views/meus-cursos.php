<?php

/**
 * Partial: Meus Cursos (professor)
 * Exibido dentro do modal / fragment
 */
?>
<div class="page container page-stack">
    <div class="page-header-block">
        <h1>Meus Cursos</h1>
        <p>Gerencie seus cursos publicados e acesse rapidamente as ações principais.</p>
    </div>

    <!-- Busca removida — exibir lista completa como página de gerenciamento -->

    <?php if (!empty($cursos)): ?>
        <div class="course-grid course-grid--instructor">
            <?php foreach ($cursos as $curso): ?>
                <?php
                $courseCard = [
                    'course' => $curso,
                    'class' => 'course-card--instructor',
                    'title' => $curso['titulo'],
                    'title_href' => '?page=gerenciar-curso&id=' . urlencode((string)$curso['id']),
                    'thumbnail' => $curso['thumbnail'] ?? '',
                    'eyebrow' => 'Meu curso',
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
                            'class' => 'btn-outline btn-sm'
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
        <?php if (isset($total_paginas) && $total_paginas > 1): ?>
            <div class="pagination panel-spacing-top">
                <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                    <?php $active = ($p == ($pagina ?? 1)); ?>
                    <a href="?page=meus-cursos&p=<?php echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm<?php echo $active ? ' is-active-page' : ''; ?>"><?php echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php
        $courseCard = [
            'class' => 'course-card--empty',
            'eyebrow' => 'Catálogo vazio',
            'title' => 'Você ainda não tem cursos',
            'description' => 'Crie sua primeira oferta e organize seu catálogo com a mesma linguagem visual do restante da plataforma.',
            'primary_action' => [
                'label' => 'Criar curso',
                'href' => '?page=criar-curso'
            ]
        ];
        include __DIR__ . '/course-card.php';
        ?>
    <?php endif; ?>
</div>
