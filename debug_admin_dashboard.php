<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Set up session for testing
$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'admin';
$_SESSION['user_name'] = 'Athenas Admin';

echo "<h1>Admin Dashboard Debug</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Database Connection: ✅</h2>";
    
    // Test user count
    echo "<h3>User Stats:</h3>";
    $stmt = $db->prepare("SELECT COUNT(*) as total_users FROM users WHERE status = 'active'");
    $stmt->execute();
    $userStats = $stmt->fetch();
    echo "Active Users: " . ($userStats['total_users'] ?? 0) . "<br>";
    
    // Test all users
    $stmt = $db->query("SELECT id, name, email, role, status FROM users");
    $users = $stmt->fetchAll();
    echo "<h4>All Users:</h4>";
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Name: {$user['name']}, Role: {$user['role']}, Status: {$user['status']}<br>";
    }
    
    // Test table existence
    echo "<h3>Table Status:</h3>";
    $tables = ['tasks', 'leaves', 'expenses'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "$table: ✅ ({$result['count']} records)<br>";
        } catch (Exception $e) {
            echo "$table: ❌ (Table doesn't exist)<br>";
        }
    }
    
    // Test admin controller data
    echo "<h3>Admin Controller Data:</h3>";
    require_once __DIR__ . '/app/controllers/AdminController.php';
    
    $controller = new AdminController();
    $reflection = new ReflectionClass($controller);
    
    $getStatsMethod = $reflection->getMethod('getAdminStats');
    $getStatsMethod->setAccessible(true);
    $stats = $getStatsMethod->invoke($controller);
    
    echo "<pre>Stats: " . print_r($stats, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<h2>Error: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>