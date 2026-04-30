<?php

if (!function_exists('load_project_env')) {
    function load_project_env(?string $envFile = null): void
    {
        static $loadedFiles = [];

        $envFile = $envFile ?: __DIR__ . '/../.env';
        if (isset($loadedFiles[$envFile])) {
            return;
        }

        $loadedFiles[$envFile] = true;

        if (!is_file($envFile) || !is_readable($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key === '' || getenv($key) !== false) {
                continue;
            }

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

if (!function_exists('env_value')) {
    function env_value(string $key, $default = null)
    {
        load_project_env();

        $value = getenv($key);
        if ($value === false) {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('env_bool')) {
    function env_bool(string $key, bool $default = false): bool
    {
        $value = env_value($key);
        if ($value === null) {
            return $default;
        }

        $normalized = strtolower(trim((string)$value));
        if ($normalized === '') {
            return $default;
        }

        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }
}

if (!function_exists('env_int')) {
    function env_int(string $key, int $default): int
    {
        $value = env_value($key);
        if ($value === null || !is_numeric($value)) {
            return $default;
        }

        return (int)$value;
    }
}
