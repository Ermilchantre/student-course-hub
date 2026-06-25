<?php

/* ==================================================
   STUDENT INTEREST MODEL
   --------------------------------------------------
   Purpose: Manages student interest-related database operations in a Slim 4 application.
   Functionality: Provides methods to retrieve, create, delete, search, and analyze student interest data.
   Server-Side Role: Handles database interactions for student interest management and reporting.
   ==================================================
*/

namespace App\Models;

/* ==================================================
   IMPORTS
   --------------------------------------------------
   Purpose: Imports required dependencies for database operations and error handling.
   Functionality: Provides PDO for database connectivity and Exception for error management.
   Server-Side Role: Enables secure database interactions and robust error handling.
   Logic: Imports PDO and Exception classes.
*/
use PDO;
use Exception;

/* ==================================================
   STUDENT INTEREST CLASS
   --------------------------------------------------
   Purpose: Manages student interest data operations in the application.
   Functionality: Supports retrieving, creating, deleting, searching, and analyzing student interests, including filtering by programme and staff.
   Server-Side Role: Interacts with the database to manage student interest records and generate statistics.
   Logic: Initializes PDO dependency and defines methods for CRUD operations, searches, and analytics.
*/
class StudentInterest
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
       Server-Side Role: Sets up the model for student interest-related database operations.
       Logic: Stores injected PDO dependency.
    */
    public function __construct(PDO $db)
    {
        // Assigns injected PDO instance to class property
        $this->db = $db;
    }

    /* ==================================================
       FIND BY EMAIL METHOD
       --------------------------------------------------
       Purpose: Retrieves student interest records by email.
       Functionality: Fetches interests with programme and leader details for a given email.
       Server-Side Role: Queries the database for email-specific interest data.
       Logic: Uses a prepared statement to join multiple tables and group by interest ID.
    */
    public function findByEmail($email)
    {
        /* Queries student interests by email
           - Purpose: Retrieves all interest records associated with a specific email.
           - Functionality: Joins student_interests, programmes, modules, staff_members, and users tables to include programme title and leader details.
           - Server-Side Role: Provides detailed interest data for user-specific views.
           - Logic: Uses prepared statement to prevent SQL injection and groups results by interest ID.
        */
        $stmt = $this->db->prepare(
            'SELECT si.*, p.title AS programme_title, u.full_name AS leader_name, u.email AS leader_email, ' .
            'sm.department AS leader_department, sm.office_location AS leader_office_location ' .
            'FROM student_interests si ' .
            'JOIN programmes p ON si.programme_id = p.id ' .
            'LEFT JOIN modules m ON m.programme_id = p.id ' .
            'LEFT JOIN staff_members sm ON m.module_leader_id = sm.id ' .
            'LEFT JOIN users u ON sm.user_id = u.id ' .
            'WHERE si.email = ? ' .
            'GROUP BY si.id'
        );
        $stmt->execute([$email]);
        return $stmt->fetchAll();
    }

    /* ==================================================
       CREATE METHOD
       --------------------------------------------------
       Purpose: Creates a new student interest record in the database.
       Functionality: Inserts interest data after validating required fields.
       Server-Side Role: Persists student interest data securely.
       Logic: Validates input, uses a prepared statement to insert data, and returns the new ID.
    */
    public function create($data)
    {
        try {
            /* Validates required fields
               - Purpose: Ensures all necessary fields are provided.
               - Functionality: Checks for required fields and throws an exception if any are missing.
               - Server-Side Role: Enforces data integrity before insertion.
               - Logic: Iterates through required fields and validates their presence.
            */
            $requiredFields = [
                'student_name',
                'email',
                'interest_password_hash',
                'phone',
                'programme_id',
                'study_level',
                'additional_message',
                'newsletter_consent',
                'privacy_consent'
            ];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            /* Inserts new student interest record
               - Purpose: Stores a new student interest in the database.
               - Functionality: Inserts validated data into the student_interests table.
               - Server-Side Role: Manages interest creation with secure data insertion.
               - Logic: Uses prepared statement to insert data and returns the last inserted ID.
            */
            $stmt = $this->db->prepare(
                'INSERT INTO student_interests (student_name, email, interest_password_hash, phone, programme_id, ' .
                'study_level, additional_message, newsletter_consent, privacy_consent) ' .
                'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $result = $stmt->execute([
                $data['student_name'],
                $data['email'],
                $data['interest_password_hash'],
                $data['phone'],
                $data['programme_id'],
                $data['study_level'],
                $data['additional_message'],
                $data['newsletter_consent'],
                $data['privacy_consent']
            ]);

            /* Checks insertion success
               - Purpose: Verifies that the record was successfully inserted.
               - Functionality: Returns the last inserted ID or throws an exception on failure.
               - Server-Side Role: Ensures successful data persistence.
               - Logic: Throws exception if execution fails; otherwise returns new ID.
            */
            if ($result) {
                return $this->db->lastInsertId();
            } else {
                throw new Exception('Failed to insert record');
            }
        } catch (Exception $e) {
            /* Handles insertion errors
               - Purpose: Manages errors during record creation.
               - Functionality: Throws the caught exception for upstream handling.
               - Server-Side Role: Ensures robust error handling.
               - Logic: Rethrows exception to propagate error details.
            */
            throw $e;
        }
    }

    /* ==================================================
       DELETE METHOD
       --------------------------------------------------
       Purpose: Deletes a student interest record by ID.
       Functionality: Removes the interest record with the specified ID.
       Server-Side Role: Manages secure deletion of student interest data.
       Logic: Uses a prepared statement to delete the record and returns success status.
    */
    public function delete($id)
    {
        /* Deletes student interest record
           - Purpose: Removes a specific student interest from the database.
           - Functionality: Deletes the record with the given ID.
           - Server-Side Role: Handles secure deletion of interest data.
           - Logic: Uses prepared statement and checks if rows were affected.
        */
        $stmt = $this->db->prepare('DELETE FROM student_interests WHERE id = ?');
        $result = $stmt->execute([$id]);
        return $result && $stmt->rowCount() > 0;
    }

    /* ==================================================
       FIND ALL METHOD
       --------------------------------------------------
       Purpose: Retrieves all student interest records.
       Functionality: Fetches all interests with associated programme titles.
       Server-Side Role: Queries the database for a complete list of interests.
       Logic: Uses a SELECT query to join student_interests and programmes tables.
    */
    public function findAll()
    {
        /* Queries all student interests
           - Purpose: Retrieves all student interest records with programme titles.
           - Functionality: Joins student_interests with programmes to include programme title.
           - Server-Side Role: Provides comprehensive interest data for display or processing.
           - Logic: Uses PDO query to fetch all records.
        */
        $stmt = $this->db->query(
            'SELECT si.*, p.title AS programme_title ' .
            'FROM student_interests si JOIN programmes p ON si.programme_id = p.id'
        );
        return $stmt->fetchAll();
    }

    /* ==================================================
       SEARCH METHOD
       --------------------------------------------------
       Purpose: Searches student interests by name or email with pagination.
       Functionality: Fetches paginated interest records matching the query, including programme titles.
       Server-Side Role: Queries the database for filtered and paginated interest data.
       Logic: Uses prepared statements to count and fetch matching records.
    */
    public function search($query, $page = 1, $pageSize = 10)
    {
        /* Sanitizes pagination parameters
           - Purpose: Ensures pagination inputs are valid integers.
           - Functionality: Calculates offset for paginated results.
           - Server-Side Role: Enhances query efficiency and security.
           - Logic: Converts pageSize to integer and computes offset.
        */
        $pageSize = (int)$pageSize;
        $offset = ($page - 1) * $pageSize;
        $like = "%$query%";

        /* Counts total matching records
           - Purpose: Determines the total number of interests matching the query.
           - Functionality: Counts records where student name or email matches the query.
           - Server-Side Role: Supports pagination by providing total count.
           - Logic: Uses prepared statement to count matching records.
        */
        $countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM student_interests si JOIN programmes p ON si.programme_id = p.id ' .
            'WHERE si.student_name LIKE ? OR si.email LIKE ?'
        );
        $countStmt->execute([$like, $like]);
        $total = $countStmt->fetchColumn();

        /* Queries paginated matching records
           - Purpose: Retrieves paginated interests matching the query.
           - Functionality: Fetches records with programme titles, limited by page size and offset.
           - Server-Side Role: Provides filtered and paginated data for search results.
           - Logic: Uses prepared statement to fetch matching records securely.
        */
        $sql = 'SELECT si.*, p.title AS programme_title FROM student_interests si ' .
               'JOIN programmes p ON si.programme_id = p.id ' .
               'WHERE si.student_name LIKE ? OR si.email LIKE ? ' .
               'LIMIT ' . $pageSize . ' OFFSET ' . $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$like, $like]);
        $results = $stmt->fetchAll();

        /* Returns search results with metadata
           - Purpose: Provides search results with total count for pagination.
           - Functionality: Returns an array with results and total count.
           - Server-Side Role: Structures data for UI rendering.
           - Logic: Combines results and total count in a structured array.
        */
        return [
            'results' => $results,
            'total' => (int)$total
        ];
    }

    /* ==================================================
       FIND BY STAFF USER ID METHOD
       --------------------------------------------------
       Purpose: Retrieves student interests linked to a staff memberÃ¢â‚¬â„¢s modules.
       Functionality: Fetches distinct interests for programmes associated with a staff memberÃ¢â‚¬â„¢s modules.
       Server-Side Role: Queries the database for staff-specific interest data.
       Logic: Uses a prepared statement to join multiple tables and fetch distinct interests.
    */
    public function findByStaffUserId($user_id)
    {
        /* Queries student interests by staff user ID
           - Purpose: Retrieves interests linked to programmes with modules led by the staff member.
           - Functionality: Joins student_interests, programmes, modules, and staff_members to fetch interests.
           - Server-Side Role: Provides interest data for staff-specific views.
           - Logic: Uses prepared statement to fetch distinct records securely.
        */
        $sql = 'SELECT DISTINCT si.*, p.title AS programme_title ' .
               'FROM student_interests si ' .
               'JOIN programmes p ON si.programme_id = p.id ' .
               'JOIN modules m ON m.programme_id = p.id ' .
               'JOIN staff_members sm ON m.module_leader_id = sm.id ' .
               'WHERE sm.user_id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    /* ==================================================
       DELETE BY ID AND EMAIL METHOD
       --------------------------------------------------
       Purpose: Deletes a student interest record by ID and email.
       Functionality: Removes the interest record matching both ID and email.
       Server-Side Role: Manages secure deletion of specific interest data.
       Logic: Uses a prepared statement to delete the record and checks success.
    */
    public function deleteByIdAndEmail($id, $email)
    {
        /* Deletes student interest by ID and email
           - Purpose: Removes a specific interest record with matching ID and email.
           - Functionality: Deletes the record using both identifiers for security.
           - Server-Side Role: Ensures secure and precise deletion.
           - Logic: Uses prepared statement and checks if rows were affected.
        */
        $stmt = $this->db->prepare('DELETE FROM student_interests WHERE id = ? AND email = ?');
        $stmt->execute([$id, $email]);
        return $stmt->rowCount() > 0;
    }

    /* ==================================================
       FIND ALL WITH FILTERS METHOD
       --------------------------------------------------
       Purpose: Retrieves all student interest records with optional programme filter.
       Functionality: Fetches interests with programme titles, optionally filtered by programme ID.
       Server-Side Role: Queries the database for filtered interest data.
       Logic: Uses a prepared statement with dynamic programme ID filter.
    */
    public function findAllWithFilters($programmeId = null)
    {
        /* Builds dynamic query for interests
           - Purpose: Retrieves all interests, optionally filtered by programme ID.
           - Functionality: Joins student_interests with programmes and applies programme filter if provided.
           - Server-Side Role: Provides filtered interest data for display or processing.
           - Logic: Constructs query with optional WHERE clause for programme ID.
        */
        $sql = 'SELECT si.*, p.title AS programme_title, si.created_at ' .
               'FROM student_interests si ' .
               'JOIN programmes p ON si.programme_id = p.id';
        $params = [];
        if ($programmeId) {
            $sql .= ' WHERE si.programme_id = ?';
            $params[] = $programmeId;
        }
        $sql .= ' ORDER BY si.created_at DESC';

        /* Executes filtered query
           - Purpose: Fetches interest records with programme titles.
           - Functionality: Executes query with optional programme ID parameter.
           - Server-Side Role: Retrieves structured data for UI or reporting.
           - Logic: Uses prepared statement to execute query securely.
        */
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /* ==================================================
       SEARCH ADVANCED METHOD
       --------------------------------------------------
       Purpose: Performs an advanced search of student interests with pagination and filters.
       Functionality: Searches interests by query and programme ID, returning paginated results with metadata.
       Server-Side Role: Queries the database for advanced search results.
       Logic: Builds dynamic query with search and filter conditions, returning results and pagination metadata.
    */
    public function searchAdvanced($query = '', $programmeId = null, $page = 1, $pageSize = 10)
    {
        /* Sanitizes pagination parameters
           - Purpose: Ensures pagination inputs are valid integers.
           - Functionality: Calculates offset for paginated results.
           - Server-Side Role: Enhances query efficiency and security.
           - Logic: Converts pageSize to integer and computes offset.
        */
        $pageSize = (int)$pageSize;
        $offset = ($page - 1) * $pageSize;

        /* Builds dynamic WHERE clause
           - Purpose: Constructs conditions for search and programme filter.
           - Functionality: Adds conditions for query and programme ID if provided.
           - Server-Side Role: Enables flexible filtering of results.
           - Logic: Creates WHERE clause with LIKE conditions for query and programme ID filter.
        */
        $whereConditions = [];
        $params = [];
        if (!empty($query)) {
            $whereConditions[] = '(si.student_name LIKE ? OR si.email LIKE ? OR p.title LIKE ?)';
            $like = "%$query%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($programmeId) {
            $whereConditions[] = 'si.programme_id = ?';
            $params[] = $programmeId;
        }
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        /* Counts total matching records
           - Purpose: Determines the total number of interests matching the filters.
           - Functionality: Counts records based on query and programme ID conditions.
           - Server-Side Role: Supports pagination by providing total count.
           - Logic: Uses prepared statement to count matching records.
        */
        $countSql = "SELECT COUNT(*) FROM student_interests si "
            . "JOIN programmes p ON si.programme_id = p.id "
            . $whereClause;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        /* Queries paginated matching records
           - Purpose: Retrieves paginated interests matching the filters.
           - Functionality: Fetches records with programme titles, ordered by creation date.
           - Server-Side Role: Provides filtered and paginated data for search results.
           - Logic: Uses prepared statement to fetch records with limit and offset.
        */
        $sql = "SELECT si.*, p.title AS programme_title, si.created_at FROM student_interests si " .
               "JOIN programmes p ON si.programme_id = p.id $whereClause " .
               "ORDER BY si.created_at DESC LIMIT $pageSize OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        /* Returns search results with metadata
           - Purpose: Provides search results with pagination metadata.
           - Functionality: Returns results, total count, page, page size, and total pages.
           - Server-Side Role: Structures data for UI rendering and pagination.
           - Logic: Combines results and pagination metadata in a structured array.
        */
        return [
            'results' => $results,
            'total' => (int)$total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => ceil($total / $pageSize)
        ];
    }

    /* ==================================================
       GET STATS METHOD
       --------------------------------------------------
       Purpose: Generates statistics for student interests.
       Functionality: Provides total interests, interests by programme, and monthly trends, optionally filtered by programme.
       Server-Side Role: Queries the database for analytical data on student interests.
       Logic: Uses multiple queries to compute total counts and grouped statistics.
    */
    public function getStats($programmeId = null)
    {
        /* Builds dynamic WHERE clause
           - Purpose: Constructs condition for programme filter if provided.
           - Functionality: Adds programme ID filter to queries if specified.
           - Server-Side Role: Enables filtered statistics for specific programmes.
           - Logic: Creates WHERE clause for programme ID or leaves empty.
        */
        $whereClause = '';
        $params = [];
        if ($programmeId) {
            $whereClause = 'WHERE si.programme_id = ?';
            $params[] = $programmeId;
        }

        /* Counts total interests
           - Purpose: Retrieves the total number of student interests.
           - Functionality: Counts all interest records, optionally filtered by programme ID.
           - Server-Side Role: Provides total count for statistics.
           - Logic: Uses prepared statement to count records.
        */
        $totalSql = "SELECT COUNT(*) FROM student_interests si $whereClause";
        $totalStmt = $this->db->prepare($totalSql);
        $totalStmt->execute($params);
        $total = $totalStmt->fetchColumn();

        /* Counts interests by programme
           - Purpose: Retrieves interest counts grouped by programme.
           - Functionality: Joins student_interests with programmes to count by programme title.
           - Server-Side Role: Provides programme-specific interest metrics.
           - Logic: Uses prepared statement to group and count records by programme.
        */
        $byProgrammeSql = "SELECT p.title, COUNT(*) as count "
            . "FROM student_interests si "
            . "JOIN programmes p ON si.programme_id = p.id $whereClause "
            . "GROUP BY si.programme_id, p.title "
            . "ORDER BY count DESC";
        $byProgrammeStmt = $this->db->prepare($byProgrammeSql);
        $byProgrammeStmt->execute($params);
        $byProgramme = $byProgrammeStmt->fetchAll();

        /* Counts interests by month
           - Purpose: Retrieves interest counts for the last 12 months, grouped by month.
           - Functionality: Counts interests within the last year, optionally filtered by programme.
           - Server-Side Role: Provides temporal trends for interest data.
           - Logic: Uses prepared statement with date filter to group by month.
        */
        $byMonthWhereClause = $whereClause;
        if ($programmeId) {
            $byMonthWhereClause .= ' AND si.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)';
        } else {
            $byMonthWhereClause = 'WHERE si.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)';
        }
        $byMonthSql = "SELECT DATE_FORMAT(si.created_at, '%Y-%m') as month, COUNT(*) as count " .
                      "FROM student_interests si $byMonthWhereClause GROUP BY month ORDER BY month DESC";
        $byMonthStmt = $this->db->prepare($byMonthSql);
        $byMonthStmt->execute($params);
        $byMonth = $byMonthStmt->fetchAll();

        /* Returns statistics data
           - Purpose: Provides structured statistics for student interests.
           - Functionality: Returns total count, programme breakdown, and monthly trends.
           - Server-Side Role: Structures analytical data for reporting.
           - Logic: Combines total, by programme, and by month results in a structured array.
        */
        return [
            'total' => (int)$total,
            'by_programme' => $byProgramme,
            'by_month' => $byMonth
        ];
    }
}
