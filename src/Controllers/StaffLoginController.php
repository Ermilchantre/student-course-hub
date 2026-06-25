<?php

/* ==================================================
   STAFF LOGIN CONTROLLER
   --------------------------------------------------
   Purpose: Manages staff authentication functionality in a Slim 4 application.
   Functionality: Handles rendering the staff login form, processing login requests, and managing logout operations.
   Server-Side Role: Processes HTTP requests for staff authentication and session management.
   ==================================================
*/

namespace App\Controllers;

/* ==================================================
   IMPORTS
   --------------------------------------------------
   Purpose: Imports required PSR-7 interfaces and application dependencies.
   Functionality: Provides types for request/response handling, Twig for templating, User model for database operations, and SecurityHelper for input sanitization.
   Server-Side Role: Ensures compatibility with Slim 4 and secure data handling.
   Logic: Imports PSR-7 interfaces, Twig, User model, and SecurityHelper.
*/
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use App\Models\User;
use App\Helpers\SecurityHelper;
use App\Helpers\UrlHelper;

/* ==================================================
   STAFF LOGIN CONTROLLER CLASS
   --------------------------------------------------
   Purpose: Manages staff login, logout, and authentication processes.
   Functionality: Renders the login form, authenticates staff credentials, manages sessions, and handles logout.
   Server-Side Role: Handles staff authentication requests and session management.
   Logic: Initializes dependencies and defines methods for login form display, login processing, logout, and authentication.
*/
class StaffLoginController
{
    /* ==================================================
       PROPERTY DEFINITION
       --------------------------------------------------
       Purpose: Stores dependencies and authentication data.
       Functionality: Holds references to Twig environment, User model, and temporary storage for user/staff IDs and role.
       Server-Side Role: Provides access to template rendering, database services, and authentication data.
       Logic: Declares private properties for dependency injection and authentication state.
    */
    private $twig;
    private $user;
    private $currentUserId;
    private $currentUserRole;
    private $currentStaffId;

    /* ==================================================
       CONSTRUCTOR
       --------------------------------------------------
       Purpose: Initializes the controller with required dependencies.
       Functionality: Assigns Twig and User model instances for use in methods.
       Server-Side Role: Sets up the controller for staff authentication requests.
       Logic: Stores injected Twig and User dependencies.
    */
    public function __construct(Environment $twig, User $user)
    {
        // Assigns injected dependencies to class properties
        $this->twig = $twig;
        $this->user = $user;
    }

    /* ==================================================
       SHOW LOGIN FORM ACTION
       --------------------------------------------------
       Purpose: Renders the staff login form.
       Functionality: Displays the login template with base path and staff login indicator.
       Server-Side Role: Serves the staff login view.
       Logic: Renders the Twig template with base_path and is_staff_login attributes.
    */
    public function showLoginForm(Request $request, Response $response): Response
    {
        /* Renders the login form template
           - Purpose: Displays the form for staff to enter login credentials.
           - Functionality: Passes base path and staff login flag to the Twig template.
           - Server-Side Role: Provides the login interface for staff users.
           - Logic: Uses Twig to render the admin-login.twig template with necessary attributes.
        */
        $response->getBody()->write($this->twig->render(
            'admin/admin-login.twig',
            [
                'base_path' => fn() => $request->getAttribute('base_path'),
                'is_staff_login' => true
            ]
        ));
        return $response;
    }

