<?php

/* ==================================================
   ADMIN LOGIN CONTROLLER
   --------------------------------------------------
   Purpose: Manages admin and staff login, logout, and password reset functionality in a Slim 4 application.
   Functionality: Handles login form rendering, authentication, session management, password reset requests, and email notifications.
   Server-Side Role: Processes authentication-related requests and renders corresponding views.
   ==================================================
*/

namespace App\Controllers;

/* ==================================================
   IMPORTS
   --------------------------------------------------
   Purpose: Imports required PSR-7 interfaces and application dependencies.
   Functionality: Provides types for request/response handling, Twig, models, and SendGrid for email.
   Server-Side Role: Ensures compatibility with Slim 4 and access to necessary services.
   Logic: Imports PSR-7 interfaces, Twig, User model, SecurityHelper, and SendGrid libraries.
*/
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use App\Models\User;
use App\Helpers\SecurityHelper;
use App\Helpers\UrlHelper;
use SendGrid;
use SendGrid\Mail\Mail;

/* ==================================================
   ADMIN LOGIN CONTROLLER CLASS
   --------------------------------------------------
   Purpose: Manages authentication-related functionality for admin and staff users.
   Functionality: Renders login/reset forms, authenticates users, manages sessions, and sends password reset emails.
   Server-Side Role: Handles authentication workflows and user session management.
   Logic: Initializes dependencies and defines methods for login, logout, and password reset.
*/
class AdminLoginController
{
    /* ==================================================
       PROPERTY DEFINITION
       --------------------------------------------------
       Purpose: Stores dependencies and user session data.
       Functionality: Holds references to Twig, User model, and current user details.
       Server-Side Role: Provides access to services and stores authentication state.
       Logic: Declares private properties for dependency injection and user data.
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
       Functionality: Assigns Twig environment and User model instances.
       Server-Side Role: Sets up the controller for handling authentication requests.
       Logic: Stores injected dependencies for use in methods.
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
       Purpose: Renders the admin login form.
       Functionality: Displays the login template with base path for assets.
       Server-Side Role: Serves the login page view for admin/staff users.
       Logic: Renders the Twig template with base_path attribute.
    */
    public function showLoginForm(Request $request, Response $response): Response
    {
        // Renders the login form template
        $response->getBody()->write(
            $this->twig->render(
                'admin/admin-login.twig',
                [
                    // Provides base_path for template asset paths
                    'base_path' => fn() => $request->getAttribute('base_path')
                ]
            )
        );
        return $response;
    }

    /* ==================================================
       LOGIN ACTION
       --------------------------------------------------
       Purpose: Handles user login requests.
       Functionality: Validates input, authenticates users, sets session data, and redirects or returns JSON.
       Server-Side Role: Processes login attempts for admin/staff users.
       Logic: Checks for AJAX, validates input, authenticates, sets session, and responds accordingly.
    */
    public function login(Request $request, Response $response): Response
    {
        // Retrieves and sanitizes form data
        $data = $request->getParsedBody();
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        // Checks if request is AJAX
        $isAjax = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        // Validates input
        if (empty($email) || empty($password)) {
            if ($isAjax) {
                // Returns JSON error for AJAX requests
                $response->getBody()->write(
                    json_encode(
                        [
                            'success' => false,
                            'message' => 'Email and password are required.'
                        ]
                    )
                );
                return $response->withHeader('Content-Type', 'application/json');
            }

            // Redirects with error for non-AJAX requests
            return $response->withHeader('Location', UrlHelper::path($request, '/admin/login?error=invalid_credentials'))
                ->withStatus(302);
        }

        // Authenticates user
        $authenticated = $this->authenticateUser($email, $password);

        if ($authenticated) {
            // Starts session if not active
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Sets session data based on user role
            if ($this->currentUserRole === 'admin') {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_email'] = $email;
                $_SESSION['admin_id'] = $this->getUserId();
            } elseif ($this->currentUserRole === 'staff') {
                $_SESSION['staff_logged_in'] = true;
                $_SESSION['staff_email'] = $email;
                $_SESSION['staff_id'] = $this->getUserId();
                $_SESSION['staff_member_id'] = $this->currentStaffId;
            }

            $_SESSION['user_role'] = $this->currentUserRole;

            if ($isAjax) {
                // Returns JSON success for AJAX requests
                $response->getBody()->write(
                    json_encode(
                        [
                            'success' => true,
                            'message' => 'Login successful!',
                            'redirect' => $this->getRedirectUrl($request)
                        ]
                    )
                );
                return $response->withHeader('Content-Type', 'application/json');
            }

            // Redirects to dashboard for non-AJAX requests
            return $response->withHeader('Location', $this->getRedirectUrl($request))
                ->withStatus(302);
        } else {
            if ($isAjax) {
                // Returns JSON error for AJAX requests
                $response->getBody()->write(
                    json_encode(
                        [
                            'success' => false,
                            'message' => 'Invalid email or password.'
                        ]
                    )
                );
                return $response->withHeader('Content-Type', 'application/json');
            }

            // Redirects with error for non-AJAX requests
            return $response->withHeader('Location', UrlHelper::path($request, '/admin/login?error=invalid_credentials'))
                ->withStatus(302);
        }
    }

