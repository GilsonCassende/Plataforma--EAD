<?php

/**
 * View/Partial: Criar Aula
 * Suporta ?partial=1 para retorno somente do formulário (usado pelo modal)
 */

// obter course_id por GET ou fallback
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : (int)($_POST['course_id'] ?? 0);
$module_id = isset($module_id) ? (int)$module_id : (int)($_GET['module_id'] ?? $_POST['module_id'] ?? 0);
$module_options = is_array($module_options ?? null) ? $module_options : [];

if (isset($_GET['partial']) && $_GET['partial'] == '1'):
?>
    <div class="editor-card create-lesson" data-modal-fragment="create-lesson">
        <div class="editor-card__header">
            <h2 class="editor-card__title">Adicionar Aula</h2>
            <p class="editor-card__hint">Preencha o conteúdo e o tipo da aula para vincular ao curso atual.</p>
        </div>

        <form id="form-criar-aula" method="post" action="<?php echo BASE_URL; ?>/index.php" enctype="multipart/form-data" class="editor-card__body">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="acao" value="criar_aula">
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label>Título da aula</label>
                <input type="text" name="titulo" required>
            </div>

            <div class="form-group">
                <label>Tipo</label>
                <select name="tipo">
                    <option value="video">Vídeo</option>
                    <option value="pdf">PDF</option>
                    <option value="texto">Texto</option>
                </select>
            </div>

            <div class="form-group">
                <label>Descrição curta (opcional)</label>
                <input type="text" name="descricao" placeholder="Breve descrição da aula">
            </div>

            <div class="form-group">
                <label>Conteúdo / Notas da aula</label>
                <textarea name="conteudo" rows="6" placeholder="Insira o conteúdo da aula, anotações ou transcrição (opcional)"></textarea>
            </div>

            <div class="form-group">
                <label>Arquivo (opcional)</label>
                <input type="file" name="arquivo">
            </div>

            <div class="form-group">
                <label>URL do YouTube (opcional)</label>
                <input type="text" name="youtube_url" placeholder="https://youtu.be/.... ou https://www.youtube.com/watch?v=...">
                <small>Se preencher a URL do YouTube, o vídeo será embutido na aula. Use 'Unlisted' se preferir não indexar.</small>
            </div>

            <div class="panel-actions">
                <button class="btn btn-primary" type="submit">Adicionar Aula</button>
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
            </div>
        </form>
    </div>

<?php
    return;
endif;

// Fallback: se acessado diretamente, mostrar página simples
?>
<section class="container">
    <div class="editor-card create-lesson">
        <div class="editor-card__header">
            <h1 class="editor-card__title">Adicionar Aula</h1>
            <p class="editor-card__hint">Crie uma nova aula com conteúdo, arquivo e vídeo em uma página dedicada e estável.</p>
        </div>

        <form id="form-criar-aula" method="post" action="<?php echo BASE_URL; ?>/index.php" enctype="multipart/form-data" class="editor-card__body">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="acao" value="criar_aula">
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label>Módulo</label>
                <select name="module_id" required>
                    <option value="">Selecione o módulo</option>
                    <?php foreach ($module_options as $moduleOption): ?>
                        <option value="<?php echo htmlspecialchars((string)($moduleOption['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" <?php echo $module_id === (int)($moduleOption['id'] ?? 0) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string)($moduleOption['titulo'] ?? 'Módulo'), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Título da aula</label>
                <input type="text" name="titulo" required>
            </div>

            <div class="form-group">
                <label>Tipo</label>
                <select name="tipo">
                    <option value="video">Vídeo</option>
                    <option value="pdf">PDF</option>
                    <option value="texto">Texto</option>
                </select>
            </div>

            <div class="form-group">
                <label>Descrição curta (opcional)</label>
                <input type="text" name="descricao" placeholder="Breve descrição da aula">
            </div>

            <div class="form-group">
                <label>Conteúdo / Notas da aula</label>
                <textarea name="conteudo" rows="7" placeholder="Insira o conteúdo da aula, anotações ou transcrição" required></textarea>
            </div>

            <div class="form-group">
                <label>Arquivo (opcional)</label>
                <input type="file" name="arquivo">
            </div>

            <div class="form-group">
                <label>URL do YouTube (opcional)</label>
                <input type="text" name="youtube_url" placeholder="https://youtu.be/... ou https://www.youtube.com/watch?v=...">
                <small>Se preencher a URL do YouTube, o vídeo será embutido na aula.</small>
            </div>

            <div class="panel-actions">
                <button class="btn btn-primary" type="submit">Adicionar Aula</button>
                <a href="?page=gerenciar-curso&id=<?php echo htmlspecialchars($course_id, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</section>
