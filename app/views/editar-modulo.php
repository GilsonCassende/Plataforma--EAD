<?php
/**
 * View/Partial: Editar Módulo
 */

$modulo = $modulo ?? [];
$courseId = (int)($modulo['course_id'] ?? $_GET['course_id'] ?? 0);
$isPartial = isset($_GET['partial']) && $_GET['partial'] == '1';
?>

<?php if ($isPartial): ?>
    <div class="editor-card edit-module" data-modal-fragment="edit-module">
        <div class="editor-card__header">
            <h2 class="editor-card__title">Editar Módulo</h2>
            <p class="editor-card__hint">Atualize o nome e o contexto pedagógico desta etapa do curso.</p>
        </div>

        <form method="post" action="<?php echo BASE_URL; ?>/index.php" class="editor-card__body">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="acao" value="atualizar_modulo">
            <input type="hidden" name="module_id" value="<?php echo htmlspecialchars((string)($modulo['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label>Título do módulo</label>
                <input type="text" name="titulo" required value="<?php echo htmlspecialchars((string)($modulo['titulo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" rows="4" placeholder="Opcional. Explique o objetivo deste módulo."><?php echo htmlspecialchars((string)($modulo['descricao'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="panel-actions">
                <button class="btn btn-primary" type="submit">Salvar Módulo</button>
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
            </div>
        </form>
    </div>
    <?php return; ?>
<?php endif; ?>

<section class="container">
    <div class="editor-card edit-module">
        <div class="editor-card__header">
            <h1 class="editor-card__title">Editar Módulo</h1>
            <p class="editor-card__hint">Revise a etapa do curso sem perder a organização da trilha.</p>
        </div>

        <form method="post" action="<?php echo BASE_URL; ?>/index.php" class="editor-card__body">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="acao" value="atualizar_modulo">
            <input type="hidden" name="module_id" value="<?php echo htmlspecialchars((string)($modulo['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label>Título do módulo</label>
                <input type="text" name="titulo" required value="<?php echo htmlspecialchars((string)($modulo['titulo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" rows="5" placeholder="Opcional. Explique o objetivo deste módulo."><?php echo htmlspecialchars((string)($modulo['descricao'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="panel-actions">
                <button class="btn btn-primary" type="submit">Salvar Módulo</button>
                <a href="?page=gerenciar-curso&id=<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</section>
