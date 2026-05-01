<?php

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$documentRoot = realpath(__DIR__) ?: __DIR__;
$target = realpath($documentRoot . $requestPath);

if ($target && str_starts_with($target, $documentRoot) && is_file($target)) {
    return false;
}

$_GET['url'] = ltrim($requestPath, '/');
require __DIR__ . '/index.php';
