<?php
// Setup script for integrated task management system
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>🚀 Setting up Integrated Task Management System</h2>";
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/database/planner_integration_schema.sql');
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $db->exec($statement);
                echo "<p>✅ Executed: " . substr($statement, 0, 50) . "...</p>";
            } catch (Exception $e) {
                echo "<p>⚠️ Warning: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p><strong>New Features Available:</strong></p>";
    echo "<ul>";
    echo "<li>📅 <a href='/ergon/planner'>Daily Planner</a> - Plan your daily tasks</li>";
    echo "<li>🌅 <a href='/ergon/evening-update'>Evening Update</a> - Report progress</li>";
    echo "<li>✅ Enhanced Task Management with progress tracking</li>";
    echo "</ul>";
    
    echo "<p><strong>Integration Benefits:</strong></p>";
    echo "<ul>";
    echo "<li>Assigned tasks automatically appear in daily planner</li>";
    echo "<li>Personal tasks can be added to daily plan</li>";
    echo "<li>Evening updates sync progress back to main tasks</li>";
    echo "<li>Admin visibility into daily productivity</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>❌ Setup Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>