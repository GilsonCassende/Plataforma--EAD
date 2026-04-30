<?php

/**
 * Script CLI de correções/migrações (movido de public)
 * Uso: php scripts/corrigir.php
 */
if (PHP_SAPI !== 'cli') {
    echo "This endpoint is CLI only.\n";
    exit(1);
}

require_once __DIR__ . '/../config/helpers.php';

// Exemplo: executar migração/ajustes necessários
echo "Running maintenance tasks...\n";
if (function_exists('registrar_log')) registrar_log('maintenance', 'scripts/corrigir.php executed');

// TODO: implementar tarefas específicas conforme necessidade
echo "Done.\n";
