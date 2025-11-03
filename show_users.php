<?php
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h3>ERGON System Users</h3>";
    
    $stmt = $db->query("SELECT id, name, email, role, status FROM users ORDER BY role, name");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<p>No users found in the system.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . ucfirst($user['role']) . "</td>";
            echo "<td>" . ucfirst($user['status']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<br><p><strong>Note:</strong> Passwords are hashed and cannot be displayed. Default password is usually 'password123' or 'admin123' for test accounts.</p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>