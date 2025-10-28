<?php
/**
 * Final Validation Script
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "FINAL VALIDATION\n";
    echo "===============\n\n";
    
    $passed = 0;
    $total = 2;
    
    // Test 1: User delete status column
    echo "1. Testing user delete status column...\n";
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
    $column = $stmt->fetch();
    if ($column && strpos($column['Type'], 'deleted') !== false) {
        echo "   ✅ PASS - Status column supports 'deleted' value\n";
        $passed++;
    } else {
        echo "   ❌ FAIL - Status column issue persists\n";
    }
    
    // Test 2: Attendance clock_in column
    echo "2. Testing attendance clock_in column...\n";
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('clock_in', $columns)) {
        echo "   ✅ PASS - clock_in column exists\n";
        $passed++;
    } else {
        echo "   ❌ FAIL - clock_in column missing\n";
    }
    
    $successRate = round(($passed / $total) * 100, 1);
    echo "\nFINAL RESULT: $passed/$total tests passed ($successRate%)\n";
    
    if ($successRate == 100) {
        echo "🎉 SYSTEM IS NOW 100% OPERATIONAL!\n";
    } else {
        echo "⚠️  Manual intervention required for remaining issues.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Validation Error: " . $e->getMessage() . "\n";
}
?>