<?php

/* ==================================================
   ADMIN INTERESTS CONTROLLER
   --------------------------------------------------
   Purpose: Manages student interests in the admin dashboard of a Slim 4 application.
   Functionality: Handles rendering the interests page, listing interests, deleting interests, exporting data, and searching with filters.
   Server-Side Role: Processes HTTP requests for managing student interest data and rendering related views.
   ==================================================
*/

namespace App\Controllers;

/* ==================================================
   IMPORTS
   --------------------------------------------------
   Purpose: Imports required PSR-7 interfaces and application dependencies.
   Functionality: Provides types for request/response handling and model/helper classes.
   Server-Side Role: Ensures compatibility with Slim 4 and access to necessary services.
   Logic: Imports PSR-7 interfaces, Twig, and models for data operations.
*/
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use App\Models\StudentInterest;
use App\Models\ActivityLog;
use App\Helpers\SecurityHelper;

/* ==================================================
   ADMIN INTERESTS CONTROLLER CLASS
   --------------------------------------------------
   Purpose: Manages student interest-related functionality in the admin dashboard.
   Functionality: Renders views, lists interests, deletes records, exports data, and handles searches.
   Server-Side Role: Processes requests for student interest management and data export.
   Logic: Initializes dependencies and defines methods for various interest-related actions.
*/
class AdminInterestsController
{
    /* ==================================================
       PROPERTY DEFINITION
       --------------------------------------------------
       Purpose: Stores dependencies for rendering and data operations.
       Functionality: Holds references to Twig, StudentInterest, and ActivityLog models.
       Server-Side Role: Provides access to services for template rendering and data manipulation.
       Logic: Declares private properties for dependency injection.
    */
    private $twig;
    private $interest;
    private $log;

    /* ==================================================
       CONSTRUCTOR
       --------------------------------------------------
       Purpose: Initializes the controller with required dependencies.
       Functionality: Assigns Twig environment, StudentInterest, and ActivityLog instances.
       Server-Side Role: Sets up the controller for handling interest-related requests.
       Logic: Stores injected dependencies for use in methods.
    */
    public function __construct(Environment $twig, StudentInterest $interest, ActivityLog $log)
    {
        // Assigns injected dependencies to class properties
        $this->twig = $twig;
        $this->interest = $interest;
        $this->log = $log;
    }

