<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Fixing daily_tasks Table</h2>";
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'daily_tasks'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Creating daily_tasks table...</p>";
        
        $sql = "CREATE TABLE daily_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            task_id INT NULL,
            scheduled_date DATE NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            planned_duration INT DEFAULT 60,
            priority VARCHAR(20) DEFAULT 'medium',
            status VARCHAR(50) DEFAULT 'not_started',
            start_time TIMESTAMP NULL,
            completion_time TIMESTAMP NULL,
            completed_percentage INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $db->exec($sql);
        echo "<p style='color: green;'>✅ daily_tasks table created successfully</p>";
    } else {
        echo "<p>daily_tasks table exists, checking columns...</p>";
        
        // Check if user_id column exists
        $stmt = $db->query("SHOW COLUMNS FROM daily_tasks LIKE 'user_id'");
        if ($stmt->rowCount() == 0) {
            echo "<p>Adding user_id column...</p>";
            $db->exec("ALTER TABLE daily_tasks ADD COLUMN user_id INT NOT NULL AFTER id");
            echo "<p style='color: green;'>✅ user_id column added</p>";
        } else {
            echo "<p style='color: green;'>✅ user_id column already exists</p>";
        }
        
        // Check other essential columns
        $requiredColumns = [
            'task_id' => 'INT NULL',
            'scheduled_date' => 'DATE NOT NULL',
            'title' => 'VARCHAR(255) NOT NULL',
            'status' => 'VARCHAR(50) DEFAULT \'not_started\'',
            'planned_duration' => 'INT DEFAULT 60'
        ];
        
        foreach ($requiredColumns as $column => $definition) {
            $stmt = $db->query("SHOW COLUMNS FROM daily_tasks LIKE '$column'");
            if ($stmt->rowCount() == 0) {
                echo "<p>Adding $column column...</p>";
                $db->exec("ALTER TABLE daily_tasks ADD COLUMN $column $definition");
                echo "<p style='color: green;'>✅ $column column added</p>";
            }
        }
    }
    
    echo "<p style='color: green;'><strong>✅ daily_tasks table is now properly configured!</strong></p>";
    echo "<p>You can now test the daily planner at: <a href='/ergon/workflow/daily-planner'>Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>