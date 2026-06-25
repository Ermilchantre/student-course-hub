<?php

/* ==================================================
   AUTH MIDDLEWARE
   --------------------------------------------------
   Purpose: Secures routes by enforcing admin authentication in a Slim 4 application.
   Functionality: Checks for admin session, logs unauthorized attempts, redirects to login if needed, and adds a base_path attribute.
   Server-Side Role: Protects admin routes by ensuring only authenticated users can access them.
    Logic: Starts a session, checks admin status, logs unauthorized access, redirects if not authenticated, and passes the request to the next handler.
   ==================================================
*/

namespace App\Middleware;

/* ==================================================
   IMPORTS
   --------------------------------------------------
   Purpose: Imports required PSR-7 interfaces and logger for middleware functionality.
   Functionality: Provides type definitions for request, response, and logging.
   Server-Side Role: Ensures compatibility with Slim 4's PSR-7 standards and logging capabilities.
   Logic: Imports necessary interfaces for request handling and logging.
*/
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

/* ==================================================
   AUTH MIDDLEWARE CLASS
   --------------------------------------------------
   Purpose: Implements authentication middleware for admin routes.
   Functionality: Verifies admin session, logs unauthorized access, and adds base_path for templates.
   Server-Side Role: Acts as a gatekeeper for admin routes, ensuring secure access.
   Logic: Checks session status, redirects unauthorized users, and passes valid requests to the next handler.
*/
class AuthMiddleware
{
    /* ==================================================
       PROPERTY DEFINITION
       --------------------------------------------------
       Purpose: Stores the logger instance for tracking unauthorized access.
       Functionality: Holds a reference to the PSR-3 LoggerInterface for logging.
       Server-Side Role: Enables logging of security events.
       Logic: Initializes the logger via dependency injection.
    */
    private $logger;

    /* ==================================================
       CONSTRUCTOR
       --------------------------------------------------
       Purpose: Initializes the middleware with a logger dependency.
       Functionality: Assigns the provided logger to the class property.
       Server-Side Role: Sets up logging for unauthorized access attempts.
       Logic: Stores the logger instance for use in the middleware.
    */
    public function __construct(LoggerInterface $logger)
    {
        // Assigns the logger instance
        $this->logger = $logger;
    }

    /* ==================================================
       MIDDLEWARE LOGIC
       --------------------------------------------------
       Purpose: Processes requests to enforce admin authentication.
       Functionality: Checks session, logs unauthorized attempts, redirects if needed, and adds base_path.
       Server-Side Role: Ensures only authenticated admins access protected routes.
       Logic: Starts session, checks admin status, logs and redirects if unauthorized, or proceeds with the request.
    */
    public function __invoke(Request $request, Response $response, callable $next): Response
    {
        // Starts session if not already active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Checks if user is logged in as admin
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            // Logs unauthorized access attempt with IP and path
            $this->logger->warning(
                'Unauthorized admin access attempt',
                [
                    'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                    'path' => $request->getUri()->getPath()
                ]
            );

            // Redirects to admin login page with 302 status
            return $response->withHeader('Location', '/admin/login')
                ->withStatus(302);
        }

        // Adds base_path attribute for Twig templates
        $request = $request->withAttribute('base_path', '/student-course-hub/public');

        // Passes the request to the next middleware or route handler
        return $next($request, $response);
    }
}
