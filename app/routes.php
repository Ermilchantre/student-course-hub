<?php

// ==============================================================================
// Application Route Definitions (Summary)
// ------------------------------------------------------------------------------
// Defines all HTTP routes for the Student Course Hub application.
// Detailed explanations are provided in comments above each route block below.
// Usage: Included during application bootstrap to register all routes with Slim.
// ==============================================================================

use App\Controllers\HomeController;
use App\Controllers\ProgrammeController;
use App\Controllers\InterestController;
use App\Controllers\AdminDashboardController;
use App\Controllers\AdminModulesController;
use App\Controllers\AdminProgrammesController;
use App\Controllers\AdminInterestsController;
use App\Controllers\AdminLoginController;
use App\Controllers\AdminStaffController;
use App\Controllers\StaffDashboardController;
use App\Controllers\StaffLoginController;
use App\Middleware\AuthMiddleware;
use App\Middleware\StaffAuthMiddleware;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {
    // -----------------------------
    // Public routes
    // These endpoints are accessible to all users (students, staff, guests).
    // Includes home page, programme details, interest registration, and the 'about' page.
    // No authentication required.
    // -----------------------------
    $app->get('/', [HomeController::class, 'index']);
    $app->get('/programme/{id}', [ProgrammeController::class, 'show']);
    $app->get('/register-interest', [InterestController::class, 'index']);
    $app->post('/register-interest', [InterestController::class, 'store']);
    $app->get('/my-interests', [InterestController::class, 'myInterests']);
    $app->post('/my-interests/remove', [InterestController::class, 'removeInterest']);
    // 'About' page route: renders the institutional page using Twig.
    $app->get('/about', function ($request, $response) {
        $base_path = $request->getAttribute('base_path') ?? '';
        $twig = $this->get('view');
        $response->getBody()->write(
            $twig->render('about.twig', [
                'base_path' => $base_path
            ])
        );
        return $response;
    });

    // -----------------------------
    // Admin authentication routes (public)
    // Allow login, logout, and password reset for administrators.
    // No prior authentication required.
    // -----------------------------
    $app->get('/admin/login', [AdminLoginController::class, 'showLoginForm']);
    $app->post('/admin/login', [AdminLoginController::class, 'login']);
    $app->get('/admin/logout', [AdminLoginController::class, 'logout']);
    // Admin password reset routes: allow requesting and setting a new password via token.
    $app->get('/admin/forgot-password', [AdminLoginController::class, 'showForgotPasswordForm']);
    $app->post('/admin/forgot-password', [AdminLoginController::class, 'handleForgotPassword']);
    // This route displays the password reset form, accessed via a link with a token.
    $app->get('/admin/reset-password', [AdminLoginController::class, 'showResetPasswordForm']); // expects ?token=...
    $app->post('/admin/reset-password', [AdminLoginController::class, 'handleResetPassword']);

    // -----------------------------
    // Staff authentication routes (public)
    // Allow login and logout for staff members. No prior authentication required.
    // -----------------------------
    $app->get('/staff/login', [StaffLoginController::class, 'showLoginForm']);
    $app->post('/staff/login', [StaffLoginController::class, 'login']);
    $app->get('/staff/logout', [StaffLoginController::class, 'logout']);

    // -----------------------------
    // Staff protected routes group
    // All routes within this group require staff authentication.
    // StaffAuthMiddleware ensures only authenticated staff members can access.
    // Includes staff dashboard and APIs for the staff member's own modules/programmes.
    // -----------------------------
    $app->group(
        '/staff',
        function ($group) {
            $group->get('/dashboard', [StaffDashboardController::class, 'index']);
            $group->get('/api/my-modules', [StaffDashboardController::class, 'myModules']);
            $group->get('/api/my-programmes', [StaffDashboardController::class, 'myProgrammes']);
        }
    )->add(StaffAuthMiddleware::class);

    // -----------------------------
    // Admin protected routes group
    // All routes within this group require administrator authentication.
    // AuthMiddleware ensures only authenticated admins can access.
    // Includes dashboard, management pages, and admin APIs (CRUD, export, statistics, etc).
    // -----------------------------
    $app->group(
        '/admin',
        function ($group) {
            // Admin dashboard and management pages
            $group->get('/dashboard', [AdminDashboardController::class, 'index']);
            $group->get('/modules', [AdminModulesController::class, 'index']);
            $group->get('/programmes', [AdminProgrammesController::class, 'index']);
            $group->get('/interests', [AdminInterestsController::class, 'index']);
            // Admin APIs for CRUD, export, advanced search, etc.
            $group->get('/api/programmes', [AdminProgrammesController::class, 'list']);
            $group->post('/api/programmes', [AdminProgrammesController::class, 'create']);
            $group->put('/api/programmes/{id}', [AdminProgrammesController::class, 'update']);
            $group->delete('/api/programmes/{id}', [AdminProgrammesController::class, 'delete']);
            $group->post('/api/programmes/{id}', [AdminProgrammesController::class, 'update']);
            $group->get('/api/modules', [AdminModulesController::class, 'list']);
            $group->get('/api/modules/{id}', [AdminModulesController::class, 'show']);
            $group->post('/api/modules', [AdminModulesController::class, 'create']);
            $group->put('/api/modules/{id}', [AdminModulesController::class, 'update']);
            $group->delete('/api/modules/{id}', [AdminModulesController::class, 'delete']);
            $group->get('/api/staff', [AdminModulesController::class, 'listStaff']);
            $group->get('/api/interests', [AdminInterestsController::class, 'list']);
            $group->delete('/api/interests/{id}', [AdminInterestsController::class, 'delete']);
            $group->get('/api/programmes/{id}', [AdminProgrammesController::class, 'show']);
            $group->get('/api/interests/all', [AdminInterestsController::class, 'listAll']);
            $group->get('/api/interests/search', [AdminInterestsController::class, 'search']);
            // Advanced admin APIs for export, search, statistics, and staff registration
            $group->get('/api/interests/export', [AdminInterestsController::class, 'export']);
            $group->post('/api/interests/delete-multiple', [AdminInterestsController::class, 'deleteMultiple']);
            $group->get('/api/interests/search-advanced', [AdminInterestsController::class, 'searchAdvanced']);
            $group->get('/api/interests/stats', [AdminInterestsController::class, 'getStats']);
            $group->get('/staff/register', [AdminStaffController::class, 'showRegisterForm']);
            $group->post('/staff/register', [AdminStaffController::class, 'register']);
            // ---------------------------------------------------------------------
            // /api/stats endpoint:
            // This endpoint provides summary statistics for the admin dashboard.
            // It queries the database to count the total number of student interests and programmes,
            // and returns these counts along with a (mocked) satisfaction percentage as a JSON object.
            // The frontend uses this data to display key metrics on the admin dashboard.
            // ---------------------------------------------------------------------
            $group->get(
                '/api/stats',
                function (Request $request, Response $response) {
                    $db = $this->get('db');
                    $students = $db->query('SELECT COUNT(*) FROM student_interests')->fetchColumn();
                    $programmes = $db->query('SELECT COUNT(*) FROM programmes')->fetchColumn();
                    $response->getBody()->write(
                        json_encode(
                            [
                            'students' => $students,
                            'programmes' => $programmes,
                            'satisfaction' => '89' // Mocked, replace with real data
                            ]
                        )
                    );
                    return $response->withHeader('Content-Type', 'application/json');
                }
            );

            // ---------------------------------------------------------------------
            // /api/activities endpoint:
            // This endpoint provides the admin dashboard with a feed of the 5 most recent
            // system activities and notifications. It queries the database for the latest
            // notifications, joins with user data to show who triggered each activity, and
            // returns an array of activities (icon, title, description, user, timestamp) as JSON.
            // The frontend uses this to render a real-time activity feed for administrators.
            // ---------------------------------------------------------------------
            $group->get(
                '/api/activities',
                function (Request $request, Response $response) {
                    $db = $this->get('db');
                    $stmt = $db->query(
                        '
                SELECT
                    n.id,
                    n.title,
                    n.message,
                    n.type,
                    n.created_at,
                    u.full_name as user_name
                FROM notifications n
                LEFT JOIN users u ON n.user_id = u.id
                ORDER BY n.created_at DESC
                LIMIT 5
            '
                    );
                    $activities = $stmt->fetchAll();
                    $response->getBody()->write(
                        json_encode(
                            array_map(
                                function ($activity) {
                                    $iconMap = [
                                    'success' => 'check-circle',
                                    'warning' => 'exclamation-triangle',
                                    'error' => 'times-circle',
                                    'info' => 'info-circle'
                                    ];

                                    return [
                                    'icon' => $iconMap[$activity['type']] ?? 'info-circle',
                                    'title' => $activity['title'],
                                    'description' => $activity['message'],
                                    'user' => $activity['user_name'] ?? 'System',
                                    'created_at' => $activity['created_at']
                                    ];
                                },
                                $activities
                            )
                        )
                    );
                    return $response->withHeader('Content-Type', 'application/json');
                }
            );

            // =====================================================================
            // Notification Management Endpoints
            // ---------------------------------------------------------------------
            // These endpoints allow administrators to manage system notifications.
            // For example, deleting a notification by its ID. The result is returned
            // as a JSON response indicating success or failure, which can be used
            // by the frontend to update the UI accordingly.
            // =====================================================================

            // API to delete notification by ID: receives the ID, tries to remove from the database, and returns success/error.
            $group->delete(
                '/api/notifications/{id}/delete',
                function (Request $request, Response $response, array $args) {
                    $db = $this->get('db');
                    $notificationId = $args['id'];

                    try {
                        $stmt = $db->prepare('DELETE FROM notifications WHERE id = ?');
                        $result = $stmt->execute([$notificationId]);

                        if ($result && $stmt->rowCount() > 0) {
                            $response->getBody()->write(json_encode(['success' => true]));
                        } else {
                            $response->getBody()->write(
                                json_encode([
                                    'success' => false,
                                    'message' => 'Notification not found'
                                ])
                            );
                        }
                    } catch (Exception $e) {
                        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Database error']));
                    }

                    return $response->withHeader('Content-Type', 'application/json');
                }
            );
        }
    )->add(AuthMiddleware::class);
};
