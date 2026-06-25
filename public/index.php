<?php

/* ==================================================
   SLIM 4 APPLICATION BOOTSTRAP
   --------------------------------------------------
   Purpose: Initializes the Slim 4 application for handling HTTP requests.
   Functionality: Sets up error reporting, loads environment variables, configures the dependency container, and defines middleware and routes.
   Server-Side Role: Acts as the entry point for the application, routing requests and rendering responses.
    Logic: Includes necessary files, sets up error handling, and runs the application.
   ==================================================
*/

/* ==================================================
   ERROR REPORTING CONFIGURATION
   --------------------------------------------------
   Purpose: Enables comprehensive error reporting for debugging.
   Functionality: Configures PHP to display all errors and startup issues.
   Server-Side Role: Facilitates development by exposing errors in the application.
   Logic: Sets PHP ini directives to show errors and enable full error reporting.
*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ==================================================
   IMPORTS AND DEPENDENCIES
   --------------------------------------------------
   Purpose: Imports required classes and libraries for the application.
   Functionality: Loads PSR-7 interfaces, Slim framework, Twig templating, and dependency injection container.
   Server-Side Role: Provides necessary components for request handling, routing, and templating.
   Logic: Uses PHP's use statements to import classes and includes the Composer autoloader.
*/
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use DI\Container;

require_once __DIR__ . '/../vendor/autoload.php';

/* ==================================================
   ENVIRONMENT VARIABLES LOADING
   --------------------------------------------------
   Purpose: Loads environment variables from a .env file.
   Functionality: Uses Dotenv to load configuration settings like API keys.
   Server-Side Role: Provides secure access to environment-specific settings.
   Logic: Checks for the .env file and loads variables if it exists.
*/
if (file_exists(__DIR__ . '/../.env')) {
    // Creates an immutable Dotenv instance
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    // Loads environment variables
    $dotenv->load();
}

$appConfig = require __DIR__ . '/../config/app.php';
$basePath = $appConfig['base_path'];

/* ==================================================
   APPLICATION INITIALIZATION
   --------------------------------------------------
   Purpose: Sets up the Slim 4 application with dependencies and middleware.
   Functionality: Initializes the container, creates the app, adds middleware, and loads routes.
   Server-Side Role: Configures the application to handle HTTP requests and responses.
   Logic: Wraps setup in a try-catch block to handle initialization errors gracefully.
*/
try {
    // Creates a new dependency injection container
    $container = new Container();
    $container->set('app.base_path', $basePath);

    /* ==================================================
       DEPENDENCY LOADING
       --------------------------------------------------
       Purpose: Loads application dependencies into the container.
       Functionality: Includes and executes the dependencies.php file to register services.
       Server-Side Role: Provides dependency injection for services like Twig and database connections.
       Logic: Invokes the dependencies.php file, passing the container for configuration.
    */
    (include __DIR__ . '/../app/dependencies.php')($container);

    // Sets the container for the Slim application
    AppFactory::setContainer($container);
    // Creates the Slim application instance
    $app = AppFactory::create();
    $app->setBasePath($basePath);

    /* ==================================================
       ERROR MIDDLEWARE
       --------------------------------------------------
       Purpose: Configures error handling for the application.
       Functionality: Adds Slim's error middleware to display errors during development.
       Server-Side Role: Ensures errors are caught and displayed appropriately.
       Logic: Enables error middleware with display, logging, and detailed output.
    */
    $app->addErrorMiddleware(true, true, true);

    /* ==================================================
       BASE PATH MIDDLEWARE
       --------------------------------------------------
       Purpose: Adds a global middleware to set the base path attribute.
       Functionality: Attaches an empty base path to all requests for compatibility.
       Server-Side Role: Ensures consistent base path handling across routes.
       Logic: Adds a middleware that sets the base_path attribute on the request.
    */
    $app->add(
        function (Request $request, RequestHandlerInterface $handler) use ($basePath): Response {
            $request = $request->withAttribute('base_path', $basePath);
            return $handler->handle($request);
        }
    );

    /* ==================================================
       ROUTE LOADING
       --------------------------------------------------
       Purpose: Defines application routes for handling HTTP requests.
       Functionality: Includes and executes the routes.php file to register routes.
       Server-Side Role: Maps URLs to controller actions or handlers.
       Logic: Invokes the routes.php file, passing the Slim app instance.
    */
    (include __DIR__ . '/../app/routes.php')($app);

    // Runs the Slim application
    $app->run();
} catch (Exception $e) {
    /* ==================================================
       EXCEPTION HANDLING
       --------------------------------------------------
       Purpose: Handles application initialization errors.
       Functionality: Displays detailed error information for debugging.
       Server-Side Role: Provides clear error feedback during development.
       Logic: Outputs the error message, file, line, and stack trace.
    */
    echo "Error: " . $e->getMessage();
    echo "<br>File: " . $e->getFile();
    echo "<br>Line: " . $e->getLine();
    echo "<br>Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
