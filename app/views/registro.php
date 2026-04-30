<?php
/**
 * View: Página de Registro de Aluno
 */
?>

<section class="auth-shell" aria-labelledby="registro-title">
    <div class="auth-card auth-card--wide">
        <span class="auth-eyebrow">Cadastro de aluno</span>
        <h1 id="registro-title">Crie sua conta</h1>
        <p class="subtitle">Cadastre-se para acompanhar cursos, quizzes e certificados com uma conta protegida por verificação de email.</p>

        <form id="registro-form" method="POST" class="form" novalidate data-loading-form>
            <input type="hidden" name="acao" value="registrar">
            <input type="hidden" name="role" value="aluno">
            <?php echo csrf_input(); ?>

            <div class="form-group">
                <label for="nome">Nome completo</label>
                <input
                    type="text"
                    id="nome"
                    name="nome"
                    placeholder="Seu nome completo"
                    autocomplete="name"
                    required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="voce@empresa.com"
                    autocomplete="email"
                    required>
                <small class="form-help">Aceitamos apenas emails válidos e não descartáveis.</small>
            </div>

            <div class="form-group">
                <label for="senha">Senha forte</label>
                <div class="field-with-toggle">
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Mínimo 8 caracteres, com letra e número"
                        autocomplete="new-password"
                        required>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_senha">Confirmar senha</label>
                <div class="field-with-toggle">
                    <input
                        type="password"
                        id="confirm_senha"
                        name="confirm_senha"
                        placeholder="Repita a senha"
                        autocomplete="new-password"
                        required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" data-loading-text="Criando conta...">Criar conta</button>
        </form>

        <div class="auth-note">
            <strong>Importante:</strong> após o cadastro, enviaremos um código para ativar sua conta antes do primeiro acesso.
        </div>

        <div class="auth-footer">
            <p>Já tem conta? <a href="<?php echo BASE_URL; ?>/index.php?page=login">Fazer login</a></p>
            <p>Vai lecionar? <a href="<?php echo BASE_URL; ?>/index.php?page=registro-professor">Criar conta de instrutor</a></p>
            <p>Já se cadastrou e quer validar o email? <a href="<?php echo BASE_URL; ?>/index.php?page=confirmar-email">Confirmar conta</a></p>
        </div>
    </div>
</section>
