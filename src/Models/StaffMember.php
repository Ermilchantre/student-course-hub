<?php

/* ==================================================
   STAFF MEMBER MODEL
   --------------------------------------------------
   Purpose: Manages staff member-related database operations in a Slim 4 application.
   Functionality: Provides methods to retrieve, create, and query staff member data, including associated modules.
   Server-Side Role: Handles database interactions for staff member data management.
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
   STAFF MEMBER CLASS
   --------------------------------------------------
   Purpose: Manages staff member data operations in the application.
   Functionality: Supports retrieving all staff members, finding specific staff members, creating new records, and fetching associated modules.
   Server-Side Role: Interacts with the database to manage staff member records and relationships.
   Logic: Initializes PDO dependency and defines methods for staff member CRUD operations and module queries.
*/
class StaffMember
{
    /* ==================================================
       PROPERTY DEFINITION
       --------------------------------------------------
       Purpose: Stores the database connection.
       Functionality: Holds reference to PDO instance for database operations.
       Server-Side Role: Provides access to database services.
       Logic: Declares protected property for PDO dependency injection.
    */
    protected $db;

    /* ==================================================
       CONSTRUCTOR
       --------------------------------------------------
       Purpose: Initializes the model with a database connection.
       Functionality: Assigns PDO instance for use in methods.
       Server-Side Role: Sets up the model for staff member-related database operations.
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
       Purpose: Retrieves all staff member records from the database.
       Functionality: Fetches all staff members, regardless of status.
       Server-Side Role: Queries the database for a complete list of staff members.
       Logic: Executes a SELECT query to fetch all staff member records.
    */
    public function all()
    {
        /* Queries all staff members
           - Purpose: Retrieves all records from the staff_members table.
           - Functionality: Fetches all columns for staff members.
           - Server-Side Role: Provides comprehensive staff data for display or processing.
           - Logic: Uses PDO query to fetch all records.
        */
        $stmt = $this->db->query('SELECT * FROM staff_members');
        return $stmt->fetchAll();
    }

    /* ==================================================
       FIND METHOD
       --------------------------------------------------
       Purpose: Retrieves a specific staff member by ID.
       Functionality: Fetches a single staff member record based on the provided ID.
       Server-Side Role: Queries the database for a specific staff member.
       Logic: Uses a prepared statement to fetch the staff member record securely.
    */
    public function find($id)
    {
        /* Queries staff member by ID
           - Purpose: Retrieves a specific staff member record.
           - Functionality: Fetches all columns for the staff member with the given ID.
           - Server-Side Role: Provides detailed staff data for specific views.
           - Logic: Uses prepared statement to prevent SQL injection and fetch record.
        */
        $stmt = $this->db->prepare('SELECT * FROM staff_members WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /* ==================================================
       CREATE METHOD
       --------------------------------------------------
       Purpose: Creates a new staff member record in the database.
       Functionality: Inserts staff member data including user ID, title, department, and other attributes.
       Server-Side Role: Persists new staff member data to the database.
       Logic: Uses a prepared statement to insert data and returns the new staff member ID.
    */
    public function create($data)
    {
        /* Inserts new staff member record
           - Purpose: Stores a new staff member in the database.
           - Functionality: Inserts user ID, title, department, specialization, bio, and other details.
           - Server-Side Role: Manages staff member creation with secure data insertion.
           - Logic: Uses prepared statement to insert data; defaults is_active to 1.
        */
        $stmt = $this->db->prepare(
            'INSERT INTO staff_members (
                user_id, title, department, specialization, bio, photo_url, phone, office_location, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(
            [
                $data['user_id'],
                $data['title'],
                $data['department'],
                $data['specialization'],
                $data['bio'],
                $data['photo_url'],
                $data['phone'],
                $data['office_location'],
                $data['is_active'] ?? 1
            ]
        );
        return $this->db->lastInsertId();
    }

    /* ==================================================
       FIND BY USER ID METHOD
       --------------------------------------------------
       Purpose: Retrieves a staff member by their associated user ID.
       Functionality: Fetches a staff member record with user details for a given user ID.
       Server-Side Role: Queries the database for staff member data linked to a user.
       Logic: Uses a prepared statement to fetch staff member and user data securely.
    */
    public function findByUserId($userId)
    {
        /* Queries staff member by user ID
           - Purpose: Retrieves an active staff member linked to a specific user ID.
           - Functionality: Joins staff_members with users table to include full name and email.
           - Server-Side Role: Provides staff data for user-specific views or authentication.
           - Logic: Uses prepared statement to fetch active staff member record.
        */
        $stmt = $this->db->prepare(
            '
            SELECT sm.*, u.full_name, u.email
            FROM staff_members sm
            JOIN users u ON sm.user_id = u.id
            WHERE sm.user_id = ? AND sm.is_active = TRUE
        '
        );
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /* ==================================================
       FIND MODULES BY LEADER ID METHOD
       --------------------------------------------------
       Purpose: Retrieves active modules led by a specific staff member.
       Functionality: Fetches modules with programme details where the staff member is the leader.
       Server-Side Role: Queries the database for modules associated with a staff member.
       Logic: Uses a prepared statement to fetch modules with programme titles and levels.
    */
    public function findModulesByLeaderId($leaderId)
    {
        /* Queries modules by leader ID
           - Purpose: Retrieves active modules where the staff member is the leader.
           - Functionality: Joins modules with programmes to include programme title and level, ordered by title and year.
           - Server-Side Role: Provides module data for staff-specific views.
           - Logic: Uses prepared statement to prevent SQL injection and fetch results.
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
}
