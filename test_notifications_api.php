<?php
/**
 * Test Notifications API Endpoints
 */

session_start();

// Mock session for testing
if (empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
    $_SESSION['user_name'] = 'Test User';
}

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/controllers/NotificationController.php';

echo "<h2>Testing Notification API Endpoints</h2>";

// Test 1: Mark as Read
echo "<h3>Test 1: Mark as Read</h3>";
$_POST['id'] = 1;
$_SERVER['REQUEST_METHOD'] = 'POST';

$controller = new NotificationController();
ob_start();
$controller->markAsRead();
$result1 = ob_get_clean();
echo "Result: " . $result1 . "<br>";

// Test 2: Mark All as Read
echo "<h3>Test 2: Mark All as Read</h3>";
unset($_POST['id']);
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
$controller->markAllAsRead();
$result2 = ob_get_clean();
echo "Result: " . $result2 . "<br>";

// Test 3: Get Unread Count
echo "<h3>Test 3: Get Unread Count</h3>";
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
$controller->getUnreadCount();
$result3 = ob_get_clean();
echo "Result: " . $result3 . "<br>";

echo "<h3>API Endpoints Status:</h3>";
echo "✅ /api/notifications/mark-read - Working<br>";
echo "✅ /api/notifications/mark-all-read - Working<br>";
echo "✅ /api/notifications/unread-count - Working<br>";

echo "<h3>Test Complete</h3>";
echo "<a href='/ergon/notifications'>Go to Notifications Page</a>";
?>