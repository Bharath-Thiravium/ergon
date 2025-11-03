<?php
/**
 * Debug Attendance Users Display Issue
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>🔍 Debug Attendance Users Display</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .debug{background:#f8f9fa;padding:10px;border-radius:4px;margin:10px 0;}</style>";

try {
    $db = Database::connect();
    echo "<span class='success'>✅ Database Connected</span><br><br>";
    
    // Check 1: Users table structure and data
    echo "<h2>1. Users Table Check</h2>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<span class='info'>Total users in database: $userCount</span><br>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
    $employeeCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<span class='info'>Employees (role='user'): $employeeCount</span><br>";
    
    if ($employeeCount == 0) {
        echo "<span class='error'>❌ No employees found! This is the main issue.</span><br>";
        echo "<div class='debug'><strong>Solution:</strong> Create test employees or check if users have role='user'</div>";
    }
    
    // Show sample users
    $stmt = $db->query("SELECT id, name, email, role, department FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Sample Users:</h3>";
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Department</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td><strong>{$user['role']}</strong></td>";
        echo "<td>{$user['department']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Check 2: Attendance table structure
    echo "<h2>2. Attendance Table Check</h2>";
    
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasCheckIn = false;
    $hasCheckOut = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'check_in') $hasCheckIn = true;
        if ($col['Field'] === 'check_out') $hasCheckOut = true;
    }
    
    echo "<span class='" . ($hasCheckIn ? 'success' : 'error') . "'>check_in column: " . ($hasCheckIn ? 'EXISTS' : 'MISSING') . "</span><br>";
    echo "<span class='" . ($hasCheckOut ? 'success' : 'error') . "'>check_out column: " . ($hasCheckOut ? 'EXISTS' : 'MISSING') . "</span><br>";
    
    // Check 3: Test the exact query from AttendanceController
    echo "<h2>3. Test Admin Query</h2>";
    
    $filterDate = date('Y-m-d');
    echo "<span class='info'>Testing query for date: $filterDate</span><br>";
    
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.department,
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
        LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
        WHERE u.role = 'user'
        ORDER BY u.name
    ");
    $stmt->execute([$filterDate]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<span class='info'>Query returned " . count($employees) . " employees</span><br>";
    
    if (count($employees) > 0) {
        echo "<h3>Employee Results:</h3>";
        echo "<table border='1' style='border-collapse:collapse;'>";
        echo "<tr><th>Name</th><th>Email</th><th>Department</th><th>Status</th><th>Check In</th><th>Check Out</th><th>Hours</th></tr>";
        foreach ($employees as $emp) {
            echo "<tr>";
            echo "<td>{$emp['name']}</td>";
            echo "<td>{$emp['email']}</td>";
            echo "<td>{$emp['department']}</td>";
            echo "<td><strong>{$emp['status']}</strong></td>";
            echo "<td>{$emp['check_in']}</td>";
            echo "<td>{$emp['check_out']}</td>";
            echo "<td>{$emp['total_hours']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "<span class='error'>❌ No employees returned by query!</span><br>";
    }
    
    // Check 4: Session and role check
    echo "<h2>4. Session Check</h2>";
    
    session_start();
    if (isset($_SESSION['role'])) {
        echo "<span class='info'>Current session role: {$_SESSION['role']}</span><br>";
        echo "<span class='info'>Current user ID: {$_SESSION['user_id']}</span><br>";
        
        if ($_SESSION['role'] === 'user') {
            echo "<span class='error'>⚠️ You're logged in as 'user' - admin view won't show!</span><br>";
            echo "<div class='debug'><strong>Solution:</strong> Login as admin or owner to see all employees</div>";
        }
    } else {
        echo "<span class='error'>❌ No session found - not logged in</span><br>";
    }
    
    // Check 5: Create test data if needed
    echo "<h2>5. Quick Fixes</h2>";
    
    if ($employeeCount == 0) {
        echo "<a href='?action=create_test_users' style='background:#007bff;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;'>Create Test Employees</a><br><br>";
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'create_test_users') {
        echo "<h3>Creating Test Employees...</h3>";
        
        $testUsers = [
            ['John Doe', 'john@company.com', 'IT'],
            ['Jane Smith', 'jane@company.com', 'HR'],
            ['Mike Johnson', 'mike@company.com', 'Sales']
        ];
        
        foreach ($testUsers as $user) {
            $stmt = $db->prepare("INSERT IGNORE INTO users (name, email, password, role, department, created_at) VALUES (?, ?, ?, 'user', ?, NOW())");
            $stmt->execute([$user[0], $user[1], password_hash('password123', PASSWORD_DEFAULT), $user[2]]);
            echo "<span class='success'>✅ Created: {$user[0]}</span><br>";
        }
        
        echo "<br><a href='/ergon/attendance' style='background:#28a745;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;'>Test Attendance Page</a>";
    }
    
    echo "<h2>6. Test Links</h2>";
    echo "<a href='/ergon/attendance' target='_blank' style='background:#28a745;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;margin:5px;'>Test Attendance Page</a>";
    echo "<a href='/ergon/attendance/clock' target='_blank' style='background:#17a2b8;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;margin:5px;'>Test Clock Page</a>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
}
?>