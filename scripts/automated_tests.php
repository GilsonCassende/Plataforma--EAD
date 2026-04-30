<?php
// Testes automatizados básicos para Plataforma-EAD
// Uso: php automated_tests.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$BASE = getenv('BASE_URL') ?: 'http://localhost/Plataforma-EAD/public/index.php';
$cookieFile = __DIR__ . '/.cookies.txt';

function http_get($url, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['body' => $res, 'info' => $info];
}

function http_post($url, $postFields, $files = [], $cookieFile = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    // attach files
    foreach ($files as $k => $path) {
        if (file_exists($path)) {
            $postFields[$k] = new CURLFile($path);
        }
    }

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    if ($res === false) $res = curl_error($ch);
    curl_close($ch);
    return ['body' => $res, 'info' => $info];
}

function extract_csrf($html) {
    if (preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) return $m[1];
    if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $m)) return $m[1];
    return null;
}

function extract_id_from_redirect($redirect) {
    if (preg_match('/[&?]id=(\d+)/', $redirect, $m)) return $m[1];
    return null;
}

// garantir cookie file vazio
@unlink($cookieFile);

echo "Base URL: $BASE\n";

// 1) Acessar página de login para obter CSRF
echo "[1] GET login page...\n";
$r = http_get($BASE . '?page=login', $cookieFile);
$csrf = extract_csrf($r['body']);
if (!$csrf) { echo "ERRO: Não foi possível localizar CSRF na página de login\n"; exit(2); }
echo " CSRF encontrado: $csrf\n";

// 2) Login (professor)
echo "[2] Fazendo login (professor)...\n";
$post = ['acao' => 'login', 'email' => 'joao@ead.com', 'senha' => 'senha123', 'csrf_token' => $csrf];
$res = http_post($BASE, $post, [], $cookieFile, ['Accept: application/json']);
$json = @json_decode($res['body'], true);
if (!$json || empty($json['sucesso'])) {
    echo "ERRO: Login falhou. Resposta: \n" . $res['body'] . "\n";
    exit(3);
}
echo " Login OK. Redirect: " . ($json['redirect'] ?? 'n/a') . "\n";

// 3) GET criar-curso partial para CSRF e form
echo "[3] GET criar-curso partial...\n";
$r = http_get($BASE . '?page=criar-curso&partial=1', $cookieFile);
$csrf = extract_csrf($r['body']);
if (!$csrf) { echo "ERRO: CSRF no criar-curso não encontrado\n"; exit(4); }
echo " CSRF (criar-curso): $csrf\n";

// criar pequeno arquivo de thumbnail temporário (1x1 PNG)
$thumbPath = __DIR__ . '/thumb_test.png';
file_put_contents($thumbPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgkoxr2sAAAAASUVORK5CYII='));

echo "[4] Criar curso via POST...\n";
$post = ['acao' => 'criar_curso', 'titulo' => 'Curso de Teste Automático', 'descricao' => 'Descrição gerada pelo script', 'categoria' => 'Automatizado', 'csrf_token' => $csrf];
$res = http_post($BASE, $post, ['thumbnail' => $thumbPath], $cookieFile, ['Accept: application/json']);
$json = @json_decode($res['body'], true);
if (!$json || empty($json['sucesso'])) {
    echo "ERRO: Falha ao criar curso. Resposta: \n" . $res['body'] . "\n";
    exit(5);
}
echo " Curso criado. Redirect: " . ($json['redirect'] ?? '') . "\n";
$courseId = extract_id_from_redirect($json['redirect'] ?? '');
if (!$courseId) { echo "Aviso: não foi possível extrair course_id do redirect. Continuando...\n"; }
else echo " course_id = $courseId\n";

// 5) Criar aula (se courseId disponível)
if ($courseId) {
    echo "[5] GET criar-aula partial...\n";
    $r = http_get($BASE . '?page=criar-aula&partial=1&course_id=' . $courseId, $cookieFile);
    $csrf = extract_csrf($r['body']);
    if (!$csrf) { echo "ERRO: CSRF no criar-aula não encontrado\n"; exit(6); }
    echo " CSRF (criar-aula): $csrf\n";

    echo "[6] Criar aula via POST...\n";
    $post = ['acao' => 'criar_aula', 'course_id' => $courseId, 'titulo' => 'Aula de Teste', 'descricao' => 'Conteúdo de teste', 'tipo' => 'texto', 'csrf_token' => $csrf];
    $res = http_post($BASE, $post, [], $cookieFile, ['Accept: application/json']);
    $json = @json_decode($res['body'], true);
    if (!$json || empty($json['sucesso'])) {
        echo "ERRO: Falha ao criar aula. Resposta: \n" . $res['body'] . "\n";
        exit(7);
    }
    echo " Aula criada. Mensagem: " . ($json['mensagem'] ?? 'OK') . "\n";
}

// 7) Criar quiz (tentativa simples - se souber lesson_id, seria melhor)
echo "[7] Criar quiz (sem lesson_id específico, tentativa via POST)...\n";
$post = ['acao' => 'criar_quiz', 'lesson_id' => 0, 'titulo' => 'Quiz Automático'];
$res = http_post($BASE, $post, [], $cookieFile, ['Accept: application/json']);
$json = @json_decode($res['body'], true);
if ($json && !empty($json['sucesso'])) {
    echo " Quiz criado: " . json_encode($json) . "\n";
} else {
    echo " Aviso: criação de quiz pode requerer lesson_id válido. Resposta: " . $res['body'] . "\n";
}

// limpar artefatos
@unlink($thumbPath);
@unlink($cookieFile);

echo "\nTestes finalizados.\n";
exit(0);

?>
