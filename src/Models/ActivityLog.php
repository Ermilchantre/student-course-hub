<?php

/* ==================================================
   ACTIVITY LOG MODEL
   --------------------------------------------------
   Purpose: Manages logging and retrieval of activity records in a Slim 4 application.
   Functionality: Logs user actions, retrieves recent activities, and formats them for display.
   Server-Side Role: Handles database operations for activity logging and reporting.
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
   ACTIVITY LOG CLASS
   --------------------------------------------------
   Purpose: Manages activity logging and retrieval operations.
   Functionality: Logs user actions, fetches recent activities, and formats them with user-friendly details.
   Server-Side Role: Interacts with the database to store and retrieve activity logs.
   Logic: Initializes PDO dependency and defines methods for logging, retrieving, and formatting activities.
*/
class ActivityLog
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
       Server-Side Role: Sets up the model for activity log operations.
       Logic: Stores injected PDO dependency.
    */
    public function __construct(PDO $db)
    {
        // Assigns injected PDO instance to class property
        $this->db = $db;
    }

    /* ==================================================
       LOG METHOD
       --------------------------------------------------
       Purpose: Records an activity log entry in the database.
       Functionality: Inserts user action details, including user ID, action type, and affected record data.
       Server-Side Role: Stores activity logs for auditing and tracking.
       Logic: Prepares and executes an INSERT query with sanitized data.
    */
    public function log($data)
    {
        /* Prepares and executes INSERT query for activity log
           - Purpose: Stores activity details in the activity_logs table.
           - Functionality: Inserts user ID, action, table name, record ID, old/new values, IP address, and user agent.
           - Server-Side Role: Persists activity data for future retrieval.
           - Logic: Uses prepared statement to prevent SQL injection; encodes values as JSON.
        */
        $stmt = $this->db->prepare(
            'INSERT INTO activity_logs (
                user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(
            [
                $data['user_id'],
                $data['action'],
                $data['table_name'],
                $data['record_id'],
                json_encode($data['old_values']),
                json_encode($data['new_values']),
                $data['ip_address'],
                $data['user_agent']
            ]
        );
    }

    /* ==================================================
       GET RECENT ACTIVITIES METHOD
       --------------------------------------------------
       Purpose: Retrieves recent activity logs from the database.
       Functionality: Fetches a specified number of recent activities with user details.
       Server-Side Role: Queries the database for activity log data.
       Logic: Prepares and executes a SELECT query with a limit, joining with users table.
    */
    public function getRecentActivities($limit = 10)
    {
        /* Sanitizes limit parameter
           - $limit = (int)$limit: Ensures the limit is an integer.
           - Purpose: Prevents invalid or malicious input for query limit.
           - Server-Side Role: Enhances query security.
           - Logic: Casts limit to integer to ensure valid SQL.
        */
        $limit = (int)$limit;

        /* Prepares and executes SELECT query for recent activities
           - Purpose: Retrieves recent activity logs with user names.
           - Functionality: Joins activity_logs with users table and orders by creation date.
           - Server-Side Role: Fetches activity data for display or reporting.
           - Logic: Uses prepared statement and PDO::FETCH_ASSOC for structured results.
        */
        $stmt = $this->db->prepare(
            "SELECT
                al.id,
                al.action,
                al.table_name,
                al.record_id,
                al.old_values,
                al.new_values,
                al.created_at,
                u.full_name as user_name
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT $limit"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==================================================
       FORMAT ACTIVITY FOR DISPLAY METHOD
       --------------------------------------------------
       Purpose: Formats activity log entries for user-friendly display.
       Functionality: Transforms raw activity data into formatted titles, descriptions, and icons.
       Server-Side Role: Prepares activity data for presentation in the UI.
       Logic: Processes each activity based on action and table, assigns appropriate metadata, and formats timestamps.
    */
    public function formatActivityForDisplay($activity)
    {
        /* Initializes formatted activity array
           - Purpose: Prepares storage for formatted activity data.
           - Functionality: Creates an array to hold processed activity entries.
           - Server-Side Role: Structures data for UI rendering.
           - Logic: Initializes empty array for formatted results.
        */
        $formatted = [];

        /* Processes each activity entry
           - Purpose: Converts raw activity data into user-friendly format.
           - Functionality: Assigns titles, descriptions, and icons based on action and table.
           - Server-Side Role: Enhances data for display purposes.
           - Logic: Uses switch statements to handle different actions and table names.
        */
        foreach ($activity as $item) {
            $title = '';
            $description = '';
            $icon = 'uil-clock-three';

            switch ($item['action']) {
                case 'INSERT':
                    /* Handles INSERT actions
                       - Purpose: Formats log entries for record creation.
                       - Functionality: Assigns specific titles, descriptions, and icons for new records.
                       - Server-Side Role: Prepares user-friendly data for insertions.
                       - Logic: Decodes new_values JSON and sets metadata based on table name.
                    */
                    switch ($item['table_name']) {
                        case 'programmes':
                            $newValues = json_decode($item['new_values'], true);
                            $title = 'New program added';
                            $description = $newValues['title'] ?? 'Unknown program';
                            $icon = 'uil-plus-circle';
                            break;
                        case 'student_interests':
                            $newValues = json_decode($item['new_values'], true);
                            $title = 'New interest registered';
                            $description = 'New interest in ' . ($newValues['programme_id'] ?? 'program');
                            $icon = 'uil-user-plus';
                            break;
                        case 'modules':
                            $title = 'New module added';
                            $description = 'Module created';
                            $icon = 'uil-plus-circle';
                            break;
                    }
                    break;

                case 'UPDATE':
                    /* Handles UPDATE actions
                       - Purpose: Formats log entries for record updates.
                       - Functionality: Assigns specific titles, descriptions, and icons for updated records.
                       - Server-Side Role: Prepares user-friendly data for updates.
                       - Logic: Decodes new_values JSON and sets metadata based on table name.
                    */
                    switch ($item['table_name']) {
                        case 'programmes':
                            $newValues = json_decode($item['new_values'], true);
                            $title = 'Program updated';
                            $description = $newValues['title'] ?? 'Program modified';
                            $icon = 'uil-edit';
                            break;
                        case 'student_interests':
                            $newValues = json_decode($item['new_values'], true);
                            $title = 'Interest status updated';
                            $description = 'Status changed to ' . ($newValues['status'] ?? 'updated');
                            $icon = 'uil-edit';
                            break;
                    }
                    break;

                case 'DELETE':
                    /* Handles DELETE actions
                       - Purpose: Formats log entries for record deletions.
                       - Functionality: Assigns generic title, description, and icon for deletions.
                       - Server-Side Role: Prepares user-friendly data for deletions.
                       - Logic: Uses table name for description and sets trash icon.
                    */
                    $title = 'Record deleted';
                    $description = 'Item removed from ' . $item['table_name'];
                    $icon = 'uil-trash-alt';
                    break;
            }

            /* Adds formatted activity to result array
               - Purpose: Stores formatted activity data if valid.
               - Functionality: Includes title, description, icon, time ago, and user name.
               - Server-Side Role: Prepares data for UI rendering.
               - Logic: Adds entry to formatted array if title is set.
            */
            if ($title) {
                $formatted[] = [
                    'title' => $title,
                    'description' => $description,
                    'icon' => $icon,
                    'time_ago' => $this->timeAgo($item['created_at']),
                    'user' => $item['user_name'] ?? 'Admin'
                ];
            }
        }

        return $formatted;
    }

    /* ==================================================
       TIME AGO (PRIVATE METHOD)
       --------------------------------------------------
       Purpose: Converts a timestamp into a human-readable "time ago" format.
       Functionality: Calculates the time difference and formats it as minutes, hours, or days ago.
       Server-Side Role: Enhances activity logs with user-friendly timestamps.
       Logic: Computes time difference and returns formatted string based on duration.
    */
    private function timeAgo($datetime)
    {
        /* Calculates time difference
           - Purpose: Determines elapsed time since the activity occurred.
           - Functionality: Converts datetime to timestamp and compares with current time.
           - Server-Side Role: Provides readable time format for activity logs.
           - Logic: Uses strtotime and time to calculate difference in seconds.
        */
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;

        /* Formats time difference
           - Purpose: Returns human-readable time ago string.
           - Functionality: Returns appropriate string based on time difference (seconds, minutes, hours, or days).
           - Server-Side Role: Enhances UI with readable timestamps.
           - Logic: Uses conditional checks to format time difference.
        */
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } else {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
    }
}
