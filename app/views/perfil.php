<?php

/**
 * View: Perfil do Usuário
 */
?>

<section class="perfil-section">
    <div class="container">
        <div class="perfil-header">
            <h1>Meu Perfil</h1>
            <p>Gerencie seus dados pessoais e credenciais em um único espaço, sem ações duplicadas.</p>
        </div>

        <?php if (isset($usuario) && $usuario): ?>
            <div class="perfil-container">
                <div class="perfil-card">
                    <div class="perfil-info">
                        <div class="avatar">
                            <?php if (!empty($usuario['fotografia'])): ?>
                                <img src="<?php echo htmlspecialchars(BASE_URL . '/uploads/' . ltrim($usuario['fotografia'], '/'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($usuario['nome']); ?>">
                            <?php else: ?>
                                <span><?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="info-details">
                            <h2><?php echo htmlspecialchars($usuario['nome']); ?></h2>
                            <p class="email">📧 <?php echo htmlspecialchars($usuario['email']); ?></p>
                            <p class="role">
                                <span class="badge badge-<?php echo htmlspecialchars($usuario['role'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php
                                    $roles = [
                                        'aluno' => '👨‍🎓 Aluno',
                                        'professor' => '👨‍🏫 Professor',
                                        'admin' => '⚙️ Administrador'
                                    ];
                                    echo $roles[$usuario['role']] ?? ucfirst($usuario['role']);
                                    ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="perfil-summary">
                        <div class="perfil-highlights">
                            <span class="perfil-pill">Conta ativa</span>
                            <span class="perfil-pill">Dados centralizados</span>
                        </div>
                        <p class="perfil-note">Atualize nome, email e senha diretamente nos formulários ao lado.</p>

                        <div class="perfil-export">
                            <strong>Backup da conta</strong>
                            <p>
                                <?php if (($usuario['role'] ?? '') === 'professor'): ?>
                                    Exporte seus dados, cursos e alunos em um pacote organizado para download.
                                <?php else: ?>
                                    Baixe uma cópia organizada dos seus dados, progresso, quizzes e certificados.
                                <?php endif; ?>
                            </p>

                            <form
                                method="post"
                                action="<?php echo BASE_URL; ?>/index.php?page=<?php echo ($usuario['role'] ?? '') === 'professor' ? 'exportar-dados-professor' : 'exportar-dados-aluno'; ?>"
                                class="perfil-export__form"
                                data-export-form>
                                <?php echo csrf_input(); ?>
                                <?php if (($usuario['role'] ?? '') === 'professor'): ?>
                                    <input type="hidden" name="scope" value="all">
                                <?php endif; ?>
                                <div class="field-with-toggle">
                                    <input type="password" name="backup_password" placeholder="Senha opcional AES-256">
                                </div>
                                <button type="submit" class="btn btn-primary ui-btn ui-btn--primary" data-loading-text="Preparando backup...">
                                    <?php echo ($usuario['role'] ?? '') === 'professor' ? 'Exportar dados' : 'Baixar meus dados'; ?>
                                </button>
                            </form>
                        </div>

                        <div class="perfil-export">
                            <strong>Backup automático</strong>
                            <p>Ative a rotina automática para gerar backups em segundo plano e receber aviso por email.</p>
                            <form method="post" class="perfil-export__form">
                                <input type="hidden" name="acao" value="atualizar_backup_preferencias">
                                <?php echo csrf_input(); ?>
                                <label class="perfil-export__check">
                                    <input type="checkbox" name="backup_auto_enabled" value="1" <?php echo !empty($backupPreferences['auto_enabled']) ? 'checked' : ''; ?>>
                                    <span>Ativar backup automático</span>
                                </label>
                                <label class="perfil-export__field">
                                    Frequência:
                                    <select name="backup_frequency">
                                        <option value="daily" <?php echo (($backupPreferences['frequency'] ?? 'daily') === 'daily') ? 'selected' : ''; ?>>diária</option>
                                        <option value="weekly" <?php echo (($backupPreferences['frequency'] ?? '') === 'weekly') ? 'selected' : ''; ?>>semanal</option>
                                    </select>
                                </label>
                                <label class="perfil-export__check">
                                    <input type="checkbox" name="backup_receive_email" value="1" <?php echo !empty($backupPreferences['receive_email']) ? 'checked' : ''; ?>>
                                    <span>Receber por email</span>
                                </label>
                                <button type="submit" class="btn btn-outline ui-btn">Salvar preferências</button>
                            </form>
                        </div>

                        <div class="perfil-export">
                            <strong>Restaurar backup</strong>
                            <p>Envie um pacote ZIP validado para recuperar cursos, progresso, quizzes, certificados e anexos compatíveis.</p>

                            <form
                                method="post"
                                action="<?php echo BASE_URL; ?>/index.php?page=validar-backup"
                                class="perfil-export__form"
                                enctype="multipart/form-data">
                                <?php echo csrf_input(); ?>
                                <input type="file" name="backup_zip" accept=".zip,application/zip" required>
                                <div class="field-with-toggle">
                                    <input type="password" name="backup_password" placeholder="Senha do backup, se existir">
                                </div>
                                <button type="submit" class="btn btn-outline ui-btn">Restaurar Backup</button>
                            </form>

                            <?php if (!empty($backupRestorePreview) && is_array($backupRestorePreview)): ?>
                                <?php $summary = $backupRestorePreview['summary'] ?? []; ?>
                                <?php $counts = $summary['counts'] ?? []; ?>
                                <?php $previewCourses = $summary['courses'] ?? []; ?>
                                <?php $previewModules = $summary['modules'] ?? []; ?>
                                <div class="perfil-note" style="margin-top: 16px;">
                                    <strong>Resumo validado</strong><br>
                                    Tipo: <?php echo htmlspecialchars((string)($summary['type'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?><br>
                                    Cursos: <?php echo htmlspecialchars((string)($counts['courses'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> |
                                    Aulas: <?php echo htmlspecialchars((string)($counts['lessons'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> |
                                    Quizzes: <?php echo htmlspecialchars((string)($counts['quizzes'] ?? 0), ENT_QUOTES, 'UTF-8'); ?><br>
                                    Progresso: <?php echo htmlspecialchars((string)($counts['progress'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> |
                                    Certificados: <?php echo htmlspecialchars((string)($counts['certificates'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>
                                </div>

                                <form method="post" action="<?php echo BASE_URL; ?>/index.php?page=restaurar-backup" class="perfil-export__form" style="margin-top: 12px;">
                                    <?php echo csrf_input(); ?>
                                    <input type="hidden" name="backup_token" value="<?php echo htmlspecialchars((string)($backupRestorePreview['token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="field-with-toggle">
                                        <input type="password" name="backup_password" placeholder="Senha do backup, se existir">
                                    </div>
                                    <label class="perfil-export__field">
                                        Escopo da restauração:
                                        <select name="restore_scope">
                                            <option value="full">Completa</option>
                                            <option value="user">Somente dados do usuário</option>
                                            <option value="course">Curso específico</option>
                                            <option value="module">Módulo específico</option>
                                        </select>
                                    </label>
                                    <label class="perfil-export__field">
                                        Curso:
                                        <select name="restore_course_source_id">
                                            <option value="">Selecione</option>
                                            <?php foreach ($previewCourses as $coursePreview): ?>
                                                <option value="<?php echo htmlspecialchars((string)($coursePreview['source_id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars((string)($coursePreview['titulo'] ?? 'Curso'), ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="perfil-export__field">
                                        Módulo:
                                        <select name="restore_module_source_id">
                                            <option value="">Selecione</option>
                                            <?php foreach ($previewModules as $modulePreview): ?>
                                                <option value="<?php echo htmlspecialchars((string)($modulePreview['source_id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars((string)($modulePreview['titulo'] ?? 'Módulo'), ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <button type="submit" class="btn btn-primary ui-btn ui-btn--primary">Confirmar restauração</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="perfil-forms">
                    <!-- Formulário de Edição -->
                    <div class="perfil-form" id="editar">
                        <h3>Editar Informações</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="acao" value="atualizar_perfil">
                            <?php echo csrf_input(); ?>

                            <div class="form-group">
                                <label>Foto de Perfil:</label>
                                <div class="perfil-upload">
                                    <div class="perfil-upload-preview">
                                        <?php if (!empty($usuario['fotografia'])): ?>
                                            <img src="<?php echo htmlspecialchars(BASE_URL . '/uploads/' . ltrim($usuario['fotografia'], '/'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($usuario['nome']); ?>">
                                        <?php else: ?>
                                            <span><?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="perfil-upload-fields">
                                        <input type="file" name="fotografia" accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif">
                                        <small>Envie uma imagem JPG, PNG ou GIF de até 5MB.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Nome Completo:</label>
                                <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary ui-btn ui-btn--primary">Salvar Alterações</button>
                        </form>
                    </div>

                    <!-- Formulário de Senha -->
                    <div class="perfil-form" id="senha">
                        <h3>Alterar Senha</h3>
                        <form method="POST">
                            <input type="hidden" name="acao" value="alterar_senha">
                            <?php echo csrf_input(); ?>

                            <div class="form-group">
                                <label>Senha Atual:</label>
                                <div class="field-with-toggle">
                                    <input type="password" name="senha_atual" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Nova Senha:</label>
                                <div class="field-with-toggle">
                                    <input type="password" name="nova_senha" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Confirmar Senha:</label>
                                <div class="field-with-toggle">
                                    <input type="password" name="confirmar_senha" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary ui-btn ui-btn--primary">Alterar Senha</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-error">Erro ao carregar perfil.</div>
        <?php endif; ?>
    </div>
</section>

