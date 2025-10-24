<?php
/**
 * Test Daily Task Planner Setup
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>Testing Daily Task Planner Setup</h2>";
    
    // Test 1: Check if tables exist
    $tables = ['projects', 'task_categories', 'project_tasks', 'daily_task_entries'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table '$table' exists</p>";
        } else {
            echo "<p>❌ Table '$table' missing</p>";
        }
    }
    
    // Test 2: Check sample data
    $stmt = $conn->query("SELECT COUNT(*) as count FROM projects");
    $projectCount = $stmt->fetch()['count'];
    echo "<p>📊 Projects: $projectCount</p>";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM task_categories");
    $categoryCount = $stmt->fetch()['count'];
    echo "<p>📋 Task Categories: $categoryCount</p>";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM project_tasks");
    $taskCount = $stmt->fetch()['count'];
    echo "<p>✅ Project Tasks: $taskCount</p>";
    
    // Test 3: Check department data
    echo "<h3>Department Data:</h3>";
    $stmt = $conn->query("SELECT department, COUNT(*) as count FROM task_categories GROUP BY department");
    while ($row = $stmt->fetch()) {
        echo "<p>🏢 {$row['department']}: {$row['count']} categories</p>";
    }
    
    // Test 4: Check user departments
    echo "<h3>User Departments:</h3>";
    $stmt = $conn->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL");
    while ($row = $stmt->fetch()) {
        echo "<p>👤 Department: {$row['department']}</p>";
    }
    
    echo "<h3>✅ Setup Test Complete!</h3>";
    echo "<p><a href='/ergon/daily-planner'>Test Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>