    /* ==================================================
       INDEX ACTION
       --------------------------------------------------
       Purpose: Renders the main interests page.
       Functionality: Displays the interests template with base path for assets.
       Server-Side Role: Serves the admin interests view.
       Logic: Renders the Twig template with base_path attribute.
    */
    public function index(Request $request, Response $response): Response
    {
        // Renders the interests template
        $response->getBody()->write(
            $this->twig->render(
                'admin/interests.twig',
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
       Purpose: Retrieves interests based on user role.
       Functionality: Lists interests for staff by user ID or for admins by email.
       Server-Side Role: Provides JSON data for interest listings.
       Logic: Fetches interests based on role and returns them as JSON.
    */
    public function list(Request $request, Response $response): Response
    {
        // Retrieves user role and ID from request attributes
        $role = $request->getAttribute('role');
        $user_id = $request->getAttribute('user_id');
        if ($role === 'staff') {
            // Fetches interests for staff user
            $interests = $this->interest->findByStaffUserId($user_id);
        } else {
            // Sanitizes email from query parameters
            $email = SecurityHelper::sanitizeEmail($request->getQueryParams()['email'] ?? '');
            // Fetches interests by email for admins
            $interests = $this->interest->findByEmail($email);
        }
        // Returns interests as JSON
        $response->getBody()->write(json_encode($interests));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /* ==================================================
       DELETE ACTION
       --------------------------------------------------
       Purpose: Deletes a single interest record.
       Functionality: Removes an interest by ID and logs the action.
       Server-Side Role: Handles deletion of student interest records securely.
       Logic: Sanitizes ID, deletes record, logs action, and returns appropriate status.
    */
    public function delete(Request $request, Response $response, array $args): Response
    {
        // Sanitizes the interest ID
        $id = SecurityHelper::sanitizeInt($args['id'] ?? null);
        // Attempts to delete the interest
        $deleted = $this->interest->delete($id);

        if ($deleted) {
            // Logs the deletion action
            $this->log->log(
                [
                    'user_id' => 1, // Hardcoded for now
                    'action' => 'delete',
                    'table_name' => 'student_interests',
                    'record_id' => $id,
                    'old_values' => [],
                    'new_values' => [],
                    'ip_address' => $request->getServerParams()['REMOTE_ADDR'],
                    'user_agent' => $request->getHeaderLine('User-Agent')
                ]
            );
            // Returns 204 No Content on success
            return $response->withStatus(204);
        } else {
            // Returns 404 with error message if interest not found
            $response->getBody()->write(json_encode(['error' => 'Interest not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    }

    /* ==================================================
       LIST ALL ACTION
       --------------------------------------------------
       Purpose: Retrieves all interest records.
       Functionality: Fetches all student interests from the database.
       Server-Side Role: Provides a complete list of interests in JSON format.
       Logic: Calls the findAll method and returns results as JSON.
    */
    public function listAll(Request $request, Response $response): Response
    {
        // Fetches all interests
        $interests = $this->interest->findAll();
        // Returns interests as JSON
        $response->getBody()->write(json_encode($interests));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /* ==================================================
       SEARCH ACTION
       --------------------------------------------------
       Purpose: Searches interests with pagination.
       Functionality: Performs a search based on a query string with pagination support.
       Server-Side Role: Filters and paginates interest data for efficient retrieval.
       Logic: Sanitizes query, retrieves paginated results, and returns them as JSON.
    */
    public function search(Request $request, Response $response): Response
    {
        // Sanitizes search query
        $q = SecurityHelper::sanitizeString($request->getQueryParams()['q'] ?? '');
        // Retrieves pagination parameters
        $page = isset($request->getQueryParams()['page']) ? (int)$request->getQueryParams()['page'] : 1;
        $pageSize = isset($request->getQueryParams()['pageSize']) ? (int)$request->getQueryParams()['pageSize'] : 10;
        // Performs search with pagination
        $data = $this->interest->search($q, $page, $pageSize);
        // Returns search results as JSON
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /* ==================================================
       EXPORT ACTION
       --------------------------------------------------
       Purpose: Exports interest data in CSV or Excel format.
       Functionality: Exports interests based on a programme filter in the specified format.
       Server-Side Role: Generates downloadable files for interest data export.
       Logic: Sanitizes inputs, fetches filtered interests, and delegates to export methods.
    */
    public function export(Request $request, Response $response): Response
    {
        // Sanitizes export format and programme ID
        $format = SecurityHelper::sanitizeString($request->getQueryParams()['format'] ?? 'csv');
        $programmeId = SecurityHelper::sanitizeInt($request->getQueryParams()['programme_id'] ?? null);

        // Fetches interests with optional programme filter
        $interests = $this->interest->findAllWithFilters($programmeId);

        if ($format === 'csv') {
            // Exports as CSV
            return $this->exportCSV($response, $interests);
        } elseif ($format === 'excel') {
            // Exports as Excel-compatible CSV
            return $this->exportExcel($response, $interests);
        } else {
            // Returns error for invalid format
            $response->getBody()->write(json_encode(['error' => 'Invalid format']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    /* ==================================================
       DELETE MULTIPLE ACTION
       --------------------------------------------------
       Purpose: Deletes multiple interest records.
       Functionality: Deletes a list of interest IDs and logs each deletion.
       Server-Side Role: Handles bulk deletion of student interests securely.
       Logic: Sanitizes IDs, deletes records, logs actions, and returns deletion count.
    */
    public function deleteMultiple(Request $request, Response $response): Response
    {
        // Retrieves and sanitizes IDs from request body
        $data = $request->getParsedBody();
        $ids = isset($data['ids']) && is_array($data['ids']) ? array_map([SecurityHelper::class, 'sanitizeInt'], $data['ids']) : [];

        if (empty($ids)) {
            // Returns error if no IDs provided
            $response->getBody()->write(json_encode(['error' => 'No IDs provided']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Tracks number of successful deletions
        $deletedCount = 0;
        foreach ($ids as $id) {
            if ($this->interest->delete($id)) {
                $deletedCount++;
                // Logs each deletion
                $this->log->log(
                    [
                        'user_id' => 1, // Hardcoded for now
                        'action' => 'delete',
                        'table_name' => 'student_interests',
                        'record_id' => $id,
                        'old_values' => [],
                        'new_values' => [],
                        'ip_address' => $request->getServerParams()['REMOTE_ADDR'],
                        'user_agent' => $request->getHeaderLine('User-Agent')
                    ]
                );
            }
        }

        // Returns success response with deletion count
        $response->getBody()->write(
            json_encode(
                [
                    'status' => 'success',
                    'message' => "Deleted {$deletedCount} interests",
                    'deleted_count' => $deletedCount
                ]
            )
        );
        return $response->withHeader('Content-Type', 'application/json');
    }

    /* ==================================================
       SEARCH ADVANCED ACTION
       --------------------------------------------------
       Purpose: Performs advanced search with programme filter and pagination.
       Functionality: Searches interests with multiple criteria and pagination.
       Server-Side Role: Provides filtered, paginated interest data for advanced queries.
       Logic: Sanitizes inputs, performs advanced search, and returns results as JSON.
    */
    public function searchAdvanced(Request $request, Response $response): Response
    {
        // Retrieves and sanitizes query parameters
        $params = $request->getQueryParams();
        $q = SecurityHelper::sanitizeString($params['q'] ?? '');
        $programmeId = SecurityHelper::sanitizeInt($params['programme_id'] ?? null);
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $pageSize = isset($params['pageSize']) ? (int)$params['pageSize'] : 10;

        // Performs advanced search with filters
        $data = $this->interest->searchAdvanced($q, $programmeId, $page, $pageSize);
        // Returns search results as JSON
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /* ==================================================
       GET STATS ACTION
       --------------------------------------------------
       Purpose: Retrieves statistics for student interests.
       Functionality: Fetches stats for a specific programme or all interests.
       Server-Side Role: Provides statistical data for dashboard analytics.
       Logic: Sanitizes programme ID, fetches stats, and returns them as JSON.
    */
    public function getStats(Request $request, Response $response): Response
    {
        // Sanitizes programme ID
        $programmeId = SecurityHelper::sanitizeInt($request->getQueryParams()['programme_id'] ?? null);
        // Fetches statistics
        $stats = $this->interest->getStats($programmeId);

        // Returns stats as JSON
        $response->getBody()->write(
            json_encode(
                [
                    'status' => 'success',
                    'data' => $stats
                ]
            )
        );
        return $response->withHeader('Content-Type', 'application/json');
    }

    /* ==================================================
       EXPORT CSV HELPER
       --------------------------------------------------
       Purpose: Exports interest data as a CSV file.
       Functionality: Generates a CSV file with interest details and sets download headers.
       Server-Side Role: Provides downloadable CSV output for interest data.
       Logic: Formats interest data into CSV and sets appropriate response headers.
    */
    private function exportCSV(Response $response, array $interests): Response
    {
        // Generates a timestamped filename
        $filename = 'interests_' . date('Y-m-d_H-i-s') . '.csv';

        // Builds CSV content
        $csv = "Name,Email,Programme,Registration Date\n";
        foreach ($interests as $interest) {
            $csv .= sprintf(
                '"%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $interest['student_name']),
                str_replace('"', '""', $interest['email']),
                str_replace('"', '""', $interest['programme_title']),
                $interest['created_at'] ?? date('Y-m-d H:i:s')
            );
        }

        // Writes CSV content to response
        $response->getBody()->write($csv);
        // Sets CSV headers for download
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /* ==================================================
       EXPORT EXCEL HELPER
       --------------------------------------------------
       Purpose: Exports interest data as an Excel-compatible CSV file.
       Functionality: Generates a CSV with UTF-8 BOM for Excel compatibility and sets download headers.
       Server-Side Role: Provides downloadable Excel-compatible output for interest data.
       Logic: Formats interest data with UTF-8 BOM and sets appropriate response headers.
    */
    private function exportExcel(Response $response, array $interests): Response
    {
        // Generates a timestamped filename
        $filename = 'interests_' . date('Y-m-d_H-i-s') . '.xlsx';

        // Adds UTF-8 BOM for Excel compatibility
        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM
        $csv .= "Name,Email,Programme,Registration Date\n";
        foreach ($interests as $interest) {
            $csv .= sprintf(
                '"%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $interest['student_name']),
                str_replace('"', '""', $interest['email']),
                str_replace('"', '""', $interest['programme_title']),
                $interest['created_at'] ?? date('Y-m-d H:i:s')
            );
        }

        // Writes CSV content to response
        $response->getBody()->write($csv);
        // Sets Excel-compatible headers for download
        return $response
            ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
