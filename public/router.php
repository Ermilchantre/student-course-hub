<?php

/* ==================================================
   STATIC FILE SERVING AND ROUTING
   --------------------------------------------------
   Purpose: Handles serving static files and routes other requests to the main application.
   Functionality: Serves static assets (e.g., CSS, JS, images) directly and forwards non-static requests to index.php.
   Server-Side Role: Optimizes performance by serving static files without invoking the full application stack.
   ==================================================
*/

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

$appConfig = require __DIR__ . '/../config/app.php';
$basePath = $appConfig['base_path'];

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

if ($basePath !== '' && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

if ($path === '' || $path === false) {
    $path = '/';
}

$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];
$extension = pathinfo($path, PATHINFO_EXTENSION);

if (in_array($extension, $staticExtensions)) {
    $filePath = __DIR__ . $path;
    if (file_exists($filePath)) {
        $contentTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject'
        ];

        if (isset($contentTypes[$extension])) {
            header('Content-Type: ' . $contentTypes[$extension]);
        }

        readfile($filePath);
        exit;
    }
}

require __DIR__ . '/index.php';
