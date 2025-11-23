<?php
// Fix user_preferences table structure
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Fixing User Preferences Table</h2>";
    
    // Drop existing table if it exists with wrong structure
    $db->exec("DROP TABLE IF EXISTS user_preferences");
    echo "✅ Dropped existing table<br>";
    
    // Create table with correct structure
    $sql = "CREATE TABLE user_preferences (
        user_id INT PRIMARY KEY,
        theme VARCHAR(20) DEFAULT 'light',
        dashboard_layout VARCHAR(20) DEFAULT 'default',
        language VARCHAR(10) DEFAULT 'en',
        timezone VARCHAR(50) DEFAULT 'UTC',
        notifications_email TINYINT(1) DEFAULT 1,
        notifications_browser TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "✅ Created table with correct structure<br>";
    
    // Verify columns exist
    $result = $db->query("DESCRIBE user_preferences");
    echo "<h3>Table Structure:</h3>";
    while ($row = $result->fetch()) {
        echo "{$row['Field']} - {$row['Type']}<br>";
    }
    
    echo "<h3>✅ Table Fixed!</h3>";
    echo "<p>Now try saving your preferences again.</p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>