<?php

/* ==================================================
   ADMIN MODULES CONTROLLER
   --------------------------------------------------
   Purpose: Manages module-related functionality in the admin dashboard of a Slim 4 application.
   Functionality: Handles rendering the modules page, listing modules, creating, updating, deleting, and fetching staff members.
   Server-Side Role: Processes HTTP requests for module management and data retrieval.
   Version: 1.0
   Last Updated: July 23, 2025
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
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Twig\Environment;
use App\Helpers\SecurityHelper;

/* ==================================================
   ADMIN MODULES CONTROLLER CLASS
   --------------------------------------------------
   Purpose: Manages module-related actions in the admin dashboard.
   Functionality: Renders views, lists modules, creates, updates, deletes, and fetches staff data.
   Server-Side Role: Processes module-related requests and database operations.
   Logic: Initializes dependencies and defines methods for module management.
*/
class AdminModulesController
{
    /* ==================================================
       PROPERTY DEFINITION
       --------------------------------------------------
       Purpose: Stores dependencies for database and template rendering.
       Functionality: Holds PDO connection and optional Twig environment.
       Server-Side Role: Provides access to database and rendering services.
       Logic: Declares protected properties for dependency injection.
    */
    protected $db;
    protected $twig;

    /* ==================================================
       CONSTRUCTOR
       --------------------------------------------------
       Purpose: Initializes the controller with database and optional Twig dependencies.
       Functionality: Assigns PDO and Twig instances for use in methods.
       Server-Side Role: Sets up the controller for module-related operations.
       Logic: Stores injected PDO and optional Twig environment.
    */
    public function __construct(\PDO $db, ?Environment $twig = null)
    {
        // Assigns injected dependencies to class properties
        $this->db = $db;
        $this->twig = $twig;
    }

