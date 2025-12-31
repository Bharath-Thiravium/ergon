<?php
/**
 * Migration: Update attendance table with project_id and location columns
 * This ensures admin attendance records display Location and Project columns correctly
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/DatabaseHelper.php';

try {
    $db = Database::connect();
    
    echo "Starting attendance table migration...\n";
    
    // Add project_id column if it doesn't exist
    try {
        $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'project_id'");
        if ($stmt->rowCount() == 0) {
            DatabaseHelper::safeExec($db, "ALTER TABLE attendance ADD COLUMN project_id INT NULL", "Add project_id column");
            echo "✓ Added project_id column\n";
        } else {
            echo "✓ project_id column already exists\n";
        }
    } catch (Exception $e) {
        echo "✗ Error adding project_id column: " . $e->getMessage() . "\n";
    }
    
    // Add manual_entry column if it doesn't exist
    try {
        $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'manual_entry'");
        if ($stmt->rowCount() == 0) {
            DatabaseHelper::safeExec($db, "ALTER TABLE attendance ADD COLUMN manual_entry TINYINT(1) DEFAULT 0", "Add manual_entry column");
            echo "✓ Added manual_entry column\n";
        } else {
            echo "✓ manual_entry column already exists\n";
        }
    } catch (Exception $e) {
        echo "✗ Error adding manual_entry column: " . $e->getMessage() . "\n";
    }
    
    // Add latitude column if it doesn't exist
    try {
        $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'latitude'");
        if ($stmt->rowCount() == 0) {
            DatabaseHelper::safeExec($db, "ALTER TABLE attendance ADD COLUMN latitude DECIMAL(10, 8) NULL", "Add latitude column");
            echo "✓ Added latitude column\n";
        } else {
            echo "✓ latitude column already exists\n";
        }
    } catch (Exception $e) {
        echo "✗ Error adding latitude column: " . $e->getMessage() . "\n";
    }
    
    // Add longitude column if it doesn't exist
    try {
        $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'longitude'");
        if ($stmt->rowCount() == 0) {
            DatabaseHelper::safeExec($db, "ALTER TABLE attendance ADD COLUMN longitude DECIMAL(11, 8) NULL", "Add longitude column");
            echo "✓ Added longitude column\n";
        } else {
            echo "✓ longitude column already exists\n";
        }
    } catch (Exception $e) {
        echo "✗ Error adding longitude column: " . $e->getMessage() . "\n";
    }
    
    // Add index for project_id if it doesn't exist
    try {
        $stmt = $db->query("SHOW INDEX FROM attendance WHERE Key_name = 'idx_project_id'");
        if ($stmt->rowCount() == 0) {
            DatabaseHelper::safeExec($db, "ALTER TABLE attendance ADD INDEX idx_project_id (project_id)", "Add project_id index");
            echo "✓ Added project_id index\n";
        } else {
            echo "✓ project_id index already exists\n";
        }
    } catch (Exception $e) {
        echo "✗ Error adding project_id index: " . $e->getMessage() . "\n";
    }
    
    echo "\nMigration completed successfully!\n";
    echo "Admin attendance records will now display Location and Project columns correctly.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
