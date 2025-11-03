<?php
/**
 * Debug Employee Attendance Updates Issue
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>🔍 Debug Employee Attendance Updates</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    $db = Database::connect();
    
    // Test 1: Check attendance table structure
    echo "<h2>1. Attendance Table Structure</h2>";
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Test 2: Check recent attendance records
    echo "<h2>2. Recent Attendance Records</h2>";
    $stmt = $db->query("SELECT a.*, u.name FROM attendance a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 10");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($records) > 0) {
        echo "<table border='1' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>User</th><th>Check In</th><th>Check Out</th><th>Status</th><th>Created</th></tr>";
        foreach ($records as $record) {
            echo "<tr>";
            echo "<td>{$record['id']}</td>";
            echo "<td>{$record['name']}</td>";
            echo "<td>" . ($record['check_in'] ?: 'NULL') . "</td>";
            echo "<td>" . ($record['check_out'] ?: 'NULL') . "</td>";
            echo "<td>{$record['status']}</td>";
            echo "<td>{$record['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "<span class='error'>❌ No attendance records found</span><br>";
    }
    
    // Test 3: Test the admin query
    echo "<h2>3. Test Admin Query (Today's Data)</h2>";
    
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
        ORDER BY u.role DESC, u.name
    ");
    $stmt->execute([$filterDate]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<span class='info'>Query returned " . count($employees) . " employees for date: $filterDate</span><br>";
    
    if (count($employees) > 0) {
        echo "<table border='1' style='border-collapse:collapse;'>";
        echo "<tr><th>Name</th><th>Department</th><th>Status</th><th>Check In</th><th>Check Out</th><th>Hours</th></tr>";
        foreach ($employees as $emp) {
            $statusColor = $emp['status'] === 'Present' ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$emp['name']}</td>";
            echo "<td>{$emp['department']}</td>";
            echo "<td style='color:$statusColor; font-weight:bold;'>{$emp['status']}</td>";
            echo "<td>" . ($emp['check_in'] ? date('H:i', strtotime($emp['check_in'])) : '-') . "</td>";
            echo "<td>" . ($emp['check_out'] ? date('H:i', strtotime($emp['check_out'])) : ($emp['check_in'] ? 'Working...' : '-')) . "</td>";
            echo "<td>{$emp['total_hours']}h</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // Test 4: Check if there are any attendance records for today
    echo "<h2>4. Today's Attendance Summary</h2>";
    
    $stmt = $db->prepare("SELECT COUNT(*) as total, COUNT(check_out) as completed FROM attendance WHERE DATE(check_in) = ?");
    $stmt->execute([$filterDate]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<span class='info'>Total attendance records today: {$summary['total']}</span><br>";
    echo "<span class='info'>Completed (with check-out): {$summary['completed']}</span><br>";
    echo "<span class='info'>Still working: " . ($summary['total'] - $summary['completed']) . "</span><br>";
    
    // Test 5: Create test attendance record
    echo "<h2>5. Test Clock In/Out Functions</h2>";
    
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        
        if ($action === 'test_clock_in') {
            // Get first user for testing
            $stmt = $db->query("SELECT id, name FROM users WHERE role = 'user' LIMIT 1");
            $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($testUser) {
                // Check if already clocked in today
                $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE() AND check_out IS NULL");
                $stmt->execute([$testUser['id']]);
                
                if (!$stmt->fetch()) {
                    $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, latitude, longitude, location, status, created_at) VALUES (?, NOW(), 28.6139, 77.2090, 'Test Location', 'present', NOW())");
                    $stmt->execute([$testUser['id']]);
                    echo "<span class='success'>✅ Test clock-in created for {$testUser['name']}</span><br>";
                } else {
                    echo "<span class='info'>ℹ️ {$testUser['name']} already clocked in today</span><br>";
                }
            }
        }
        
        if ($action === 'test_clock_out') {
            // Find user with clock-in but no clock-out
            $stmt = $db->prepare("SELECT a.id, u.name FROM attendance a LEFT JOIN users u ON a.user_id = u.id WHERE DATE(a.check_in) = CURDATE() AND a.check_out IS NULL LIMIT 1");
            $stmt->execute();
            $workingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($workingUser) {
                $stmt = $db->prepare("UPDATE attendance SET check_out = NOW(), updated_at = NOW() WHERE id = ?");
                $stmt->execute([$workingUser['id']]);
                echo "<span class='success'>✅ Test clock-out created for {$workingUser['name']}</span><br>";
            } else {
                echo "<span class='info'>ℹ️ No users currently working to clock out</span><br>";
            }
        }
    }
    
    echo "<div style='margin:20px 0;'>";
    echo "<a href='?action=test_clock_in' style='background:#28a745;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;margin:5px;'>Test Clock In</a>";
    echo "<a href='?action=test_clock_out' style='background:#dc3545;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;margin:5px;'>Test Clock Out</a>";
    echo "<a href='/ergon/attendance' style='background:#007bff;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;margin:5px;'>View Attendance Page</a>";
    echo "</div>";
    
    // Test 6: Check if the issue is with the JOIN
    echo "<h2>6. Simplified Query Test</h2>";
    
    $stmt = $db->prepare("SELECT u.name, a.check_in, a.check_out FROM users u LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ? WHERE u.role = 'user'");
    $stmt->execute([$filterDate]);
    $simpleResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Name</th><th>Check In (Raw)</th><th>Check Out (Raw)</th></tr>";
    foreach ($simpleResults as $result) {
        echo "<tr>";
        echo "<td>{$result['name']}</td>";
        echo "<td>" . ($result['check_in'] ?: '<em>NULL</em>') . "</td>";
        echo "<td>" . ($result['check_out'] ?: '<em>NULL</em>') . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
}
?>