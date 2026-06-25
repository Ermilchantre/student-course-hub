<?php

/* ==================================================
   STAFF DASHBOARD CONTROLLER
   --------------------------------------------------
   Purpose: Manages the staff dashboard functionality in a Slim 4 application.
   Functionality: Handles rendering of the staff dashboard, retrieving modules and programmes associated with a staff member, and fetching student interests.
   Server-Side Role: Processes HTTP requests for staff dashboard operations and manages database interactions.
   ==================================================
*/

namespace App\Controllers;

/* ==================================================
   IMPORTS
   --------------------------------------------------
   Purpose: Imports required PSR-7 interfaces and application dependencies.
   Functionality: Provides types for request/response handling, Twig for templating, and models for database operations.
   Server-Side Role: Ensures compatibility with Slim 4 and enables data retrieval and rendering.
   Logic: Imports PSR-7 interfaces, Twig, and models for Module, Programme, and StaffMember.
*/
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use App\Models\Module;
use App\Models\Programme;
use App\Models\StaffMember;
use App\Helpers\UrlHelper;

/* ==================================================
   STAFF DASHBOARD CONTROLLER CLASS
   --------------------------------------------------
   Purpose: Manages staff dashboard operations in the application.
   Functionality: Renders the dashboard view, retrieves modules and programmes for a staff member, and provides student interest data.
   Server-Side Role: Handles staff-specific HTTP requests and database interactions.
   Logic: Initializes dependencies and defines methods for dashboard display, module retrieval, programme retrieval, and student interest querying.
*/
class StaffDashboardController
{
    /* ==================================================
       PROPERTY DEFINITION
       --------------------------------------------------
       Purpose: Stores dependencies for rendering and database operations.
       Functionality: Holds references to Twig environment and models for Module, Programme, and StaffMember.
       Server-Side Role: Provides access to template rendering and database services.
       Logic: Declares private properties for dependency injection.
    */
    private $twig;
    private $module;
    private $programme;
    private $staffMember;

    /* ==================================================
       CONSTRUCTOR
       --------------------------------------------------
       Purpose: Initializes the controller with required dependencies.
       Functionality: Assigns Twig and model instances for use in methods.
       Server-Side Role: Sets up the controller for staff dashboard requests.
       Logic: Stores injected Twig, Module, Programme, and StaffMember dependencies.
    */
    public function __construct(Environment $twig, Module $module, Programme $programme, StaffMember $staffMember)
    {
        // Assigns injected dependencies to class properties
        $this->twig = $twig;
        $this->module = $module;
        $this->programme = $programme;
        $this->staffMember = $staffMember;
    }

