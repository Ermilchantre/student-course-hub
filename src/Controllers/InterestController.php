<?php

/* ==================================================
   INTEREST CONTROLLER
   --------------------------------------------------
   Purpose: Manages student interest registration and related functionality in a Slim 4 application.
   Functionality: Handles rendering the interest registration form, processing submissions, retrieving user interests, and deleting interests.
   Server-Side Role: Processes HTTP requests for interest-related operations and manages database interactions.
   ==================================================
*/

namespace App\Controllers;

/* ==================================================
   IMPORTS
   --------------------------------------------------
   Purpose: Imports required PSR-7 interfaces and application dependencies.
   Functionality: Provides types for request/response handling, Twig for templating, models for database operations, and SecurityHelper for input sanitization.
   Server-Side Role: Ensures compatibility with Slim 4 and secure data handling.
   Logic: Imports PSR-7 interfaces, Twig, StudentInterest, Programme, Notification models, Exception, and SecurityHelper.
*/
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use App\Models\StudentInterest;
use App\Models\Programme;
use App\Models\Notification;
use Exception;
use App\Helpers\SecurityHelper;
use App\Helpers\UrlHelper;

/* ==================================================
   INTEREST CONTROLLER CLASS
   --------------------------------------------------
   Purpose: Manages student interest registration workflows in the application.
   Functionality: Renders interest registration forms, processes submissions, retrieves user-specific interests, and handles interest deletion.
   Server-Side Role: Handles interest-related HTTP.requests, interacts with the database, and ensures secure data processing.
   Logic: Initializes dependencies and defines methods for form display, registration, retrieval, and deletion.
*/
class InterestController
{
    /* ==================================================
       PROPERTY DEFINITION
       --------------------------------------------------
       Purpose: Stores dependencies for rendering and database operations.
       Functionality: Holds references to Twig environment and models for StudentInterest, Programme, and Notification.
       Server-Side Role: Provides access to template rendering and database services.
       Logic: Declares private properties for dependency injection.
    */
    private $twig;
    private $interest;
    private $programme;
    private $notification;

    /* ==================================================
       CONSTRUCTOR
       --------------------------------------------------
       Purpose: Initializes the controller with required dependencies.
       Functionality: Assigns Twig and model instances for use in methods.
       Server-Side Role: Sets up the controller for interest-related requests.
       Logic: Stores injected Twig, StudentInterest, Programme, and Notification dependencies.
    */
    public function __construct(
        Environment $twig,
        StudentInterest $interest,
        Programme $programme,
        Notification $notification
    ) {
        // Assigns injected dependencies to class properties
        $this->twig = $twig;
        $this->interest = $interest;
        $this->programme = $programme;
        $this->notification = $notification;
    }

    /* ==================================================
       INDEX ACTION
       --------------------------------------------------
       Purpose: Renders the student interest registration form.
       Functionality: Retrieves all programmes and displays the registration template with programme data.
       Server-Side Role: Serves the interest registration view.
       Logic: Fetches programme data and renders the Twig template with programmes and base path.
    */
    public function index(Request $request, Response $response): Response
    {
        /* Retrieves all programmes
           - $programmes = $this->programme->all(): Fetches all available programmes from the database.
           - Purpose: Provides programme data for the registration formÃ¢â‚¬â„¢s programme selection.
           - Server-Side Role: Queries the database to populate the form with programme options.
           - Logic: Uses the Programme model to retrieve all records.
        */
        $programmes = $this->programme->all();

        /* Renders the interest registration form
           - Purpose: Displays the form with available programmes for users to express interest.
           - Functionality: Passes programme data and base path to the Twig template.
           - Server-Side Role: Combines data and renders the HTML output.
           - Logic: Uses Twig to render the register-interest.twig template.
        */
        $response->getBody()->write(
            $this->twig->render(
                'register-interest.twig',
                [
                    'programmes' => $programmes,
                    'base_path' => fn() => $request->getAttribute('base_path')
                ]
            )
        );
        return $response;
    }

