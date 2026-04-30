<?php
$path = __DIR__ . '/../public/css/style.css';
if (!file_exists($path)) {
    echo "Arquivo não encontrado: $path\n";
    exit(1);
}
$css = file_get_contents($path);
$open = substr_count($css, '{');
$close = substr_count($css, '}');
echo "Verificando $path\n";
echo "Aberturas: $open, Fechamentos: $close\n";
if ($open === $close) {
    echo "OK: Número de chaves balanceado.\n";
    exit(0);
} else {
    echo "ERRO: Chaves não balanceadas.\n";
    exit(2);
}
?>