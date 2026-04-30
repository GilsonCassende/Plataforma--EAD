<?php

/**
 * INICIALIZADOR - Criar/Resetar Banco de Dados
 * Acesse: http://localhost/Plataforma-EAD/public/inicializar.php
 */

// Inicializador web desativado. Use o script CLI: scripts/initialize.php
http_response_code(404);
echo "<h1>404 - Não Encontrado</h1><p>Este recurso foi desativado. Para inicializar o banco, execute via CLI: <code>APP_ENV=development INIT_CONFIRM_TOKEN=... php scripts/initialize.php &lt;token&gt;</code></p>";
if (file_exists(__DIR__ . '/../config/helpers.php')) {
    @include_once __DIR__ . '/../config/helpers.php';
    if (function_exists('registrar_log')) registrar_log('access_denied', 'Tentativa de acesso a public/inicializar.php via web');
}
exit;
echo "<html>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Inicializar - Plataforma EAD</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }";
echo ".container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo "h1 { color: #333; }";
echo ".success { color: green; font-size: 18px; font-weight: bold; }";
echo ".error { color: red; font-size: 18px; font-weight: bold; }";
echo ".warning { color: orange; font-size: 16px; }";
echo ".info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #2196F3; }";
echo ".success-box { background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #4CAF50; }";
echo ".error-box { background: #ffebee; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #f44336; }";
echo "code { background: #f0f0f0; padding: 3px 6px; border-radius: 3px; font-family: monospace; }";
echo "table { width: 100%; border-collapse: collapse; margin: 20px 0; }";
echo "th { background: #f0f0f0; padding: 10px; text-align: left; border: 1px solid #ddd; }";
echo "td { padding: 10px; border: 1px solid #ddd; }";
echo ".btn { display: inline-block; padding: 10px 20px; margin: 10px 0; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; }";
echo ".btn:hover { background: #1976D2; }";
echo "hr { margin: 30px 0; border: none; border-top: 1px solid #ddd; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🚀 Inicializador - Plataforma EAD</h1>";

// Função para resetar banco
function resetarBanco()
{
    try {
        // Conectar
        $pdo = new PDO(
            'mysql:host=localhost;charset=utf8mb4',
            'root',
            '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        echo "<h2>1️⃣ Deletando banco antigo...</h2>";
        $pdo->exec("DROP DATABASE IF EXISTS ead_platform");
        echo "<span class='success'>✅ Banco deletado</span><br>";

        echo "<h2>2️⃣ Criando novo banco...</h2>";
        $pdo->exec("CREATE DATABASE ead_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<span class='success'>✅ Novo banco criado</span><br>";

        echo "<h2>3️⃣ Importando schema...</h2>";
        $pdo->exec("USE ead_platform");

        // Ler arquivo schema.sql
        $schemaFile = __DIR__ . '/../migrations/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("Arquivo schema.sql não encontrado em: $schemaFile");
        }

        $sql = file_get_contents($schemaFile);

        // Usar mysqli_multi_query para importar vários comandos SQL corretamente
        $mysqli = new mysqli('localhost', 'root', '', '', 3306);
        if ($mysqli->connect_errno) {
            throw new Exception('Erro ao conectar via mysqli: ' . $mysqli->connect_error);
        }

        // Ativar múltiplas instruções
        if (!$mysqli->multi_query($sql)) {
            $err = $mysqli->error;
            $mysqli->close();
            throw new Exception('Erro ao executar schema.sql via mysqli_multi_query: ' . $err);
        }

        // Consumir todos os resultados para garantir execução completa
        $commands_executed = 0;
        do {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
            $commands_executed++;
        } while ($mysqli->more_results() && $mysqli->next_result());

        $mysqli->close();

        echo "<span class='success'>✅ Schema importado (aprox. $commands_executed comandos)</span><br>";

        echo "<h2>4️⃣ Gerando hashes de senha...</h2>";
        $pdo = new PDO(
            'mysql:host=localhost;dbname=ead_platform;charset=utf8mb4',
            'root',
            '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $hash = password_hash('senha123', PASSWORD_BCRYPT);
        echo "Hash gerada: <code>$hash</code><br>";

        echo "<h2>5️⃣ Atualizando senhas de teste...</h2>";
        $stmt = $pdo->prepare("UPDATE users SET senha_hash = ? WHERE email IN ('admin@ead.com', 'joao@ead.com', 'maria@ead.com', 'carlos@ead.com', 'ana@ead.com', 'bruno@ead.com')");
        $stmt->execute([$hash]);

        echo "<span class='success'>✅ Senhas atualizadas</span><br>";

        // Verificar dados
        echo "<h2>6️⃣ Verificando dados...</h2>";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $resultado = $stmt->fetch();
        echo "<span class='success'>✅ Total de usuários: " . $resultado['total'] . "</span><br>";

        // Listar usuários
        echo "<h2>Usuários Cadastrados</h2>";
        $stmt = $pdo->query("SELECT id, nome, email, role FROM users ORDER BY role DESC");
        $usuarios = $stmt->fetchAll();

        echo "<table>";
        echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Role</th></tr>";
        foreach ($usuarios as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['nome'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Testar login
        echo "<h2>7️⃣ Testando login...</h2>";
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(['admin@ead.com']);
        $admin = $stmt->fetch();

        if ($admin && password_verify('senha123', $admin['senha_hash'])) {
            echo "<div class='success-box'>";
            echo "<span class='success'>✅ LOGIN FUNCIONANDO!</span><br><br>";
            echo "<strong>Credenciais de Teste:</strong><br>";
            echo "Email: <code>admin@ead.com</code><br>";
            echo "Senha: <code>senha123</code><br>";
            echo "</div>";
        } else {
            echo "<div class='error-box'>";
            echo "<span class='error'>❌ Erro ao testar login!</span>";
            echo "</div>";
        }

        echo "<hr>";
        echo "<a href='/Plataforma-EAD/public/index.php?page=login' class='btn'>🚀 Ir para Login</a>";
        echo "<a href='/Plataforma-EAD/public/diagnostico.php' class='btn' style='background: #666;'>🔍 Ver Diagnóstico</a>";

        return true;
    } catch (Exception $e) {
        echo "<div class='error-box'>";
        echo "<span class='error'>❌ Erro:</span><br>";
        echo htmlspecialchars($e->getMessage());
        echo "</div>";
        return false;
    }
}

// Processar
if ($_GET['confirmar'] === 'sim') {
    echo "<div class='info'>";
    echo "⏳ Processando... isto pode levar alguns segundos...";
    echo "</div>";
    resetarBanco();
} else {
    echo "<div class='warning' style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
    echo "<strong>⚠️ AVISO:</strong> Isto irá:<br>";
    echo "• Deletar o banco de dados atual (e TODOS os dados)<br>";
    echo "• Criar novo banco do zero<br>";
    echo "• Importar schema.sql<br>";
    echo "• Configurar usuários de teste<br>";
    echo "</div>";

    echo "<form method='GET' style='margin-top: 20px;'>";
    echo "<input type='hidden' name='confirmar' value='sim'>";
    echo "<button type='submit' class='btn' style='background: #f44336;'>🔄 Resetar Banco e Inicializar</button>";
    echo "<a href='/Plataforma-EAD/public/index.php' class='btn' style='background: #666;'>❌ Cancelar</a>";
    echo "</form>";

    echo "<div class='info' style='margin-top: 30px;'>";
    echo "<strong>ℹ️ Observação:</strong> Se o XAMPP não está rodando, clique em 'Start' na painel de controle do XAMPP primeiro.";
    echo "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
