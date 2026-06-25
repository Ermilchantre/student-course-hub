<?php

/* ==================================================
   NOTIFICATION MODEL
   --------------------------------------------------
   Purpose: Manages notification-related database operations in a Slim 4 application.
   Functionality: Handles creation, retrieval, formatting, and status updates for notifications.
   Server-Side Role: Interacts with the database to store and manage notification data.
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
   NOTIFICATION CLASS
   --------------------------------------------------
   Purpose: Manages notification data operations in the application.
   Functionality: Supports creating notifications, retrieving recent notifications, formatting for display, marking as read, and counting unread notifications.
   Server-Side Role: Handles database interactions for notification management.
   Logic: Initializes PDO dependency and defines methods for notification CRUD and status operations.
*/
class Notification
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
       Server-Side Role: Sets up the model for notification-related database operations.
       Logic: Stores injected PDO dependency.
    */
    public function __construct(PDO $db)
    {
        // Assigns injected PDO instance to class property
        $this->db = $db;
    }

    /* ==================================================
       CREATE METHOD
       --------------------------------------------------
       Purpose: Creates a new notification record in the database.
       Functionality: Inserts notification data including user ID, title, message, type, and related table/ID.
       Server-Side Role: Persists notification data for user alerts and tracking.
       Logic: Uses a prepared statement to insert data securely.
    */
    public function create($data)
    {
        /* Inserts new notification record
           - Purpose: Stores a new notification in the database.
           - Functionality: Inserts user ID, title, message, type, related table, and related ID.
           - Server-Side Role: Manages notification creation with secure data insertion.
           - Logic: Uses prepared statement to prevent SQL injection and execute insertion.
        */
        $stmt = $this->db->prepare(
            'INSERT INTO notifications (
                user_id, title, message, type, related_table, related_id
            ) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(
            [
                $data['user_id'],
                $data['title'],
                $data['message'],
                $data['type'],
                $data['related_table'],
                $data['related_id']
            ]
        );
    }

    /* ==================================================
       GET RECENT NOTIFICATIONS METHOD
       --------------------------------------------------
       Purpose: Retrieves recent notification records from the database.
       Functionality: Fetches a specified number of recent notifications with user details.
       Server-Side Role: Queries the database for notification data.
       Logic: Uses a prepared statement to fetch notifications with a limit, joining with users table.
    */
    public function getRecentNotifications($limit = 10)
    {
        /* Sanitizes limit parameter
           - $limit = (int)$limit: Ensures the limit is an integer.
           - Purpose: Prevents invalid or malicious input for query limit.
           - Server-Side Role: Enhances query security.
           - Logic: Casts limit to integer to ensure valid SQL.
        */
        $limit = (int)$limit;

        /* Queries recent notifications
           - Purpose: Retrieves recent notifications with user names.
           - Functionality: Joins notifications with users table to include user details, ordered by creation date.
           - Server-Side Role: Fetches notification data for display or processing.
           - Logic: Uses prepared statement and PDO::FETCH_ASSOC for structured results.
        */
        $stmt = $this->db->prepare(
            "SELECT
                n.id,
                n.title,
                n.message,
                n.type,
                n.is_read,
                n.related_table,
                n.related_id,
                n.created_at,
                u.full_name as user_name
            FROM notifications n
            LEFT JOIN users u ON n.user_id = u.id
            ORDER BY n.created_at DESC
            LIMIT $limit"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==================================================
       FORMAT NOTIFICATIONS FOR DISPLAY METHOD
       --------------------------------------------------
       Purpose: Formats notification records for user-friendly display.
       Functionality: Transforms raw notification data into formatted titles, descriptions, and icons.
       Server-Side Role: Prepares notification data for presentation in the UI.
       Logic: Processes each notification, assigns icons based on type, and formats timestamps.
    */
    public function formatNotificationsForDisplay($notifications)
    {
        /* Initializes formatted notification array
           - Purpose: Prepares storage for formatted notification data.
           - Functionality: Creates an array to hold processed notification entries.
           - Server-Side Role: Structures data for UI rendering.
           - Logic: Initializes empty array for formatted results.
        */
        $formatted = [];

        /* Processes each notification
           - Purpose: Converts raw notification data into user-friendly format.
           - Functionality: Assigns icons, formats timestamps, and includes user details.
           - Server-Side Role: Enhances data for display purposes.
           - Logic: Iterates through notifications, formats each, and adds to result array.
        */
        foreach ($notifications as $item) {
            $icon = $this->getIconForType($item['type']);
            $formatted[] = [
                'id' => $item['id'],
                'title' => $item['title'],
                'description' => $item['message'],
                'icon' => $icon,
                'time_ago' => $this->timeAgo($item['created_at']),
                'user' => $item['user_name'] ?? 'System',
                'type' => $item['type'],
                'is_read' => $item['is_read']
            ];
        }

        return $formatted;
    }

    /* ==================================================
       GET ICON FOR TYPE (PRIVATE METHOD)
       --------------------------------------------------
       Purpose: Assigns an icon based on notification type.
       Functionality: Returns a specific icon class for success, warning, error, or info notifications.
       Server-Side Role: Enhances notification display with appropriate visual indicators.
       Logic: Uses a switch statement to map notification types to icon classes.
    */
    private function getIconForType($type)
    {
        /* Maps notification type to icon
           - Purpose: Provides a visual icon for each notification type.
           - Functionality: Returns a CSS class for the corresponding notification type.
           - Server-Side Role: Enhances UI with type-specific icons.
           - Logic: Uses switch to assign icons, defaults to info icon.
        */
        switch ($type) {
            case 'success':
                return 'uil-check-circle';
            case 'warning':
                return 'uil-exclamation-triangle';
            case 'error':
                return 'uil-times-circle';
            case 'info':
            default:
                return 'uil-info-circle';
        }
    }

    /* ==================================================
       TIME AGO (PRIVATE METHOD)
       --------------------------------------------------
       Purpose: Converts a timestamp into a human-readable "time ago" format.
       Functionality: Calculates the time difference and formats it as minutes, hours, or days ago.
       Server-Side Role: Enhances notifications with user-friendly timestamps.
       Logic: Computes time difference and returns formatted string based on duration.
    */
    private function timeAgo($datetime)
    {
        /* Calculates time difference
           - Purpose: Determines elapsed time since the notification was created.
           - Functionality: Converts datetime to timestamp and compares with current time.
           - Server-Side Role: Provides readable time format for notifications.
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

    /* ==================================================
       MARK AS READ METHOD
       --------------------------------------------------
       Purpose: Marks a notification as read in the database.
       Functionality: Updates the is_read status of a notification by ID.
       Server-Side Role: Manages notification read status updates.
       Logic: Uses a prepared statement to update the notification record.
    */
    public function markAsRead($id)
    {
        /* Updates notification read status
           - Purpose: Marks a specific notification as read.
           - Functionality: Sets is_read to TRUE for the given notification ID.
           - Server-Side Role: Persists notification status changes in the database.
           - Logic: Uses prepared statement to update record securely.
        */
        $stmt = $this->db->prepare('UPDATE notifications SET is_read = TRUE WHERE id = ?');
        $stmt->execute([$id]);
    }

    /* ==================================================
       GET UNREAD COUNT METHOD
       --------------------------------------------------
       Purpose: Counts unread notifications, optionally for a specific user.
       Functionality: Returns the number of unread notifications, filtered by user ID if provided.
       Server-Side Role: Queries the database for unread notification counts.
       Logic: Uses a prepared statement to count unread notifications with optional user filter.
    */
    public function getUnreadCount($userId = null)
    {
        /* Queries unread notification count
           - Purpose: Retrieves the number of unread notifications.
           - Functionality: Counts notifications with is_read = FALSE, optionally filtered by user ID.
           - Server-Side Role: Provides unread notification metrics for UI or logic.
           - Logic: Uses prepared statement with dynamic query based on userId presence.
        */
        if ($userId) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE');
            $stmt->execute([$userId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE is_read = FALSE');
            $stmt->execute();
        }
        return $stmt->fetchColumn();
    }
}