    /* ==================================================
       INDEX ACTION
       --------------------------------------------------
       Purpose: Renders the module management page.
       Functionality: Displays the modules template with base path for assets.
       Server-Side Role: Serves the module management view.
       Logic: Checks Twig availability and renders the template with base_path.
    */
    public function index(Request $request, Response $response): Response
    {
        // Checks if Twig is available
        if (!$this->twig) {
            // Returns error if Twig is not available
            $response->getBody()->write('Twig template engine not available');
            return $response->withStatus(500);
        }

        // Renders the modules template
        $response->getBody()->write(
            $this->twig->render(
                'admin/modules.twig',
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
       Purpose: Retrieves all modules with programme and leader details.
       Functionality: Fetches module data and formats photo URLs.
       Server-Side Role: Provides JSON data for module listings.
       Logic: Queries database, processes photo URLs, and returns JSON response.
    */
    public function list(Request $request, Response $response): Response
    {
        try {
            // Retrieves base path for constructing photo URLs
            $basePath = $request->getAttribute('base_path') ?? '';
            // Queries modules with programme and leader details
            $modules = $this->db->query(
                'SELECT m.*, p.title AS programme_title, s.title AS module_leader, ' .
                's.photo_url AS leader_photo_url, ' .
                'CONCAT(s.title, " ", u.full_name) AS module_leader_full ' .
                'FROM modules m ' .
                'LEFT JOIN programmes p ON m.programme_id = p.id ' .
                'LEFT JOIN staff_members s ON m.module_leader_id = s.id ' .
                'LEFT JOIN users u ON s.user_id = u.id'
            )->fetchAll(\PDO::FETCH_ASSOC);

            // Adds formatted photo_url to each module
            foreach ($modules as &$module) {
                if (!empty($module['leader_photo_url'])) {
                    // Checks if photo URL is absolute
                    if (
                        strpos($module['leader_photo_url'], 'http') === 0 ||
                        strpos($module['leader_photo_url'], '/') === 0
                    ) {
                        $module['photo_url'] = $module['leader_photo_url'];
                    } else {
                        // Constructs relative photo URL
                        $module['photo_url'] = $basePath . '/images/staff/' . $module['leader_photo_url'];
                    }
                } else {
                    $module['photo_url'] = null;
                }
            }

            // Returns modules as JSON
            $response->getBody()->write(
                json_encode(
                    [
                        'status' => 'success',
                        'data' => $modules
                    ]
                )
            );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\PDOException $e) {
            // Returns error response for database issues
            $response->getBody()->write(
                json_encode(
                    [
                        'status' => 'error',
                        'message' => 'Erro ao listar mÃƒÂ³dulos: ' . $e->getMessage()
                    ]
                )
            );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /* ==================================================
       CREATE ACTION
       --------------------------------------------------
       Purpose: Adds a new module to the database.
       Functionality: Validates and inserts module data, including associated staff.
       Server-Side Role: Handles creation of new module records securely.
       Logic: Sanitizes inputs, inserts module, adds staff associations, and returns JSON response.
    */
    public function create(Request $request, Response $response): Response
    {
        try {
            // Parses JSON request body
            $data = json_decode($request->getBody()->getContents(), true);

            // Sanitizes user input
            $title = SecurityHelper::sanitizeString($data['title'] ?? '');
            $programme_id = SecurityHelper::sanitizeInt($data['programme_id'] ?? 0);
            $description = SecurityHelper::sanitizeString($data['description'] ?? '');
            $year = SecurityHelper::sanitizeInt($data['year'] ?? 0);
            $credits = isset($data['credits']) ? SecurityHelper::sanitizeInt($data['credits']) : 20;
            $module_leader_id = isset($data['module_leader_id']) ? SecurityHelper::sanitizeInt($data['module_leader_id']) : null;
            $is_core = isset($data['is_core']) ? (bool)$data['is_core'] : true;
            $is_active = isset($data['is_active']) ? (bool)$data['is_active'] : true;
            $created_by = isset($data['created_by']) ? SecurityHelper::sanitizeInt($data['created_by']) : 1;
            $module_staff_ids = [];
            if (!empty($data['module_staff_ids']) && is_array($data['module_staff_ids'])) {
                foreach ($data['module_staff_ids'] as $staffId) {
                    $module_staff_ids[] = SecurityHelper::sanitizeInt($staffId);
                }
            }

            // Validates required fields
            if (
                empty($title) ||
                empty($programme_id) ||
                empty($description) ||
                empty($year)
            ) {
                $response->getBody()->write(
                    json_encode(
                        [
                            'status' => 'error',
                            'message' => 'Campos obrigatÃƒÂ³rios (title, programme_id, description, year) nÃƒÂ£o fornecidos'
                        ]
                    )
                );
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Prepares and executes module insertion query
            $stmt = $this->db->prepare(
                'INSERT INTO modules (' .
                'title, programme_id, description, year, credits, ' .
                'module_leader_id, is_core, is_active, created_by' .
                ') VALUES (' .
                ':title, :programme_id, :description, :year, :credits, ' .
                ':module_leader_id, :is_core, :is_active, :created_by' .
                ')'
            );

            $stmt->execute(
                [
                    ':title' => $title,
                    ':programme_id' => $programme_id,
                    ':description' => $description,
                    ':year' => $year,
                    ':credits' => $credits,
                    ':module_leader_id' => $module_leader_id,
                    ':is_core' => $is_core,
                    ':is_active' => $is_active,
                    ':created_by' => $created_by
                ]
            );

            // Retrieves the new module ID
            $moduleId = $this->db->lastInsertId();
            // Inserts associated staff (excluding leader)
            if (!empty($module_staff_ids)) {
                $staffIds = $module_staff_ids;
                $leaderId = $module_leader_id;
                foreach ($staffIds as $staffId) {
                    if ($staffId && $staffId != $leaderId) {
                        $stmtStaff = $this->db->prepare('INSERT INTO module_staff (module_id, staff_id) VALUES (?, ?)');
                        $stmtStaff->execute([$moduleId, $staffId]);
                    }
                }
            }

            // Returns success response with new module ID
            $response->getBody()->write(
                json_encode(
                    [
                        'status' => 'success',
                        'message' => 'MÃƒÂ³dulo criado com sucesso',
                        'id' => $moduleId
                    ]
                )
            );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\PDOException $e) {
            // Returns error response for database issues
            $response->getBody()->write(
                json_encode(
                    [
                        'status' => 'error',
                        'message' => 'Erro ao criar mÃƒÂ³dulo: ' . $e->getMessage()
                    ]
                )
            );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /* ==================================================
       UPDATE ACTION
       --------------------------------------------------
       Purpose: Updates an existing module in the database.
       Functionality: Validates and updates module data and associated staff.
       Server-Side Role: Handles updates to module records securely.
       Logic: Sanitizes inputs, updates module, refreshes staff associations, and returns JSON response.
    */
    public function update(Request $request, Response $response): Response
    {
        try {
            // Parses JSON request body
            $data = json_decode($request->getBody()->getContents(), true);
            // Retrieves module ID from route
            $id = $request->getAttribute('id');

            // Validates ID and data
            if (!$id || empty($data)) {
                $response->getBody()->write(
                    json_encode(
                        [
                            'status' => 'error',
                            'message' => 'ID do mÃƒÂ³dulo ou dados nÃƒÂ£o fornecidos'
                        ]
                    )
                );
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Prepares and executes module update query
            $stmt = $this->db->prepare(
                '
                UPDATE modules
                SET title = :title,
                    programme_id = :programme_id,
                    description = :description,
                    year = :year,
                    credits = :credits,
                    module_leader_id = :module_leader_id,
                    is_core = :is_core,
                    is_active = :is_active
                WHERE id = :id
            '
            );

            $stmt->execute(
                [
                    ':title' => SecurityHelper::sanitizeString($data['title'] ?? null),
                    ':programme_id' => SecurityHelper::sanitizeInt($data['programme_id'] ?? null),
                    ':description' => SecurityHelper::sanitizeString($data['description'] ?? null),
                    ':year' => SecurityHelper::sanitizeInt($data['year'] ?? null),
                    ':credits' => SecurityHelper::sanitizeInt($data['credits'] ?? null),
                    ':module_leader_id' => SecurityHelper::sanitizeInt($data['module_leader_id'] ?? null),
                    ':is_core' => isset($data['is_core']) ? (bool)$data['is_core'] : null,
                    ':is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : null,
                    ':id' => $id
                ]
            );

            // Updates associated staff by removing old and adding new
            $stmtDel = $this->db->prepare('DELETE FROM module_staff WHERE module_id = ?');
            $stmtDel->execute([$id]);
            $staffChanges = false;
            if (!empty($data['module_staff_ids']) && is_array($data['module_staff_ids'])) {
                $staffIds = $data['module_staff_ids'];
                $leaderId = $data['module_leader_id'] ?? null;
                foreach ($staffIds as $staffId) {
                    if ($staffId && $staffId != $leaderId) {
                        $stmtStaff = $this->db->prepare('INSERT INTO module_staff (module_id, staff_id) VALUES (?, ?)');
                        $stmtStaff->execute([$id, $staffId]);
                        $staffChanges = true;
                    }
                }
            }

            // Checks for changes in module or staff
            $moduleChanges = $stmt->rowCount() > 0;
            error_log("Module update - Module changes: " . ($moduleChanges ? 'true' : 'false') . ", Staff changes: " . ($staffChanges ? 'true' : 'false'));
            if (!$moduleChanges && !$staffChanges) {
                $response->getBody()->write(
                    json_encode(
                        [
                            'status' => 'error',
                            'message' => 'MÃƒÂ³dulo nÃƒÂ£o encontrado ou sem alteraÃƒÂ§ÃƒÂµes'
                        ]
                    )
                );
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Returns success response
            $response->getBody()->write(
                json_encode(
                    [
                        'status' => 'success',
                        'message' => 'MÃƒÂ³dulo atualizado com sucesso'
                    ]
                )
            );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\PDOException $e) {
            // Returns error response for database issues
            $response->getBody()->write(
                json_encode(
                    [
                        'status' => 'error',
                        'message' => 'Erro ao atualizar mÃƒÂ³dulo: ' . $e->getMessage()
                    ]
                )
            );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /* ==================================================
       DELETE ACTION
       --------------------------------------------------
       Purpose: Deletes a module from the database.
       Functionality: Removes a module by ID.
       Server-Side Role: Handles secure deletion of module records.
       Logic: Validates ID, deletes module, and returns JSON response.
    */
    public function delete(Request $request, Response $response): Response
    {
        try {
            // Retrieves module ID from route
            $id = $request->getAttribute('id');

            // Validates ID
            if (!$id) {
                $response->getBody()->write(
                    json_encode(
                        [
                            'status' => 'error',
                            'message' => 'ID do mÃƒÂ³dulo nÃƒÂ£o fornecido'
                        ]
                    )
                );
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Prepares and executes deletion query
            $stmt = $this->db->prepare('DELETE FROM modules WHERE id = ?');
            $stmt->execute([$id]);

            // Checks if deletion occurred
            if ($stmt->rowCount() === 0) {
                $response->getBody()->write(
                    json_encode(
                        [
                            'status' => 'error',
                            'message' => 'MÃƒÂ³dulo nÃƒÂ£o encontrado'
                        ]
                    )
                );
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Returns success response
            $response->getBody()->write(
                json_encode(
                    [
                        'status' => 'success',
                        'message' => 'MÃƒÂ³dulo excluÃƒÂ­do com sucesso'
                    ]
                )
            );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\PDOException $e) {
            // Returns error response for database issues
            $response->getBody()->write(
                json_encode(
                    [
                        'status' => 'error',
                        'message' => 'Erro ao excluir mÃƒÂ³dulo: ' . $e->getMessage()
                    ]
                )
            );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /* ==================================================
       LIST STAFF ACTION
       --------------------------------------------------
       Purpose: Retrieves active staff members for dropdowns.
       Functionality: Fetches staff data for module leader selection.
       Server-Side Role: Provides JSON data for staff member listings.
       Logic: Queries active staff members and returns them as JSON.
    */
    public function listStaff(Request $request, Response $response): Response
    {
        try {
            // Queries active staff members
            $staff = $this->db->query(
                '
                SELECT sm.id, sm.title, u.full_name, sm.department, sm.specialization
                FROM staff_members sm
                JOIN users u ON sm.user_id = u.id
                WHERE sm.is_active = TRUE
                ORDER BY u.full_name, sm.title
            '
            )->fetchAll(\PDO::FETCH_ASSOC);

            // Returns staff as JSON
            $response->getBody()->write(
                json_encode(
                    [
                        'status' => 'success',
                        'data' => $staff
                    ]
                )
            );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\PDOException $e) {
            // Returns error response for database issues
            $response->getBody()->write(
                json_encode(
                    [
                        'status' => 'error',
                        'message' => 'Erro ao listar staff members: ' . $e->getMessage()
                    ]
                )
            );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /* ==================================================
       SHOW ACTION
       --------------------------------------------------
       Purpose: Retrieves a specific module by ID.
       Functionality: Fetches module details and associated staff IDs.
       Server-Side Role: Provides JSON data for a single module.
       Logic: Queries module and staff data, and returns JSON response.
    */
    public function show(Request $request, Response $response): Response
    {
        try {
            // Retrieves module ID from route
            $id = $request->getAttribute('id');

            // Validates ID
            if (!$id) {
                $response->getBody()->write(
                    json_encode(
                        [
                            'status' => 'error',
                            'message' => 'ID do mÃƒÂ³dulo nÃƒÂ£o fornecido'
                        ]
                    )
                );
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Queries module details
            $stmt = $this->db->prepare(
                '
                SELECT m.*, p.title AS programme_title, s.title AS module_leader
                FROM modules m
                LEFT JOIN programmes p ON m.programme_id = p.id
                LEFT JOIN staff_members s ON m.module_leader_id = s.id
                WHERE m.id = ?
            '
            );
            $stmt->execute([$id]);
            $module = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Checks if module exists
            if (!$module) {
                $response->getBody()->write(
                    json_encode(
                        [
                            'status' => 'error',
                            'message' => 'MÃƒÂ³dulo nÃƒÂ£o encontrado'
                        ]
                    )
                );
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Fetches associated staff IDs
            $stmtStaff = $this->db->prepare('SELECT staff_id FROM module_staff WHERE module_id = ?');
            $stmtStaff->execute([$id]);
            $staffIds = $stmtStaff->fetchAll(\PDO::FETCH_COLUMN);
            $module['staff_ids'] = $staffIds;

            // Returns module data as JSON
            $response->getBody()->write(
                json_encode(
                    [
                        'status' => 'success',
                        'data' => $module
                    ]
                )
            );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\PDOException $e) {
            // Returns error response for database issues
            $response->getBody()->write(
                json_encode(
                    [
                        'status' => 'error',
                        'message' => 'Erro ao buscar mÃƒÂ³dulo: ' . $e->getMessage()
                    ]
                )
            );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
