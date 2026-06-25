<?php

/* ==================================================
   HOME CONTROLLER
   --------------------------------------------------
   Purpose: Manages the homepage functionality in a Slim 4 application.
   Functionality: Handles rendering of the homepage, including paginated programme listings and testimonial data.
   Server-Side Role: Processes HTTP requests for the homepage and retrieves data for display.
   ==================================================
*/

namespace App\Controllers;

/* ==================================================
   IMPORTS
   --------------------------------------------------
   Purpose: Imports required PSR-7 interfaces and application dependencies.
   Functionality: Provides types for request/response handling, Twig for templating, and Programme model for database operations.
   Server-Side Role: Ensures compatibility with Slim 4 and enables data retrieval and rendering.
   Logic: Imports PSR-7 interfaces, Twig environment, and Programme model for controller operations.
*/
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use App\Models\Programme;

/* ==================================================
   HOME CONTROLLER CLASS
   --------------------------------------------------
   Purpose: Manages the homepage display in the application.
   Functionality: Retrieves paginated programme data, processes testimonial images, and renders the homepage template.
   Server-Side Role: Handles homepage requests, interacts with the database, and prepares data for the view.
   Logic: Initializes dependencies and defines the index method for homepage rendering.
*/
class HomeController
{
    /* ==================================================
       PROPERTY DEFINITION
       --------------------------------------------------
       Purpose: Stores dependencies for rendering and database operations.
       Functionality: Holds references to Twig environment and Programme model.
       Server-Side Role: Provides access to template rendering and programme data retrieval.
       Logic: Declares private properties for dependency injection.
    */
    private $twig;
    private $programme;

    /* ==================================================
       CONSTRUCTOR
       --------------------------------------------------
       Purpose: Initializes the controller with required dependencies.
       Functionality: Assigns Twig and Programme model instances for use in methods.
       Server-Side Role: Sets up the controller for homepage requests.
       Logic: Stores injected Twig and Programme model dependencies.
    */
    public function __construct(Environment $twig, Programme $programme)
    {
        // Assigns injected dependencies to class properties
        $this->twig = $twig;
        $this->programme = $programme;
    }

    /* ==================================================
       INDEX ACTION
       --------------------------------------------------
       Purpose: Renders the homepage with programme listings and testimonials.
       Functionality: Retrieves paginated programmes, processes testimonial images with associated names, and renders the homepage template.
       Server-Side Role: Handles GET requests for the homepage, queries the database, and prepares data for display.
       Logic: Processes query parameters for pagination and filtering, fetches programme data, organizes testimonial images, and renders the Twig template.
    */
    public function index(Request $request, Response $response): Response
    {
        /* Retrieves query parameters for pagination and filtering
           - $queryParams = $request->getQueryParams(): Extracts query parameters (e.g., page, level) from the request URL.
           - Purpose: Captures user input for pagination (page number) and optional programme level filtering.
           - Server-Side Role: Provides parameters to control programme data retrieval and display.
           - Logic: Sets default page to 1 and ensures itÃ¢â‚¬â„¢s a positive integer; captures optional level filter.
        */
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? max(1, (int)$queryParams['page']) : 1;
        $perPage = 6;
        $offset = ($page - 1) * $perPage;
        $level = isset($queryParams['level']) ? $queryParams['level'] : null;

        /* Fetches paginated programme data
           - $programmes = $this->programme->paginate($perPage, $offset, $level): Retrieves a subset of programmes based on pagination parameters and optional level filter.
           - $totalProgrammes = $this->programme->countFiltered($level): Counts total programmes matching the level filter for pagination calculations.
           - $totalPages = (int)ceil($totalProgrammes / $perPage): Calculates total pages for pagination.
           - Purpose: Retrieves programme data for display and calculates pagination metadata.
           - Server-Side Role: Queries the database to fetch programmes and supports pagination logic.
           - Logic: Uses Programme model methods to fetch data and compute total pages.
        */
        $programmes = $this->programme->paginate($perPage, $offset, $level);
        $totalProgrammes = $this->programme->countFiltered($level);
        $totalPages = (int)ceil($totalProgrammes / $perPage);

        // Ensure each programme has a description
        foreach ($programmes as &$programme) {
            if (empty($programme['description'])) {
                $programme['description'] = 'No description available.';
            }

            // Limit description if too long - make it more concise for compact cards
            if (strlen($programme['description']) > 200) {
                $programme['description'] = substr($programme['description'], 0, 200) . '...';
            }
        }

        /* Processes testimonial images and assigns names
           - Purpose: Loads images from the testimonials directory and associates them with fictional names based on gender.
           - Functionality: Scans the testimonials directory, categorizes images by filename prefix (studentm for male, studentw for female), and pairs them with predefined names.
           - Server-Side Role: Prepares testimonial data for display on the homepage.
           - Logic: Checks for directory existence, categorizes images, assigns names from male and female arrays, and builds testimonial data array.
        */
        $testimonialDir = __DIR__ . '/../../public/images/testimonials/';
        $testimonialData = [];
        $maleNames = ['James Smith', 'John Johnson', 'Robert Williams', 'Michael Brown', 'David Jones'];
        $femaleNames = ['Emily Smith', 'Olivia Johnson', 'Sophia Williams', 'Ava Brown', 'Isabella Jones'];
        $maleImages = [];
        $femaleImages = [];
        if (is_dir($testimonialDir)) {
            foreach (glob($testimonialDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) as $file) {
                $filename = strtolower(basename($file));
                if (strpos($filename, 'studentm') === 0) {
                    $maleImages[] = '/images/testimonials/' . basename($file);
                } elseif (strpos($filename, 'studentw') === 0) {
                    $femaleImages[] = '/images/testimonials/' . basename($file);
                }
            }
        }
        foreach ($maleImages as $i => $img) {
            $testimonialData[] = [
                'image' => $img,
                'name' => $maleNames[$i % count($maleNames)]
            ];
        }
        foreach ($femaleImages as $i => $img) {
            $testimonialData[] = [
                'image' => $img,
                'name' => $femaleNames[$i % count($femaleNames)]
            ];
        }

        /* Renders the homepage template
           - Purpose: Displays the homepage with programme listings, pagination data, and testimonials.
           - Functionality: Passes programmes, pagination details, level filter, base path, and testimonial data to the Twig template.
           - Server-Side Role: Combines all prepared data and renders the final HTML output.
           - Logic: Uses Twig to render the home.twig template with all necessary data.
        */
        $response->getBody()->write(
            $this->twig->render(
                'home.twig',
                [
                    'programmes' => $programmes,
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'level' => $level,
                    'base_path' => fn() => $request->getAttribute('base_path'),
                    'testimonial_data' => $testimonialData
                ]
            )
        );
        return $response;
    }
}
