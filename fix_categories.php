<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/populate_categories.sql');
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->exec($statement);
        }
    }
    
    echo "Task categories populated successfully!\n";
    
    // Test the API
    echo "\nTesting API for department ID 1:\n";
    
    // Get department name for ID 1
    $stmt = $db->prepare("SELECT name FROM departments WHERE id = 1");
    $stmt->execute();
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dept) {
        echo "Department: " . $dept['name'] . "\n";
        
        // Get categories for this department
        $stmt = $db->prepare("SELECT category_name FROM task_categories WHERE department_name = ? ORDER BY category_name");
        $stmt->execute([$dept['name']]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Categories found: " . count($categories) . "\n";
        foreach ($categories as $cat) {
            echo "- " . $cat['category_name'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>