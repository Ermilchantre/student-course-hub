<?php

/* ==================================================
   ADMIN STAFF CONTROLLER
   --------------------------------------------------
   Purpose: Manages staff registration functionality in the admin dashboard of a Slim 4 application.
   Functionality: Handles rendering the staff registration form and processing staff creation with user and staff data.
   Server-Side Role: Processes HTTP requests for staff registration and manages database operations.
   ==================================================
*/

namespace App\Controllers;

/* ==================================================
   IMPORTS
   --------------------------------------------------
   Purpose: Imports required PSR-7 interfaces and application dependencies.
   Functionality: Provides types for request/response handling, Twig, and SecurityHelper.
   Server-Side Role: Ensures compatibility with Slim 4 and secure data handling.
   Logic: Imports PSR-7 interfaces, Twig, and SecurityHelper for controller operations.
*/
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use App\Helpers\SecurityHelper;

/* ==================================================
   ADMIN STAFF CONTROLLER CLASS
   --------------------------------------------------
   Purpose: Manages staff registration operations in the admin dashboard.
   Functionality: Renders the registration form and processes staff creation, including user account and photo upload.
   Server-Side Role: Handles staff registration workflows and database interactions.
   Logic: Initializes dependencies and defines methods for form display and registration.
*/
class AdminStaffController
{
    /* ==================================================
       PROPERTY DEFINITION
       --------------------------------------------------
       Purpose: Stores dependencies for rendering and database operations.
       Functionality: Holds references to Twig environment and PDO connection.
       Server-Side Role: Provides access to template rendering and database services.
       Logic: Declares private properties for dependency injection.
    */
    private $twig;
    private $db;

    /* ==================================================
       CONSTRUCTOR
       --------------------------------------------------
       Purpose: Initializes the controller with required dependencies.
       Functionality: Assigns Twig and PDO instances for use in methods.
       Server-Side Role: Sets up the controller for staff registration requests.
       Logic: Stores injected Twig and PDO dependencies.
    */
    public function __construct(Environment $twig, \PDO $db)
    {
        // Assigns injected dependencies to class properties
        $this->twig = $twig;
        $this->db = $db;
    }

    /* ==================================================
       SHOW REGISTER FORM ACTION
       --------------------------------------------------
       Purpose: Renders the staff registration form.
       Functionality: Displays the registration template with base path for assets.
       Server-Side Role: Serves the staff registration view.
       Logic: Renders the Twig template with base_path attribute.
    */
    public function showRegisterForm(Request $request, Response $response): Response
    {
        // Renders the staff registration form template
        $response->getBody()->write(
            $this->twig->render(
                'admin/staff-register.twig',
                [
                    // Provides base_path for template asset paths
                    'base_path' => fn() => $request->getAttribute('base_path')
                ]
            )
        );
        return $response;
    }

