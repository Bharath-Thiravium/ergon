<?php
/**
 * Final Fix for Remaining 2 Issues
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "FINAL FIX EXECUTION\n";
    echo "==================\n\n";
    
    // Fix 1: User delete status column data truncation
    echo "1. Fixing user_delete status column...\n";
    $db->exec("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'deleted', 'suspended') DEFAULT 'active'");
    echo "   ✅ Status column updated with proper enum values\n";
    
    // Fix 2: Missing clock_in column in attendance table
    echo "2. Fixing attendance table structure...\n";
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('clock_in', $columns)) {
        $db->exec("ALTER TABLE attendance ADD COLUMN clock_in TIMESTAMP NULL AFTER user_id");
        echo "   ✅ Added clock_in column\n";
    }
    
    if (!in_array('clock_out', $columns)) {
        $db->exec("ALTER TABLE attendance ADD COLUMN clock_out TIMESTAMP NULL AFTER clock_in");
        echo "   ✅ Added clock_out column\n";
    }
    
    // Rename existing columns if needed
    if (in_array('check_in', $columns)) {
        $db->exec("ALTER TABLE attendance CHANGE check_in clock_in TIMESTAMP NULL");
        echo "   ✅ Renamed check_in to clock_in\n";
    }
    
    if (in_array('check_out', $columns)) {
        $db->exec("ALTER TABLE attendance CHANGE check_out clock_out TIMESTAMP NULL");
        echo "   ✅ Renamed check_out to clock_out\n";
    }
    
    echo "\n✅ ALL FIXES APPLIED SUCCESSFULLY!\n";
    echo "System should now be 100% operational.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>