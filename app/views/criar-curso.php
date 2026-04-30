<?php
/**
 * View: Criar Curso (Professor)
 */
?>

<?php
// If requested as partial (loaded into modal), return only the form markup
if (isset($_GET['partial']) && $_GET['partial'] == '1'):
?>
    <div class="editor-card create-course">
        <div class="editor-card__header">
            <h2 class="editor-card__title">Criar Novo Curso</h2>
            <p class="editor-card__hint">Configure a base do curso e publique depois pelo painel de gestão.</p>
        </div>

        <form id="form-criar-curso" method="post" action="<?php echo BASE_URL; ?>/index.php" enctype="multipart/form-data" class="editor-card__body">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="acao" value="criar_curso">

            <div class="form-group">
                <label>Título</label>
                <input type="text" name="titulo" required>
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" required></textarea>
            </div>

            <div class="form-group">
                <label>Categoria</label>
                <input type="text" name="categoria">
            </div>

            <div class="form-group">
                <label>Estrutura do curso</label>
                <select name="course_structure" required>
                    <option value="single_module">Curso com módulo único</option>
                    <option value="multi_module">Curso com múltiplos módulos</option>
                </select>
                <small>No modo de módulo único, o sistema cria um módulo principal automaticamente. No modo com múltiplos módulos, o professor organiza etapas e quizzes por módulo.</small>
            </div>

            <div class="form-group">
                <label>Thumbnail (jpg, png)</label>
                <input type="file" name="thumbnail" accept="image/*">
            </div>

            <div class="panel-actions">
                <button class="btn btn-primary" type="submit">Criar Curso</button>
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
            </div>
        </form>
    </div>

<?php
    return; // render only partial
endif;

// otherwise render as full page (show the full form)
?>
<section class="container">
    <div class="editor-card create-course">
        <div class="editor-card__header">
            <h1 class="editor-card__title">Criar Novo Curso</h1>
            <p class="editor-card__hint">Defina a estrutura inicial do curso antes de adicionar aulas e quizzes.</p>
        </div>

        <form id="form-criar-curso" method="post" action="<?php echo BASE_URL; ?>/index.php" enctype="multipart/form-data" class="editor-card__body">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="acao" value="criar_curso">

            <div class="form-group">
                <label>Título</label>
                <input type="text" name="titulo" required>
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" required></textarea>
            </div>

            <div class="form-group">
                <label>Categoria</label>
                <input type="text" name="categoria">
            </div>

            <div class="form-group">
                <label>Estrutura do curso</label>
                <select name="course_structure" required>
                    <option value="single_module">Curso com módulo único</option>
                    <option value="multi_module">Curso com múltiplos módulos</option>
                </select>
                <small>No modo de módulo único, o sistema cria um módulo principal automaticamente. No modo com múltiplos módulos, o professor organiza etapas e quizzes por módulo.</small>
            </div>

            <div class="form-group">
                <label>Thumbnail (jpg, png)</label>
                <input type="file" name="thumbnail" accept="image/*">
            </div>

            <div class="panel-actions">
                <button class="btn btn-primary" type="submit">Criar Curso</button>
                <a href="?page=dashboard" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</section>
