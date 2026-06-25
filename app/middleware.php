<?php

// Basic middleware configuration (to be expanded)
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use Slim\App;
use Psr\Log\LoggerInterface;

return function (App $app, LoggerInterface $logger) {

    // Global error middleware
    $app->addErrorMiddleware(true, true, true);

    // Add specific middlewares to routes (defined in routes.php)
    $app->add(new GuestMiddleware());
};