    /* ==================================================
       STORE ACTION
       --------------------------------------------------
       Purpose: Processes student interest registration submissions.
       Functionality: Sanitizes input, generates a PIN, creates an interest record, sends a notification, and handles response rendering.
       Server-Side Role: Manages secure creation of interest records and associated notifications.
       Logic: Sanitizes form data, validates programme, creates interest with hashed PIN, logs notification, and returns JSON or HTML response.
    */
    public function store(Request $request, Response $response): Response
    {
        /* Retrieves and sanitizes form data
           - $data = $request->getParsedBody(): Extracts form fields (e.g., name, email, program_id) from the request body.
           - Purpose: Captures user input for interest registration.
           - Server-Side Role: Provides raw data for sanitization and processing.
           - Logic: Sanitizes inputs using SecurityHelper to prevent injection attacks.
        */
        $data = $request->getParsedBody();
        $name = SecurityHelper::sanitizeString($data['name'] ?? '');
        $email = SecurityHelper::sanitizeEmail($data['email'] ?? '');
        $phone = SecurityHelper::sanitizeString($data['phone'] ?? '');
        $program_id = SecurityHelper::sanitizeInt($data['program_id'] ?? 0);
        $message = SecurityHelper::sanitizeString($data['message'] ?? '');
        $newsletter = isset($data['newsletter']) ? (bool)$data['newsletter'] : false;
        $privacy = isset($data['privacy']) ? (bool)$data['privacy'] : false;

        try {
            /* Generates a random 6-digit PIN and creates interest record
               - $pin = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT): Generates a random 6-digit PIN.
               - $passwordHash = password_hash($pin, PASSWORD_DEFAULT): Hashes the PIN for secure storage.
               - $programme = $this->programme->findById($program_id): Retrieves programme details to determine study level.
               - $level = $programme && $programme['level'] === 'UG' ? 'undergraduate' : 'postgraduate': Sets study level based on programme.
               - Purpose: Prepares and stores interest data with a secure PIN.
               - Server-Side Role: Ensures secure data creation and programme validation.
               - Logic: Creates an interest record with sanitized data and hashed PIN.
            */
            $pin = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $passwordHash = password_hash($pin, PASSWORD_DEFAULT);
            $programme = $this->programme->findById($program_id);
            $level = $programme && $programme['level'] === 'UG' ? 'undergraduate' : 'postgraduate';
            $this->interest->create(
                [
                    'student_name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'programme_id' => $program_id,
                    'study_level' => $level,
                    'additional_message' => $message,
                    'newsletter_consent' => $newsletter,
                    'privacy_consent' => $privacy,
                    'interest_password_hash' => $passwordHash
                ]
            );
        } catch (Exception $e) {
            /* Handles errors during interest creation
               - Purpose: Catches and communicates database or processing errors.
               - Functionality: Returns a JSON error response with the exception message.
               - Server-Side Role: Ensures graceful error handling and user feedback.
               - Logic: Writes error message to response with 500 status.
            */
            $response->getBody()->write(json_encode(['error' => 'Error registering interest: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        /* Creates a notification for the interest
           - Purpose: Logs a notification for the new interest registration.
           - Functionality: Creates a notification record with details about the interest.
           - Server-Side Role: Records administrative notifications for tracking.
           - Logic: Uses Notification model to create a record with user_id 1 and interest details.
        */
        $this->notification->create(
            [
                'user_id' => 1,
                'title' => 'New Interest Registered',
                'message' => "New interest registered by {$name} in {$program_id}",
                'type' => 'info',
                'related_table' => 'student_interests',
                'related_id' => 0
            ]
        );

        /* Handles response based on request type
           - Purpose: Returns appropriate response for AJAX or standard form submissions.
           - Functionality: Returns JSON with PIN for AJAX requests or renders success template for standard requests.
           - Server-Side Role: Provides flexible response handling based on client expectations.
           - Logic: Checks Accept header for JSON preference; otherwise, renders success template with PIN.
        */
        if ($request->hasHeader('Accept') && strpos($request->getHeaderLine('Accept'), 'application/json') !== false) {
            $response->getBody()->write(json_encode(['success' => true, 'pin' => $pin]));
            return $response->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(
            $this->twig->render(
                'register-interest-success.twig',
                [
                    'pin' => $pin,
                    'base_path' => fn() => $request->getAttribute('base_path')
                ]
            )
        );
        return $response;
    }

    /* ==================================================
       MY INTERESTS ACTION
       --------------------------------------------------
       Purpose: Displays a userÃ¢â‚¬â„¢s registered interests based on email and PIN.
       Functionality: Retrieves and verifies interests using email and PIN, then renders a template with results or errors.
       Server-Side Role: Handles retrieval and display of user-specific interest data.
       Logic: Validates input, verifies PIN against hashed values, and renders the interests template.
    */
    public function myInterests(Request $request, Response $response): Response
    {
        /* Retrieves and processes query parameters
           - $params = $request->getQueryParams(): Extracts email and interest_password from the request URL.
           - Purpose: Captures user input for retrieving interests.
           - Server-Side Role: Provides parameters for filtering and verifying interests.
           - Logic: Checks if both email and password are provided to determine form submission.
        */
        $params = $request->getQueryParams();
        $email = $params['email'] ?? null;
        $inputPassword = $params['interest_password'] ?? null;
        $interests = [];
        $error = null;
        $formSubmitted = (
            ($email !== null && $email !== '') &&
            ($inputPassword !== null && $inputPassword !== '')
        );

        /* Verifies and retrieves interests
           - Purpose: Fetches interests matching the provided email and verifies the PIN.
           - Functionality: Queries interests by email, checks PIN against stored hash, and collects valid interests.
           - Server-Side Role: Ensures secure access to user-specific interest data.
           - Logic: Uses password_verify to match input PIN with stored hash; sets error if no matches found.
        */
        if ($formSubmitted) {
            $all = $this->interest->findByEmail($email);
            foreach ($all as $interest) {
                if (
                    isset($interest['interest_password_hash']) &&
                    password_verify(
                        $inputPassword,
                        $interest['interest_password_hash']
                    )
                ) {
                    $interests[] = $interest;
                }
            }
            if (empty($interests)) {
                $error = 'No interests found for this email and security code.';
            }
        }

        /* Renders the interests template
           - Purpose: Displays the userÃ¢â‚¬â„¢s interests or an error message.
           - Functionality: Passes email, interests, error, and base path to the Twig template.
           - Server-Side Role: Combines data and renders the HTML output.
           - Logic: Uses Twig to render the my-interests.twig template with relevant data.
        */
        $response->getBody()->write(
            $this->twig->render(
                'my-interests.twig',
                [
                    'email' => $email,
                    'interests' => $interests,
                    'error' => $error,
                    'base_path' => fn() => $request->getAttribute('base_path')
                ]
            )
        );
        return $response;
    }

    /* ==================================================
       REMOVE INTEREST ACTION
       --------------------------------------------------
       Purpose: Deletes a specific interest record.
       Functionality: Sanitizes input and removes an interest based on ID and email, then redirects to the interests page.
       Server-Side Role: Handles deletion of interest records securely.
       Logic: Sanitizes input, deletes the interest, and redirects to the my-interests page.
    */
    public function removeInterest(Request $request, Response $response): Response
    {
        /* Retrieves and sanitizes form data
           - $data = $request->getParsedBody(): Extracts interest_id and email from the request body.
           - Purpose: Captures input for deleting a specific interest.
           - Server-Side Role: Provides sanitized data for secure deletion.
           - Logic: Uses SecurityHelper to sanitize interest_id and email.
        */
        $data = $request->getParsedBody();
        $interestId = SecurityHelper::sanitizeInt($data['interest_id'] ?? null);
        $email = SecurityHelper::sanitizeEmail($data['email'] ?? null);

        /* Deletes the interest record
           - Purpose: Removes the specified interest from the database.
           - Functionality: Calls the deleteByIdAndEmail method if both ID and email are provided.
           - Server-Side Role: Ensures secure deletion of interest records.
           - Logic: Uses the StudentInterest model to delete the record matching ID and email.
        */
        if ($interestId && $email) {
            $this->interest->deleteByIdAndEmail($interestId, $email);
        }

        /* Redirects to the my-interests page
           - Purpose: Returns the user to the interests page after deletion.
           - Functionality: Redirects with the email parameter preserved.
           - Server-Side Role: Handles navigation after the deletion operation.
           - Logic: Uses a 302 redirect with the sanitized email in the query string.
        */
        return $response
            ->withHeader('Location', UrlHelper::path($request, '/my-interests?email=' . urlencode($email)))
            ->withStatus(302);
    }
}
