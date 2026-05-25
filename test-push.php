<?php
/**
 * Test Push Notification
 * DELETE this file after testing
 */
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/services/PushService.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { die('Login first'); }

$userId = (int)$_SESSION['user_id'];
$db = Database::connect();

$stmt = $db->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
$stmt->execute([$userId]);
$subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Subscriptions for user $userId:</h3><pre>" . print_r($subs, true) . "</pre>";

if (empty($subs)) {
    echo "<p style='color:red'>No subscriptions found.</p>";
    exit;
}

// Check VAPID keys
$vapidPublic  = $_ENV['VAPID_PUBLIC_KEY']  ?? '';
$vapidPrivate = $_ENV['VAPID_PRIVATE_KEY'] ?? '';
echo "<p>VAPID Public: " . ($vapidPublic  ? '✅ Set (' . substr($vapidPublic, 0, 20) . '...)' : '❌ MISSING') . "</p>";
echo "<p>VAPID Private: " . ($vapidPrivate ? '✅ Set' : '❌ MISSING') . "</p>";
echo "<p>OpenSSL: " . (extension_loaded('openssl') ? '✅ Available' : '❌ Missing') . "</p>";
echo "<p>cURL: " . (extension_loaded('curl') ? '✅ Available' : '❌ Missing') . "</p>";

if (!$vapidPublic || !$vapidPrivate) {
    echo "<p style='color:red'>VAPID keys missing in .env — push cannot work.</p>";
    exit;
}

// Send and capture any errors
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

PushService::sendToUser(
    $userId,
    '🔔 Ergon Test',
    'Push notifications working! ' . date('H:i:s'),
    '/ergon/notifications'
);

$errors = ob_get_clean();

if ($errors) {
    echo "<h3 style='color:red'>Errors during send:</h3><pre style='background:#fee;padding:10px'>" . htmlspecialchars($errors) . "</pre>";
} else {
    echo "<p style='color:green;font-size:18px'>✅ Push sent with no PHP errors — check OS notifications!</p>";
}

// Also check error log for curl response
$logFile = __DIR__ . '/storage/logs/push_test.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . ' push test for user ' . $userId . PHP_EOL, FILE_APPEND);

echo "<p><a href='/ergon/dashboard'>← Back</a> &nbsp; <strong>Delete this file after testing.</strong></p>";
