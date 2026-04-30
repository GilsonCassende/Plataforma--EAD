<?php
/**
 * Partial / Page: Atividades Recentes (professor)
 * Exibe resumo do catálogo recente do professor, sem logs internos.
 */
$isPartial = isset($_GET['partial']) && $_GET['partial'] == '1';
?>
<?php if ($isPartial): ?>
    <div class="activities-panel" data-modal-fragment="activities-feed">
        <div class="activities-panel__header">
            <h3>Movimentação recente do seu catálogo</h3>
            <p>Veja os cursos mais recentes e acesse rapidamente a gestão de cada um.</p>
        </div>
<?php else: ?>
    <div class="page container page-stack activities-page">
        <div class="page-header-block">
            <h1>Atividades Recentes</h1>
            <p>Resumo dos cursos mais recentes publicados no seu ambiente de ensino.</p>
        </div>
<?php endif; ?>

    <div class="activities-summary">
        <article class="activities-summary__item">
            <span>Total de cursos</span>
            <strong><?php echo htmlspecialchars((string)($total_cursos_atividade ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
        </article>
        <article class="activities-summary__item">
            <span>Alunos alcançados</span>
            <strong><?php echo htmlspecialchars((string)($total_alunos_atividade ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
        </article>
        <article class="activities-summary__item">
            <span>Aulas publicadas</span>
            <strong><?php echo htmlspecialchars((string)($total_aulas_atividade ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
        </article>
    </div>

    <?php if (!empty($recent_courses)): ?>
        <div class="data-table-card panel-block activities-feed">
            <h3>Últimos cursos criados</h3>
            <ul class="simple-list">
                <?php foreach ($recent_courses as $rc): ?>
                    <li class="activities-feed__item">
                        <div class="activities-feed__content">
                            <strong><?php echo htmlspecialchars((string)($rc['titulo'] ?? 'Curso sem título'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <div class="list-meta">
                                Criado em:
                                <?php echo !empty($rc['created_at']) ? htmlspecialchars(date('d/m/Y', strtotime((string)$rc['created_at'])), ENT_QUOTES, 'UTF-8') : '-'; ?>
                                · Status: <?php echo htmlspecialchars((string)($rc['status'] ?? 'ativo'), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="list-meta">
                                <?php echo htmlspecialchars((string)($rc['total_alunos'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> alunos
                                · <?php echo htmlspecialchars((string)($rc['total_aulas'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> aulas
                            </div>
                        </div>
                        <a
                            href="?page=gerenciar-curso&id=<?php echo htmlspecialchars((string)($rc['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                            class="btn btn-outline btn-sm ui-btn ui-btn--small"
                            data-fragment="?page=gerenciar-curso&partial=1&id=<?php echo htmlspecialchars((string)($rc['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                            data-fragment-title="Gerenciar Curso">Gerenciar</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>Nenhuma atividade recente para exibir.</p>
        </div>
    <?php endif; ?>
</div>
