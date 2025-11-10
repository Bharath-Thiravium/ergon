<?php
// Debug script for planner functionality
session_start();

// Simulate logged in user for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Use a test user ID
    $_SESSION['username'] = 'test_user';
    $_SESSION['role'] = 'user';
}

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "Database connection: OK\n";
    
    // Check if tables exist
    $tables = ['daily_tasks', 'daily_workflow_status', 'departments'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "Table $table: EXISTS\n";
        } else {
            echo "Table $table: MISSING\n";
        }
    }
    
    // Check daily_tasks table structure
    echo "\nDaily tasks table structure:\n";
    $stmt = $db->query("DESCRIBE daily_tasks");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    // Test data insertion
    echo "\nTesting data insertion...\n";
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    $stmt = $db->prepare("INSERT INTO daily_tasks (title, description, assigned_to, planned_date, priority, estimated_hours, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'planned', NOW())");
    $result = $stmt->execute([
        'Test Task ' . time(),
        'Test description',
        $userId,
        $today,
        'medium',
        1.0
    ]);
    
    if ($result) {
        echo "Data insertion: SUCCESS (ID: " . $db->lastInsertId() . ")\n";
    } else {
        echo "Data insertion: FAILED\n";
        print_r($stmt->errorInfo());
    }
    
    // Check existing data
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM daily_tasks WHERE assigned_to = ? AND planned_date = ?");
    $stmt->execute([$userId, $today]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Existing tasks for today: {$count['count']}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>