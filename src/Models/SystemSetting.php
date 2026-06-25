<?php

/* ==================================================
   SYSTEM SETTING MODEL
   --------------------------------------------------
   Purpose: Manages system setting retrieval from the database in a Slim 4 application.
   Functionality: Provides a method to fetch the value of a system setting by its key.
   Server-Side Role: Handles database interactions for accessing system configuration data.
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
   SYSTEM SETTING CLASS
   --------------------------------------------------
   Purpose: Manages retrieval of system settings from the database.
   Functionality: Supports fetching a setting value based on its key.
   Server-Side Role: Interacts with the database to access system configuration settings.
   Logic: Initializes PDO dependency and defines a method to retrieve settings.
*/
class SystemSetting
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
       Server-Side Role: Sets up the model for system setting-related database operations.
       Logic: Stores injected PDO dependency.
    */
    public function __construct(PDO $db)
    {
        // Assigns injected PDO instance to class property
        $this->db = $db;
    }

    /* ==================================================
       GET METHOD
       --------------------------------------------------
       Purpose: Retrieves a system setting value by its key.
       Functionality: Fetches the value associated with a specific setting key from the database.
       Server-Side Role: Queries the database for system configuration data.
       Logic: Uses a prepared statement to fetch the setting value securely.
    */
    public function get($key)
    {
        /* Queries system setting by key
           - Purpose: Retrieves the value of a specific system setting.
           - Functionality: Fetches the setting_value column for the given setting_key.
           - Server-Side Role: Provides configuration data for application settings.
           - Logic: Uses prepared statement to prevent SQL injection and returns the value.
        */
        $stmt = $this->db->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        return $stmt->fetchColumn();
    }
}
