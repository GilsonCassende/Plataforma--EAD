<?php
/**
 * View: Redefinição de senha
 */
?>

<section class="auth-shell" aria-labelledby="reset-password-title">
    <div class="auth-card auth-card--wide">
        <span class="auth-eyebrow">Nova senha</span>
        <h1 id="reset-password-title">Redefinir senha</h1>
        <p class="subtitle">Crie uma nova senha forte para voltar a acessar sua conta com segurança.</p>

        <?php if (empty($tokenValido)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars((string)($mensagemToken ?: 'O link de redefinição é inválido ou expirou.'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="auth-footer auth-footer--inline">
                <p><a href="<?php echo BASE_URL; ?>/index.php?page=esqueci-senha">Solicitar novo link</a></p>
                <p><a href="<?php echo BASE_URL; ?>/index.php?page=login">Voltar para login</a></p>
            </div>
        <?php else: ?>
            <form id="reset-password-form" method="POST" class="form" novalidate data-loading-form>
                <input type="hidden" name="acao" value="redefinir_senha">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars((string)$token, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo csrf_input(); ?>

                <div class="form-group">
                    <label for="senha">Nova senha</label>
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
                    <label for="confirm_senha">Confirmar nova senha</label>
                    <div class="field-with-toggle">
                        <input
                            type="password"
                            id="confirm_senha"
                            name="confirm_senha"
                            placeholder="Repita a nova senha"
                            autocomplete="new-password"
                            required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" data-loading-text="Redefinindo...">Salvar nova senha</button>
            </form>

            <div class="auth-note">
                <strong>Recomendação:</strong> use uma senha exclusiva para esta plataforma e evite reutilizar credenciais antigas.
            </div>
        <?php endif; ?>
    </div>
</section>
