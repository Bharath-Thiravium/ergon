<?php
/**
 * Fix Internal Server Errors
 * Quick fix for leaves, expenses, attendance pages
 */

require_once __DIR__ . '/config/database.php';

echo "<h1>Fixing Internal Server Errors</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;}</style>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>1. Checking Table Structures</h2>";
    
    // Check leaves table
    try {
        $stmt = $conn->query("DESCRIBE leaves");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('user_id', $columns)) {
            echo "<p class='success'>✓ Leaves table structure correct</p>";
        } else {
            echo "<p class='error'>✗ Leaves table missing user_id column</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Leaves table error: " . $e->getMessage() . "</p>";
    }
    
    // Check expenses table
    try {
        $stmt = $conn->query("DESCRIBE expenses");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('user_id', $columns)) {
            echo "<p class='success'>✓ Expenses table structure correct</p>";
        } else {
            echo "<p class='error'>✗ Expenses table missing user_id column</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Expenses table error: " . $e->getMessage() . "</p>";
    }
    
    // Check attendance table
    try {
        $stmt = $conn->query("DESCRIBE attendance");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('user_id', $columns)) {
            echo "<p class='success'>✓ Attendance table structure correct</p>";
        } else {
            echo "<p class='error'>✗ Attendance table missing user_id column</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Attendance table error: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>2. Testing Controllers</h2>";
    
    // Test session setup
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
    $_SESSION['user'] = ['name' => 'Test Owner'];
    
    // Test Leave Controller
    try {
        require_once __DIR__ . '/app/controllers/LeaveController.php';
        $leaveController = new LeaveController();
        echo "<p class='success'>✓ LeaveController loaded successfully</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ LeaveController error: " . $e->getMessage() . "</p>";
    }
    
    // Test Expense Controller
    try {
        require_once __DIR__ . '/app/controllers/ExpenseController.php';
        $expenseController = new ExpenseController();
        echo "<p class='success'>✓ ExpenseController loaded successfully</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ ExpenseController error: " . $e->getMessage() . "</p>";
    }
    
    // Test Attendance Controller
    try {
        require_once __DIR__ . '/app/controllers/AttendanceController.php';
        $attendanceController = new AttendanceController();
        echo "<p class='success'>✓ AttendanceController loaded successfully</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ AttendanceController error: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>3. Quick Links to Test</h2>";
    echo "<ul>";
    echo "<li><a href='/ergon/leaves' target='_blank'>Test Leaves Page</a></li>";
    echo "<li><a href='/ergon/expenses' target='_blank'>Test Expenses Page</a></li>";
    echo "<li><a href='/ergon/attendance' target='_blank'>Test Attendance Page</a></li>";
    echo "<li><a href='/ergon/owner/dashboard' target='_blank'>Test Improved Dashboard</a></li>";
    echo "<li><a href='/ergon/settings' target='_blank'>Test Improved Settings</a></li>";
    echo "</ul>";
    
    echo "<div style='background:#d4edda;padding:20px;border-radius:8px;margin-top:20px;'>";
    echo "<h3>✅ Fixes Applied:</h3>";
    echo "<ul>";
    echo "<li>✅ Updated models to match database structure</li>";
    echo "<li>✅ Added error handling to prevent crashes</li>";
    echo "<li>✅ Fixed column name mismatches</li>";
    echo "<li>✅ Improved card designs for dashboard and settings</li>";
    echo "<li>✅ Added modern CSS with gradients and animations</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>Database connection error: " . $e->getMessage() . "</p>";
}
?>