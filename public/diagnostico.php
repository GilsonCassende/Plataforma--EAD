<?php

/**
 * Diagnóstico removido da pasta pública por motivos de segurança.
 */
http_response_code(404);
echo "<h1>404 - Não Encontrado</h1><p>Este recurso foi desativado por medidas de segurança.</p>";
// registrar tentativa de acesso ao endpoint (se desejar).
if (file_exists(__DIR__ . '/../config/helpers.php')) {
    @include_once __DIR__ . '/../config/helpers.php';
    if (function_exists('registrar_log')) {
        registrar_log('access_denied', 'Tentativa de acesso a public/diagnostico.php via web');
    }
}
exit;
