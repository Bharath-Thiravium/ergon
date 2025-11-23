<?php
// Test script to debug preferences saving issue
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "Database connection: OK\n";
    
    // Check if table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'user_preferences'");
    if ($checkTable->rowCount() > 0) {
        echo "Table 'user_preferences' exists: OK\n";
        
        // Show table structure
        $structure = $db->query("DESCRIBE user_preferences");
        echo "Table structure:\n";
        while ($row = $structure->fetch()) {
            echo "  {$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
        }
    } else {
        echo "Table 'user_preferences' does not exist\n";
        
        // Create table
        $createSql = "CREATE TABLE IF NOT EXISTS user_preferences (
            user_id INT PRIMARY KEY,
            theme VARCHAR(20) DEFAULT 'light',
            dashboard_layout VARCHAR(20) DEFAULT 'default',
            language VARCHAR(10) DEFAULT 'en',
            timezone VARCHAR(50) DEFAULT 'UTC',
            notifications_email TINYINT(1) DEFAULT 1,
            notifications_browser TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL
        )";
        
        if ($db->exec($createSql) !== false) {
            echo "Table created successfully\n";
        } else {
            echo "Failed to create table\n";
        }
    }
    
    // Test insert/update with a dummy user ID (999)
    $testUserId = 999;
    $testPrefs = [
        'theme' => 'dark',
        'dashboard_layout' => 'compact',
        'language' => 'en',
        'timezone' => 'UTC',
        'notifications_email' => '1',
        'notifications_browser' => '0'
    ];
    
    // Check if test record exists
    $checkStmt = $db->prepare("SELECT user_id FROM user_preferences WHERE user_id = ?");
    $checkStmt->execute([$testUserId]);
    $exists = $checkStmt->fetch();
    
    if ($exists) {
        // Update
        $sql = "UPDATE user_preferences SET 
                theme = ?, dashboard_layout = ?, language = ?, timezone = ?, 
                notifications_email = ?, notifications_browser = ?, updated_at = NOW() 
                WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $testPrefs['theme'],
            $testPrefs['dashboard_layout'],
            $testPrefs['language'],
            $testPrefs['timezone'],
            $testPrefs['notifications_email'],
            $testPrefs['notifications_browser'],
            $testUserId
        ]);
        echo "Update test: " . ($result ? "SUCCESS" : "FAILED") . "\n";
    } else {
        // Insert
        $sql = "INSERT INTO user_preferences (user_id, theme, dashboard_layout, language, timezone, notifications_email, notifications_browser) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $testUserId,
            $testPrefs['theme'],
            $testPrefs['dashboard_layout'],
            $testPrefs['language'],
            $testPrefs['timezone'],
            $testPrefs['notifications_email'],
            $testPrefs['notifications_browser']
        ]);
        echo "Insert test: " . ($result ? "SUCCESS" : "FAILED") . "\n";
    }
    
    if (!$result) {
        echo "Error info: " . json_encode($stmt->errorInfo()) . "\n";
    }
    
    // Clean up test record
    $cleanupStmt = $db->prepare("DELETE FROM user_preferences WHERE user_id = ?");
    $cleanupStmt->execute([$testUserId]);
    echo "Test record cleaned up\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>