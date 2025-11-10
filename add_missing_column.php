<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['role'] = 'user';
}

echo "<h2>Adding Missing Column: morning_submitted_at</h2>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    
    // Check if table exists first
    $stmt = $db->query("SHOW TABLES LIKE 'daily_workflow_status'");
    if ($stmt->rowCount() == 0) {
        // Create the entire table if it doesn't exist
        $sql = "CREATE TABLE daily_workflow_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            workflow_date DATE NOT NULL,
            morning_submitted_at TIMESTAMP NULL,
            evening_updated_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_date (user_id, workflow_date)
        )";
        $db->exec($sql);
        echo "<p style='color: green;'>✓ Created daily_workflow_status table with all columns</p>";
    } else {
        // Table exists, check if column exists
        $stmt = $db->query("SHOW COLUMNS FROM daily_workflow_status LIKE 'morning_submitted_at'");
        if ($stmt->rowCount() == 0) {
            // Add the missing column
            $db->exec("ALTER TABLE daily_workflow_status ADD COLUMN morning_submitted_at TIMESTAMP NULL");
            echo "<p style='color: green;'>✓ Added morning_submitted_at column</p>";
        } else {
            echo "<p style='color: blue;'>• morning_submitted_at column already exists</p>";
        }
        
        // Check if evening_updated_at column exists
        $stmt = $db->query("SHOW COLUMNS FROM daily_workflow_status LIKE 'evening_updated_at'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE daily_workflow_status ADD COLUMN evening_updated_at TIMESTAMP NULL");
            echo "<p style='color: green;'>✓ Added evening_updated_at column</p>";
        } else {
            echo "<p style='color: blue;'>• evening_updated_at column already exists</p>";
        }
    }
    
    // Show final table structure
    echo "<h3>Final Table Structure:</h3>";
    $stmt = $db->query("DESCRIBE daily_workflow_status");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Database fix completed!</h3>";
    echo "<p><a href='/ergon/daily-workflow/morning-planner'>Test Morning Planner Now</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}
?>