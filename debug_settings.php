<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Check settings table structure
    echo "Settings table structure:\n";
    $stmt = $db->query("SHOW COLUMNS FROM settings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check current data
    echo "\nCurrent settings data:\n";
    $stmt = $db->query("SELECT * FROM settings LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        foreach ($result as $key => $value) {
            echo "- $key: $value\n";
        }
    } else {
        echo "No settings found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>