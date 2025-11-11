<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Fix HTML entities in department names
    $db->exec("UPDATE departments SET name = 'Finance & Accounts' WHERE name = 'Finance &amp; Accounts'");
    
    echo "Fixed department names\n";
    
    // Test API for existing departments
    $departments = [1, 5, 6, 13, 14, 15];
    
    foreach ($departments as $deptId) {
        $stmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
        $stmt->execute([$deptId]);
        $dept = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dept) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM task_categories WHERE department_name = ?");
            $stmt->execute([$dept['name']]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "Dept {$deptId} ({$dept['name']}): {$count['count']} categories\n";
        }
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>