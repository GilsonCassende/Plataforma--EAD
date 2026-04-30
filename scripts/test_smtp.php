<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/autoload.php';

$to = $argv[1] ?? null;
$mode = strtolower(trim((string)($argv[2] ?? 'raw')));

if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php scripts/test_smtp.php destinatario@exemplo.com [raw|confirmacao|recuperacao]\n");
    exit(1);
}

$timestamp = date('Y-m-d H:i:s');
$subject = 'Teste SMTP - Plataforma EAD';
$body = '<div style="font-family:Arial,sans-serif;color:#172033;line-height:1.6">'
    . '<h2>Teste SMTP da Plataforma EAD</h2>'
    . '<p>Se este email chegou, o transporte SMTP real está funcionando.</p>'
    . '<p><strong>Ambiente:</strong> ' . htmlspecialchars((string)env_value('APP_ENV', 'production'), ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><strong>Data/Hora:</strong> ' . htmlspecialchars($timestamp, ENT_QUOTES, 'UTF-8') . '</p>';

if ($mode === 'confirmacao') {
    $subject = 'Teste SMTP - Fluxo de confirmação';
    $body .= '<p><a href="' . htmlspecialchars(APP_URL . '/index.php?page=confirmar-email&token=token-teste', ENT_QUOTES, 'UTF-8') . '">Link de confirmação de teste</a></p>';
} elseif ($mode === 'recuperacao') {
    $subject = 'Teste SMTP - Fluxo de recuperação';
    $body .= '<p><a href="' . htmlspecialchars(APP_URL . '/index.php?page=redefinir-senha&token=token-teste', ENT_QUOTES, 'UTF-8') . '">Link de recuperação de teste</a></p>';
}

$body .= '</div>';

$ok = enviar_email($to, $subject, $body, 'text/html');

if (!$ok) {
    fwrite(STDERR, "Falha ao enviar email via SMTP. Verifique logs/app.log e configuração .env\n");
    exit(2);
}

fwrite(STDOUT, "Email enviado com sucesso para {$to}\n");