    /* ==================================================
       INDEX ACTION
       --------------------------------------------------
       Purpose: Renders the staff dashboard with relevant data.
       Functionality: Retrieves staff member data, led modules, associated programmes, and student interests, then displays the dashboard template.
       Server-Side Role: Handles GET requests for the staff dashboard and prepares data for display.
       Logic: Verifies staff session, fetches data, logs debug information, and renders the Twig template.
    */
    public function index(Request $request, Response $response): Response
    {
        /* Initializes session if not started
           - Purpose: Ensures session is active to access staff ID.
           - Functionality: Starts session if not already active.
           - Server-Side Role: Provides access to session data for authentication.
           - Logic: Checks session status and starts it if necessary.
        */
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        /* Retrieves staff ID from session
           - $staffUserId = $_SESSION['staff_id'] ?? 0: Extracts staff ID from session data.
           - Purpose: Identifies the logged-in staff member.
           - Server-Side Role: Authenticates the user for dashboard access.
           - Logic: Defaults to 0 if staff_id is not set in session.
        */
        $staffUserId = $_SESSION['staff_id'] ?? 0;

        /* Logs session information for debugging
           - Purpose: Records session data for troubleshooting.
           - Functionality: Logs staff ID and session data to error log.
           - Server-Side Role: Assists in debugging session-related issues.
           - Logic: Uses error_log to capture session details.
        */
        error_log("Staff Dashboard - Session staff_id: " . $staffUserId);
        error_log("Staff Dashboard - Session data: " . print_r($_SESSION, true));

        /* Retrieves staff member data
           - $staffMember = $this->staffMember->findByUserId($staffUserId): Fetches staff details based on user ID.
           - Purpose: Retrieves staff member information for dashboard display.
           - Server-Side Role: Queries the database for staff data.
           - Logic: Uses StaffMember model to find record; redirects to login if not found.
        */
        $staffMember = $this->staffMember->findByUserId($staffUserId);

        /* Logs staff member data for debugging
           - Purpose: Records staff member data for troubleshooting.
           - Functionality: Logs retrieved staff data to error log.
           - Server-Side Role: Assists in debugging staff data retrieval.
           - Logic: Uses error_log to capture staff member details.
        */
        error_log("Staff Dashboard - Staff member data: " . print_r($staffMember, true));

        /* Redirects if staff member not found
           - Purpose: Ensures only authenticated staff access the dashboard.
           - Functionality: Redirects to login page if staff member data is missing.
           - Server-Side Role: Enforces authentication and prevents unauthorized access.
           - Logic: Returns a 302 redirect to /staff/login if no staff member is found.
        */
        if (!$staffMember) {
            error_log("Staff Dashboard - No staff member found for user_id: " . $staffUserId);
            return $response->withHeader('Location', UrlHelper::path($request, '/staff/login'))
                ->withStatus(302);
        }

        /* Retrieves modules led by the staff member
           - $ledModules = $this->module->findByLeaderId($staffMember['id']): Fetches modules where the staff member is the leader.
           - Purpose: Collects module data for display on the dashboard.
           - Server-Side Role: Queries the database for module information.
           - Logic: Uses Module model to find modules associated with the staff memberÃ¢â‚¬â„¢s ID.
        */
        $ledModules = $this->module->findByLeaderId($staffMember['id']);

        /* Logs modules data for debugging
           - Purpose: Records module data for troubleshooting.
           - Functionality: Logs count and details of led modules to error log.
           - Server-Side Role: Assists in debugging module retrieval.
           - Logic: Uses error_log to capture module data.
        */
        error_log("Staff Dashboard - Led modules count: " . count($ledModules));
        error_log("Staff Dashboard - Led modules data: " . print_r($ledModules, true));

        /* Retrieves programmes with staffÃ¢â‚¬â„¢s modules
           - $programmesWithModules = $this->programme->findByStaffMember($staffMember['id']): Fetches programmes containing modules taught by the staff member.
           - Purpose: Provides programme data for dashboard display.
           - Server-Side Role: Queries the database for programme information.
           - Logic: Uses Programme model to find relevant programmes.
        */
        $programmesWithModules = $this->programme->findByStaffMember($staffMember['id']);

        /* Logs programmes data for debugging
           - Purpose: Records programme data for troubleshooting.
           - Functionality: Logs count and details of programmes to error log.
           - Server-Side Role: Assists in debugging programme retrieval.
           - Logic: Uses error_log to capture programme data.
        */
        error_log("Staff Dashboard - Programmes count: " . count($programmesWithModules));
        error_log("Staff Dashboard - Programmes data: " . print_r($programmesWithModules, true));

        /* Retrieves student interests for staffÃ¢â‚¬â„¢s modules
           - $studentInterests = $this->getStudentInterestsForStaff($staffUserId): Fetches student interests related to the staff memberÃ¢â‚¬â„¢s modules.
           - Purpose: Collects student interest data for dashboard display.
           - Server-Side Role: Queries the database for interest information.
           - Logic: Calls private method to fetch interests with joined programme and module data.
        */
        $studentInterests = $this->getStudentInterestsForStaff($staffUserId);

        /* Renders the staff dashboard template
           - Purpose: Displays the staff dashboard with staff, module, programme, and interest data.
           - Functionality: Passes staff member, modules, programmes, interests, and base path to the Twig template.
           - Server-Side Role: Combines all data and renders the final HTML output.
           - Logic: Uses Twig to render the dashboard.twig template with all prepared data.
        */
        $response->getBody()->write(
            $this->twig->render(
                'staff/dashboard.twig',
                [
                    'base_path' => fn() => $request->getAttribute('base_path'),
                    'staff_member' => $staffMember,
                    'led_modules' => $ledModules,
                    'programmes_with_modules' => $programmesWithModules,
                    'student_interests' => $studentInterests
                ]
            )
        );
        return $response;
    }

