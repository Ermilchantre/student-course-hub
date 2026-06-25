<?php

/* ==================================================
   USER MODEL
   --------------------------------------------------
   Purpose: Manages user-related database operations in a Slim 4 application.
   Functionality: Provides methods to retrieve, create, and update user data, including password reset functionality.
   Server-Side Role: Handles database interactions for user account management and authentication.
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
   USER CLASS
   --------------------------------------------------
   Purpose: Manages user data operations in the application.
   Functionality: Supports retrieving users, creating new users, managing password reset tokens, and updating passwords.
   Server-Side Role: Interacts with the database to manage user records and authentication data.
   Logic: Initializes PDO dependency and defines methods for user CRUD operations and password reset functionality.
*/
class User
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
       Server-Side Role: Sets up the model for user-related database operations.
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
       Purpose: Retrieves all user records from the database.
       Functionality: Fetches all users, regardless of status.
       Server-Side Role: Queries the database for a complete list of users.
       Logic: Executes a SELECT query to fetch all user records.
    */
    public function all()
    {
        /* Queries all users
           - Purpose: Retrieves all user records from the database.
           - Functionality: Fetches all columns from the users table.
           - Server-Side Role: Provides comprehensive user data for display or processing.
           - Logic: Uses PDO query to fetch all records.
        */
        $stmt = $this->db->query('SELECT * FROM users');
        return $stmt->fetchAll();
    }

    /* ==================================================
       FIND METHOD
       --------------------------------------------------
       Purpose: Retrieves a single user by their ID.
       Functionality: Fetches a user record based on the provided ID.
       Server-Side Role: Queries the database for a specific user.
       Logic: Uses a prepared statement to fetch the user record securely.
    */
    public function find($id)
    {
        /* Queries user by ID
           - Purpose: Retrieves a specific user record.
           - Functionality: Fetches all columns for the user with the given ID.
           - Server-Side Role: Provides detailed user data for specific views or operations.
           - Logic: Uses prepared statement to prevent SQL injection and fetch record.
        */
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /* ==================================================
       CREATE METHOD
       --------------------------------------------------
       Purpose: Creates a new user record in the database.
       Functionality: Inserts user data including username, email, password hash, and other attributes.
       Server-Side Role: Persists new user data to the database.
       Logic: Uses a prepared statement to insert data and returns the new user ID.
    */
    public function create($data)
    {
        /* Inserts new user record
           - Purpose: Stores a new user in the database.
           - Functionality: Inserts username, email, password hash, full name, role, and active status.
           - Server-Side Role: Manages user creation with secure data insertion.
           - Logic: Uses prepared statement to insert data; defaults role to 'staff' and is_active to 1.
        */
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(
            [
                $data['username'],
                $data['email'],
                $data['password_hash'],
                $data['full_name'],
                $data['role'] ?? 'staff',
                $data['is_active'] ?? 1
            ]
        );
        return $this->db->lastInsertId();
    }

    /* ==================================================
       FIND BY EMAIL METHOD
       --------------------------------------------------
       Purpose: Retrieves a user by their email address.
       Functionality: Fetches a user record based on the provided email.
       Server-Side Role: Queries the database for authentication or user lookup.
       Logic: Uses a prepared statement to fetch the user record securely.
    */
    public function findByEmail($email)
    {
        /* Queries user by email
           - Purpose: Retrieves a specific user record by email.
           - Functionality: Fetches all columns for the user with the given email.
           - Server-Side Role: Supports authentication and user lookup operations.
           - Logic: Uses prepared statement to prevent SQL injection and fetch record.
        */
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /* ==================================================
       SAVE RESET TOKEN METHOD
       --------------------------------------------------
       Purpose: Saves a password reset token and its expiry for a user.
       Functionality: Updates the user record with a reset token and expiry timestamp.
       Server-Side Role: Manages password reset token storage in the database.
       Logic: Uses a prepared statement to update the user record securely.
    */
    public function saveResetToken($userId, $token, $expiry)
    {
        /* Updates user with reset token
           - Purpose: Stores a password reset token and its expiry for a user.
           - Functionality: Updates reset_token and reset_token_expiry columns for the specified user ID.
           - Server-Side Role: Supports password reset functionality.
           - Logic: Uses prepared statement to update the record securely.
        */
        $stmt = $this->db->prepare('UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?');
        $stmt->execute([$token, $expiry, $userId]);
    }

    /* ==================================================
       IS VALID RESET TOKEN METHOD
       --------------------------------------------------
       Purpose: Checks if a password reset token is valid and not expired.
       Functionality: Verifies if a token exists and its expiry is in the future.
       Server-Side Role: Validates reset tokens for password reset processes.
       Logic: Uses a prepared statement to check token validity.
    */
    public function isValidResetToken($token)
    {
        /* Validates password reset token
           - Purpose: Checks if a reset token is valid and not expired.
           - Functionality: Queries for a user with a matching token and valid expiry.
           - Server-Side Role: Ensures secure password reset validation.
           - Logic: Uses prepared statement to check token and expiry; returns boolean.
        */
        $stmt = $this->db->prepare('SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()');
        $stmt->execute([$token]);
        return $stmt->fetch() !== false;
    }

    /* ==================================================
       GET USER ID BY RESET TOKEN METHOD
       --------------------------------------------------
       Purpose: Retrieves the user ID associated with a valid reset token.
       Functionality: Fetches the user ID for a matching and non-expired token.
       Server-Side Role: Supports password reset by identifying the user.
       Logic: Uses a prepared statement to fetch the user ID or return null.
    */
    public function getUserIdByResetToken($token)
    {
        /* Queries user ID by reset token
           - Purpose: Retrieves the user ID for a valid reset token.
           - Functionality: Fetches the ID for a user with a matching token and valid expiry.
           - Server-Side Role: Supports password reset by identifying the user.
           - Logic: Uses prepared statement to fetch ID; returns null if not found.
        */
        $stmt = $this->db->prepare('SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ? $row['id'] : null;
    }

    /* ==================================================
       UPDATE PASSWORD METHOD
       --------------------------------------------------
       Purpose: Updates a userÃ¢â‚¬â„¢s password in the database.
       Functionality: Hashes and stores a new password for the specified user.
       Server-Side Role: Manages secure password updates.
       Logic: Uses password_hash to generate a hash and updates the user record.
    */
    public function updatePassword($userId, $password)
    {
        /* Updates user password
           - Purpose: Stores a new password for the specified user.
           - Functionality: Hashes the password and updates the password_hash column.
           - Server-Side Role: Ensures secure password storage.
           - Logic: Uses password_hash and prepared statement to update the record.
        */
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $userId]);
    }

    /* ==================================================
       CLEAR RESET TOKEN METHOD
       --------------------------------------------------
       Purpose: Clears the password reset token for a user.
       Functionality: Removes the reset token and expiry from the user record.
       Server-Side Role: Manages cleanup after password reset.
       Logic: Uses a prepared statement to clear token fields.
    */
    public function clearResetToken($userId)
    {
        /* Clears reset token and expiry
           - Purpose: Removes password reset token data for a user.
           - Functionality: Sets reset_token and reset_token_expiry to NULL.
           - Server-Side Role: Cleans up reset token data post-reset.
           - Logic: Uses prepared statement to update the record securely.
        */
        $stmt = $this->db->prepare('UPDATE users SET reset_token = NULL, reset_token_expiry = NULL WHERE id = ?');
        $stmt->execute([$userId]);
    }
}
