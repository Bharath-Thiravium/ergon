<?php
session_start();

// Test if admin management is accessible
echo "<h2>Admin Management Test</h2>";

echo "<p><strong>Session Data:</strong></p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<p><strong>Direct Links:</strong></p>";
echo "<a href='/ergon/admin/management'>Admin Management</a><br>";
echo "<a href='/ergon/users'>User Management</a><br>";

// Test if AdminManagementController exists
if (file_exists(__DIR__ . '/app/controllers/AdminManagementController.php')) {
    echo "<p>✅ AdminManagementController.php exists</p>";
} else {
    echo "<p>❌ AdminManagementController.php missing</p>";
}

// Test if management view exists
if (file_exists(__DIR__ . '/app/views/admin/management.php')) {
    echo "<p>✅ admin/management.php view exists</p>";
} else {
    echo "<p>❌ admin/management.php view missing</p>";
}

echo "<p><strong>Current URL:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
?>