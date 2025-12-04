<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>User Management Data Analysis</h2>";
    
    // Get all users
    $stmt = $db->prepare("SELECT id, name, email, role, status FROM users WHERE status != 'deleted' ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Database Records:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>{$user['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for specific users mentioned
    echo "<h3>Specific User Analysis:</h3>";
    
    $nelsonUsers = $db->prepare("SELECT * FROM users WHERE name LIKE '%Nelson%'");
    $nelsonUsers->execute();
    $nelsons = $nelsonUsers->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Users with 'Nelson' in name:</h4>";
    foreach ($nelsons as $nelson) {
        echo "<p>ID: {$nelson['id']}, Name: {$nelson['name']}, Email: {$nelson['email']}, Role: {$nelson['role']}</p>";
    }
    
    // Check if there's a user with ID 37
    $user37 = $db->prepare("SELECT * FROM users WHERE id = 37");
    $user37->execute();
    $user37Data = $user37->fetch(PDO::FETCH_ASSOC);
    
    echo "<h4>User ID 37:</h4>";
    if ($user37Data) {
        echo "<p>Found: ID: {$user37Data['id']}, Name: {$user37Data['name']}, Email: {$user37Data['email']}, Role: {$user37Data['role']}</p>";
    } else {
        echo "<p>No user found with ID 37</p>";
    }
    
    // Role distribution
    echo "<h3>Role Distribution:</h3>";
    $roleStats = $db->query("SELECT role, COUNT(*) as count FROM users WHERE status != 'deleted' GROUP BY role");
    while ($row = $roleStats->fetch(PDO::FETCH_ASSOC)) {
        echo "<p>{$row['role']}: {$row['count']} users</p>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>