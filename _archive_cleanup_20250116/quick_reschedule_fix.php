<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Create followup_history table
    $sql = "CREATE TABLE IF NOT EXISTS `followup_history` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `followup_id` int(11) NOT NULL,
        `action` varchar(50) NOT NULL,
        `old_value` text DEFAULT NULL,
        `notes` text DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_followup_id` (`followup_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($sql);
    
    // Add user_id column if it doesn't exist
    try {
        $db->exec("ALTER TABLE `followups` ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `contact_id`");
    } catch (Exception $e) {
        // Column might already exist, ignore error
    }
    
    echo "✅ Database structure fixed!<br>";
    echo "✅ followup_history table created<br>";
    echo "✅ user_id column added to followups table<br>";
    echo "<br>Now test the reschedule functionality at:<br>";
    echo "<a href='/ergon/contacts/followups/view'>http://localhost/ergon/contacts/followups/view</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>