    /* ==================================================
       REGISTER ACTION
       --------------------------------------------------
       Purpose: Processes staff registration requests.
       Functionality: Validates and sanitizes input, creates user and staff records, and handles photo uploads.
       Server-Side Role: Manages secure creation of staff accounts and associated data.
       Logic: Sanitizes input, validates fields, creates user, uploads photo, creates staff record, and returns JSON response.
    */
    public function register(Request $request, Response $response): Response
    {
        /* Retrieves form data and uploaded files
           - $data = $request->getParsedBody(): Extracts form fields (e.g., username, email, password) from the request body,
             typically sent via a POST request with content type multipart/form-data or application/x-www-form-urlencoded.
             Returns an associative array of form field names and their values.
           - $uploadedFiles = $request->getUploadedFiles(): Retrieves files uploaded in the request, such as a profile photo,
             under the 'photo' key. Returns an array of PSR-7 UploadedFile objects for processing.
           - Purpose: Captures user input and optional file uploads from the staff registration form for validation and storage.
           - Server-Side Role: Provides the raw data needed for subsequent sanitization, validation, and database operations.
        */
        $data = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();

        // Sanitizes user input
        $username = SecurityHelper::sanitizeString($data['username'] ?? '');
        $email = SecurityHelper::sanitizeEmail($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $full_name = SecurityHelper::sanitizeString($data['full_name'] ?? '');
        $title = SecurityHelper::sanitizeString($data['title'] ?? '');
        $department = SecurityHelper::sanitizeString($data['department'] ?? '');
        $specialization = SecurityHelper::sanitizeString($data['specialization'] ?? '');
        $bio = SecurityHelper::sanitizeString($data['bio'] ?? '');
        $phone = SecurityHelper::sanitizeString($data['phone'] ?? '');
        $office_location = SecurityHelper::sanitizeString($data['office_location'] ?? '');
        $is_active = isset($data['is_active']) ? 1 : 0;

        /* Validates required fields and constraints
           - Purpose: Ensures all required fields are provided and meet specific criteria before proceeding with registration.
           - Functionality: Checks each field for emptiness or invalid format, returning a JSON error response for the first failure.
           - Server-Side Role: Prevents invalid data from being processed, enhancing security and data integrity.
           - Logic: Each field is validated individually; if a validation fails, a 400 Bad Request response is returned with the specific field and error message.
        */
        // Checks if username is empty or too short (minimum 3 characters)
        if ($username === '' || strlen($username) < 3) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'field' => 'username',
                'message' => $username === '' ? 'Username is required.' : 'Username must be at least 3 characters.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Checks if email is empty
        if ($email === '') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'field' => 'email',
                'message' => 'Email is required.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Validates email format using PHP's filter_var
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'field' => 'email',
                'message' => 'Invalid email address.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Checks if full name is empty
        if ($full_name === '') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'field' => 'full_name',
                'message' => 'Full name is required.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Checks if title is empty
        if ($title === '') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'field' => 'title',
                'message' => 'Title is required.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Checks if department is empty
        if ($department === '') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'field' => 'department',
                'message' => 'Department is required.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Checks if specialization is empty
        if ($specialization === '') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'field' => 'specialization',
                'message' => 'Specialization is required.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Checks if phone number is empty
        if ($phone === '') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'field' => 'phone',
                'message' => 'Phone is required.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Checks if office location is empty
        if ($office_location === '') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'field' => 'office_location',
                'message' => 'Office location is required.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Checks if bio is empty
        if ($bio === '') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'field' => 'bio',
                'message' => 'Bio is required.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Checks if password is empty
        if ($password === '') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'field' => 'password',
                'message' => 'Password is required.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Defines password length constraints
        $minLength = 6;
        $maxLength = 20;
        // Checks if password length is within allowed range
        if (strlen($password) < $minLength || strlen($password) > $maxLength) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'field' => 'password',
                'message' => 'Password must be between 6 and 20 characters.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Creates user record
        $userModel = new \App\Models\User($this->db);
        $userData = [
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $full_name,
            'role' => 'staff',
            'is_active' => $is_active
        ];
        try {
            // Inserts user into the users table
            $user_id = $userModel->create($userData);
        } catch (\PDOException $e) {
            // Handles duplicate username or email errors
            if ($e->getCode() == 23000) {
                $msg = strpos($e->getMessage(), 'username') !== false ? 'Username already exists.' : 'Email already exists.';
                $field = strpos($e->getMessage(), 'username') !== false ? 'username' : 'email';
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'field' => $field,
                    'message' => $msg
                ]));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }
            throw $e;
        }

        // Handles photo upload
        $photo_url = null;
        if (isset($uploadedFiles['photo']) && $uploadedFiles['photo']->getError() === UPLOAD_ERR_OK) {
            $photo = $uploadedFiles['photo'];
            // Generates unique filename for the photo
            $filename = uniqid('staff_') . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $photo->getClientFilename());
            $uploadDir = __DIR__ . '/../../public/images/staff/';
            // Creates upload directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            // Moves photo to upload directory
            $photo->moveTo($uploadDir . $filename);
            $photo_url = '/images/staff/' . $filename;
        }

        // Prepares staff data
        $staffData = [
            'user_id' => $user_id,
            'title' => $title,
            'department' => $department,
            'specialization' => $specialization,
            'bio' => $bio,
            'photo_url' => $photo_url,
            'phone' => $phone,
            'office_location' => $office_location,
            'is_active' => $is_active
        ];

        // Creates staff record
        $staffModel = new \App\Models\StaffMember($this->db);
        $staffModel->create($staffData);

        // Returns success response
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Staff registered successfully'
        ]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }
}
