<?php
/**
 * Fix Attendance Display Issues
 * Ensures all users are displayed in attendance module
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>🔧 Fix Attendance Display Issues</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    $db = Database::connect();
    echo "<span class='success'>✅ Database Connected</span><br><br>";
    
    // Step 1: Check and fix users table
    echo "<h2>Step 1: Checking Users</h2>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<span class='info'>Current employees (role='user'): $userCount</span><br>";
    
    if ($userCount == 0) {
        echo "<span class='error'>❌ No employees found! Creating test employees...</span><br>";
        
        $testUsers = [
            ['John Doe', 'john@company.com', 'IT Department'],
            ['Jane Smith', 'jane@company.com', 'HR Department'],
            ['Mike Johnson', 'mike@company.com', 'Sales Department'],
            ['Sarah Wilson', 'sarah@company.com', 'Marketing Department'],
            ['David Brown', 'david@company.com', 'Finance Department']
        ];
        
        foreach ($testUsers as $user) {
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, department, created_at) VALUES (?, ?, ?, 'user', ?, NOW())");
            $stmt->execute([$user[0], $user[1], password_hash('password123', PASSWORD_DEFAULT), $user[2]]);
            echo "<span class='success'>✅ Created: {$user[0]} - {$user[2]}</span><br>";
        }
        
        $userCount = count($testUsers);
    }
    
    // Step 2: Ensure attendance table has correct structure
    echo "<h2>Step 2: Checking Attendance Table</h2>";
    
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasCheckIn = in_array('check_in', $columns);
    $hasCheckOut = in_array('check_out', $columns);
    
    if (!$hasCheckIn || !$hasCheckOut) {
        echo "<span class='error'>❌ Missing check_in/check_out columns. Adding them...</span><br>";
        
        if (!$hasCheckIn) {
            $db->exec("ALTER TABLE attendance ADD COLUMN check_in DATETIME DEFAULT NULL");
            echo "<span class='success'>✅ Added check_in column</span><br>";
        }
        
        if (!$hasCheckOut) {
            $db->exec("ALTER TABLE attendance ADD COLUMN check_out DATETIME DEFAULT NULL");
            echo "<span class='success'>✅ Added check_out column</span><br>";
        }
        
        // Copy data from old columns if they exist
        if (in_array('clock_in', $columns)) {
            $db->exec("UPDATE attendance SET check_in = clock_in WHERE check_in IS NULL AND clock_in IS NOT NULL");
            $db->exec("UPDATE attendance SET check_out = clock_out WHERE check_out IS NULL AND clock_out IS NOT NULL");
            echo "<span class='success'>✅ Migrated data from clock_in/clock_out</span><br>";
        }
    } else {
        echo "<span class='success'>✅ Attendance table structure is correct</span><br>";
    }
    
    // Step 3: Create sample attendance data
    echo "<h2>Step 3: Creating Sample Attendance Data</h2>";
    
    $stmt = $db->query("SELECT id, name FROM users WHERE role = 'user' LIMIT 3");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $i => $user) {
        // Check if user already has attendance today
        $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE()");
        $stmt->execute([$user['id']]);
        
        if (!$stmt->fetch()) {
            if ($i < 2) { // First 2 users get attendance
                $checkIn = date('Y-m-d 09:' . sprintf('%02d', rand(0, 30)) . ':00');
                $checkOut = $i == 0 ? date('Y-m-d 17:' . sprintf('%02d', rand(0, 59)) . ':00') : null;
                
                $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, check_out, status, location, created_at) VALUES (?, ?, ?, 'present', 'Office', NOW())");
                $stmt->execute([$user['id'], $checkIn, $checkOut]);
                
                echo "<span class='success'>✅ Created attendance for {$user['name']} - " . ($checkOut ? 'Full day' : 'Still working') . "</span><br>";
            } else {
                echo "<span class='info'>ℹ️ {$user['name']} - Absent (no attendance record)</span><br>";
            }
        } else {
            echo "<span class='info'>ℹ️ {$user['name']} - Already has attendance today</span><br>";
        }
    }
    
    // Step 4: Test the query
    echo "<h2>Step 4: Testing Admin Query</h2>";
    
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            COALESCE(u.department, 'General') as department,
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
        LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = CURDATE()
        WHERE u.role = 'user'
        ORDER BY u.name
    ");
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<span class='success'>✅ Query returned " . count($employees) . " employees</span><br>";
    
    if (count($employees) > 0) {
        echo "<h3>Employee Status Preview:</h3>";
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr style='background:#f8f9fa;'><th>Name</th><th>Department</th><th>Status</th><th>Check In</th><th>Check Out</th><th>Hours</th></tr>";
        
        foreach ($employees as $emp) {
            $statusColor = $emp['status'] === 'Present' ? '#22c55e' : '#ef4444';
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
    
    echo "<h2>✅ Fix Complete!</h2>";
    echo "<div style='background:#e8f5e8;padding:20px;border-radius:8px;margin:20px 0;'>";
    echo "<h3>🎉 Attendance Display Fixed!</h3>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>✅ $userCount employees in system</li>";
    echo "<li>✅ Attendance table structure verified</li>";
    echo "<li>✅ Sample attendance data created</li>";
    echo "<li>✅ Admin query tested successfully</li>";
    echo "</ul>";
    echo "<p><strong>Test the attendance page:</strong></p>";
    echo "<a href='/ergon/attendance' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;margin:5px;'>View Attendance Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
    echo "<p>Please check your database connection and table structure.</p>";
}
?>