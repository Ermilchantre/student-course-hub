<?php

/* ==================================================
   ADMIN DASHBOARD CONTROLLER
   --------------------------------------------------
   Purpose: Handles the admin dashboard functionality in a Slim 4 application.
   Functionality: Renders the dashboard with statistics, recent notifications, and base path for templates.
   Server-Side Role: Processes requests for the admin dashboard, fetching data and rendering the view.
   ==================================================
*/

namespace App\Controllers;

/* ==================================================
   IMPORTS
   --------------------------------------------------
   Purpose: Imports required PSR-7 interfaces and application dependencies.
   Functionality: Provides types for request/response handling and model/template dependencies.
   Server-Side Role: Ensures compatibility with Slim 4 and access to necessary services.
   Logic: Imports PSR-7 interfaces, Twig, and model classes for data operations.
*/
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use App\Models\Programme;
use App\Models\StudentInterest;
use App\Models\Notification;

/* ==================================================
   ADMIN DASHBOARD CONTROLLER CLASS
   --------------------------------------------------
   Purpose: Manages the admin dashboard view and data.
   Functionality: Fetches statistics and notifications, then renders the dashboard template.
   Server-Side Role: Orchestrates data retrieval and template rendering for the admin dashboard.
   Logic: Initializes dependencies, processes requests, and returns a rendered response.
*/
class AdminDashboardController
{
    /* ==================================================
       PROPERTY DEFINITION
       --------------------------------------------------
       Purpose: Stores dependencies for template rendering and data access.
       Functionality: Holds references to Twig, Programme, StudentInterest, and Notification models.
       Server-Side Role: Provides access to services for rendering and data operations.
       Logic: Declares private properties for dependency injection.
    */
    private $twig;
    private $programme;
    private $interest;
    private $notification;

    /* ==================================================
       CONSTRUCTOR
       --------------------------------------------------
       Purpose: Initializes the controller with required dependencies.
       Functionality: Assigns Twig environment and model instances to properties.
       Server-Side Role: Sets up the controller for handling dashboard requests.
       Logic: Stores injected dependencies for use in methods.
    */
    public function __construct(
        Environment $twig,
        Programme $programme,
        StudentInterest $interest,
        Notification $notification
    ) {
        // Assigns injected dependencies to class properties
        $this->twig = $twig;
        $this->programme = $programme;
        $this->interest = $interest;
        $this->notification = $notification;
    }

    /* ==================================================
       INDEX ACTION
       --------------------------------------------------
       Purpose: Handles the main dashboard request.
       Functionality: Fetches statistics and notifications, then renders the dashboard template.
       Server-Side Role: Processes the dashboard route, preparing and rendering data.
       Logic: Gathers stats, retrieves and formats notifications, and renders the Twig template.
    */
    public function index(Request $request, Response $response): Response
    {
        // Defines static and dynamic statistics for the dashboard
        $stats = [
            'students' => 1247, // Hardcoded student count
            'programmes' => count($this->programme->all()), // Counts all programmes
            'satisfaction' => '89%' // Hardcoded satisfaction rate
        ];

        // Fetches recent notifications (limited to 10)
        $recentNotifications = $this->notification->getRecentNotifications(10);
        // Formats notifications for display
        $formattedNotifications = $this->notification->formatNotificationsForDisplay($recentNotifications);

        // Defines a closure to retrieve the base_path attribute
        $basePath = function () use ($request) {
            return $request->getAttribute('base_path');
        };

        // Renders the dashboard template with data
        $response->getBody()->write(
            $this->twig->render(
                'admin/dashboard.twig',
                [
                    'stats' => $stats,
                    'recent_activities' => $formattedNotifications,
                    'base_path' => $basePath
                ]
            )
        );

        // Returns the response with the rendered template
        return $response;
    }
}
