<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Drop and recreate the table cleanly
    $db->exec("DROP TABLE IF EXISTS daily_tasks");
    
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql);
    
    echo "✅ daily_tasks table recreated successfully!<br>";
    echo "Now test the planner: <a href='/ergon/workflow/daily-planner'>Daily Planner</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>