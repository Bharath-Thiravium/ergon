<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Create followup_history table
    $db->exec("CREATE TABLE IF NOT EXISTS `followup_history` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `followup_id` int(11) NOT NULL,
        `action` varchar(50) NOT NULL,
        `old_value` text DEFAULT NULL,
        `notes` text DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_followup_id` (`followup_id`),
        INDEX `idx_created_by` (`created_by`),
        INDEX `idx_created_at` (`created_at`),
        FOREIGN KEY (`followup_id`) REFERENCES `followups` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Add user_id column if missing
    $db->exec("ALTER TABLE `followups` ADD COLUMN IF NOT EXISTS `user_id` int(11) DEFAULT NULL AFTER `contact_id`");
    
    echo "✅ Fixed missing table and columns!";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>