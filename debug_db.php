<?php
// Debug database connection and queries
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "✅ Database connection successful\n";
    echo "Environment: " . $database->getEnvironment() . "\n";
    echo "Config: " . json_encode($database->getConfig()) . "\n\n";
    
    // Test users table
    echo "Testing users table:\n";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Total users: " . $result['count'] . "\n";
    
    // Test specific user
    echo "\nTesting user ID 2:\n";
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([2]);
    $user = $stmt->fetch();
    if ($user) {
        echo "User found: " . json_encode($user) . "\n";
    } else {
        echo "❌ User ID 2 not found\n";
    }
    
    // Test admin stats query
    echo "\nTesting admin stats query:\n";
    $sql = "SELECT 
                (SELECT COUNT(*) FROM users WHERE status = 'active') as total_users,
                (SELECT COUNT(*) FROM tasks WHERE status != 'completed') as active_tasks,
                (SELECT COUNT(*) FROM leaves WHERE status = 'Pending') as pending_leaves,
                (SELECT COUNT(*) FROM expenses WHERE status = 'pending') as pending_expenses,
                (SELECT COUNT(*) FROM tasks WHERE deadline < NOW() AND status != 'completed') as overdue_tasks";
    
    $stmt = $conn->query($sql);
    $stats = $stmt->fetch();
    echo "Stats: " . json_encode($stats) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>