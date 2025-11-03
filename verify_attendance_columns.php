<?php
// Verify attendance column consistency across user and admin panels
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>🔍 Attendance Column Verification</h2>";
    
    // 1. Check table structure
    echo "<h3>1. Database Table Structure</h3>";
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Check if check_in/check_out columns exist
    $hasCheckIn = false;
    $hasCheckOut = false;
    $hasClockIn = false;
    $hasClockOut = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'check_in') $hasCheckIn = true;
        if ($col['Field'] === 'check_out') $hasCheckOut = true;
        if ($col['Field'] === 'clock_in') $hasClockIn = true;
        if ($col['Field'] === 'clock_out') $hasClockOut = true;
    }
    
    echo "<h3>2. Column Existence Check</h3>";
    echo "<p>✅ check_in exists: " . ($hasCheckIn ? "YES" : "NO") . "</p>";
    echo "<p>✅ check_out exists: " . ($hasCheckOut ? "YES" : "NO") . "</p>";
    echo "<p>⚠️ clock_in exists: " . ($hasClockIn ? "YES (should be removed)" : "NO (good)") . "</p>";
    echo "<p>⚠️ clock_out exists: " . ($hasClockOut ? "YES (should be removed)" : "NO (good)") . "</p>";
    
    // 3. Test sample data
    echo "<h3>3. Sample Attendance Data</h3>";
    $stmt = $db->query("SELECT u.name, a.check_in, a.check_out, a.status FROM attendance a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.check_in DESC LIMIT 5");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($records)) {
        echo "<p>No attendance records found.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>User</th><th>Check In</th><th>Check Out</th><th>Status</th></tr>";
        foreach ($records as $record) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($record['name'] ?? 'Unknown') . "</td>";
            echo "<td>" . ($record['check_in'] ?? 'NULL') . "</td>";
            echo "<td>" . ($record['check_out'] ?? 'NULL') . "</td>";
            echo "<td>" . ($record['status'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Test user attendance query
    echo "<h3>4. User Panel Query Test</h3>";
    try {
        $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM attendance a LEFT JOIN users u ON a.user_id = u.id WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT 5");
        $stmt->execute([1]); // Test with user ID 1
        $userRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>✅ User attendance query successful. Found " . count($userRecords) . " records.</p>";
        
        if (!empty($userRecords)) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>User</th><th>Check In</th><th>Check Out</th><th>Hours</th></tr>";
            foreach ($userRecords as $record) {
                $hours = 0;
                if ($record['check_in'] && $record['check_out']) {
                    $hours = round((strtotime($record['check_out']) - strtotime($record['check_in'])) / 3600, 1);
                }
                echo "<tr>";
                echo "<td>" . htmlspecialchars($record['user_name'] ?? 'Unknown') . "</td>";
                echo "<td>" . ($record['check_in'] ? date('H:i', strtotime($record['check_in'])) : '-') . "</td>";
                echo "<td>" . ($record['check_out'] ? date('H:i', strtotime($record['check_out'])) : '-') . "</td>";
                echo "<td>" . $hours . "h</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p>❌ User attendance query failed: " . $e->getMessage() . "</p>";
    }
    
    // 5. Test admin attendance query
    echo "<h3>5. Admin Panel Query Test</h3>";
    try {
        $filterDate = date('Y-m-d');
        $stmt = $db->prepare("
            SELECT 
                u.id,
                u.name,
                u.email,
                u.role,
                COALESCE(d.name, u.department, 'Not Assigned') as department,
                a.check_in,
                a.check_out,
                CASE 
                    WHEN a.check_in IS NOT NULL THEN 'Present'
                    ELSE 'Absent'
                END as status,
                CASE 
                    WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                        ROUND(TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) / 60.0, 2)
                    ELSE 0
                END as total_hours
            FROM users u
            LEFT JOIN departments d ON u.department = d.id
            LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
            WHERE u.role = 'user'
            ORDER BY u.name
            LIMIT 5
        ");
        $stmt->execute([$filterDate]);
        $adminRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>✅ Admin attendance query successful. Found " . count($adminRecords) . " records.</p>";
        
        if (!empty($adminRecords)) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Employee</th><th>Department</th><th>Status</th><th>Check In</th><th>Check Out</th><th>Hours</th></tr>";
            foreach ($adminRecords as $record) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($record['name']) . "</td>";
                echo "<td>" . htmlspecialchars($record['department']) . "</td>";
                echo "<td>" . $record['status'] . "</td>";
                echo "<td>" . ($record['check_in'] ? date('H:i', strtotime($record['check_in'])) : '-') . "</td>";
                echo "<td>" . ($record['check_out'] ? date('H:i', strtotime($record['check_out'])) : '-') . "</td>";
                echo "<td>" . number_format($record['total_hours'], 2) . "h</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Admin attendance query failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>6. Summary</h3>";
    if ($hasCheckIn && $hasCheckOut && !$hasClockIn && !$hasClockOut) {
        echo "<p>✅ <strong>SUCCESS:</strong> All column names are consistent (check_in/check_out)</p>";
    } else {
        echo "<p>⚠️ <strong>WARNING:</strong> Column name inconsistencies detected</p>";
        if (!$hasCheckIn || !$hasCheckOut) {
            echo "<p>❌ Missing required columns: check_in or check_out</p>";
        }
        if ($hasClockIn || $hasClockOut) {
            echo "<p>⚠️ Old columns still exist: clock_in or clock_out (should be removed)</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Database Connection Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>