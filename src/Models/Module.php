<?php

/* ==================================================
   MODULE MODEL
   --------------------------------------------------
   Purpose: Manages module-related database operations in a Slim 4 application.
   Functionality: Provides methods to retrieve, create, update, and delete modules, as well as fetch related programmes and professors.
   Server-Side Role: Handles database interactions for module data management.
   Version: 1.0
   Last Updated: July 23, 2025
   ==================================================
*/

namespace App\Models;

/* ==================================================
   IMPORTS
   --------------------------------------------------
   Purpose: Imports required dependencies for database operations.
   Functionality: Provides PDO for database connectivity.
   Server-Side Role: Enables secure database interactions.
   Logic: Imports PDO for database queries.
*/
use PDO;

/* ==================================================
   MODULE CLASS
   --------------------------------------------------
   Purpose: Manages module data operations in the application.
   Functionality: Supports retrieval, creation, updating, deletion of modules, and fetching related programme and professor data.
   Server-Side Role: Interacts with the database to manage module records and relationships.
   Logic: Initializes PDO dependency and defines methods for module CRUD operations and related queries.
*/
class Module
{
    /* ==================================================
       PROPERTY DEFINITION
       --------------------------------------------------
       Purpose: Stores the database connection.
       Functionality: Holds reference to PDO instance for database operations.
       Server-Side Role: Provides access to database services.
       Logic: Declares private property for PDO dependency injection.
    */
    private $db;

    /* ==================================================
       CONSTRUCTOR
       --------------------------------------------------
       Purpose: Initializes the model with a database connection.
       Functionality: Assigns PDO instance for use in methods.
       Server-Side Role: Sets up the model for module-related database operations.
       Logic: Stores injected PDO dependency.
    */
    public function __construct(PDO $db)
    {
        // Assigns injected PDO instance to class property
        $this->db = $db;
    }

    /* ==================================================
       ALL METHOD
       --------------------------------------------------
       Purpose: Retrieves all active modules with associated programme and leader data.
       Functionality: Fetches module records along with programme titles and module leader details.
       Server-Side Role: Queries the database for a comprehensive list of active modules.
       Logic: Executes a SELECT query with joins to fetch module data and returns results.
    */
    public function all()
    {
        /* Queries all active modules
           - Purpose: Retrieves all active module records with programme and leader information.
           - Functionality: Joins modules with programmes and staff_members tables to include titles and leader names.
           - Server-Side Role: Provides a complete dataset of active modules for display or processing.
           - Logic: Uses PDO query to fetch all active modules with associated data.
        */
        $stmt = $this->db->query(
            'SELECT m.*, p.title AS programme_title, s.title AS leader_title, ' .
            'CONCAT(s.title, " ", s.department) AS leader_name ' .
            'FROM modules m ' .
            'LEFT JOIN programmes p ON m.programme_id = p.id ' .
            'LEFT JOIN staff_members s ON m.module_leader_id = s.id ' .
            'WHERE m.is_active = TRUE'
        );
        return $stmt->fetchAll();
    }

    /* ==================================================
       FIND BY PROGRAMME ID METHOD
       --------------------------------------------------
       Purpose: Retrieves active modules for a specific programme.
       Functionality: Fetches modules with leader details for a given programme ID.
       Server-Side Role: Queries the database for programme-specific module data.
       Logic: Uses a prepared statement to fetch modules and associated leader information.
    */
    public function findByProgrammeId($programmeId)
    {
        /* Queries modules by programme ID
           - Purpose: Retrieves active modules linked to a specific programme.
           - Functionality: Joins modules with staff_members and users tables to include leader details like title, name, email, and photo.
           - Server-Side Role: Provides detailed module data for programme-specific views.
           - Logic: Uses prepared statement to prevent SQL injection and fetches results.
        */
        $stmt = $this->db->prepare(
            '
            SELECT m.*,
                   s.title AS leader_title,
                   u.full_name AS leader_name,
                   s.photo_url AS leader_photo_url,
                   u.email AS leader_email,
                   s.department AS leader_department,
                   s.specialization AS leader_specialization,
                   s.phone AS leader_phone,
                   s.office_location AS leader_office
            FROM modules m
            LEFT JOIN staff_members s ON m.module_leader_id = s.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE m.programme_id = ? AND m.is_active = TRUE
            '
        );
        $stmt->execute([$programmeId]);
        return $stmt->fetchAll();
    }

