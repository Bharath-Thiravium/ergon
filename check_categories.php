<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Check if task_categories table exists
    $stmt = $db->query("SHOW TABLES LIKE 'task_categories'");
    if ($stmt->rowCount() == 0) {
        echo "task_categories table does not exist\n";
        exit;
    }
    
    // Get table structure
    echo "Table structure:\n";
    $stmt = $db->query("DESCRIBE task_categories");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
    
    // Count total categories
    $stmt = $db->query("SELECT COUNT(*) as total FROM task_categories");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "\nTotal categories: $total\n";
    
    // Show department breakdown
    echo "\nCategories by department:\n";
    $stmt = $db->query("SELECT department_name, COUNT(*) as count FROM task_categories GROUP BY department_name ORDER BY department_name");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['department_name']}: {$row['count']} categories\n";
    }
    
    // Show sample categories
    echo "\nSample categories:\n";
    $stmt = $db->query("SELECT department_name, category_name FROM task_categories ORDER BY department_name, category_name LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['department_name']}: {$row['category_name']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>