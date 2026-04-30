<?php

require_once __DIR__ . '/env.php';

load_project_env(__DIR__ . '/../.env');

$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

// Autoloader simples: procura classes em app/controllers e app/models
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../app/controllers/',
        __DIR__ . '/../app/models/',
        __DIR__ . '/../app/services/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }

    return false;
});
