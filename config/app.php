<?php

/**
 * Detects the URL base path when the app runs in a subdirectory (e.g. XAMPP).
 * Override with APP_BASE_PATH in .env when auto-detection is not enough.
 */
function appBasePath(): string
{
    static $basePath = null;

    if ($basePath !== null) {
        return $basePath;
    }

    if (!empty($_ENV['APP_BASE_PATH'])) {
        $basePath = rtrim($_ENV['APP_BASE_PATH'], '/');
        return $basePath;
    }

    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptDir === '/' || $scriptDir === '.') {
        $basePath = '';
    } else {
        $basePath = rtrim($scriptDir, '/');
    }

    return $basePath;
}

return [
    'base_path' => appBasePath(),
];
