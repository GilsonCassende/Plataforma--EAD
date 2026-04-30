<?php
/**
 * View: Editar Curso (Professor)
 * Supports partial rendering when ?partial=1 (for modal forms)
 */

$curso = $curso ?? [];
$usuarioAtual = $_SESSION['usuario'] ?? [];
$cancelUrl = (($usuarioAtual['role'] ?? '') === 'admin')
    ? '?page=admin-cursos'
    : '?page=dashboard';

// If requested as partial (loaded into modal), return only the form markup
if (isset($_GET['partial']) && $_GET['partial'] == '1'):
?>
    <div class="card edit-course" data-modal-fragment="edit-course">
        <div class="edit-course__header">
            <h2>Editar Curso</h2>
            <p>Atualize os dados principais do curso com mais clareza e espaçamento consistente.</p>
        </div>

        <form id="form-editar-curso" class="edit-course__form" method="post" action="<?php echo BASE_URL; ?>/index.php" enctype="multipart/form-data">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="acao" value="atualizar_curso">
            <input type="hidden" name="course_id" value="<?php echo (int)($curso['id'] ?? $_GET['id'] ?? 0); ?>">

            <div class="form-group">
                <label>Título</label>
                <input type="text" name="titulo" required value="<?php echo htmlspecialchars($curso['titulo'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" required><?php echo htmlspecialchars($curso['descricao'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Categoria</label>
                <input type="text" name="categoria" value="<?php echo htmlspecialchars($curso['categoria'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Estrutura do curso</label>
                <input type="text" value="<?php echo (($curso['course_structure'] ?? 'single_module') === 'multi_module') ? 'Múltiplos módulos' : 'Módulo único'; ?>" readonly>
                <small>A estrutura modular é definida na criação do curso para manter a trilha consistente.</small>
            </div>

            <div class="form-group">
                <label>Thumbnail (jpg, png)</label>
                <input type="file" name="thumbnail" accept="image/*">
                <?php if (!empty($curso['thumbnail'])): ?>
                    <div class="current-thumb">
                        <span>Thumb atual</span>
                        <img class="thumb-preview" src="<?php echo BASE_URL; ?>/uploads/<?php echo htmlspecialchars($curso['thumbnail']); ?>" alt="Thumbnail">
                    </div>
                <?php endif; ?>
            </div>

            <div class="panel-actions edit-course__actions">
                <button class="btn btn-primary" type="submit">Salvar Alterações</button>
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
            </div>
        </form>
    </div>

<?php
    return; // render only partial
endif;

// otherwise render as full page
?>
<section class="container">
    <div class="card edit-course">
        <div class="edit-course__header">
            <h1>Editar Curso</h1>
            <p>Revise título, descrição, categoria e thumb dentro de um formulário mais limpo e melhor distribuído.</p>
        </div>

        <form id="form-editar-curso" class="edit-course__form" method="post" action="<?php echo BASE_URL; ?>/index.php" enctype="multipart/form-data">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="acao" value="atualizar_curso">
            <input type="hidden" name="course_id" value="<?php echo (int)($curso['id'] ?? $_GET['id'] ?? 0); ?>">

            <div class="form-group">
                <label>Título</label>
                <input type="text" name="titulo" required value="<?php echo htmlspecialchars($curso['titulo'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" required><?php echo htmlspecialchars($curso['descricao'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Categoria</label>
                <input type="text" name="categoria" value="<?php echo htmlspecialchars($curso['categoria'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Estrutura do curso</label>
                <input type="text" value="<?php echo (($curso['course_structure'] ?? 'single_module') === 'multi_module') ? 'Múltiplos módulos' : 'Módulo único'; ?>" readonly>
                <small>A estrutura modular é definida na criação do curso para manter a trilha consistente.</small>
            </div>

            <div class="form-group">
                <label>Thumbnail (jpg, png)</label>
                <input type="file" name="thumbnail" accept="image/*">
                <?php if (!empty($curso['thumbnail'])): ?>
                    <div class="current-thumb">
                        <span>Thumb atual</span>
                        <img class="thumb-preview" src="<?php echo BASE_URL; ?>/uploads/<?php echo htmlspecialchars($curso['thumbnail']); ?>" alt="Thumbnail">
                    </div>
                <?php endif; ?>
            </div>

            <div class="panel-actions edit-course__actions">
                <button class="btn btn-primary" type="submit">Salvar Alterações</button>
                <a href="<?php echo $cancelUrl; ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</section>
