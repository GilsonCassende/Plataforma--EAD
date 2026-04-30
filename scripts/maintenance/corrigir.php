<?php

/**
 * CORRIGIR - MOVIDO PARA SCRIPTS/MAINTENANCE
 * Script de manutenção para execução local apenas. Remove-se a funcionalidade
 * de alterar senhas em massa pela web.
 */

if (php_sapi_name() !== 'cli') {
    echo "Acesso negado. Este script não está disponível via web.\n";
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

echo "Corrigir (modo CLI) - operação restrita.\n";
// Exemplo seguro: listar usuários e não alterar senhas automaticamente
try {
    $stmt = $pdo->query("SELECT id, nome, email, role FROM users ORDER BY role DESC");
    $usuarios = $stmt->fetchAll();
    foreach ($usuarios as $u) {
        echo "{$u['id']} - {$u['nome']} ({$u['email']}) - role={$u['role']}\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "Nenhuma alteração foi realizada. Para operações de escrita, use processos administrativos seguros.\n";