    /* ==================================================
       LOGIN ACTION
       --------------------------------------------------
       Purpose: Processes staff login requests.
       Functionality: Sanitizes input, authenticates credentials, sets session data, and handles AJAX or standard responses.
       Server-Side Role: Manages secure staff authentication and session initialization.
       Logic: Validates input, authenticates user, sets session, and returns JSON or redirect response.
    */
    public function login(Request $request, Response $response): Response
    {
        /* Retrieves and sanitizes form data
           - $data = $request->getParsedBody(): Extracts email and password from the request body.
           - Purpose: Captures user input for authentication.
           - Server-Side Role: Provides sanitized data for secure processing.
           - Logic: Uses SecurityHelper to sanitize email and password inputs.
        */
        $data = $request->getParsedBody();
        $email = SecurityHelper::sanitizeEmail($data['email'] ?? '');
        $password = SecurityHelper::sanitizeString($data['password'] ?? '');

        /* Checks for AJAX request
           - $isAjax = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest': Determines if the request is AJAX.
           - Purpose: Enables conditional response handling for AJAX or standard requests.
           - Server-Side Role: Supports flexible response formats based on client type.
           - Logic: Checks the X-Requested-With header for XMLHttpRequest.
        */
        $isAjax = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        /* Validates input fields
           - Purpose: Ensures required fields are provided.
           - Functionality: Checks for empty email or password and returns appropriate error response.
           - Server-Side Role: Prevents processing of invalid input.
           - Logic: Returns JSON error for AJAX or redirects with error query for standard requests.
        */
        if (empty($email) || empty($password)) {
            if ($isAjax) {
                $response->getBody()->write(
                    json_encode([
                        'success' => false,
                        'message' => 'Email and password are required.'
                    ])
                );
                return $response->withHeader('Content-Type', 'application/json');
            }
            return $response->withHeader('Location', UrlHelper::path($request, '/staff/login?error=invalid_credentials'))
                ->withStatus(302);
        }

        /* Authenticates staff credentials
           - $authenticated = $this->authenticateStaff($email, $password): Verifies email and password.
           - Purpose: Validates staff credentials against stored data.
           - Server-Side Role: Ensures secure authentication.
           - Logic: Calls private authenticateStaff method to verify credentials.
        */
        $authenticated = $this->authenticateStaff($email, $password);

        if ($authenticated) {
            /* Initializes session and stores user data
               - Purpose: Sets up session for authenticated staff.
               - Functionality: Starts session if needed and stores staff data.
               - Server-Side Role: Manages session creation for authenticated users.
               - Logic: Sets session variables for staff login status, email, IDs, and role.
            */
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['staff_logged_in'] = true;
            $_SESSION['staff_email'] = $email;
            $_SESSION['staff_id'] = $this->currentUserId;
            $_SESSION['staff_member_id'] = $this->currentStaffId;
            $_SESSION['user_role'] = 'staff';

            /* Handles response based on request type
               - Purpose: Returns appropriate response for AJAX or standard requests.
               - Functionality: Returns JSON success with redirect URL for AJAX or redirects to dashboard for standard requests.
               - Server-Side Role: Provides flexible response handling.
               - Logic: Checks isAjax to determine response format.
            */
            if ($isAjax) {
                $response->getBody()->write(
                    json_encode([
                        'success' => true,
                        'message' => 'Login successful!',
                        'redirect' => UrlHelper::path($request, '/staff/dashboard')
                    ])
                );
                return $response->withHeader('Content-Type', 'application/json');
            }
            return $response->withHeader('Location', UrlHelper::path($request, '/staff/dashboard'))
                ->withStatus(302);
        } else {
            /* Handles failed authentication
               - Purpose: Provides feedback for invalid credentials.
               - Functionality: Returns JSON error for AJAX or redirects with error query for standard requests.
               - Server-Side Role: Informs user of authentication failure.
               - Logic: Checks isAjax to determine response format.
            */
            if ($isAjax) {
                $response->getBody()->write(
                    json_encode([
                        'success' => false,
                        'message' => 'Invalid email or password.'
                    ])
                );
                return $response->withHeader('Content-Type', 'application/json');
            }
            return $response->withHeader('Location', UrlHelper::path($request, '/staff/login?error=invalid_credentials'))
                ->withStatus(302);
        }
    }

