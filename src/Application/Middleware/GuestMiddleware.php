<?php

/* ==================================================
   GUEST MIDDLEWARE
   --------------------------------------------------
   Purpose: Middleware to handle guest-specific request modifications in a Slim 4 application.
   Functionality: Adds a base_path attribute to HTTP requests for guest routes.
   Server-Side Role: Modifies incoming requests to include path context for guest access.

   ==================================================
*/

namespace App\Middleware;

/* ==================================================
   IMPORTS
   --------------------------------------------------
   Purpose: Imports required PSR-7 interfaces for request and response handling.
   Functionality: Provides type definitions for middleware parameters.
   Server-Side Role: Ensures compatibility with Slim 4's PSR-7 compliant middleware.
   Logic: Imports ServerRequestInterface and ResponseInterface for type safety.
*/
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/* ==================================================
   GUEST MIDDLEWARE CLASS
   --------------------------------------------------
   Purpose: Defines middleware for guest-specific request processing.
   Functionality: Adds a base_path attribute to requests and passes them to the next handler.
   Server-Side Role: Enhances request objects for guest routes in the application pipeline.
   Logic: Implements the __invoke method to modify the request and delegate to the next middleware.
*/
class GuestMiddleware
{
    public function __invoke(Request $request, Response $response, callable $next): Response
    {
        // Adds base_path attribute to the request
        $request = $request->withAttribute('base_path', '/student-course-hub/public');
        // Passes the modified request to the next middleware or route handler
        return $next($request, $response);
    }
}
