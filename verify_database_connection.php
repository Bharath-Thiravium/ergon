<?php
require_once __DIR__ . '/app/config/database.php';

echo "<h2>🔍 Database Connection Verification</h2>";

try {
    // Check database configuration
    $db = Database::connect();
    
    // Get connection info
    $stmt = $db->query("SELECT DATABASE() as db_name, USER() as db_user, @@hostname as db_host");
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>📋 Connection Details:</h3>";
    echo "<p><strong>Database:</strong> {$info['db_name']}</p>";
    echo "<p><strong>User:</strong> {$info['db_user']}</p>";
    echo "<p><strong>Host:</strong> {$info['db_host']}</p>";
    
    // Verify this is production database
    $userCount = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
    echo "<p><strong>Total Users:</strong> $userCount</p>";
    
    // Check if frontend is using same database
    echo "<h3>🎯 Frontend Database Check:</h3>";
    $frontendUsers = $db->query("SELECT id, name, email FROM users WHERE id IN (1, 16, 37, 57, 58, 59) ORDER BY id");
    $users = $frontendUsers->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "<p>ID: {$user['id']} - {$user['name']} - {$user['email']}</p>";
    }
    
    // Check database file path (if applicable)
    echo "<h3>📁 Configuration Check:</h3>";
    if (file_exists(__DIR__ . '/app/config/database.php')) {
        echo "<p>✅ Database config file exists</p>";
        
        // Read config to verify settings
        $config = file_get_contents(__DIR__ . '/app/config/database.php');
        if (strpos($config, 'u494785662_ergon') !== false) {
            echo "<p>✅ Connected to production database: u494785662_ergon</p>";
        } else {
            echo "<p>⚠️ Database name mismatch in config</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Connection Error: " . $e->getMessage() . "</p>";
}
?>