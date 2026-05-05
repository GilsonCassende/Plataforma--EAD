<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/autoload.php';

$service = new LessonMediaService(defined('UPLOADS_DIR') ? UPLOADS_DIR : (__DIR__ . '/../public/uploads'));
$result = $service->pruneOrphanedLessonAudio();

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