    /* ==================================================
       MY MODULES ACTION
       --------------------------------------------------
       Purpose: Retrieves modules led by the staff member in JSON format.
       Functionality: Returns a JSON response with modules associated with the staff member.
       Server-Side Role: Handles AJAX requests for module data.
       Logic: Verifies staff session, fetches modules, and returns JSON response.
    */
    public function myModules(Request $request, Response $response): Response
    {
        /* Initializes session if not started
           - Purpose: Ensures session is active to access staff ID.
           - Functionality: Starts session if not already active.
           - Server-Side Role: Provides access to session data for authentication.
           - Logic: Checks session status and starts it if necessary.
        */
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        /* Retrieves staff ID and member data
           - $staffUserId = $_SESSION['staff_id'] ?? 0: Extracts staff ID from session.
           - $staffMember = $this->staffMember->findByUserId($staffUserId): Fetches staff details.
           - Purpose: Verifies the logged-in staff member.
           - Server-Side Role: Ensures authenticated access to module data.
           - Logic: Defaults to 0 if staff_id is not set; redirects to login if staff not found.
        */
        $staffUserId = $_SESSION['staff_id'] ?? 0;
        $staffMember = $this->staffMember->findByUserId($staffUserId);

        /* Redirects if staff member not found
           - Purpose: Prevents unauthorized access to module data.
           - Functionality: Redirects to login page if staff member data is missing.
           - Server-Side Role: Enforces authentication.
           - Logic: Returns a 302 redirect to /staff/login if no staff member is found.
        */
        if (!$staffMember) {
            return $response->withHeader('Location', UrlHelper::path($request, '/staff/login'))
                ->withStatus(302);
        }

        /* Retrieves modules led by the staff member
           - $modules = $this->module->findByLeaderId($staffMember['id']): Fetches modules where the staff member is the leader.
           - Purpose: Collects module data for JSON response.
           - Server-Side Role: Queries the database for module information.
           - Logic: Uses Module model to find modules associated with the staff memberÃ¢â‚¬â„¢s ID.
        */
        $modules = $this->module->findByLeaderId($staffMember['id']);

        /* Returns JSON response with modules
           - Purpose: Provides module data in JSON format for AJAX requests.
           - Functionality: Encodes module data with a success status.
           - Server-Side Role: Delivers structured data to the client.
           - Logic: Writes JSON response with status and module data.
        */
        $response->getBody()->write(
            json_encode(
                [
                    'status' => 'success',
                    'data' => $modules
                ]
            )
        );
        return $response->withHeader('Content-Type', 'application/json');
    }

    /* ==================================================
       MY PROGRAMMES ACTION
       --------------------------------------------------
       Purpose: Retrieves programmes associated with the staff member in JSON format.
       Functionality: Returns a JSON response with programmes containing modules taught by the staff member.
       Server-Side Role: Handles AJAX requests for programme data.
       Logic: Verifies staff session, fetches programmes, and returns JSON response.
    */
    public function myProgrammes(Request $request, Response $response): Response
    {
        /* Initializes session if not started
           - Purpose: Ensures session is active to access staff ID.
           - Functionality: Starts session if not already active.
           - Server-Side Role: Provides access to session data for authentication.
           - Logic: Checks session status and starts it if necessary.
        */
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        /* Retrieves staff ID and member data
           - $staffUserId = $_SESSION['staff_id'] ?? 0: Extracts staff ID from session.
           - $staffMember = $this->staffMember->findByUserId($staffUserId): Fetches staff details.
           - Purpose: Verifies the logged-in staff member.
           - Server-Side Role: Ensures authenticated access to programme data.
           - Logic: Defaults to 0 if staff_id is not set; redirects to login if staff not found.
        */
        $staffUserId = $_SESSION['staff_id'] ?? 0;
        $staffMember = $this->staffMember->findByUserId($staffUserId);

        /* Redirects if staff member not found
           - Purpose: Prevents unauthorized access to programme data.
           - Functionality: Redirects to login page if staff member data is missing.
           - Server-Side Role: Enforces authentication.
           - Logic: Returns a 302 redirect to /staff/login if no staff member is found.
        */
        if (!$staffMember) {
            return $response->withHeader('Location', UrlHelper::path($request, '/staff/login'))
                ->withStatus(302);
        }

        /* Retrieves programmes associated with the staff member
           - $programmes = $this->programme->findByStaffMember($staffMember['id']): Fetches programmes with modules taught by the staff.
           - Purpose: Collects programme data for JSON response.
           - Server-Side Role: Queries the database for programme information.
           - Logic: Uses Programme model to find relevant programmes.
        */
        $programmes = $this->programme->findByStaffMember($staffMember['id']);

        /* Returns JSON response with programmes
           - Purpose: Provides programme data in JSON format for AJAX requests.
           - Functionality: Encodes programme data with a success status.
           - Server-Side Role: Delivers structured data to the client.
           - Logic: Writes JSON response with status and programme data.
        */
        $response->getBody()->write(
            json_encode(
                [
                    'status' => 'success',
                    'data' => $programmes
                ]
            )
        );
        return $response->withHeader('Content-Type', 'application/json');
    }