    /* ==================================================
       LOGOUT ACTION
       --------------------------------------------------
       Purpose: Handles staff logout requests.
       Functionality: Clears session data and redirects to the login page.
       Server-Side Role: Manages session termination for staff users.
       Logic: Starts session if needed, clears data, and redirects to login page.
    */
    public function logout(Request $request, Response $response): Response
    {
        /* Initializes session if not started
           - Purpose: Ensures session is active to clear data.
           - Functionality: Starts session if not already active.
           - Server-Side Role: Provides access to session for termination.
           - Logic: Checks session status and starts it if necessary.
        */
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        /* Clears session data
           - Purpose: Terminates the staff session.
           - Functionality: Unsets all session variables and destroys the session.
           - Server-Side Role: Ensures secure logout by clearing session data.
           - Logic: Uses session_unset and session_destroy to clear session.
        */
        session_unset();
        session_destroy();

        /* Redirects to login page
           - Purpose: Returns user to the login page after logout.
           - Functionality: Issues a 302 redirect to /staff/login.
           - Server-Side Role: Handles navigation post-logout.
           - Logic: Sets Location header for redirect.
        */
        return $response->withHeader('Location', UrlHelper::path($request, '/staff/login'))
            ->withStatus(302);
    }

    /* ==================================================
       AUTHENTICATE STAFF (PRIVATE METHOD)
       --------------------------------------------------
       Purpose: Verifies staff credentials against the database.
       Functionality: Queries the database for a staff user and verifies the provided password.
       Server-Side Role: Provides secure authentication logic.
       Logic: Fetches user data, verifies password, and stores authentication details.
    */
    private function authenticateStaff(string $email, string $password): bool
    {
        try {
            /* Establishes database connection
               - $db = $this->getDatabaseConnection(): Creates a PDO connection using configuration.
               - Purpose: Provides database access for authentication query.
               - Server-Side Role: Enables secure database querying.
               - Logic: Calls private method to create PDO instance.
            */
            $db = $this->getDatabaseConnection();

            /* Queries user data for staff
               - Purpose: Retrieves user with staff role and active status.
               - Functionality: Joins users and staff_members tables to fetch user ID, password hash, role, and staff ID.
               - Server-Side Role: Verifies user existence and credentials.
               - Logic: Uses prepared statement to prevent SQL injection and fetches user data.
            */
            $stmt = $db->prepare(
                "SELECT u.id, u.password_hash, u.role, u.is_active, sm.id as staff_id
                FROM users u
                LEFT JOIN staff_members sm ON u.id = sm.user_id
                WHERE u.email = ? AND u.role = 'staff' AND u.is_active = TRUE"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            /* Checks if user exists
               - Purpose: Ensures a valid staff user is found.
               - Functionality: Returns false if no user is found.
               - Server-Side Role: Prevents authentication of non-existent or non-staff users.
               - Logic: Returns false if query returns no results.
            */
            if (!$user) {
                return false;
            }

            /* Verifies password and stores authentication data
               - Purpose: Confirms password and sets user/staff data.
               - Functionality: Verifies password hash and stores user ID, role, and staff ID.
               - Server-Side Role: Ensures secure password verification and data storage.
               - Logic: Uses password_verify to check password; sets class properties if valid.
            */
            if (password_verify($password, $user['password_hash'])) {
                $this->currentUserId = $user['id'];
                $this->currentUserRole = $user['role'];
                $this->currentStaffId = $user['staff_id'] ?? null;
                return true;
            }

            return false;
        } catch (\Exception $e) {
            /* Handles authentication errors
               - Purpose: Manages database or query errors gracefully.
               - Functionality: Logs error and returns false.
               - Server-Side Role: Ensures robust error handling.
               - Logic: Logs exception message and prevents authentication on error.
            */
            error_log("Staff authentication error: " . $e->getMessage());
            return false;
        }
    }

    /* ==================================================
       GET DATABASE CONNECTION (PRIVATE METHOD)
       --------------------------------------------------
       Purpose: Creates a PDO database connection.
       Functionality: Loads database configuration and establishes a PDO instance.
       Server-Side Role: Provides database connectivity for authentication queries.
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
