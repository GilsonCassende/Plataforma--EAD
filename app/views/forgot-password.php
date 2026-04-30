<?php
/**
 * View: Solicitação de recuperação de senha
 */
?>

<section class="auth-shell" aria-labelledby="forgot-password-title">
    <div class="auth-card auth-card--wide">
        <span class="auth-eyebrow">Recuperação segura</span>
        <h1 id="forgot-password-title">Esqueceu a senha?</h1>
        <p class="subtitle">Informe seu email e, se ele estiver cadastrado, enviaremos um link temporário para redefinir sua senha.</p>

        <form id="forgot-password-form" method="POST" class="form" novalidate data-loading-form>
            <input type="hidden" name="acao" value="solicitar_recuperacao_senha">
            <?php echo csrf_input(); ?>

            <div class="form-group">
                <label for="email">Email da conta</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="seu@email.com"
                    autocomplete="email"
                    required>
            </div>

            <button type="submit" class="btn btn-primary btn-block" data-loading-text="Enviando link...">Enviar link de recuperação</button>
        </form>

        <div class="auth-note">
            <strong>Dica:</strong> o link de recuperação expira em 15 minutos por segurança.
        </div>

        <div class="auth-footer">
            <p>Lembrou a senha? <a href="<?php echo BASE_URL; ?>/index.php?page=login">Voltar para login</a></p>
            <p>O problema é ativação da conta? <a href="<?php echo BASE_URL; ?>/index.php?page=confirmar-email">Reenviar código</a></p>
        </div>
    </div>
</section>