    /* ==================================================
       GET STUDENT INTERESTS FOR STAFF (PRIVATE METHOD)
       --------------------------------------------------
       Purpose: Retrieves student interests related to the staff memberÃ¢â‚¬â„¢s modules.
       Functionality: Queries the database for interests linked to programmes and modules led by the staff member.
       Server-Side Role: Provides data for dashboard display of student interests.
       Logic: Executes a joined SQL query to fetch interest data with programme and module titles.
    */
    private function getStudentInterestsForStaff($staffUserId)
    {
        try {
            /* Establishes database connection
               - $db = $this->getDatabaseConnection(): Creates a PDO connection using configuration.
               - Purpose: Provides database access for querying student interests.
               - Server-Side Role: Enables direct database queries.
               - Logic: Calls private method to load database configuration and create PDO instance.
            */
            $db = $this->getDatabaseConnection();

            /* Prepares and executes SQL query
               - Purpose: Fetches student interests linked to the staff memberÃ¢â‚¬â„¢s modules.
               - Functionality: Joins student_interests, programmes, modules, and staff_members tables to retrieve relevant data.
               - Server-Side Role: Queries the database for comprehensive interest data.
               - Logic: Uses prepared statement to prevent SQL injection and fetches all matching records.
            */
            $stmt = $db->prepare(
                'SELECT DISTINCT si.*, p.title AS programme_title, m.title AS module_title ' .
                'FROM student_interests si ' .
                'JOIN programmes p ON si.programme_id = p.id ' .
                'JOIN modules m ON m.programme_id = p.id ' .
                'JOIN staff_members sm ON m.module_leader_id = sm.id ' .
                'WHERE sm.user_id = ? ' .
                'ORDER BY si.created_at DESC'
            );
            $stmt->execute([$staffUserId]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            /* Handles query errors
               - Purpose: Manages database errors gracefully.
               - Functionality: Logs error and returns an empty array.
               - Server-Side Role: Ensures robust error handling.
               - Logic: Logs exception message and returns empty array to prevent crashes.
            */
            error_log("Error getting student interests for staff: " . $e->getMessage());
            return [];
        }
    }

    /* ==================================================
       GET DATABASE CONNECTION (PRIVATE METHOD)
       --------------------------------------------------
       Purpose: Creates a PDO database connection.
       Functionality: Loads database configuration and establishes a PDO instance.
       Server-Side Role: Provides database connectivity for custom queries.
       Logic: Includes configuration file and returns a PDO instance with specified settings.
    */
    private function getDatabaseConnection()
    {
        /* Loads database configuration and creates PDO instance
           - $config = include __DIR__ . '/../../config/database.php': Includes database configuration file.
           - Purpose: Provides database connection details for querying.
           - Server-Side Role: Enables direct database access.
           - Logic: Returns a new PDO instance with configured DSN, username, password, and options.
        */
        $config = include __DIR__ . '/../../config/database.php';
        return new \PDO(
            $config['dsn'],
            $config['username'],
            $config['password'],
            $config['options']
        );
    }
}
