<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/Session.php';

Session::init();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    echo "Access denied. Owner role required.";
    exit;
}

echo "<h2>Dashboard Data Debug</h2>";

try {
    $db = Database::connect();
    
    // Check projects table
    echo "<h3>Projects Table:</h3>";
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM projects WHERE status = 'active'");
        $projectCount = $stmt->fetchColumn();
        echo "<p>Active Projects: <strong>{$projectCount}</strong></p>";
        
        $stmt = $db->query("SELECT * FROM projects LIMIT 5");
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($projects) {
            echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Status</th></tr>";
            foreach ($projects as $p) {
                echo "<tr><td>{$p['id']}</td><td>{$p['name']}</td><td>{$p['status']}</td></tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p>Projects table error: " . $e->getMessage() . "</p>";
    }
    
    // Check tasks table
    echo "<h3>Tasks Table:</h3>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM tasks");
    $totalTasks = $stmt->fetchColumn();
    echo "<p>Total Tasks: <strong>{$totalTasks}</strong></p>";
    
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status");
    $tasksByStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>Status</th><th>Count</th></tr>";
    foreach ($tasksByStatus as $status) {
        echo "<tr><td>{$status['status']}</td><td>{$status['count']}</td></tr>";
    }
    echo "</table>";
    
    // Test the actual methods from OwnerController
    echo "<h3>Testing OwnerController Methods:</h3>";
    
    // Active projects count
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE status = 'active'");
        $count = $stmt->fetchColumn();
        echo "<p>Active Projects (projects table): <strong>{$count}</strong></p>";
        if ($count == 0) {
            $stmt = $db->query("SELECT COUNT(DISTINCT project_name) FROM tasks WHERE project_name IS NOT NULL AND project_name != '' AND status != 'completed'");
            $count = $stmt->fetchColumn();
            echo "<p>Active Projects (from tasks): <strong>{$count}</strong></p>";
        }
    } catch (Exception $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
    
    // Completed tasks
    $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'");
    $completed = $stmt->fetchColumn();
    echo "<p>Completed Tasks: <strong>{$completed}</strong></p>";
    
    // In progress tasks
    $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status IN ('in_progress', 'assigned')");
    $inProgress = $stmt->fetchColumn();
    echo "<p>In Progress Tasks: <strong>{$inProgress}</strong></p>";
    
    // Pending tasks
    $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'not_started')");
    $pending = $stmt->fetchColumn();
    echo "<p>Pending Tasks: <strong>{$pending}</strong></p>";
    
    // Overdue tasks
    $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE (due_date < CURDATE() OR deadline < CURDATE()) AND status NOT IN ('completed', 'cancelled')");
    $overdue = $stmt->fetchColumn();
    echo "<p>Overdue Tasks: <strong>{$overdue}</strong></p>";
    
    // Check if the dashboard is actually calling the right view
    echo "<h3>Dashboard View Check:</h3>";
    echo "<p>Current session role: <strong>{$_SESSION['role']}</strong></p>";
    echo "<p>Expected view: owner/dashboard.php</p>";
    
} catch (Exception $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}
?>