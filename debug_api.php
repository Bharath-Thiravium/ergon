<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== DEPARTMENTS ===\n";
    $stmt = $db->query("SELECT id, name FROM departments ORDER BY id");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($departments as $dept) {
        echo "ID: {$dept['id']}, Name: {$dept['name']}\n";
    }
    
    echo "\n=== TASK CATEGORIES COUNT ===\n";
    $stmt = $db->query("SELECT COUNT(*) as total FROM task_categories");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total categories: {$total['total']}\n";
    
    if ($total['total'] == 0) {
        echo "\n=== POPULATING CATEGORIES ===\n";
        $sql = file_get_contents('populate_categories.sql');
        $db->exec($sql);
        echo "Categories populated!\n";
        
        $stmt = $db->query("SELECT department_name, COUNT(*) as count FROM task_categories GROUP BY department_name");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $row) {
            echo "- {$row['department_name']}: {$row['count']} categories\n";
        }
    }
    
    echo "\n=== TEST API FOR DEPT ID 2 ===\n";
    $stmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->execute([2]);
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dept) {
        echo "Department 2: {$dept['name']}\n";
        
        $stmt = $db->prepare("SELECT category_name FROM task_categories WHERE department_name = ? LIMIT 5");
        $stmt->execute([$dept['name']]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sample categories:\n";
        foreach ($categories as $cat) {
            echo "- {$cat['category_name']}\n";
        }
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>