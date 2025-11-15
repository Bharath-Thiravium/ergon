<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Check current column definition
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current users table structure:</h3>";
    foreach ($columns as $col) {
        if ($col['Field'] === 'status') {
            echo "<strong>Status column: {$col['Type']}</strong><br>";
        }
    }
    
    // Fix the column to allow 'removed'
    $db->exec("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'removed') DEFAULT 'active'");
    
    echo "<h3>✅ Fixed status column to support 'removed'</h3>";
    
    // Test the fix
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'status') {
            echo "<strong>New status column: {$col['Type']}</strong><br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>