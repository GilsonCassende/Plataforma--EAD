<?php
/**
 * View: Página de Login
 */
?>

<section class="auth-shell" aria-labelledby="login-title">
    <div class="auth-card auth-card--wide">
        <span class="auth-eyebrow">Acesso seguro</span>
        <h1 id="login-title">Entrar na sua conta</h1>
        <p class="subtitle">Use seu email e sua senha para acessar a plataforma. Contas novas precisam confirmar o email antes do primeiro login.</p>

        <form id="login-form" method="POST" class="form" novalidate data-loading-form>
            <input type="hidden" name="acao" value="login">
            <?php echo csrf_input(); ?>

            <div class="form-group">
                <label for="email">Email profissional</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="seu@email.com"
                    autocomplete="email"
                    required>
            </div>

            <div class="form-group">
                <div class="auth-inline-row">
                    <label for="senha">Senha</label>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=esqueci-senha">Esqueceu a senha?</a>
                </div>
                <div class="field-with-toggle">
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua senha"
                        autocomplete="current-password"
                        required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" data-loading-text="Entrando...">Entrar</button>
        </form>

        <div class="auth-note">
            <strong>Segurança:</strong> bloqueamos tentativas excessivas e exigimos confirmação de email para proteger sua conta.
        </div>

        <div class="auth-footer">
            <p>Não tem conta? <a href="<?php echo BASE_URL; ?>/index.php?page=registro">Criar conta de aluno</a></p>
            <p>Quer publicar cursos? <a href="<?php echo BASE_URL; ?>/index.php?page=registro-professor">Criar conta de instrutor</a></p>
            <p>Não recebeu o código? <a href="<?php echo BASE_URL; ?>/index.php?page=confirmar-email">Confirmar conta</a></p>
        </div>
    </div>
</section>
