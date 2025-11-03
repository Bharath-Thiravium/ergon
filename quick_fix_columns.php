<?php
/**
 * Quick Fix for Missing check_in/check_out Columns
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>⚡ Quick Fix: Add Missing Columns</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;}</style>";

try {
    $db = Database::connect();
    
    // Check if columns exist
    $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'check_in'");
    $hasCheckIn = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'check_out'");
    $hasCheckOut = $stmt->rowCount() > 0;
    
    echo "<h2>Adding Missing Columns:</h2>";
    
    // Add check_in column
    if (!$hasCheckIn) {
        $db->exec("ALTER TABLE attendance ADD COLUMN check_in DATETIME DEFAULT NULL");
        echo "<span class='success'>✅ Added check_in column</span><br>";
        
        // Copy from clock_in if exists
        try {
            $db->exec("UPDATE attendance SET check_in = clock_in WHERE clock_in IS NOT NULL");
            echo "<span class='success'>✅ Copied data from clock_in</span><br>";
        } catch (Exception $e) {
            // clock_in column doesn't exist, that's fine
        }
    } else {
        echo "<span class='success'>✅ check_in column already exists</span><br>";
    }
    
    // Add check_out column
    if (!$hasCheckOut) {
        $db->exec("ALTER TABLE attendance ADD COLUMN check_out DATETIME DEFAULT NULL");
        echo "<span class='success'>✅ Added check_out column</span><br>";
        
        // Copy from clock_out if exists
        try {
            $db->exec("UPDATE attendance SET check_out = clock_out WHERE clock_out IS NOT NULL");
            echo "<span class='success'>✅ Copied data from clock_out</span><br>";
        } catch (Exception $e) {
            // clock_out column doesn't exist, that's fine
        }
    } else {
        echo "<span class='success'>✅ check_out column already exists</span><br>";
    }
    
    // Test the fix
    echo "<h2>Testing Fix:</h2>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM attendance WHERE 1=1");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<span class='success'>✅ Table query successful - $count total records</span><br>";
    
    echo "<h2>✅ Fix Complete!</h2>";
    echo "<a href='/ergon/attendance' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;'>Test Attendance Page</a>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
}
?>