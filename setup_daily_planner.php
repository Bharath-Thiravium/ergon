<?php
/**
 * Daily Task Planner Setup Script
 * Run this once to set up the daily task planner system
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>Setting up Daily Task Planner...</h2>";
    
    // Read and execute the schema
    $schema = file_get_contents('daily_task_planner_schema.sql');
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $conn->exec($statement);
            echo "<p>✅ Executed: " . substr($statement, 0, 50) . "...</p>";
        }
    }
    
    echo "<h3>✅ Daily Task Planner setup completed successfully!</h3>";
    echo "<p><strong>You can now access:</strong></p>";
    echo "<ul>";
    echo "<li><a href='/daily-planner'>Daily Task Planner (Employee)</a></li>";
    echo "<li><a href='/daily-planner/dashboard'>Manager Dashboard</a></li>";
    echo "</ul>";
    
    echo "<h4>Sample Data Created:</h4>";
    echo "<ul>";
    echo "<li>4 Projects (ERP, Solar Site, Marketing Campaign, Office Renovation)</li>";
    echo "<li>Task categories for all departments</li>";
    echo "<li>Sample project tasks</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error setting up Daily Task Planner:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>