<?php
/**
 * Test Fixes Script
 * Verify all reported issues are resolved
 */

session_start();
require_once __DIR__ . '/config/database.php';

// Set up test session (simulate logged in owner)
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';
$_SESSION['user'] = ['name' => 'Test Owner', 'department' => 'Management'];

echo "<h1>ERGON Fix Verification</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .pass{color:green;} .fail{color:red;} .test{margin:10px 0;padding:10px;border:1px solid #ddd;}</style>";

$tests = [
    'Database Connection' => function() {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            return $conn ? "✓ Connected successfully" : "✗ Connection failed";
        } catch (Exception $e) {
            return "✗ Error: " . $e->getMessage();
        }
    },
    
    'Settings Table' => function() {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->query("SELECT COUNT(*) FROM settings");
            $count = $stmt->fetchColumn();
            return "✓ Settings table exists with $count records";
        } catch (Exception $e) {
            return "✗ Settings table error: " . $e->getMessage();
        }
    },
    
    'Departments Table' => function() {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->query("SELECT COUNT(*) FROM departments");
            $count = $stmt->fetchColumn();
            return "✓ Departments table exists with $count records";
        } catch (Exception $e) {
            return "✗ Departments table error: " . $e->getMessage();
        }
    },
    
    'Admin Positions Table' => function() {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->query("SELECT COUNT(*) FROM admin_positions");
            $count = $stmt->fetchColumn();
            return "✓ Admin positions table exists with $count records";
        } catch (Exception $e) {
            return "✗ Admin positions table error: " . $e->getMessage();
        }
    },
    
    'Daily Planner Table' => function() {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->query("SELECT COUNT(*) FROM daily_planner");
            $count = $stmt->fetchColumn();
            return "✓ Daily planner table exists with $count records";
        } catch (Exception $e) {
            return "✗ Daily planner table error: " . $e->getMessage();
        }
    },
    
    'Users with Departments' => function() {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE department IS NOT NULL AND department != ''");
            $count = $stmt->fetchColumn();
            return "✓ $count users have departments assigned";
        } catch (Exception $e) {
            return "✗ Users department check error: " . $e->getMessage();
        }
    },
    
    'Sample Data' => function() {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $leaves = $conn->query("SELECT COUNT(*) FROM leaves")->fetchColumn();
            $expenses = $conn->query("SELECT COUNT(*) FROM expenses")->fetchColumn();
            $attendance = $conn->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
            return "✓ Sample data: $leaves leaves, $expenses expenses, $attendance attendance records";
        } catch (Exception $e) {
            return "✗ Sample data error: " . $e->getMessage();
        }
    }
];

foreach ($tests as $testName => $testFunc) {
    echo "<div class='test'>";
    echo "<strong>$testName:</strong> ";
    $result = $testFunc();
    $class = strpos($result, '✓') === 0 ? 'pass' : 'fail';
    echo "<span class='$class'>$result</span>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>Quick Links to Test Fixed Issues:</h2>";
echo "<ul>";
echo "<li><a href='/ergon/admin/management'>Admin Management (Choose User field)</a></li>";
echo "<li><a href='/ergon/owner/approvals'>Owner Approvals (404 fix)</a></li>";
echo "<li><a href='/ergon/settings'>System Settings (Save Settings fix)</a></li>";
echo "<li><a href='/ergon/planner/calendar'>Daily Planner (Department field & dark mode)</a></li>";
echo "<li><a href='/ergon/leaves'>Leave Overview (sample data)</a></li>";
echo "<li><a href='/ergon/expenses'>Expense Overview (sample data)</a></li>";
echo "<li><a href='/ergon/attendance'>Attendance Overview (sample data)</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Run <a href='/ergon/run_fixes.php'>Database Fix Script</a> if any tables are missing</li>";
echo "<li>Test each functionality manually</li>";
echo "<li>Create additional users if needed</li>";
echo "</ol>";
?>