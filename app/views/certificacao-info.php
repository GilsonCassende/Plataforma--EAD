<?php
$isAuthenticated = !empty($_SESSION['usuario']);
$primaryCtaHref = $isAuthenticated ? '?page=dashboard' : '?page=registro';
?>

<section class="certificate-info-page">
    <div class="container certificate-info-shell">
        <header class="certificate-info-header">
            <span class="certificate-info-eyebrow">Informações do certificado</span>
            <h1>Como funciona o certificado da Plataforma EAD</h1>
            <p>
                Esta página explica de forma simples como é o certificado, quando ele é liberado
                e como qualquer pessoa pode verificar a autenticidade online.
            </p>
        </header>

        <section class="certificate-info-section">
            <h2>Como é o certificado</h2>
            <p>
                O certificado é emitido em formato digital, com visual institucional, código único
                de verificação e QR Code. Ele pode ser visualizado na plataforma e baixado em PDF.
            </p>

            <div class="certificate-info-points">
                <article>
                    <strong>Formato</strong>
                    <span>PDF e versão online</span>
                </article>
                <article>
                    <strong>Identificação</strong>
                    <span>Código exclusivo por certificado</span>
                </article>
                <article>
                    <strong>Validação</strong>
                    <span>QR Code e página pública oficial</span>
                </article>
            </div>
        </section>

        <section class="certificate-info-section">
            <h2>Quando o certificado é liberado</h2>
            <p>
                O certificado só é liberado quando o aluno cumpre as regras definidas para o curso.
                Isso inclui concluir a trilha exigida e ser aprovado nas avaliações obrigatórias.
            </p>
        </section>

        <section class="certificate-info-section">
            <h2>Como funciona a verificação</h2>
            <p>
                Cada certificado possui um código único e um QR Code. Ao abrir a validação,
                a plataforma mostra o status do documento e os dados públicos permitidos.
            </p>

            <ul class="certificate-info-list">
                <li>Nome do aluno</li>
                <li>Curso concluído</li>
                <li>Professor responsável</li>
                <li>Carga horária</li>
                <li>Data de emissão</li>
                <li>Status de autenticidade</li>
            </ul>
        </section>

        <section class="certificate-info-section">
            <h2>Por que isso é importante</h2>
            <p>
                Esse modelo ajuda a reduzir falsificações e dá mais confiança a quem recebe,
                analisa ou compartilha o certificado.
            </p>
        </section>

        <footer class="certificate-info-footer">
            <p>
                O certificado foi pensado para ser claro, profissional e verificável.
            </p>
            <a href="<?php echo htmlspecialchars($primaryCtaHref, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary ui-btn ui-btn--primary">
                <?php echo $isAuthenticated ? 'Ir para o dashboard' : 'Começar agora'; ?>
            </a>
        </footer>
    </div>
</section>
