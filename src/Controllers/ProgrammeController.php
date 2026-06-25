<?php

/* ==================================================
   PROGRAMME CONTROLLER
   --------------------------------------------------
   Purpose: Manages programme-related functionality in a Slim 4 application.
   Functionality: Handles rendering of programme details, including associated modules and staff information.
   Server-Side Role: Processes HTTP requests for programme details and retrieves data from the database.
   ==================================================
*/

namespace App\Controllers;

/* ==================================================
   IMPORTS
   --------------------------------------------------
   Purpose: Imports required PSR-7 interfaces and application dependencies.
   Functionality: Provides types for request/response handling, Twig for templating, and models for database operations.
   Server-Side Role: Ensures compatibility with Slim 4 and enables data retrieval and rendering.
   Logic: Imports PSR-7 interfaces, Twig, and models for Programme, Module, and StaffMember.
*/
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use App\Models\Programme;
use App\Models\Module;
use App\Models\StaffMember;

/* ==================================================
   PROGRAMME CONTROLLER CLASS
   --------------------------------------------------
   Purpose: Manages programme detail display in the application.
   Functionality: Retrieves programme details, associated modules, related programmes, professors, and staff, then renders the programme details view.
   Server-Side Role: Handles programme-related HTTP requests and database interactions.
   Logic: Initializes dependencies and defines a method for displaying programme details.
*/
class ProgrammeController
{
    /* ==================================================
       PROPERTY DEFINITION
       --------------------------------------------------
       Purpose: Stores dependencies for rendering and database operations.
       Functionality: Holds references to Twig environment and models for Programme, Module, and StaffMember.
       Server-Side Role: Provides access to template rendering and database services.
       Logic: Declares private properties for dependency injection.
    */
    private $twig;
    private $programme;
    private $module;
    private $staff;

    /* ==================================================
       CONSTRUCTOR
       --------------------------------------------------
       Purpose: Initializes the controller with required dependencies.
       Functionality: Assigns Twig and model instances for use in methods.
       Server-Side Role: Sets up the controller for programme-related requests.
       Logic: Stores injected Twig, Programme, Module, and StaffMember dependencies.
    */
    public function __construct(Environment $twig, Programme $programme, Module $module, StaffMember $staff)
    {
        // Assigns injected dependencies to class properties
        $this->twig = $twig;
        $this->programme = $programme;
        $this->module = $module;
        $this->staff = $staff;
    }

    /* ==================================================
       SHOW ACTION
       --------------------------------------------------
       Purpose: Renders the programme details page.
       Functionality: Retrieves programme data, associated modules, related programmes, professors, and staff, then displays the details template.
       Server-Side Role: Handles GET requests for programme details and prepares data for display.
       Logic: Fetches programme by ID, retrieves related modules and staff, enhances module data, and renders the Twig template.
    */
    public function show(Request $request, Response $response, array $args): Response
    {
        /* Retrieves programme by ID
           - $programme = $this->programme->findById($args['id']): Fetches programme details using the provided ID from route arguments.
           - Purpose: Retrieves the specific programme to display its details.
           - Server-Side Role: Queries the database to fetch programme data.
           - Logic: Uses the Programme model to find the record by ID; handles non-existent programmes.
        */
        $programme = $this->programme->findById($args['id']);
        if (!$programme) {
            /* Handles case where programme is not found
               - Purpose: Displays an error message if the programme does not exist.
               - Functionality: Renders the programme details template with an error message.
               - Server-Side Role: Provides user feedback for invalid programme IDs.
               - Logic: Uses Twig to render the template with an error and base path.
            */
            $response->getBody()->write(
                $this->twig->render(
                    'programme-details.twig',
                    [
                        'error' => 'Programme not found',
                        'base_path' => fn() => $request->getAttribute('base_path')
                    ]
                )
            );
            return $response;
        }

        /* Fetches modules and enhances with related data
           - $modules = $this->module->findByProgrammeId($args['id']): Retrieves all modules associated with the programme.
           - Purpose: Collects module data to display alongside programme details.
           - Server-Side Role: Queries the database for module information.
           - Logic: Fetches modules, then iterates to add related programmes and professors for each module.
        */
        $modules = $this->module->findByProgrammeId($args['id']);
        foreach ($modules as &$module) {
            /* Adds related programmes and professors to each module
               - $module['other_programmes'] = $this->module->findOtherProgrammesByModule($module['title'], $args['id']): Finds other programmes sharing the same module title.
               - $module['professors'] = $this->module->getProfessors($module['id'], $module['module_leader_id'] ?? null): Retrieves professors for the module, excluding the module leader.
               - Purpose: Enhances module data with related programmes and teaching staff.
               - Server-Side Role: Enriches module information for comprehensive display.
               - Logic: Queries additional data for each module and attaches it to the module array.
            */
            $module['other_programmes'] = $this->module->findOtherProgrammesByModule($module['title'], $args['id']);
            $module['professors'] = $this->module->getProfessors($module['id'], $module['module_leader_id'] ?? null);
        }
        unset($module); // Unsets reference to avoid unintended side effects

        /* Fetches all staff members
           - $staff = $this->staff->all(): Retrieves all staff members for display.
           - Purpose: Provides staff data for the programme details page.
           - Server-Side Role: Queries the database for staff information.
           - Logic: Uses the StaffMember model to fetch all records (noted as simulation, may need adjustment).
        */
        $staff = $this->staff->all(); // SimulaÃƒÂ§ÃƒÂ£o, ajustar conforme necessÃƒÂ¡rio

        /* Renders the programme details template
           - Purpose: Displays programme details, modules, and staff information.
           - Functionality: Passes programme, modules, staff, and base path to the Twig template.
           - Server-Side Role: Combines all data and renders the final HTML output.
           - Logic: Uses Twig to render the programme-details.twig template with all prepared data.
        */
        $response->getBody()->write(
            $this->twig->render(
                'programme-details.twig',
                [
                    'programme' => $programme,
                    'modules' => $modules,
                    'staff' => $staff,
                    'base_path' => fn() => $request->getAttribute('base_path')
                ]
            )
        );
        return $response;
    }
}
