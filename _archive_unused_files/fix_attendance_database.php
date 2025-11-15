<?php
/**
 * Attendance Database Fix Script
 * Run this script to fix attendance table structure issues
 */

require_once __DIR__ . '/app/config/database.php';

try {
    echo "Starting attendance database fix...\n";
    
    $db = Database::connect();
    
    // Read and execute the SQL fix
    $sqlFile = __DIR__ . '/database/fix_attendance_minimal.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL fix file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $db->beginTransaction();
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty lines and comments
        }
        
        try {
            $db->exec($statement);
            echo "✓ Executed: " . substr(trim($statement), 0, 50) . "...\n";
        } catch (Exception $e) {
            // Only show critical errors, ignore minor ones like "table doesn't exist"
            if (strpos($e->getMessage(), "doesn't exist") === false && 
                strpos($e->getMessage(), "Duplicate") === false) {
                echo "⚠ Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    $db->commit();
    
    // Verify the fix
    echo "\n=== Verifying Fix ===\n";
    
    // Check table structure
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['id', 'user_id', 'check_in', 'check_out', 'latitude', 'longitude', 'location_name', 'status'];
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $existingColumns)) {
            echo "✓ Column '$col' exists\n";
        } else {
            echo "✗ Column '$col' missing\n";
        }
    }
    
    // Check data
    $stmt = $db->query("SELECT COUNT(*) as count FROM attendance");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Total attendance records: " . $count['count'] . "\n";
    
    echo "\n✅ Attendance database fix completed successfully!\n";
    echo "You can now use the attendance module normally.\n";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Please check the error and try again.\n";
}
?>