    /* ==================================================
       LOGOUT ACTION
       --------------------------------------------------
       Purpose: Handles user logout.
       Functionality: Clears session data and redirects to the login page.
       Server-Side Role: Terminates user session securely.
       Logic: Starts session, clears data, destroys session, and redirects.
    */
    public function logout(Request $request, Response $response): Response
    {
        // Starts session if not active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clears all session data
        session_unset();
        session_destroy();

        // Redirects to login page
        return $response->withHeader('Location', UrlHelper::path($request, '/admin/login'))
            ->withStatus(302);
    }

    /* ==================================================
       SHOW FORGOT PASSWORD FORM ACTION
       --------------------------------------------------
       Purpose: Renders the forgot password form.
       Functionality: Displays the password reset request form.
       Server-Side Role: Serves the password reset request view.
       Logic: Renders the Twig template with base_path attribute.
    */
    public function showForgotPasswordForm(Request $request, Response $response): Response
    {
        // Renders the forgot password form template
        $response->getBody()->write(
            $this->twig->render(
                'admin/admin-reset-password.twig',
                [
                    'base_path' => fn() => $request->getAttribute('base_path')
                ]
            )
        );
        return $response;
    }

    /* ==================================================
       HANDLE FORGOT PASSWORD ACTION
       --------------------------------------------------
       Purpose: Processes password reset requests.
       Functionality: Validates email, generates reset token, saves it, and sends a reset email.
       Server-Side Role: Initiates the password reset process securely.
       Logic: Checks email, generates token, saves it, sends email, and responds appropriately.
    */
    public function handleForgotPassword(Request $request, Response $response): Response
    {
        // Checks if request is AJAX
        $isAjax = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        // Retrieves and sanitizes email
        $data = $request->getParsedBody();
        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            if ($isAjax) {
                // Returns JSON error for AJAX requests
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Email is required.'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }
            // Sets session error message for non-AJAX
            $_SESSION['form_message'] = 'Email is required.';
            return $response->withHeader('Location', UrlHelper::path($request, '/admin/forgot-password'))->withStatus(302);
        }

        // Checks if user exists
        $user = $this->user->findByEmail($email);
        if (!$user) {
            if ($isAjax) {
                // Returns generic success message to prevent email enumeration
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'If the email exists, a reset link will be sent.'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }
            $_SESSION['form_message'] = 'If the email exists, a reset link will be sent.';
            return $response->withHeader('Location', UrlHelper::path($request, '/admin/forgot-password'))->withStatus(302);
        }

        // Generates and saves reset token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $this->user->saveResetToken($user['id'], $token, $expiry);

        // Sends password reset email
        $userName = $user['full_name'] ?? $email;
        $this->sendResetEmail($email, $userName, $token);

        if ($isAjax) {
            // Returns JSON success for AJAX requests
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'If the email exists, a reset link will be sent.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }
        // Sets session message for non-AJAX
        $_SESSION['form_message'] = 'If the email exists, a reset link will be sent.';
        return $response->withHeader('Location', UrlHelper::path($request, '/admin/forgot-password'))->withStatus(302);
    }

    /* ==================================================
       SHOW RESET PASSWORD FORM ACTION
       --------------------------------------------------
       Purpose: Renders the password reset form.
       Functionality: Displays the form if the reset token is valid.
       Server-Side Role: Serves the password reset form view.
       Logic: Validates token and renders the Twig template or returns an error.
    */
    public function showResetPasswordForm(Request $request, Response $response): Response
    {
        // Retrieves and validates token
        $params = $request->getQueryParams();
        $token = $params['token'] ?? '';
        if (empty($token) || !$this->user->isValidResetToken($token)) {
            // Returns error for invalid/expired token
            $response->getBody()->write('Invalid or expired token.');
            return $response;
        }

        // Renders the reset password form template
        $response->getBody()->write(
            $this->twig->render(
                'admin/admin-set-new-password.twig',
                [
                    'token' => $token,
                    'base_path' => fn() => $request->getAttribute('base_path')
                ]
            )
        );
        return $response;
    }

    /* ==================================================
       HANDLE RESET PASSWORD ACTION
       --------------------------------------------------
       Purpose: Processes password reset submissions.
       Functionality: Validates token and password, updates password, and clears token.
       Server-Side Role: Completes the password reset process securely.
       Logic: Checks token and password, updates user password, and responds appropriately.
    */
    public function handleResetPassword(Request $request, Response $response): Response
    {
        // Checks if request is AJAX
        $isAjax = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
        // Retrieves form data
        $data = $request->getParsedBody();
        $token = $data['token'] ?? '';
        $password = $data['password'] ?? '';

        // Validates token and password
        if (empty($token) || empty($password) || !$this->user->isValidResetToken($token)) {
            if ($isAjax) {
                // Returns JSON error for AJAX requests
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid or expired token.'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }
            // Sets session error message for non-AJAX
            $_SESSION['form_message'] = 'Invalid or expired token.';
            return $response->withHeader('Location', UrlHelper::path($request, '/admin/reset-password?token=' . urlencode($token)))->withStatus(302);
        }

        // Updates password and clears reset token
        $userId = $this->user->getUserIdByResetToken($token);
        $this->user->updatePassword($userId, $password);
        $this->user->clearResetToken($userId);

        if ($isAjax) {
            // Returns JSON success for AJAX requests
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Password reset successfully! You can now log in.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // Sets session message and redirects for non-AJAX
        $_SESSION['form_message'] = 'Password updated. You can now log in.';
        return $response->withHeader('Location', UrlHelper::path($request, '/admin/login'))->withStatus(302);
    }

    /* ==================================================
       AUTHENTICATE USER HELPER
       --------------------------------------------------
       Purpose: Authenticates a user based on email and password.
       Functionality: Queries the database and verifies credentials.
       Server-Side Role: Validates user login credentials securely.
       Logic: Checks user existence, role, and password hash, and sets user data.
    */
    private function authenticateUser(string $email, string $password): bool
    {
        try {
            // Gets database connection
            $db = $this->getDatabaseConnection();

            // Queries for active admin or staff user
            $stmt = $db->prepare(
                "
                SELECT u.id, u.password_hash, u.role, u.is_active, sm.id as staff_id
                FROM users u
                LEFT JOIN staff_members sm ON u.id = sm.user_id
                WHERE u.email = ? AND u.role IN ('admin', 'staff') AND u.is_active = TRUE
            "
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                return false;
            }

            // Verifies password against hash
            if (password_verify($password, $user['password_hash'])) {
                // Stores user data for session
                $this->currentUserId = $user['id'];
                $this->currentUserRole = $user['role'];
                $this->currentStaffId = $user['staff_id'] ?? null;
                return true;
            }

            return false;
        } catch (\Exception $e) {
            // Logs authentication error
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }

    /* ==================================================
       GET USER ID HELPER
       --------------------------------------------------
       Purpose: Retrieves the current user ID.
       Functionality: Returns the stored user ID or a fallback value.
       Server-Side Role: Provides user ID for session management.
       Logic: Returns the currentUserId property or 0 if unset.
    */
    private function getUserId(): int
    {
        return $this->currentUserId ?? 0;
    }

    /* ==================================================
       GET REDIRECT URL HELPER
       --------------------------------------------------
       Purpose: Determines the redirect URL based on user role.
       Functionality: Returns the appropriate dashboard URL for admin or staff.
       Server-Side Role: Directs users to the correct dashboard post-login.
       Logic: Checks user role and returns the corresponding URL.
    */
    private function getRedirectUrl(Request $request): string
    {
        if ($this->currentUserRole === 'admin') {
            return UrlHelper::path($request, '/admin/dashboard');
        } elseif ($this->currentUserRole === 'staff') {
            return UrlHelper::path($request, '/staff/dashboard');
        }
        return UrlHelper::path($request, '/admin/login');
    }

    /* ==================================================
       GET DATABASE CONNECTION HELPER
       --------------------------------------------------
       Purpose: Establishes a database connection.
       Functionality: Creates a PDO instance using configuration settings.
       Server-Side Role: Provides database access for authentication queries.
       Logic: Loads config and returns a PDO connection.
    */
    private function getDatabaseConnection()
    {
        // Loads database configuration
        $config = include __DIR__ . '/../../config/database.php';
        // Returns new PDO instance
        return new \PDO(
            $config['dsn'],
            $config['username'],
            $config['password'],
            $config['options']
        );
    }

    /* ==================================================
       SEND RESET EMAIL HELPER
       --------------------------------------------------
       Purpose: Sends a password reset email using SendGrid.
       Functionality: Constructs and sends an email with a reset link.
       Server-Side Role: Notifies users of password reset instructions.
       Logic: Builds email content and sends it via SendGrid, logging errors if any.
    */
    private function sendResetEmail($email, $userName, $token)
    {
        // Get base path safely
        $basePath = '';
        if (isset($GLOBALS['base_path'])) {
            $basePath = $GLOBALS['base_path'];
        } elseif (function_exists('base_path')) {
            $basePath = base_path();
        }

        // Constructs reset link
        $resetLink = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $basePath . '/admin/reset-password?token=' . urlencode($token);

        $subject = 'Password Reset Request - Student Course Hub';
        $body = "<p>Hello, $userName!</p><p>We received a request to reset your password. Click the link below to set a new password:</p>"
            . "<p><a href='$resetLink'>$resetLink</a></p>"
            . "<p>If you did not request this, please ignore this email.</p>";

        // Retrieves SendGrid API key
        $sendgridApiKey = $_ENV['SENDGRID_API_KEY'] ?? null;
        if (!$sendgridApiKey) {
            error_log('SendGrid API key not set.');
            return;
        }

        // Sets up SendGrid email
        $emailObj = new \SendGrid\Mail\Mail();
        $emailObj->setFrom('joanantionio681@gmail.com', 'Student Course Hub');
        $emailObj->setSubject($subject);
        $emailObj->addTo($email, $userName);
        $emailObj->addContent('text/html', $body);
        $sendgrid = new \SendGrid($sendgridApiKey);

        try {
            $sendgrid->send($emailObj);
        } catch (\Exception $e) {
            error_log('SendGrid error: ' . $e->getMessage());
        }
    }
}
