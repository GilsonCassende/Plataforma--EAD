<?php
/**
 * View/Partial: Criar Módulo
 */

$course_id = isset($course_id) ? (int)$course_id : (int)($_GET['course_id'] ?? 0);
$isPartial = isset($_GET['partial']) && $_GET['partial'] == '1';
?>

<?php if ($isPartial): ?>
    <div class="editor-card create-module" data-modal-fragment="create-module">
        <div class="editor-card__header">
            <h2 class="editor-card__title">Criar Módulo</h2>
            <p class="editor-card__hint">Agrupe aulas e o quiz do módulo em uma etapa clara da jornada do aluno.</p>
        </div>

        <form method="post" action="<?php echo BASE_URL; ?>/index.php" class="editor-card__body">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="acao" value="criar_modulo">
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars((string)$course_id, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label>Título do módulo</label>
                <input type="text" name="titulo" required placeholder="Ex.: Módulo 1 · Fundamentos">
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" rows="4" placeholder="Opcional. Explique o objetivo deste módulo."></textarea>
            </div>

            <div class="panel-actions">
                <button class="btn btn-primary" type="submit">Criar Módulo</button>
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
            </div>
        </form>
    </div>
    <?php return; ?>
<?php endif; ?>

<section class="container">
    <div class="editor-card create-module">
        <div class="editor-card__header">
            <h1 class="editor-card__title">Criar Módulo</h1>
            <p class="editor-card__hint">Organize o curso em etapas progressivas com aulas e avaliação por módulo.</p>
        </div>

        <form method="post" action="<?php echo BASE_URL; ?>/index.php" class="editor-card__body">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="acao" value="criar_modulo">
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars((string)$course_id, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label>Título do módulo</label>
                <input type="text" name="titulo" required placeholder="Ex.: Módulo 1 · Fundamentos">
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" rows="5" placeholder="Opcional. Explique o objetivo deste módulo."></textarea>
            </div>

            <div class="panel-actions">
                <button class="btn btn-primary" type="submit">Criar Módulo</button>
                <a href="?page=gerenciar-curso&id=<?php echo htmlspecialchars((string)$course_id, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</section>
