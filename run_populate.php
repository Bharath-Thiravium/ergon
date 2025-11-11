<?php
$host = 'localhost';
$dbname = 'ergon_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = file_get_contents('populate_categories.sql');
    $pdo->exec($sql);
    
    // Count categories by department
    $stmt = $pdo->query('SELECT department_name, COUNT(*) as count FROM task_categories GROUP BY department_name ORDER BY department_name');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Categories populated successfully:\n";
    foreach ($results as $row) {
        echo "- {$row['department_name']}: {$row['count']} categories\n";
    }
    
    $total = $pdo->query('SELECT COUNT(*) FROM task_categories')->fetchColumn();
    echo "Total: $total categories\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>