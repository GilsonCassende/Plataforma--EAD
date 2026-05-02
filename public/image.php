<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/helpers.php';

$src = basename((string)($_GET['src'] ?? ''));
if ($src === '') {
    http_response_code(404);
    exit('Imagem não encontrada.');
}

$sourcePath = __DIR__ . '/uploads/' . $src;
if (!is_file($sourcePath)) {
    http_response_code(404);
    exit('Imagem não encontrada.');
}

$width = max(1, min(2200, (int)($_GET['w'] ?? 0)));
$height = max(1, min(2200, (int)($_GET['h'] ?? 0)));
$fit = (string)($_GET['fit'] ?? 'contain');
$quality = max(40, min(90, (int)($_GET['q'] ?? 82)));

$extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
$cacheCandidates = [
    dirname(__DIR__) . '/storage/image-cache',
    rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plataforma-ead-image-cache',
];
$cacheDir = null;

foreach ($cacheCandidates as $candidate) {
    if (!is_dir($candidate) && !@mkdir($candidate, 0775, true) && !is_dir($candidate)) {
        continue;
    }
    if (is_dir($candidate) && is_writable($candidate)) {
        $cacheDir = $candidate;
        break;
    }
}

$cacheKey = md5($src . '|' . $width . '|' . $height . '|' . $fit . '|' . $quality . '|' . filemtime($sourcePath));
$cacheFile = $cacheDir ? ($cacheDir . '/' . $cacheKey . '.jpg') : null;

if ($cacheFile === null || !is_file($cacheFile)) {
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $sourceImage = @imagecreatefromjpeg($sourcePath);
            break;
        case 'png':
            $sourceImage = @imagecreatefrompng($sourcePath);
            break;
        case 'gif':
            $sourceImage = @imagecreatefromgif($sourcePath);
            break;
        case 'webp':
            $sourceImage = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false;
            break;
        default:
            $sourceImage = false;
            break;
    }

    if (!$sourceImage) {
        header('Content-Type: ' . (mime_content_type($sourcePath) ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($sourcePath));
        readfile($sourcePath);
        exit;
    }

    $srcWidth = imagesx($sourceImage);
    $srcHeight = imagesy($sourceImage);

    if ($width <= 0) {
        $width = $srcWidth;
    }
    if ($height <= 0) {
        $height = $srcHeight;
    }

    $targetImage = imagecreatetruecolor($width, $height);
    imagefill($targetImage, 0, 0, imagecolorallocate($targetImage, 255, 255, 255));

    if ($fit === 'cover') {
        $scale = max($width / $srcWidth, $height / $srcHeight);
    } else {
        $scale = min($width / $srcWidth, $height / $srcHeight);
    }

    $resizeWidth = max(1, (int)round($srcWidth * $scale));
    $resizeHeight = max(1, (int)round($srcHeight * $scale));
    $dstX = (int)floor(($width - $resizeWidth) / 2);
    $dstY = (int)floor(($height - $resizeHeight) / 2);

    imagecopyresampled(
        $targetImage,
        $sourceImage,
        $dstX,
        $dstY,
        0,
        0,
        $resizeWidth,
        $resizeHeight,
        $srcWidth,
        $srcHeight
    );

    if ($cacheFile === null) {
        header('Content-Type: image/jpeg');
        header('Cache-Control: no-store');
        imagejpeg($targetImage, null, $quality);
        imagedestroy($sourceImage);
        imagedestroy($targetImage);
        exit;
    }

    imagejpeg($targetImage, $cacheFile, $quality);
    imagedestroy($sourceImage);
    imagedestroy($targetImage);
}

header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($cacheFile));
header('Cache-Control: public, max-age=2592000, immutable');
readfile($cacheFile);
