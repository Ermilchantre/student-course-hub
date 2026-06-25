<?php

require_once __DIR__ . '/../vendor/autoload.php';
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

$key_getenv = getenv('SENDGRID_API_KEY');
$key_env = $_ENV['SENDGRID_API_KEY'] ?? null;
echo 'SENDGRID_API_KEY via getenv: ' . ($key_getenv ?: 'NÃƒÆ’O DEFINIDA') . '<br>';
echo 'SENDGRID_API_KEY via $_ENV: ' . ($key_env ?: 'NÃƒÆ’O DEFINIDA') . '<br>';
