<?php

/**
 * DIAGNÓSTICO - MOVIDO PARA SCRIPTS/MAINTENANCE
 * Este script foi movido para fora da pasta pública por segurança.
 * Uso: executar localmente/CLI por administrador.
 */

// Somente rodar via CLI
if (php_sapi_name() !== 'cli') {
    echo "Acesso negado. Este script não está disponível via web.\n";
    http_response_code(403);
    exit;
}

// Carregar bootstrap mínimo
require_once __DIR__ . '/../../config/database.php';

// Reutiliza o diagnóstico original de forma segura para execução local
echo "Diagnóstico de Login - execução CLI\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $resultado = $stmt->fetch();
    echo "Total de usuários: " . ($resultado['total'] ?? 0) . "\n";
} catch (Exception $e) {
    echo "Erro ao consultar usuários: " . $e->getMessage() . "\n";
}

// Gerar hash de exemplo só para uso local
$hash_valida = password_hash('senha123', PASSWORD_BCRYPT);
echo "Exemplo de hash para 'senha123': $hash_valida\n";

echo "Pronto. Este script não pode ser executado pela web por motivos de segurança.\n";
