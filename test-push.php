<?php
/**
 * Test Push Notification - run once to verify end-to-end delivery
 * URL: /ergon/test-push.php
 * DELETE this file after testing
 */
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/services/PushService.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { die('Login first'); }

$userId = (int)$_SESSION['user_id'];
$db = Database::connect();

// Check subscriptions
$stmt = $db->prepare("SELECT id, type, endpoint FROM push_subscriptions WHERE user_id = ?");
$stmt->execute([$userId]);
$subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Subscriptions for user $userId:</h3><pre>" . print_r($subs, true) . "</pre>";

if (empty($subs)) {
    echo "<p style='color:red'>No subscriptions found. Reload the main app page first.</p>";
    exit;
}

// Send test push
PushService::sendToUser(
    $userId,
    '🔔 Ergon Test Notification',
    'Push notifications are working! You will now receive alerts outside the app.',
    '/ergon/notifications'
);

echo "<p style='color:green;font-size:18px'>✅ Push sent to $userId — check your browser/OS notifications!</p>";
echo "<p><a href='/ergon/dashboard'>← Back to Dashboard</a> &nbsp; <strong>Delete this file after testing.</strong></p>";