    /* ==================================================
       CREATE METHOD
       --------------------------------------------------
       Purpose: Creates a new module record in the database.
       Functionality: Inserts module data including title, programme ID, description, year, and leader.
       Server-Side Role: Persists new module data to the database.
       Logic: Uses a prepared statement to insert data and returns the new module ID.
    */
    public function create($data)
    {
        /* Inserts new module record
           - Purpose: Stores a new module in the database.
           - Functionality: Inserts module details with a hardcoded created_by value.
           - Server-Side Role: Manages module creation with secure data insertion.
           - Logic: Uses prepared statement to insert data and returns last inserted ID.
        */
        $stmt = $this->db->prepare(
            'INSERT INTO modules (' .
            'title, programme_id, description, year, module_leader_id, created_by' .
            ') VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'],
            $data['programme_id'],
            $data['description'],
            $data['year'],
            $data['module_leader_id'],
            1 // Hardcoded created_by value
        ]);
        return $this->db->lastInsertId();
    }

    /* ==================================================
       FIND BY LEADER ID METHOD
       --------------------------------------------------
       Purpose: Retrieves active modules led by a specific staff member.
       Functionality: Fetches modules with programme titles and levels for a given leader ID.
       Server-Side Role: Queries the database for leader-specific module data.
       Logic: Uses a prepared statement to fetch modules and associated programme data.
    */
    public function findByLeaderId($leaderId)
    {
        /* Queries modules by leader ID
           - Purpose: Retrieves active modules where the specified staff member is the leader.
           - Functionality: Joins modules with programmes to include programme title and level, ordered by title and year.
           - Server-Side Role: Provides module data for staff-specific views.
           - Logic: Uses prepared statement to prevent SQL injection and fetches results.
        */
        $stmt = $this->db->prepare(
            '
            SELECT m.*, p.title AS programme_title, p.level AS programme_level
            FROM modules m
            JOIN programmes p ON m.programme_id = p.id
            WHERE m.module_leader_id = ? AND m.is_active = TRUE
            ORDER BY p.title, m.year, m.title
            '
        );
        $stmt->execute([$leaderId]);
        return $stmt->fetchAll();
    }

    /* ==================================================
       UPDATE METHOD
       --------------------------------------------------
       Purpose: Updates an existing module record in the database.
       Functionality: Modifies module details such as title, programme ID, description, year, and leader.
       Server-Side Role: Persists updated module data to the database.
       Logic: Uses a prepared statement to update the module record by ID.
    */
    public function update($id, $data)
    {
        /* Updates module record
           - Purpose: Modifies an existing module in the database.
           - Functionality: Updates specified fields for the module with the given ID.
           - Server-Side Role: Manages module updates with secure data handling.
           - Logic: Uses prepared statement to update module data securely.
        */
        $stmt = $this->db->prepare(
            'UPDATE modules SET title = ?, programme_id = ?, description = ?, year = ?, module_leader_id = ? ' .
            'WHERE id = ?'
        );
        $stmt->execute([
            $data['title'],
            $data['programme_id'],
            $data['description'],
            $data['year'],
            $data['module_leader_id'],
            $id
        ]);
    }

    /* ==================================================
       DELETE METHOD
       --------------------------------------------------
       Purpose: Deletes a module record from the database.
       Functionality: Removes the module with the specified ID.
       Server-Side Role: Handles module deletion securely.
       Logic: Uses a prepared statement to delete the module record.
    */
    public function delete($id)
    {
        /* Deletes module record
           - Purpose: Removes a module from the database.
           - Functionality: Deletes the module with the given ID.
           - Server-Side Role: Manages secure deletion of module data.
           - Logic: Uses prepared statement to delete the module securely.
        */
        $stmt = $this->db->prepare('DELETE FROM modules WHERE id = ?');
        $stmt->execute([$id]);
    }

    /* ==================================================
       FIND OTHER PROGRAMMES BY MODULE METHOD
       --------------------------------------------------
       Purpose: Finds other programmes sharing a module with the same title.
       Functionality: Retrieves programmes containing a module with the specified title, excluding the given programme.
       Server-Side Role: Queries the database for related programme data.
       Logic: Uses a prepared statement to fetch programmes with matching module titles.
    */
    public function findOtherProgrammesByModule($moduleTitle, $excludeProgrammeId)
    {
        /* Queries programmes with matching module title
           - Purpose: Identifies programmes with modules of the same title, excluding the specified programme.
           - Functionality: Joins modules and programmes tables to fetch programme IDs and titles.
           - Server-Side Role: Provides data on related programmes for module context.
           - Logic: Uses prepared statement to prevent SQL injection and exclude specific programme.
        */
        $stmt = $this->db->prepare(
            '
            SELECT p.id, p.title
            FROM modules m
            INNER JOIN programmes p ON m.programme_id = p.id
            WHERE m.title = ? AND p.id != ? AND m.is_active = TRUE
            '
        );
        $stmt->execute([$moduleTitle, $excludeProgrammeId]);
        return $stmt->fetchAll();
    }

    /* ==================================================
       GET PROFESSORS METHOD
       --------------------------------------------------
       Purpose: Retrieves professors associated with a module, excluding the module leader.
       Functionality: Fetches staff details for a module, optionally excluding the leader.
       Server-Side Role: Queries the database for professor data linked to a module.
       Logic: Uses a prepared statement to fetch staff details with dynamic exclusion of leader.
    */
    public function getProfessors($moduleId, $excludeLeaderId = null)
    {
        /* Builds and executes query for professors
           - Purpose: Retrieves staff members assigned to a module, excluding the leader if specified.
           - Functionality: Joins module_staff, staff_members, and users tables to fetch staff details.
           - Server-Side Role: Provides professor data for module-specific views.
           - Logic: Dynamically adjusts query to exclude leader if provided; uses prepared statement for security.
        */
        $sql = 'SELECT sm.*, u.full_name, u.email, sm.title, sm.department, sm.specialization, sm.photo_url '
            . 'FROM module_staff ms '
            . 'JOIN staff_members sm ON ms.staff_id = sm.id '
            . 'JOIN users u ON sm.user_id = u.id '
            . 'WHERE ms.module_id = ?';
        $params = [$moduleId];
        if ($excludeLeaderId) {
            $sql .= ' AND sm.id != ?';
            $params[] = $excludeLeaderId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
