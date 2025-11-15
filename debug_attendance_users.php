<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>All Users in Database:</h2>";
    $stmt = $db->query("SELECT id, name, email, role, status FROM users ORDER BY role, name");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
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
    
    echo "<h2>Today's Attendance Records:</h2>";
    $stmt = $db->query("SELECT a.*, u.name FROM attendance a JOIN users u ON a.user_id = u.id WHERE DATE(a.check_in) = CURDATE()");
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>User</th><th>Check In</th><th>Check Out</th></tr>";
    foreach ($attendance as $record) {
        echo "<tr>";
        echo "<td>{$record['name']}</td>";
        echo "<td>{$record['check_in']}</td>";
        echo "<td>" . ($record['check_out'] ?? 'Still working') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>