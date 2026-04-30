<?php
// Gerar hash correto para senha123
$hash = password_hash('senha123', PASSWORD_BCRYPT);
echo "Hash para 'senha123': " . $hash . "\n";
?>
