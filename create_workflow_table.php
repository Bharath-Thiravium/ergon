<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['role'] = 'user';
}

echo "<h2>Creating Missing Workflow Table</h2>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    
    // Create daily_workflow_status table
    $sql = "CREATE TABLE IF NOT EXISTS daily_workflow_status (
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
    echo "<p style='color: green;'>✓ daily_workflow_status table created successfully!</p>";
    
    // Verify table structure
    $stmt = $db->query("DESCRIBE daily_workflow_status");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Table Structure:</h3>";
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
    
    echo "<h3>✅ Fix completed!</h3>";
    echo "<p><a href='/ergon/daily-workflow/morning-planner'>Test Morning Planner Now</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}
?>