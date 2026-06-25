<?php

/* ==================================================
   ADMIN PROGRAMMES CONTROLLER
   --------------------------------------------------
   Purpose: Manages programme-related functionality in the admin dashboard of a Slim 4 application.
   Functionality: Handles rendering the programmes page, listing, creating, updating, deleting, and retrieving programme data.
   Server-Side Role: Processes HTTP requests for programme management and data operations.
   ==================================================
*/

namespace App\Controllers;

/* ==================================================
   IMPORTS
   --------------------------------------------------
   Purpose: Imports required PSR-7 interfaces and application dependencies.
   Functionality: Provides types for request/response handling, Twig, models, and helpers.
   Server-Side Role: Ensures compatibility with Slim 4 and access to necessary services.
   Logic: Imports PSR-7 interfaces, Twig, Programme, ActivityLog, and SecurityHelper.
*/
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use App\Models\Programme;
use App\Models\ActivityLog;
use App\Helpers\SecurityHelper;

/* ==================================================
   ADMIN PROGRAMMES CONTROLLER CLASS
   --------------------------------------------------
   Purpose: Manages programme-related operations in the admin dashboard.
   Functionality: Renders views, lists programmes, creates, updates, deletes, and retrieves programme details.
   Server-Side Role: Handles programme data CRUD operations and view rendering.
   Logic: Initializes dependencies and defines methods for programme management.
*/
class AdminProgrammesController
{
    /* ==================================================
       PROPERTY DEFINITION
       --------------------------------------------------
       Purpose: Stores dependencies for rendering and data operations.
       Functionality: Holds references to Twig, Programme, and ActivityLog models.
       Server-Side Role: Provides access to template rendering and data services.
       Logic: Declares private properties for dependency injection.
    */
    private $twig;
    private $programme;
    private $log;

    /* ==================================================
       CONSTRUCTOR
       --------------------------------------------------
       Purpose: Initializes the controller with required dependencies.
       Functionality: Assigns Twig, Programme, and ActivityLog instances.
       Server-Side Role: Sets up the controller for programme-related requests.
       Logic: Stores injected dependencies for use in methods.
    */
    public function __construct(Environment $twig, Programme $programme, ActivityLog $log)
    {
        // Assigns injected dependencies to class properties
        $this->twig = $twig;
        $this->programme = $programme;
        $this->log = $log;
    }

    /* ==================================================
       INDEX ACTION
       --------------------------------------------------
       Purpose: Renders the programme management page.
       Functionality: Displays the programmes template with base path for assets.
       Server-Side Role: Serves the programme management view.
       Logic: Renders the Twig template with base_path attribute.
    */
    public function index(Request $request, Response $response): Response
    {
        // Renders the programmes template
        $response->getBody()->write(
            $this->twig->render(
                'admin/programmes.twig',
                [
                    // Provides base_path for template asset paths
                    'base_path' => fn() => $request->getAttribute('base_path')
                ]
            )
        );
        return $response;
    }

