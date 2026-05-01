<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/autoload.php';

$configuredToken = trim((string)env_value('SMTP_PROBE_TOKEN', ''));
$providedToken = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));

if ($configuredToken === '' || !hash_equals($configuredToken, $providedToken)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Acesso negado.\n";
    exit;
}

$to = trim((string)($_GET['to'] ?? env_value('MAIL_FROM', '')));
if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Informe um destinatário válido em ?to=EMAIL.\n";
    exit;
}

$subject = 'Mail Probe - Plataforma EAD';
$body = '<p>Teste de email executado no runtime atual da Railway.</p>'
    . '<p><strong>APP_URL:</strong> ' . htmlspecialchars((string)APP_URL, ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><strong>Data/Hora:</strong> ' . htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8') . '</p>';

$ok = enviar_email($to, $subject, $body, 'text/html');
$lastError = get_last_mail_error();

header('Content-Type: text/plain; charset=utf-8');
echo $ok ? "EMAIL OK\n" : "EMAIL FALHOU\n";
echo 'Destinatário: ' . $to . "\n";
echo 'MAIL_TRANSPORT: ' . (string)env_value('MAIL_TRANSPORT', env_value('RESEND_API_KEY', '') !== '' ? 'resend' : 'smtp') . "\n";
echo 'RESEND_API_KEY: ' . (env_value('RESEND_API_KEY', '') !== '' ? '[configurada]' : '[vazia]') . "\n";
echo 'SMTP_HOST: ' . (string)env_value('SMTP_HOST', '') . "\n";
echo 'SMTP_PORT: ' . (string)env_value('SMTP_PORT', '') . "\n";
echo 'SMTP_USER: ' . (string)env_value('SMTP_USER', '') . "\n";
echo 'SMTP_SECURE: ' . (string)env_value('SMTP_SECURE', '') . "\n";
echo 'MAIL_FROM: ' . (string)env_value('MAIL_FROM', '') . "\n";
echo 'Erro: ' . ($lastError ?? 'nenhum') . "\n";
