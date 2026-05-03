<?php
/**
 * View: Editar Aula
 * Suporta ?partial=1 para retorno somente do formulário em modal.
 */

$aula = $aula ?? [];
$curso = $curso ?? [];
$module_options = is_array($module_options ?? null) ? $module_options : [];
$courseId = (int)($curso['id'] ?? $aula['course_id'] ?? $_GET['course_id'] ?? 0);
$cancelUrl = '?page=gerenciar-curso&id=' . $courseId;
$currentYoutubeUrl = !empty($aula['video_id']) ? ('https://www.youtube.com/watch?v=' . $aula['video_id']) : '';
$currentAudioUrl = lesson_audio_url($aula);

if (isset($_GET['partial']) && $_GET['partial'] == '1'):
?>
    <div class="editor-card edit-lesson" data-modal-fragment="edit-lesson">
        <div class="editor-card__header">
            <h2 class="editor-card__title">Editar Aula</h2>
            <p class="editor-card__hint">Atualize o conteúdo, o tipo de mídia e os detalhes pedagógicos desta aula.</p>
        </div>

        <form id="form-editar-aula" method="post" action="<?php echo BASE_URL; ?>/index.php" enctype="multipart/form-data" class="editor-card__body">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="acao" value="atualizar_aula">
            <input type="hidden" name="lesson_id" value="<?php echo htmlspecialchars((string)($aula['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label>Módulo</label>
                <select name="module_id" required>
                    <?php foreach ($module_options as $moduleOption): ?>
                        <option value="<?php echo htmlspecialchars((string)($moduleOption['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (int)($aula['module_id'] ?? 0) === (int)($moduleOption['id'] ?? 0) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string)($moduleOption['titulo'] ?? 'Módulo'), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Título da aula</label>
                <input type="text" name="titulo" required value="<?php echo htmlspecialchars($aula['titulo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label>Tipo</label>
                <select name="tipo">
                    <option value="video" <?php echo (($aula['tipo'] ?? '') === 'video') ? 'selected' : ''; ?>>Vídeo</option>
                    <option value="pdf" <?php echo (($aula['tipo'] ?? '') === 'pdf') ? 'selected' : ''; ?>>PDF</option>
                    <option value="texto" <?php echo (($aula['tipo'] ?? '') === 'texto') ? 'selected' : ''; ?>>Texto</option>
                </select>
            </div>

            <div class="form-group">
                <label>Descrição curta</label>
                <input type="text" name="descricao" value="<?php echo htmlspecialchars($aula['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Breve descrição da aula">
            </div>

            <div class="form-group">
                <label>Conteúdo / Notas da aula</label>
                <textarea name="conteudo" rows="7" required placeholder="Atualize o conteúdo da aula"><?php echo htmlspecialchars($aula['conteudo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="form-group">
                <label>Resumo da aula (opcional)</label>
                <textarea name="resumo" rows="4" placeholder="Resumo rápido para o modo econômico"><?php echo htmlspecialchars($aula['resumo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="form-group">
                <label>Substituir arquivo (opcional)</label>
                <input type="file" name="arquivo">
                <?php if (!empty($aula['url_arquivo'])): ?>
                    <small class="muted">Arquivo atual: <?php echo htmlspecialchars($aula['url_arquivo'], ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>URL do YouTube (opcional)</label>
                <input type="text" name="youtube_url" value="<?php echo htmlspecialchars($currentYoutubeUrl, ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://youtu.be/... ou https://www.youtube.com/watch?v=...">
                <small>Se preencher, o vídeo embutido será atualizado nesta aula.</small>
            </div>

            <div class="form-group">
                <label>Áudio da aula (opcional)</label>
                <input type="file" name="audio" accept=".mp3,audio/mpeg">
                <?php if (!empty($aula['audio_url'])): ?>
                    <small class="muted">Áudio atual: <a href="<?php echo htmlspecialchars($currentAudioUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars((string)$aula['audio_url'], ENT_QUOTES, 'UTF-8'); ?></a></small>
                <?php else: ?>
                    <small>Envie um MP3 para habilitar o modo econômico nesta aula.</small>
                <?php endif; ?>
            </div>

            <div class="panel-actions">
                <button class="btn btn-primary" type="submit">Salvar Aula</button>
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
            </div>
        </form>
    </div>

<?php
    return;
endif;
?>
<section class="container">
    <div class="editor-card edit-lesson">
        <div class="editor-card__header">
            <h1 class="editor-card__title">Editar Aula</h1>
            <p class="editor-card__hint">Revise e atualize esta aula em uma tela dedicada, clara e consistente.</p>
        </div>

        <form id="form-editar-aula" method="post" action="<?php echo BASE_URL; ?>/index.php" enctype="multipart/form-data" class="editor-card__body">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="acao" value="atualizar_aula">
            <input type="hidden" name="lesson_id" value="<?php echo htmlspecialchars((string)($aula['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars((string)$courseId, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label>Módulo</label>
                <select name="module_id" required>
                    <?php foreach ($module_options as $moduleOption): ?>
                        <option value="<?php echo htmlspecialchars((string)($moduleOption['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (int)($aula['module_id'] ?? 0) === (int)($moduleOption['id'] ?? 0) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string)($moduleOption['titulo'] ?? 'Módulo'), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Título da aula</label>
                <input type="text" name="titulo" required value="<?php echo htmlspecialchars($aula['titulo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label>Tipo</label>
                <select name="tipo">
                    <option value="video" <?php echo (($aula['tipo'] ?? '') === 'video') ? 'selected' : ''; ?>>Vídeo</option>
                    <option value="pdf" <?php echo (($aula['tipo'] ?? '') === 'pdf') ? 'selected' : ''; ?>>PDF</option>
                    <option value="texto" <?php echo (($aula['tipo'] ?? '') === 'texto') ? 'selected' : ''; ?>>Texto</option>
                </select>
            </div>

            <div class="form-group">
                <label>Descrição curta</label>
                <input type="text" name="descricao" value="<?php echo htmlspecialchars($aula['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Breve descrição da aula">
            </div>

            <div class="form-group">
                <label>Conteúdo / Notas da aula</label>
                <textarea name="conteudo" rows="8" required placeholder="Atualize o conteúdo da aula"><?php echo htmlspecialchars($aula['conteudo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="form-group">
                <label>Resumo da aula (opcional)</label>
                <textarea name="resumo" rows="4" placeholder="Resumo rápido para o modo econômico"><?php echo htmlspecialchars($aula['resumo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="form-group">
                <label>Substituir arquivo (opcional)</label>
                <input type="file" name="arquivo">
                <?php if (!empty($aula['url_arquivo'])): ?>
                    <small class="muted">Arquivo atual: <?php echo htmlspecialchars($aula['url_arquivo'], ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>URL do YouTube (opcional)</label>
                <input type="text" name="youtube_url" value="<?php echo htmlspecialchars($currentYoutubeUrl, ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://youtu.be/... ou https://www.youtube.com/watch?v=...">
                <small>Se preencher, o vídeo embutido será atualizado nesta aula.</small>
            </div>

            <div class="form-group">
                <label>Áudio da aula (opcional)</label>
                <input type="file" name="audio" accept=".mp3,audio/mpeg">
                <?php if (!empty($aula['audio_url'])): ?>
                    <small class="muted">Áudio atual: <a href="<?php echo htmlspecialchars($currentAudioUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars((string)$aula['audio_url'], ENT_QUOTES, 'UTF-8'); ?></a></small>
                <?php else: ?>
                    <small>Envie um MP3 para habilitar o modo econômico nesta aula.</small>
                <?php endif; ?>
            </div>

            <div class="panel-actions">
                <button class="btn btn-primary" type="submit">Salvar Aula</button>
                <a href="<?php echo htmlspecialchars($cancelUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</section>
