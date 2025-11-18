<?php
require_once __DIR__ . '/app/config/database.php';

echo "<h2>Setup SLA Timer System</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;}</style>";

try {
    $db = Database::connect();
    
    echo "<h3>1. Adding Required Database Columns</h3>";
    
    // Add SLA timing columns to daily_tasks table
    $columns = [
        'start_time' => 'TIMESTAMP NULL',
        'pause_time' => 'TIMESTAMP NULL',
        'resume_time' => 'TIMESTAMP NULL',
        'total_pause_duration' => 'INT DEFAULT 0',
        'sla_end_time' => 'TIMESTAMP NULL',
        'late_duration' => 'INT DEFAULT 0',
        'active_seconds' => 'INT DEFAULT 0'
    ];
    
    foreach ($columns as $column => $definition) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM daily_tasks LIKE '$column'");
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE daily_tasks ADD COLUMN $column $definition");
                echo "<p class='success'>✅ Added column: $column</p>";
            } else {
                echo "<p>Column $column already exists</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error adding $column: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>2. Creating SLA History Table</h3>";
    
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS sla_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                daily_task_id INT NOT NULL,
                action VARCHAR(20) NOT NULL,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                duration_seconds INT DEFAULT 0,
                notes TEXT,
                INDEX idx_daily_task_id (daily_task_id)
            )
        ");
        echo "<p class='success'>✅ SLA history table created</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error creating SLA history table: " . $e->getMessage() . "</p>";
    }
    
    echo "<p class='success'>✅ SLA system setup complete!</p>";
    echo "<p><a href='/ergon/workflow/daily-planner'>Test Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Setup failed: " . $e->getMessage() . "</p>";
}
?>