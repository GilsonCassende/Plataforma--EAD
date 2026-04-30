<?php
/**
 * View: Página de Registro de Professor
 */
?>

<section class="auth-shell" aria-labelledby="registro-professor-title">
    <div class="auth-card auth-card--wide">
        <span class="auth-eyebrow">Cadastro de instrutor</span>
        <h1 id="registro-professor-title">Criar conta de instrutor</h1>
        <p class="subtitle">Cadastre-se para publicar e gerir cursos com um fluxo de autenticação profissional, protegido e validado por email.</p>

        <form id="registro-professor-form" method="POST" class="form" novalidate data-loading-form>
            <input type="hidden" name="acao" value="registrar">
            <input type="hidden" name="role" value="professor">
            <?php echo csrf_input(); ?>

            <div class="form-group">
                <label for="nome">Nome completo</label>
                <input
                    type="text"
                    id="nome"
                    name="nome"
                    placeholder="Seu nome profissional"
                    autocomplete="name"
                    required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="voce@instituicao.com"
                    autocomplete="email"
                    required>
                <small class="form-help">Validamos formato, domínio e bloqueamos emails descartáveis.</small>
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

            <button type="submit" class="btn btn-primary btn-block ui-btn ui-btn--primary" data-loading-text="Criando conta...">Criar conta de instrutor</button>
        </form>

        <div class="auth-note">
            <strong>Ativação obrigatória:</strong> você só poderá entrar e criar cursos depois de confirmar o código enviado pela plataforma.
        </div>

        <div class="auth-footer">
            <p>Já tem conta? <a href="<?php echo BASE_URL; ?>/index.php?page=login">Fazer login</a></p>
            <p>Quer estudar? <a href="<?php echo BASE_URL; ?>/index.php?page=registro">Criar conta de aluno</a></p>
            <p>Já se cadastrou e quer validar o email? <a href="<?php echo BASE_URL; ?>/index.php?page=confirmar-email">Confirmar conta</a></p>
        </div>
    </div>
</section>
