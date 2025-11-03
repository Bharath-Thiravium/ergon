<?php
/**
 * Root Cause Analysis & Fix for Employee Attendance Updates
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>🔍 Root Cause Analysis: Employee Attendance Updates</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .warning{color:orange;}</style>";

try {
    $db = Database::connect();
    
    // Step 1: Check if attendance table has correct structure
    echo "<h2>Step 1: Database Structure Analysis</h2>";
    
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasCheckIn = in_array('check_in', $columns);
    $hasCheckOut = in_array('check_out', $columns);
    $hasClockIn = in_array('clock_in', $columns);
    $hasClockOut = in_array('clock_out', $columns);
    
    echo "<span class='" . ($hasCheckIn ? 'success' : 'error') . "'>check_in column: " . ($hasCheckIn ? 'EXISTS' : 'MISSING') . "</span><br>";
    echo "<span class='" . ($hasCheckOut ? 'success' : 'error') . "'>check_out column: " . ($hasCheckOut ? 'EXISTS' : 'MISSING') . "</span><br>";
    echo "<span class='" . ($hasClockIn ? 'info' : 'info') . "'>clock_in column: " . ($hasClockIn ? 'EXISTS' : 'NOT FOUND') . "</span><br>";
    echo "<span class='" . ($hasClockOut ? 'info' : 'info') . "'>clock_out column: " . ($hasClockOut ? 'EXISTS' : 'NOT FOUND') . "</span><br>";
    
    // Step 2: Test clock functionality
    echo "<h2>Step 2: Clock Functionality Test</h2>";
    
    if (isset($_GET['test']) && $_GET['test'] === 'clock_in') {
        // Simulate employee clock in
        $testUserId = 1; // Assuming user ID 1 exists
        
        // Check if user exists
        $stmt = $db->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'user'");
        $stmt->execute([$testUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Check if already clocked in today
            $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE()");
            $stmt->execute([$testUserId]);
            
            if (!$stmt->fetch()) {
                // Insert clock in record
                $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, latitude, longitude, location, status, created_at) VALUES (?, NOW(), 28.6139, 77.2090, 'Test Location', 'present', NOW())");
                $result = $stmt->execute([$testUserId]);
                
                if ($result) {
                    echo "<span class='success'>✅ Test clock-in successful for user: {$user['name']}</span><br>";
                } else {
                    echo "<span class='error'>❌ Test clock-in failed</span><br>";
                }
            } else {
                echo "<span class='warning'>⚠️ User already clocked in today</span><br>";
            }
        } else {
            echo "<span class='error'>❌ Test user not found</span><br>";
        }
    }
    
    if (isset($_GET['test']) && $_GET['test'] === 'clock_out') {
        // Find user with clock-in but no clock-out
        $stmt = $db->prepare("SELECT a.id, u.name FROM attendance a JOIN users u ON a.user_id = u.id WHERE DATE(a.check_in) = CURDATE() AND a.check_out IS NULL LIMIT 1");
        $stmt->execute();
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            $stmt = $db->prepare("UPDATE attendance SET check_out = NOW(), updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$record['id']]);
            
            if ($result) {
                echo "<span class='success'>✅ Test clock-out successful for user: {$record['name']}</span><br>";
            } else {
                echo "<span class='error'>❌ Test clock-out failed</span><br>";
            }
        } else {
            echo "<span class='warning'>⚠️ No users currently working to clock out</span><br>";
        }
    }
    
    // Step 3: Check admin query
    echo "<h2>Step 3: Admin Query Analysis</h2>";
    
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
    ");
    $stmt->execute([$filterDate]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<span class='info'>Admin query returned " . count($employees) . " employees</span><br>";
    
    if (count($employees) > 0) {
        echo "<table border='1' style='border-collapse:collapse; margin:10px 0;'>";
        echo "<tr><th>Name</th><th>Status</th><th>Check In</th><th>Check Out</th><th>Hours</th></tr>";
        foreach ($employees as $emp) {
            $statusColor = $emp['status'] === 'Present' ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$emp['name']}</td>";
            echo "<td style='color:$statusColor; font-weight:bold;'>{$emp['status']}</td>";
            echo "<td>" . ($emp['check_in'] ? date('H:i', strtotime($emp['check_in'])) : '-') . "</td>";
            echo "<td>" . ($emp['check_out'] ? date('H:i', strtotime($emp['check_out'])) : ($emp['check_in'] ? 'Working...' : '-')) . "</td>";
            echo "<td>{$emp['total_hours']}h</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Step 4: Check recent attendance records
    echo "<h2>Step 4: Recent Attendance Records</h2>";
    
    $stmt = $db->query("SELECT a.*, u.name FROM attendance a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($records) > 0) {
        echo "<table border='1' style='border-collapse:collapse; margin:10px 0;'>";
        echo "<tr><th>User</th><th>Check In</th><th>Check Out</th><th>Status</th><th>Created</th></tr>";
        foreach ($records as $record) {
            echo "<tr>";
            echo "<td>{$record['name']}</td>";
            echo "<td>" . ($record['check_in'] ?: 'NULL') . "</td>";
            echo "<td>" . ($record['check_out'] ?: 'NULL') . "</td>";
            echo "<td>{$record['status']}</td>";
            echo "<td>{$record['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<span class='error'>❌ No attendance records found</span><br>";
    }
    
    // Step 5: Root cause identification
    echo "<h2>Step 5: Root Cause Identification</h2>";
    
    $issues = [];
    
    if (!$hasCheckIn || !$hasCheckOut) {
        $issues[] = "Missing check_in/check_out columns in attendance table";
    }
    
    if (count($records) == 0) {
        $issues[] = "No attendance records exist - clock in/out may not be working";
    }
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($userCount == 0) {
        $issues[] = "No employee users found in system";
    }
    
    if (empty($issues)) {
        echo "<span class='success'>✅ No structural issues found</span><br>";
        echo "<span class='info'>Issue may be with real-time updates or JavaScript refresh</span><br>";
    } else {
        echo "<span class='error'>❌ Issues identified:</span><br>";
        foreach ($issues as $issue) {
            echo "<span class='error'>  - $issue</span><br>";
        }
    }
    
    // Step 6: Automated fixes
    echo "<h2>Step 6: Automated Fixes</h2>";
    
    if (isset($_GET['fix'])) {
        $fix = $_GET['fix'];
        
        if ($fix === 'columns' && (!$hasCheckIn || !$hasCheckOut)) {
            if (!$hasCheckIn) {
                $db->exec("ALTER TABLE attendance ADD COLUMN check_in DATETIME DEFAULT NULL");
                echo "<span class='success'>✅ Added check_in column</span><br>";
            }
            
            if (!$hasCheckOut) {
                $db->exec("ALTER TABLE attendance ADD COLUMN check_out DATETIME DEFAULT NULL");
                echo "<span class='success'>✅ Added check_out column</span><br>";
            }
            
            // Copy data from old columns if they exist
            if ($hasClockIn) {
                $db->exec("UPDATE attendance SET check_in = clock_in WHERE check_in IS NULL AND clock_in IS NOT NULL");
                echo "<span class='success'>✅ Migrated clock_in data</span><br>";
            }
            
            if ($hasClockOut) {
                $db->exec("UPDATE attendance SET check_out = clock_out WHERE check_out IS NULL AND clock_out IS NOT NULL");
                echo "<span class='success'>✅ Migrated clock_out data</span><br>";
            }
        }
        
        if ($fix === 'test_data') {
            // Create test users if none exist
            $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
            $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($userCount == 0) {
                $testUsers = [
                    ['Test Employee 1', 'emp1@test.com', 'IT Department'],
                    ['Test Employee 2', 'emp2@test.com', 'HR Department']
                ];
                
                foreach ($testUsers as $user) {
                    $stmt = $db->prepare("INSERT INTO users (name, email, password, role, department, created_at) VALUES (?, ?, ?, 'user', ?, NOW())");
                    $stmt->execute([$user[0], $user[1], password_hash('password123', PASSWORD_DEFAULT), $user[2]]);
                    echo "<span class='success'>✅ Created test user: {$user[0]}</span><br>";
                }
            }
        }
    }
    
    // Action buttons
    echo "<h2>Step 7: Action Buttons</h2>";
    echo "<div style='margin:20px 0;'>";
    
    if (!$hasCheckIn || !$hasCheckOut) {
        echo "<a href='?fix=columns' style='background:#dc3545;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;margin:5px;'>Fix Missing Columns</a>";
    }
    
    if ($userCount == 0) {
        echo "<a href='?fix=test_data' style='background:#28a745;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;margin:5px;'>Create Test Users</a>";
    }
    
    echo "<a href='?test=clock_in' style='background:#007bff;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;margin:5px;'>Test Clock In</a>";
    echo "<a href='?test=clock_out' style='background:#6c757d;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;margin:5px;'>Test Clock Out</a>";
    echo "<a href='/ergon/attendance' style='background:#17a2b8;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;margin:5px;'>View Attendance Page</a>";
    echo "</div>";
    
    echo "<h2>✅ Analysis Complete</h2>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Fix any identified structural issues using the buttons above</li>";
    echo "<li>Test clock in/out functionality</li>";
    echo "<li>Verify updates appear in admin panel</li>";
    echo "<li>Check browser console for JavaScript errors</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
}
?>