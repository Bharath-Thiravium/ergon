<?php
// Simple test to check admin routes
session_start();

// Mock session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';
$_SESSION['user_name'] = 'Test Owner';

echo "<h2>Testing Admin Routes</h2>";

// Test SystemAdminController
echo "<h3>System Admin Controller Test</h3>";
try {
    require_once __DIR__ . '/app/controllers/SystemAdminController.php';
    $controller = new SystemAdminController();
    echo "✓ SystemAdminController loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ SystemAdminController error: " . $e->getMessage() . "<br>";
}

// Test AdminManagementController
echo "<h3>Admin Management Controller Test</h3>";
try {
    require_once __DIR__ . '/app/controllers/AdminManagementController.php';
    $controller = new AdminManagementController();
    echo "✓ AdminManagementController loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ AdminManagementController error: " . $e->getMessage() . "<br>";
}

// Test database connection
echo "<h3>Database Connection Test</h3>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    echo "✓ Database connection successful<br>";
    
    // Check if users table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Users table exists<br>";
        
        // Check if is_system_admin column exists
        $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'is_system_admin'");
        if ($stmt->rowCount() > 0) {
            echo "✓ is_system_admin column exists<br>";
        } else {
            echo "⚠️ is_system_admin column missing - run SQL updates<br>";
        }
    } else {
        echo "❌ Users table missing<br>";
    }
    
    // Check if admin_positions table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'admin_positions'");
    if ($stmt->rowCount() > 0) {
        echo "✓ admin_positions table exists<br>";
    } else {
        echo "⚠️ admin_positions table missing - run SQL updates<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='/ergon/system-admin'>Test System Admin Page</a><br>";
echo "<a href='/ergon/admin/management'>Test Admin Management Page</a><br>";
?>