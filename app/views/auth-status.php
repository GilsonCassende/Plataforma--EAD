<?php
/**
 * View: Estado de autenticação
 */
$status = ($status ?? 'success') === 'error' ? 'error' : 'success';
?>

<section class="auth-shell" aria-labelledby="auth-status-title">
    <div class="auth-card auth-card--status auth-card--feature">
        <span class="auth-eyebrow"><?php echo $status === 'success' ? 'Tudo certo' : 'Atenção'; ?></span>
        <div class="auth-status-symbol auth-status-symbol--<?php echo $status === 'success' ? 'success' : 'error'; ?>" aria-hidden="true">
            <?php echo $status === 'success' ? '✓' : '!'; ?>
        </div>
        <h1 id="auth-status-title"><?php echo htmlspecialchars((string)($title ?? 'Atualização de conta'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="subtitle"><?php echo htmlspecialchars((string)($message ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>

        <div class="auth-status-actions">
            <?php if (!empty($primaryAction['href']) && !empty($primaryAction['label'])): ?>
                <a class="btn btn-primary" href="<?php echo htmlspecialchars((string)$primaryAction['href'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars((string)$primaryAction['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endif; ?>

            <?php if (!empty($secondaryAction['href']) && !empty($secondaryAction['label'])): ?>
                <a class="btn btn-ghost auth-status-secondary" href="<?php echo htmlspecialchars((string)$secondaryAction['href'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars((string)$secondaryAction['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if ($status === 'error'): ?>
            <div class="auth-note">
                <strong>Precisa de outra tentativa?</strong> Pode abrir a confirmação da conta para reenviar um novo código.
            </div>
        <?php endif; ?>
    </div>
</section>
