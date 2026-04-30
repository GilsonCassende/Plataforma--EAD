<?php

/**
 * Partial: Minhas Aulas (professor)
 * Lista aulas agrupadas por curso
 */
?>
<div class="page container page-stack">
    <div class="page-header-block">
        <h1>Minhas Aulas</h1>
        <p>Lista consolidada das aulas que você publicou por curso.</p>
    </div>

    <!-- Busca removida — exibindo página completa de aulas -->

    <?php if (!empty($lessons)): ?>
        <div class="data-table-card">
            <ul class="simple-list">
                <?php foreach ($lessons as $aula): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($aula['titulo']); ?></strong>
                        <div class="list-meta">Curso: <?php echo htmlspecialchars($aula['course_title'] ?? ''); ?> - Tipo: <?php echo htmlspecialchars($aula['tipo'] ?? ''); ?></div>
                        <div class="stack-actions"><a class="btn btn-sm" href="?page=aula&lesson_id=<?php echo htmlspecialchars($aula['id'], ENT_QUOTES, 'UTF-8'); ?>&course_id=<?php echo htmlspecialchars($aula['course_id'], ENT_QUOTES, 'UTF-8'); ?>">Abrir Aula</a></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php if (isset($total_paginas) && $total_paginas > 1): ?>
            <div class="pagination panel-spacing-top">
                <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                    <?php $active = ($p == ($pagina ?? 1)); ?>
                    <a href="?page=minhas-aulas&p=<?php echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm<?php echo $active ? ' is-active-page' : ''; ?>"><?php echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <p>Nenhuma aula encontrada.</p>
        </div>
    <?php endif; ?>
</div>
