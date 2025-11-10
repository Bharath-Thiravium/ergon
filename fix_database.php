<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['role'] = 'user';
}

echo "<h2>Database Fix - Adding Missing Columns</h2>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    
    echo "<h3>Creating missing tables and columns...</h3>";
    
    // Create daily_workflow_status table if it doesn't exist
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS daily_workflow_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            workflow_date DATE NOT NULL,
            morning_submitted_at TIMESTAMP NULL,
            evening_updated_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_date (user_id, workflow_date)
        )");
        echo "✓ Created daily_workflow_status table<br>";
    } catch (Exception $e) {
        echo "• daily_workflow_status table already exists<br>";
    }
    
    // Add task_category column
    try {
        $db->exec("ALTER TABLE daily_tasks ADD COLUMN task_category VARCHAR(100) DEFAULT NULL");
        echo "✓ Added task_category column<br>";
    } catch (Exception $e) {
        echo "• task_category column already exists<br>";
    }
    
    // Add company_name column
    try {
        $db->exec("ALTER TABLE daily_tasks ADD COLUMN company_name VARCHAR(255) DEFAULT NULL");
        echo "✓ Added company_name column<br>";
    } catch (Exception $e) {
        echo "• company_name column already exists<br>";
    }
    
    // Add contact_person column
    try {
        $db->exec("ALTER TABLE daily_tasks ADD COLUMN contact_person VARCHAR(255) DEFAULT NULL");
        echo "✓ Added contact_person column<br>";
    } catch (Exception $e) {
        echo "• contact_person column already exists<br>";
    }
    
    // Add contact_phone column
    try {
        $db->exec("ALTER TABLE daily_tasks ADD COLUMN contact_phone VARCHAR(20) DEFAULT NULL");
        echo "✓ Added contact_phone column<br>";
    } catch (Exception $e) {
        echo "• contact_phone column already exists<br>";
    }
    
    echo "<h3>Verifying table structures...</h3>";
    
    echo "<h4>daily_tasks table:</h4>";
    $stmt = $db->query("DESCRIBE daily_tasks");
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
    
    echo "<h4>daily_workflow_status table:</h4>";
    try {
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
    } catch (Exception $e) {
        echo "<p style='color: red;'>daily_workflow_status table not found!</p>";
    }
    
    echo "<h3>✅ Database fix completed!</h3>";
    echo "<p><a href='/ergon/daily-workflow/morning-planner'>Test Morning Planner Now</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}
?>