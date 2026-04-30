<?php

/**
 * Script CLI de diagnóstico (movido de public)
 * Uso: php scripts/diagnostico.php
 */
if (PHP_SAPI !== 'cli') {
    echo "This endpoint is CLI only.\n";
    exit(1);
}

require_once __DIR__ . '/../config/helpers.php';

echo "Plataforma-EAD diagnostic\n";
// Exibir versões e checagens básicas
echo "PHP: " . PHP_VERSION . "\n";
echo "PDO driver: ";
try {
    require_once __DIR__ . '/../config/database.php';
    echo isset($pdo) ? 'OK' : 'PDO not configured';
} catch (Exception $e) {
    echo 'error: ' . $e->getMessage();
}
echo "\n";

if (function_exists('registrar_log')) registrar_log('diagnostic', 'scripts/diagnostico.php executed');
