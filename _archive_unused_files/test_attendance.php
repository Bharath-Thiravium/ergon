<?php
/**
 * Attendance Module Test Script
 * Run this to test if attendance functionality is working
 */

require_once __DIR__ . '/app/config/database.php';

try {
    echo "Testing Attendance Module...\n\n";
    
    $db = Database::connect();
    
    // Test 1: Check table structure
    echo "=== Test 1: Table Structure ===\n";
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['id', 'user_id', 'check_in', 'check_out'];
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $existingColumns)) {
            echo "✓ Required column '$col' exists\n";
        } else {
            echo "✗ Required column '$col' missing\n";
        }
    }
    
    // Test 2: Check if users exist
    echo "\n=== Test 2: Users Check ===\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total users: " . $userCount['count'] . "\n";
    
    if ($userCount['count'] > 0) {
        $stmt = $db->query("SELECT id, name, email FROM users LIMIT 3");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $user) {
            echo "- User ID {$user['id']}: {$user['name']} ({$user['email']})\n";
        }
    }
    
    // Test 3: Check attendance records
    echo "\n=== Test 3: Attendance Records ===\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM attendance");
    $attendanceCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total attendance records: " . $attendanceCount['count'] . "\n";
    
    if ($attendanceCount['count'] > 0) {
        $stmt = $db->query("
            SELECT a.id, a.user_id, a.check_in, a.check_out, u.name 
            FROM attendance a 
            LEFT JOIN users u ON a.user_id = u.id 
            ORDER BY a.check_in DESC 
            LIMIT 3
        ");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($records as $record) {
            echo "- Record ID {$record['id']}: {$record['name']} - Check In: {$record['check_in']} - Check Out: " . ($record['check_out'] ?: 'Not yet') . "\n";
        }
    }
    
    // Test 4: Test controller instantiation
    echo "\n=== Test 4: Controller Test ===\n";
    try {
        require_once __DIR__ . '/app/controllers/UnifiedAttendanceController.php';
        $controller = new UnifiedAttendanceController();
        echo "✓ UnifiedAttendanceController instantiated successfully\n";
    } catch (Exception $e) {
        echo "✗ Controller error: " . $e->getMessage() . "\n";
    }
    
    // Test 5: Check supporting tables
    echo "\n=== Test 5: Supporting Tables ===\n";
    
    $tables = ['attendance_rules', 'shifts'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "✓ Table '$table' exists with {$count['count']} records\n";
        } catch (Exception $e) {
            echo "⚠ Table '$table' issue: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Test Summary ===\n";
    echo "✅ Attendance module test completed!\n";
    echo "If you see any ✗ or ⚠ above, please run fix_attendance_database.php first.\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}
?>