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
    echo "✅ followup_history table created/verified<br>";
    
    // Test insert
    $stmt = $db->prepare("INSERT INTO followup_history (followup_id, action, old_value, notes, created_by) VALUES (1, 'test', 'test_old', 'test_notes', 1)");
    $stmt->execute();
    echo "✅ Test history record inserted<br>";
    
    // Clean up test record
    $db->exec("DELETE FROM followup_history WHERE action = 'test'");
    echo "✅ Test record cleaned up<br>";
    
    echo "<br>History logging is now working!";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>