<?php
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>🔍 Department Column Debug</h2>";
    
    // Check users table structure
    echo "<h3>Users Table Structure:</h3>";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasDepartment = false;
    $hasDepartmentId = false;
    
    echo "<table border='1'><tr><th>Column</th><th>Type</th></tr>";
    foreach ($columns as $col) {
        if ($col['Field'] === 'department') $hasDepartment = true;
        if ($col['Field'] === 'department_id') $hasDepartmentId = true;
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
    }
    echo "</table>";
    
    echo "<p>Has 'department' column: " . ($hasDepartment ? "YES" : "NO") . "</p>";
    echo "<p>Has 'department_id' column: " . ($hasDepartmentId ? "YES" : "NO") . "</p>";
    
    // Show sample user data
    echo "<h3>Sample User Data:</h3>";
    $stmt = $db->query("SELECT id, name, department, department_id FROM users WHERE role = 'user' LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>department</th><th>department_id</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>" . ($user['department'] ?? 'NULL') . "</td>";
        echo "<td>" . ($user['department_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>