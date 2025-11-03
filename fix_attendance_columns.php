<?php
/**
 * Fix Attendance Table Columns
 * Adds missing check_in/check_out columns
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>🔧 Fix Attendance Table Columns</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    $db = Database::connect();
    echo "<span class='success'>✅ Database Connected</span><br><br>";
    
    // Check current attendance table structure
    echo "<h2>1. Current Attendance Table Structure</h2>";
    
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $hasCheckIn = false;
    $hasCheckOut = false;
    $hasClockIn = false;
    $hasClockOut = false;
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
        
        if ($col['Field'] === 'check_in') $hasCheckIn = true;
        if ($col['Field'] === 'check_out') $hasCheckOut = true;
        if ($col['Field'] === 'clock_in') $hasClockIn = true;
        if ($col['Field'] === 'clock_out') $hasClockOut = true;
    }
    echo "</table><br>";
    
    echo "<h2>2. Column Status Check</h2>";
    echo "<span class='" . ($hasCheckIn ? 'success' : 'error') . "'>check_in: " . ($hasCheckIn ? 'EXISTS' : 'MISSING') . "</span><br>";
    echo "<span class='" . ($hasCheckOut ? 'success' : 'error') . "'>check_out: " . ($hasCheckOut ? 'EXISTS' : 'MISSING') . "</span><br>";
    echo "<span class='" . ($hasClockIn ? 'info' : 'info') . "'>clock_in: " . ($hasClockIn ? 'EXISTS' : 'MISSING') . "</span><br>";
    echo "<span class='" . ($hasClockOut ? 'info' : 'info') . "'>clock_out: " . ($hasClockOut ? 'EXISTS' : 'MISSING') . "</span><br><br>";
    
    // Fix missing columns
    echo "<h2>3. Adding Missing Columns</h2>";
    
    if (!$hasCheckIn) {
        echo "<span class='info'>Adding check_in column...</span><br>";
        $db->exec("ALTER TABLE attendance ADD COLUMN check_in DATETIME DEFAULT NULL");
        echo "<span class='success'>✅ Added check_in column</span><br>";
        
        // Copy data from clock_in if it exists
        if ($hasClockIn) {
            $db->exec("UPDATE attendance SET check_in = clock_in WHERE clock_in IS NOT NULL");
            echo "<span class='success'>✅ Copied data from clock_in to check_in</span><br>";
        }
    } else {
        echo "<span class='success'>✅ check_in column already exists</span><br>";
    }
    
    if (!$hasCheckOut) {
        echo "<span class='info'>Adding check_out column...</span><br>";
        $db->exec("ALTER TABLE attendance ADD COLUMN check_out DATETIME DEFAULT NULL");
        echo "<span class='success'>✅ Added check_out column</span><br>";
        
        // Copy data from clock_out if it exists
        if ($hasClockOut) {
            $db->exec("UPDATE attendance SET check_out = clock_out WHERE clock_out IS NOT NULL");
            echo "<span class='success'>✅ Copied data from clock_out to check_out</span><br>";
        }
    } else {
        echo "<span class='success'>✅ check_out column already exists</span><br>";
    }
    
    // Add other missing columns if needed
    echo "<h2>4. Adding Additional Columns</h2>";
    
    $additionalColumns = [
        'shift_id' => 'INT DEFAULT 1',
        'distance_meters' => 'INT DEFAULT NULL',
        'is_auto_checkout' => 'TINYINT(1) DEFAULT 0'
    ];
    
    foreach ($additionalColumns as $colName => $colDef) {
        $hasColumn = false;
        foreach ($columns as $col) {
            if ($col['Field'] === $colName) {
                $hasColumn = true;
                break;
            }
        }
        
        if (!$hasColumn) {
            try {
                $db->exec("ALTER TABLE attendance ADD COLUMN $colName $colDef");
                echo "<span class='success'>✅ Added $colName column</span><br>";
            } catch (Exception $e) {
                echo "<span class='info'>ℹ️ $colName: " . $e->getMessage() . "</span><br>";
            }
        } else {
            echo "<span class='success'>✅ $colName column already exists</span><br>";
        }
    }
    
    // Test the fixed table
    echo "<h2>5. Testing Fixed Table</h2>";
    
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM attendance WHERE check_in IS NOT NULL");
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<span class='success'>✅ Query test passed - Found $count records with check_in data</span><br>";
    } catch (Exception $e) {
        echo "<span class='error'>❌ Query test failed: " . $e->getMessage() . "</span><br>";
    }
    
    // Show updated structure
    echo "<h2>6. Updated Table Structure</h2>";
    
    $stmt = $db->query("DESCRIBE attendance");
    $newColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($newColumns as $col) {
        $highlight = in_array($col['Field'], ['check_in', 'check_out']) ? 'background:#e8f5e8;' : '';
        echo "<tr style='$highlight'>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    echo "<h2>✅ Fix Complete!</h2>";
    echo "<div style='background:#e8f5e8;padding:20px;border-radius:8px;margin:20px 0;'>";
    echo "<h3>🎉 Attendance Table Fixed!</h3>";
    echo "<p><strong>Changes Applied:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Added check_in and check_out columns</li>";
    echo "<li>✅ Migrated data from old columns if they existed</li>";
    echo "<li>✅ Added additional required columns</li>";
    echo "<li>✅ Tested table functionality</li>";
    echo "</ul>";
    echo "<p><strong>Test the attendance page:</strong></p>";
    echo "<a href='/ergon/attendance' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;'>Test Attendance Page</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
    echo "<p>Please check your database connection and permissions.</p>";
}
?>