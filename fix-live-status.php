<?php
// Fix live server status column
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h3>Fixing live server status column...</h3>";
    
    // Check current column
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "Current status column: " . $column['Type'] . "<br>";
    }
    
    // Fix the column
    $db->exec("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'removed') DEFAULT 'active'");
    
    echo "✅ Status column updated<br>";
    
    // Verify fix
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "New status column: " . $column['Type'] . "<br>";
    }
    
    echo "<h3>Testing status update...</h3>";
    
    // Test update
    $stmt = $db->prepare("UPDATE users SET status = 'removed' WHERE id = 1");
    $result = $stmt->execute();
    
    if ($result) {
        echo "✅ Test update successful<br>";
        
        // Check result
        $stmt = $db->prepare("SELECT status FROM users WHERE id = 1");
        $stmt->execute();
        $status = $stmt->fetchColumn();
        
        echo "User 1 status: $status<br>";
        
        // Reset
        $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = 1");
        $stmt->execute();
        echo "✅ Reset to active<br>";
    } else {
        echo "❌ Test update failed<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>