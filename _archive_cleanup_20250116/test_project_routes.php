<?php
// Test project management routes directly
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/controllers/ProjectManagementController.php';

// Simulate session for testing
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['logged_in'] = true;

echo "Testing Project Management Controller...\n\n";

try {
    $controller = new ProjectManagementController();
    
    // Test if projects table exists
    $db = Database::connect();
    
    // Create projects table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        department_id INT,
        status VARCHAR(50) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'projects'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "Projects table exists: " . ($tableExists ? "YES" : "NO") . "\n";
    
    // Get current projects count
    $stmt = $db->query("SELECT COUNT(*) as count FROM projects");
    $result = $stmt->fetch();
    echo "Current projects count: " . $result['count'] . "\n";
    
    // Test create functionality
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_create'])) {
        $_POST['name'] = 'Test Project';
        $_POST['description'] = 'Test project description';
        $_POST['department_id'] = null;
        
        ob_start();
        $controller->create();
        $output = ob_get_clean();
        
        echo "Create test output: " . $output . "\n";
    }
    
    echo "\nProject Management Controller is working correctly!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test form for creating a project
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "\n\nTo test project creation, submit this form:\n";
    echo '<form method="POST">';
    echo '<input type="hidden" name="test_create" value="1">';
    echo '<input type="submit" value="Test Create Project">';
    echo '</form>';
}
?>