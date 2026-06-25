<?php

// ============================================================================== // Dependency Injection Container Setup
// This file registers and configures all application dependencies, including:
// - Logger (Monolog)
// - Twig templating engine
// - Database connection (PDO)
// - Models and Controllers
// - Any other services required by the app
// Usage: Included during application bootstrap to ensure all dependencies are
// available via the DI container.
// ==============================================================================

use DI\Container;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Controllers\AdminLoginController;
use App\Controllers\HomeController;
use App\Controllers\ProgrammeController;
use App\Controllers\InterestController;
use App\Controllers\AdminDashboardController;
use App\Controllers\AdminModulesController;
use App\Controllers\AdminProgrammesController;
use App\Controllers\AdminInterestsController;
use App\Controllers\AdminStaffController;
use App\Controllers\StaffLoginController;
use App\Models\User;
use App\Models\Programme;
use App\Models\Module;
use App\Models\StudentInterest;
use App\Models\StaffMember;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\UserSession;
use App\Middleware\AuthMiddleware;

return function (Container $container) {

    // ======================================================================
    // Logger Service
    // Registers the Monolog logger for application-wide logging. The logger
    // writes debug-level logs to the logs/app.log file.
    // ======================================================================
    $container->set(
        'logger',
        function () {
            $logger = new Logger('app');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
            return $logger;
        }
    );
    // Registers the Monolog logger for application-wide logging (duplicate, may be redundant)
    $container->set(
        'logger',
        function () {
            $logger = new Logger('app');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
            return $logger;
        }
    );

    // ======================================================================
    // Twig Templating Engine
    // Registers Twig for rendering views. Adds a custom 'base_path' function
    // for use in templates.
    // ======================================================================
    $container->set(
        Environment::class,
        function (Container $c) {
            $loader = new FilesystemLoader(__DIR__ . '/../src/Views');
            $twig = new Environment($loader, ['cache' => false]);
            $basePath = $c->get('app.base_path');
            $twig->addFunction(
                new \Twig\TwigFunction(
                    'base_path',
                    function () use ($basePath) {
                        return $basePath;
                    }
                )
            );
            return $twig;
        }
    );
    // Alias for Twig environment
    $container->set('view', fn(Container $c) => $c->get(Environment::class));

    // ======================================================================
    // Database Connection (PDO)
    // Registers a PDO instance for database access, using configuration from
    // config/database.php.
    // ======================================================================
    $container->set(
        \PDO::class,
        function () {
            $config = include __DIR__ . '/../config/database.php';
            return new \PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
        }
    );
    // Alias for PDO
    $container->set('db', fn(Container $c) => $c->get(\PDO::class));

    // ======================================================================
    // Model Bindings
    // Registers all Eloquent-style models with the DI container, each using
    // the PDO database connection.
    // ======================================================================
    $container->set(
        \App\Models\Programme::class,
        function (Container $c) {
            return new \App\Models\Programme($c->get(\PDO::class));
        }
    );
    // Registers Programme model with PDO dependency

    $container->set(
        \App\Models\Module::class,
        function (Container $c) {
            return new \App\Models\Module($c->get(\PDO::class));
        }
    );
    // Registers Module model with PDO dependency

    $container->set(
        \App\Models\StudentInterest::class,
        function (Container $c) {
            return new \App\Models\StudentInterest($c->get(\PDO::class));
        }
    );
    // Registers StudentInterest model with PDO dependency

    $container->set(
        \App\Models\StaffMember::class,
        function (Container $c) {
            return new \App\Models\StaffMember($c->get(\PDO::class));
        }
    );
    // Registers StaffMember model with PDO dependency

    $container->set(
        \App\Models\User::class,
        function (Container $c) {
            return new \App\Models\User($c->get(\PDO::class));
        }
    );
    // Registers User model with PDO dependency

    $container->set(
        \App\Models\ActivityLog::class,
        function (Container $c) {
            return new \App\Models\ActivityLog($c->get(\PDO::class));
        }
    );
    // Registers ActivityLog model with PDO dependency

    $container->set(
        \App\Models\Notification::class,
        function (Container $c) {
            return new \App\Models\Notification($c->get(\PDO::class));
        }
    );
    // Registers Notification model with PDO dependency

    // ======================================================================
    // Controller Bindings
    // Registers all controllers with their required dependencies (Twig, models).
    // ======================================================================
    $container->set(
        \App\Controllers\HomeController::class,
        function (Container $c) {
            return new \App\Controllers\HomeController(
                $c->get(Environment::class),
                $c->get(\App\Models\Programme::class)
            );
        }
    );
    // Registers HomeController with Twig and Programme model dependencies

    $container->set(
        \App\Controllers\ProgrammeController::class,
        function (Container $c) {
            return new \App\Controllers\ProgrammeController(
                $c->get(Environment::class),
                $c->get(\App\Models\Programme::class),
                $c->get(\App\Models\Module::class),
                $c->get(\App\Models\StaffMember::class)
            );
        }
    );
    // Registers ProgrammeController with Twig, Programme, Module, and StaffMember dependencies

    $container->set(
        \App\Controllers\InterestController::class,
        function (Container $c) {
            return new \App\Controllers\InterestController(
                $c->get(Environment::class),
                $c->get(\App\Models\StudentInterest::class),
                $c->get(\App\Models\Programme::class),
                $c->get(\App\Models\Notification::class)
            );
        }
    );
    // Registers InterestController with Twig, StudentInterest, Programme, and Notification dependencies

    $container->set(
        \App\Controllers\AdminLoginController::class,
        function (Container $c) {
            return new \App\Controllers\AdminLoginController(
                $c->get(Environment::class),
                $c->get(\App\Models\User::class)
            );
        }
    );
    // Registers AdminLoginController with Twig and User model dependencies

    $container->set(
        \App\Controllers\StaffLoginController::class,
        function (Container $c) {
            return new \App\Controllers\StaffLoginController(
                $c->get(Environment::class),
                $c->get(\App\Models\User::class)
            );
        }
    );
    // Registers StaffLoginController with Twig and User model dependencies

    $container->set(
        \App\Controllers\AdminDashboardController::class,
        function (Container $c) {
            return new \App\Controllers\AdminDashboardController(
                $c->get(Environment::class),
                $c->get(\App\Models\Programme::class),
                $c->get(\App\Models\StudentInterest::class),
                $c->get(\App\Models\Notification::class)
            );
        }
    );
    // Registers AdminDashboardController with Twig, Programme, StudentInterest, and Notification dependencies

    $container->set(
        \App\Controllers\AdminModulesController::class,
        function (Container $c) {
            return new \App\Controllers\AdminModulesController(
                $c->get(\PDO::class),
                $c->get(Environment::class)
            );
        }
    );
    // Registers AdminModulesController with PDO and Twig dependencies

    $container->set(
        \App\Controllers\AdminProgrammesController::class,
        function (Container $c) {
            return new \App\Controllers\AdminProgrammesController(
                $c->get(Environment::class),
                $c->get(\App\Models\Programme::class),
                $c->get(\App\Models\ActivityLog::class)
            );
        }
    );
    // Registers AdminProgrammesController with Twig, Programme, and ActivityLog dependencies

    $container->set(
        \App\Controllers\AdminInterestsController::class,
        function (Container $c) {
            return new \App\Controllers\AdminInterestsController(
                $c->get(Environment::class),
                $c->get(\App\Models\StudentInterest::class),
                $c->get(\App\Models\ActivityLog::class)
            );
        }
    );
    // Registers AdminInterestsController with Twig, StudentInterest, and ActivityLog dependencies

    $container->set(
        \App\Controllers\AdminStaffController::class,
        function (Container $c) {
            return new \App\Controllers\AdminStaffController(
                $c->get(Environment::class),
                $c->get(\PDO::class)
            );
        }
    );
    // Registers AdminStaffController with Twig and PDO dependencies

    $container->set(
        \App\Controllers\StaffDashboardController::class,
        function (Container $c) {
            return new \App\Controllers\StaffDashboardController(
                $c->get(Environment::class),
                $c->get(\App\Models\Module::class),
                $c->get(\App\Models\Programme::class),
                $c->get(\App\Models\StaffMember::class)
            );
        }
    );
    // Registers StaffDashboardController with Twig, Module, Programme, and StaffMember dependencies

    // ======================================================================
    // Middleware Bindings
    // Registers middleware classes for authentication and staff authentication.
    // ======================================================================
    $container->set(
        \App\Middleware\AuthMiddleware::class,
        function (Container $c) {
            return new \App\Middleware\AuthMiddleware(
                $c->get('logger'),
                $c->get('app.base_path')
            );
        }
    );
    // Registers AuthMiddleware with logger dependency

    $container->set(
        \App\Middleware\StaffAuthMiddleware::class,
        function (Container $c) {
            return new \App\Middleware\StaffAuthMiddleware(
                $c->get('logger'),
                $c->get('app.base_path')
            );
        }
    );
    // Registers StaffAuthMiddleware with logger dependency
};
