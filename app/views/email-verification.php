<?php
/**
 * View: Confirmação por código
 */

$email = trim((string)($email ?? ''));
$context = trim((string)($context ?? ''));
$contextLabel = 'Enviamos um código de 6 dígitos para concluir a ativação da sua conta.';
$inlineVerificationCode = null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$storedInlineVerification = $_SESSION['inline_verification_code'] ?? null;
if (
    is_array($storedInlineVerification)
    && (($storedInlineVerification['email'] ?? '') === $email)
    && !empty($storedInlineVerification['codigo'])
) {
    $inlineVerificationCode = (string)$storedInlineVerification['codigo'];
}

if ($context === 'signup') {
    $contextLabel = 'Sua conta foi criada. Falta só confirmar o código enviado para ativar o primeiro acesso.';
} elseif ($context === 'login') {
    $contextLabel = 'Sua conta já existe, mas ainda precisa de confirmação por código antes do login.';
}
?>

<section class="verify-code-shell" aria-labelledby="email-verification-title">
    <div class="verify-code-card">
        <a class="verify-code-close" href="<?php echo BASE_URL; ?>/index.php?page=login" aria-label="Fechar">×</a>

        <span class="auth-eyebrow">Verificação por código</span>
        <h1 id="email-verification-title">Criar uma conta</h1>
        <p class="verify-code-intro"><?php echo htmlspecialchars($contextLabel, ENT_QUOTES, 'UTF-8'); ?></p>

        <p class="verify-code-copy">
            Enviamos um código de verificação para
            <strong><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></strong>
        </p>

        <p class="verify-code-label">Por favor, insira o código abaixo.</p>

        <form id="email-verification-code-form" method="POST" class="verify-code-form" novalidate data-loading-form>
            <input type="hidden" name="acao" value="confirmar_email_codigo">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo csrf_input(); ?>

            <label class="verify-code-input-wrap" for="codigo">
                <input
                    type="text"
                    id="codigo"
                    name="codigo"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    placeholder="Código de verificação"
                    required
                    autofocus
                    data-verification-code-input>
                <span class="verify-code-counter" data-verification-code-counter>0/6</span>
            </label>

            <button type="submit" class="btn btn-primary verify-code-submit" data-loading-text="Validando...">Entrar</button>
        </form>

        <div class="verify-code-note">
            O código tem 6 dígitos e foi enviado para o email informado no cadastro.
        </div>

        <?php if ($inlineVerificationCode !== null): ?>
            <div class="alert alert-warning" style="margin-top:16px;">
                O envio do email falhou agora. Use este código para ativar a conta:
                <strong style="display:block;font-size:28px;letter-spacing:6px;margin-top:8px;"><?php echo htmlspecialchars($inlineVerificationCode, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
        <?php endif; ?>

        <div class="verify-code-footer">
            <a href="<?php echo BASE_URL; ?>/index.php?page=login">← Voltar</a>

            <form id="email-verification-resend-form" method="POST" novalidate data-loading-form>
                <input type="hidden" name="acao" value="reenviar_confirmacao_email">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo csrf_input(); ?>
                <button type="submit" class="verify-code-link" data-loading-text="Reenviando...">Reenviar código</button>
            </form>
        </div>

        <p class="verify-code-support">
            Se o código não chegar em instantes, confira spam e promoções antes de reenviar.
        </p>
    </div>
</section>
