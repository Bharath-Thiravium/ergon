<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Add columns to ra_bills table
    $sql = "ALTER TABLE ra_bills 
            ADD COLUMN selected_logo VARCHAR(100) NULL,
            ADD COLUMN selected_seal VARCHAR(100) NULL";
    
    $db->exec($sql);
    echo "Migration completed successfully: Added selected_logo and selected_seal columns to ra_bills table\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>