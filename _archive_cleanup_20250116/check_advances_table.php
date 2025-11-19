<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Check advances table structure
    echo "Checking advances table structure:\n";
    $stmt = $db->query("SHOW COLUMNS FROM advances");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check if rejection_reason exists
    $stmt = $db->query("SHOW COLUMNS FROM advances LIKE 'rejection_reason'");
    if ($stmt->rowCount() == 0) {
        echo "\nrejection_reason column is MISSING from advances table\n";
        echo "Adding rejection_reason column...\n";
        $db->exec("ALTER TABLE advances ADD COLUMN rejection_reason TEXT NULL");
        echo "Added rejection_reason to advances table\n";
    } else {
        echo "\nrejection_reason column exists in advances table\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>