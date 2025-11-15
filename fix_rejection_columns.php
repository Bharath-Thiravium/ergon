<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Check and add rejection_reason to expenses table
    $stmt = $db->query("SHOW COLUMNS FROM expenses LIKE 'rejection_reason'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE expenses ADD COLUMN rejection_reason TEXT NULL");
        echo "Added rejection_reason to expenses table\n";
    } else {
        echo "rejection_reason already exists in expenses table\n";
    }
    
    // Check and add rejection_reason to leaves table
    $stmt = $db->query("SHOW COLUMNS FROM leaves LIKE 'rejection_reason'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE leaves ADD COLUMN rejection_reason TEXT NULL");
        echo "Added rejection_reason to leaves table\n";
    } else {
        echo "rejection_reason already exists in leaves table\n";
    }
    
    echo "Database columns check completed\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>