    /* ==================================================
       LIST ACTION
       --------------------------------------------------
       Purpose: Retrieves all programmes for admin view.
       Functionality: Fetches programme data using the Programme model.
       Server-Side Role: Provides JSON data for programme listings.
       Logic: Calls allAdmin method and returns results as JSON.
    */
    public function list(Request $request, Response $response): Response
    {
        // Fetches all programmes for admin
        $programmes = $this->programme->allAdmin();
        // Returns programmes as JSON
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
       CREATE ACTION
       --------------------------------------------------
       Purpose: Creates a new programme record.
       Functionality: Handles file uploads, sanitizes inputs, validates data, and logs creation.
       Server-Side Role: Processes programme creation with secure input handling.
       Logic: Manages image upload, sanitizes data, creates programme, logs action, and returns JSON response.
    */
    public function create(Request $request, Response $response): Response
    {
        // Retrieves form data and uploaded files
        $data = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();

        // Handles image upload
        if (isset($uploadedFiles['image_url']) && $uploadedFiles['image_url']->getError() === UPLOAD_ERR_OK) {
            $image = $uploadedFiles['image_url'];
            // Generates unique filename for the image
            $filename = uniqid('programme_') . '_' . preg_replace(
                '/[^a-zA-Z0-9.\-_]/',
                '',
                $image->getClientFilename()
            );
            $targetPath = __DIR__ . '/../../public/images/programmes/' . $filename;
            // Moves uploaded image to target path
            $image->moveTo($targetPath);
            $data['image_url'] = '/images/programmes/' . $filename;
        } else {
            $data['image_url'] = null;
        }

        // Sanitizes user input
        $title = SecurityHelper::sanitizeString($data['title'] ?? '');
        $level = SecurityHelper::sanitizeString($data['level'] ?? '');
        $description = SecurityHelper::sanitizeString($data['description'] ?? '');
        $image_url = SecurityHelper::sanitizeString($data['image_url'] ?? null);
        $duration_years = isset($data['duration_years']) ? SecurityHelper::sanitizeInt($data['duration_years']) : 3;
        $credits = isset($data['credits']) ? SecurityHelper::sanitizeInt($data['credits']) : null;
        $entry_requirements = SecurityHelper::sanitizeString($data['entry_requirements'] ?? null);
        $career_prospects = SecurityHelper::sanitizeString($data['career_prospects'] ?? null);
        $is_published = !empty($data['is_published']);

        // Converts checkbox input to boolean
        if (isset($data['is_published']) && is_array($data['is_published'])) {
            $is_published = end($data['is_published']);
        }
        $is_published = !empty($is_published);

        // Converts empty strings to null
        $image_url = empty($image_url) ? null : $image_url;
        $credits = empty($credits) ? null : (int)$credits;
        $entry_requirements = empty($entry_requirements) ? null : $entry_requirements;
        $career_prospects = empty($career_prospects) ? null : $career_prospects;

        // Validates required fields
        if ($title === '' || $level === '' || $description === '') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'All required fields (title, level, description) must be filled.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            // Creates programme record
            $id = $this->programme->create([
                'title' => $title,
                'level' => $level,
                'description' => $description,
                'image_url' => $image_url,
                'credits' => $credits,
                'entry_requirements' => $entry_requirements,
                'career_prospects' => $career_prospects,
                'is_published' => $is_published
            ]);

            // Logs programme creation
            $this->log->log(
                [
                    'user_id' => 1, // Hardcoded for now
                    'action' => 'create',
                    'table_name' => 'programmes',
                    'record_id' => $id,
                    'old_values' => [],
                    'new_values' => [
                        'title' => $title,
                        'description' => $description,
                        'image_url' => $image_url,
                        'credits' => $credits,
                        'entry_requirements' => $entry_requirements,
                        'career_prospects' => $career_prospects,
                        'is_published' => $is_published
                    ],
                    'ip_address' => $request->getServerParams()['REMOTE_ADDR'],
                    'user_agent' => $request->getHeaderLine('User-Agent')
                ]
            );

            // Returns success response
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Programme created successfully'
            ]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            // Returns error response for database issues
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /* ==================================================
       UPDATE ACTION
       --------------------------------------------------
       Purpose: Updates an existing programme record.
       Functionality: Handles file uploads, sanitizes inputs, updates programme, and logs changes.
       Server-Side Role: Processes programme updates with secure input handling.
       Logic: Manages image upload, sanitizes data, updates programme, logs action, and returns JSON response.
    */
    public function update(Request $request, Response $response, array $args): Response
    {
        // Retrieves form data
        $data = $request->getParsedBody();
        // Handles is_published for various input formats
        if (!isset($data['is_published'])) {
            if (isset($_POST['is_published'])) {
                $data['is_published'] = $_POST['is_published'];
            } else {
                $input = file_get_contents('php://input');
                parse_str($input, $rawData);
                if (isset($rawData['is_published'])) {
                    $data['is_published'] = $rawData['is_published'];
                } else {
                    if (preg_match('/name="is_published"\s*\r?\n\r?\n([^\r\n]*)/', $input, $matches)) {
                        $data['is_published'] = trim($matches[1]);
                    }
                }
            }
        }
        if (!is_array($data) || empty($data)) {
            $data = [];
        }

        // Handles image upload
        $uploadedFiles = $request->getUploadedFiles();
        if (isset($uploadedFiles['image_url']) && $uploadedFiles['image_url']->getError() === UPLOAD_ERR_OK) {
            $image = $uploadedFiles['image_url'];
            // Generates unique filename for the image
            $filename = uniqid('programme_') . '_' . preg_replace(
                '/[^a-zA-Z0-9.\-_]/',
                '',
                $image->getClientFilename()
            );
            $targetPath = __DIR__ . '/../../public/images/programmes/' . $filename;
            // Moves uploaded image to target path
            $image->moveTo($targetPath);
            $data['image_url'] = '/images/programmes/' . $filename;
        } else {
            $data['image_url'] = null;
        }

        // Sanitizes user input
        $title = SecurityHelper::sanitizeString($data['title'] ?? '');
        $level = SecurityHelper::sanitizeString($data['level'] ?? '');
        $description = SecurityHelper::sanitizeString($data['description'] ?? '');
        $image_url = SecurityHelper::sanitizeString($data['image_url'] ?? null);
        $duration_years = isset($data['duration_years']) ? SecurityHelper::sanitizeInt($data['duration_years']) : 3;
        $credits = isset($data['credits']) ? SecurityHelper::sanitizeInt($data['credits']) : null;
        $entry_requirements = SecurityHelper::sanitizeString($data['entry_requirements'] ?? null);
        $career_prospects = SecurityHelper::sanitizeString($data['career_prospects'] ?? null);
        $is_published = !empty($data['is_published']);

        // Converts checkbox input to boolean
        if (isset($data['is_published']) && is_array($data['is_published'])) {
            $is_published = end($data['is_published']);
        }
        $is_published = !empty($is_published);

        // Converts empty strings to null
        $image_url = empty($image_url) ? null : $image_url;
        $credits = empty($credits) ? null : (int)$credits;
        $entry_requirements = empty($entry_requirements) ? null : $entry_requirements;
        $career_prospects = empty($career_prospects) ? null : $career_prospects;

        // Retrieves programme ID
        $id = $args['id'] ?? null;
        // Updates programme record
        $rows = $this->programme->update($id, [
            'title' => $title,
            'level' => $level,
            'description' => $description,
            'image_url' => $image_url,
            'duration_years' => $duration_years,
            'credits' => $credits,
            'entry_requirements' => $entry_requirements,
            'career_prospects' => $career_prospects,
            'is_published' => $is_published
        ]);

        // Checks if update was successful
        if ($rows === 0) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'No changes made or programme not found'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Logs programme update
        $this->log->log(
            [
                'user_id' => 1, // Hardcoded for now
                'action' => 'update',
                'table_name' => 'programmes',
                'record_id' => $id,
                'old_values' => [],
                'new_values' => [
                    'title' => $title,
                    'description' => $description,
                    'image_url' => $image_url,
                    'credits' => $credits,
                    'entry_requirements' => $entry_requirements,
                    'career_prospects' => $career_prospects,
                    'is_published' => $is_published
                ],
                'ip_address' => $request->getServerParams()['REMOTE_ADDR'],
                'user_agent' => $request->getHeaderLine('User-Agent')
            ]
        );

        // Returns success response
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Programme updated successfully'
        ]));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    /* ==================================================
       DELETE ACTION
       --------------------------------------------------
       Purpose: Deletes a programme record.
       Functionality: Removes a programme by ID and logs the action.
       Server-Side Role: Handles secure deletion of programme records.
       Logic: Deletes programme, logs action, and returns a no-content response.
    */
    public function delete(Request $request, Response $response, array $args): Response
    {
        // Deletes programme by ID
        $this->programme->delete($args['id']);
        // Logs programme deletion
        $this->log->log(
            [
                'user_id' => 1, // Hardcoded for now
                'action' => 'delete',
                'table_name' => 'programmes',
                'record_id' => $args['id'],
                'old_values' => [],
                'new_values' => [],
                'ip_address' => $request->getServerParams()['REMOTE_ADDR'],
                'user_agent' => $request->getHeaderLine('User-Agent')
            ]
        );
        // Returns no-content response
        return $response->withStatus(204);
    }

    /* ==================================================
       SHOW ACTION
       --------------------------------------------------
       Purpose: Retrieves a specific programme by ID.
       Functionality: Fetches programme details using the Programme model.
       Server-Side Role: Provides JSON data for a single programme.
       Logic: Queries programme by ID and returns JSON response or error if not found.
    */
    public function show(Request $request, Response $response, array $args): Response
    {
        // Fetches programme by ID
        $programme = $this->programme->findById($args['id']);
        // Checks if programme exists
        if (!$programme) {
            $response->getBody()->write(json_encode(['error' => 'Programme not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        // Returns programme data as JSON
        $response->getBody()->write(
            json_encode(
                [
                    'status' => 'success',
                    'data' => $programme
                ]
            )
        );
        return $response->withHeader('Content-Type', 'application/json');
    }
}
