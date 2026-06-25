<?php

/* ==================================================
   PROGRAMME MODEL
   --------------------------------------------------
   Purpose: Manages programme-related database operations in a Slim 4 application.
   Functionality: Provides methods to retrieve, create, update, delete, and paginate programme records, and fetch programmes by staff member.
   Server-Side Role: Handles database interactions for programme data management.
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
   PROGRAMME CLASS
   --------------------------------------------------
   Purpose: Manages programme data operations in the application.
   Functionality: Supports retrieving all programmes, creating, updating, deleting, paginating, and fetching programmes associated with a staff member.
   Server-Side Role: Interacts with the database to manage programme records and relationships.
   Logic: Initializes PDO dependency and defines methods for programme CRUD operations and specialized queries.
*/
class Programme
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
       Server-Side Role: Sets up the model for programme-related database operations.
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
       Purpose: Retrieves all published programme records.
       Functionality: Fetches all programmes that are marked as published.
       Server-Side Role: Queries the database for public-facing programme data.
       Logic: Executes a SELECT query to fetch published programmes.
    */
    public function all()
    {
        /* Queries all published programmes
           - Purpose: Retrieves all programme records with is_published set to TRUE.
           - Functionality: Fetches all columns from the programmes table.
           - Server-Side Role: Provides programme data for public views.
           - Logic: Uses PDO query to fetch all published records.
        */
        $stmt = $this->db->query('SELECT * FROM programmes WHERE is_published = TRUE');
        return $stmt->fetchAll();
    }

    /* ==================================================
       ALL ADMIN METHOD
       --------------------------------------------------
       Purpose: Retrieves all programme records for administrative use.
       Functionality: Fetches all programmes, including unpublished ones, ordered by creation date.
       Server-Side Role: Queries the database for admin-facing programme data.
       Logic: Executes a SELECT query to fetch all programmes sorted by created_at.
    */
    public function allAdmin()
    {
        /* Queries all programmes for admin
           - Purpose: Retrieves all programme records, regardless of publication status.
           - Functionality: Fetches all columns from the programmes table, ordered by creation date.
           - Server-Side Role: Provides comprehensive programme data for admin interfaces.
           - Logic: Uses PDO query to fetch all records sorted by created_at DESC.
        */
        $stmt = $this->db->query('SELECT * FROM programmes ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    /* ==================================================
       FIND BY ID METHOD
       --------------------------------------------------
       Purpose: Retrieves a single programme by its ID.
       Functionality: Fetches a programme record based on the provided ID.
       Server-Side Role: Queries the database for a specific programme.
       Logic: Uses a prepared statement to fetch the programme record securely.
    */
    public function findById($id)
    {
        /* Queries programme by ID
           - Purpose: Retrieves a specific programme record.
           - Functionality: Fetches all columns for the programme with the given ID.
           - Server-Side Role: Provides detailed programme data for specific views.
           - Logic: Uses prepared statement to prevent SQL injection and fetch record.
        */
        $stmt = $this->db->prepare('SELECT * FROM programmes WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /* ==================================================
       CREATE METHOD
       --------------------------------------------------
       Purpose: Creates a new programme record in the database.
       Functionality: Inserts programme data including title, level, description, and other attributes.
       Server-Side Role: Persists new programme data to the database.
       Logic: Uses a prepared statement to insert data and returns the new programme ID.
    */
    public function create($data)
    {
        /* Inserts new programme record
           - Purpose: Stores a new programme in the database.
           - Functionality: Inserts programme details with default values for optional fields.
           - Server-Side Role: Manages programme creation with secure data insertion.
           - Logic: Uses prepared statement to insert data; defaults created_by to 1.
        */
        $stmt = $this->db->prepare(
            '
            INSERT INTO programmes (
                title, level, description, image_url, is_published,
                duration_years, credits, entry_requirements, career_prospects, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        '
        );
        $stmt->execute(
            [
                $data['title'],
                $data['level'],
                $data['description'],
                $data['image_url'] ?? null,
                $data['is_published'] ?? false,
                $data['duration_years'] ?? 3,
                $data['credits'] ?? null,
                $data['entry_requirements'] ?? null,
                $data['career_prospects'] ?? null,
                $data['created_by'] ?? 1
            ]
        );
        return $this->db->lastInsertId();
    }

    /* ==================================================
       UPDATE METHOD
       --------------------------------------------------
       Purpose: Updates an existing programme record in the database.
       Functionality: Modifies programme details such as title, level, description, and other attributes.
       Server-Side Role: Persists updated programme data to the database.
       Logic: Uses a prepared statement to update the programme and logs update details.
    */
    public function update($id, $data)
    {
        /* Updates programme record
           - Purpose: Modifies an existing programme in the database.
           - Functionality: Updates specified fields for the programme with the given ID.
           - Server-Side Role: Manages programme updates with secure data handling.
           - Logic: Uses prepared statement to update data; logs update details and rows affected.
        */
        $stmt = $this->db->prepare(
            '
            UPDATE programmes SET
                title = ?,
                level = ?,
                description = ?,
                image_url = ?,
                is_published = ?,
                duration_years = ?,
                credits = ?,
                entry_requirements = ?,
                career_prospects = ?
            WHERE id = ?
        '
        );
        $stmt->execute(
            [
                $data['title'],
                $data['level'],
                $data['description'],
                $data['image_url'] ?? null,
                isset($data['is_published']) ? (int)$data['is_published'] : 0,
                $data['duration_years'] ?? 3,
                $data['credits'] ?? null,
                $data['entry_requirements'] ?? null,
                $data['career_prospects'] ?? null,
                $id
            ]
        );

        /* Logs update details for debugging
           - Purpose: Records update data and affected rows for troubleshooting.
           - Functionality: Logs input data and number of rows affected by the update.
           - Server-Side Role: Assists in debugging update operations.
           - Logic: Uses error_log to capture update details and row count.
        */
        error_log('Update data: ' . print_r($data, true));
        error_log('Rows affected: ' . $stmt->rowCount());
        return $stmt->rowCount();
    }

    /* ==================================================
       DELETE METHOD
       --------------------------------------------------
       Purpose: Deletes a programme record from the database.
       Functionality: Removes the programme with the specified ID.
       Server-Side Role: Handles programme deletion securely.
       Logic: Uses a prepared statement to delete the programme record.
    */
    public function delete($id)
    {
        /* Deletes programme record
           - Purpose: Removes a programme from the database.
           - Functionality: Deletes the programme with the given ID.
           - Server-Side Role: Manages secure deletion of programme data.
           - Logic: Uses prepared statement to delete the record securely.
        */
        $stmt = $this->db->prepare('DELETE FROM programmes WHERE id = ?');
        $stmt->execute([$id]);
    }

    /* ==================================================
       PAGINATE METHOD
       --------------------------------------------------
       Purpose: Retrieves paginated programme records, optionally filtered by level.
       Functionality: Fetches a subset of published programmes with optional level filtering.
       Server-Side Role: Queries the database for paginated programme data.
       Logic: Builds a dynamic query with pagination and optional level filter.
    */
    public function paginate($limit, $offset, $level = null)
    {
        /* Builds dynamic query for pagination
           - Purpose: Retrieves a subset of published programmes, optionally filtered by level.
           - Functionality: Constructs SQL query with limit, offset, and optional level filter.
           - Server-Side Role: Provides paginated data for programme listings.
           - Logic: Dynamically adds level filter for undergraduate or postgraduate programmes.
        */
        $sql = 'SELECT * FROM programmes WHERE is_published = TRUE';
        $params = [];
        if ($level && in_array(strtolower($level), ['undergraduate', 'postgraduate'])) {
            $sql .= ' AND level = :level';
            if (strtolower($level) === 'undergraduate') {
                $params[':level'] = 'UG';
            } elseif (strtolower($level) === 'postgraduate') {
                $params[':level'] = 'PG';
            }
        }
        $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

        /* Prepares and executes paginated query
           - Purpose: Fetches paginated programme records.
           - Functionality: Binds limit, offset, and optional level parameters to the query.
           - Server-Side Role: Ensures secure and efficient data retrieval.
           - Logic: Uses prepared statement with bound parameters for security and pagination.
        */
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /* ==================================================
       COUNT FILTERED METHOD
       --------------------------------------------------
       Purpose: Counts published programmes, optionally filtered by level.
       Functionality: Returns the total number of programmes matching the filter criteria.
       Server-Side Role: Queries the database for programme count for pagination.
       Logic: Builds a dynamic query to count programmes with optional level filter.
    */
    public function countFiltered($level = null)
    {
        /* Builds dynamic query for counting programmes
           - Purpose: Counts published programmes, optionally filtered by level.
           - Functionality: Constructs SQL query to count records with optional level filter.
           - Server-Side Role: Provides count for pagination calculations.
           - Logic: Dynamically adds level filter for undergraduate or postgraduate programmes.
        */
        $sql = 'SELECT COUNT(*) as total FROM programmes WHERE is_published = TRUE';
        $params = [];
        if ($level && in_array(strtolower($level), ['undergraduate', 'postgraduate'])) {
            $sql .= ' AND level = :level';
            if (strtolower($level) === 'undergraduate') {
                $params[':level'] = 'UG';
            } elseif (strtolower($level) === 'postgraduate') {
                $params[':level'] = 'PG';
            }
        }

        /* Executes count query
           - Purpose: Retrieves the total number of matching programmes.
           - Functionality: Binds optional level parameter and executes query.
           - Server-Side Role: Supports pagination by providing total record count.
           - Logic: Uses prepared statement and returns integer count.
        */
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    /* ==================================================
       FIND BY STAFF MEMBER METHOD
       --------------------------------------------------
       Purpose: Retrieves published programmes associated with a staff memberÃ¢â‚¬â„¢s modules.
       Functionality: Fetches programmes linked to modules led by a staff member, including module count.
       Server-Side Role: Queries the database for staff-specific programme data.
       Logic: Uses a prepared statement to fetch programmes with module counts.
    */
    public function findByStaffMember($staffId)
    {
        /* Queries programmes by staff member
           - Purpose: Retrieves published programmes with modules led by the specified staff member.
           - Functionality: Joins programmes and modules tables, counts modules, and orders by title.
           - Server-Side Role: Provides programme data for staff-specific views.
           - Logic: Uses prepared statement to fetch distinct programmes with module counts.
        */
        $stmt = $this->db->prepare(
            '
            SELECT DISTINCT p.*, COUNT(m.id) as module_count
            FROM programmes p
            JOIN modules m ON p.id = m.programme_id
            WHERE m.module_leader_id = ? AND m.is_active = TRUE AND p.is_published = TRUE
            GROUP BY p.id
            ORDER BY p.title
        '
        );
        $stmt->execute([$staffId]);
        return $stmt->fetchAll();
    }
}
