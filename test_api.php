<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    $departmentId = 1;
    
    // Get department name first
    $stmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->execute([$departmentId]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Department ID: $departmentId\n";
    echo "Department Name: " . ($department ? $department['name'] : 'Not found') . "\n\n";
    
    if ($department) {
        // Get task categories for this department
        $stmt = $db->prepare("SELECT category_name, description FROM task_categories WHERE department_name = ? AND is_active = 1 ORDER BY category_name");
        $stmt->execute([$department['name']]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Categories found: " . count($categories) . "\n";
        
        foreach ($categories as $cat) {
            echo "- " . $cat['category_name'];
            if ($cat['description']) {
                echo " (" . $cat['description'] . ")";
            }
            echo "\n";
        }
        
        echo "\nJSON Response:\n";
        echo json_encode(['categories' => $categories], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>