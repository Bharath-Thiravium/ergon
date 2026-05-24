<?php
/**
 * Push Subscription Registration
 * POST /ergon/api/push/subscribe
 */
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated']);
    exit;
}

require_once __DIR__ . '/../../app/config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$type  = $input['type'] ?? ''; // 'web' or 'fcm'
$db    = Database::connect();

// Ensure table exists
$db->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('web','fcm') NOT NULL DEFAULT 'web',
    endpoint TEXT,
    p256dh VARCHAR(255),
    auth VARCHAR(255),
    fcm_token TEXT,
    device_info VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$userId = (int)$_SESSION['user_id'];

if ($type === 'web') {
    $endpoint = $input['endpoint'] ?? '';
    $p256dh   = $input['keys']['p256dh'] ?? '';
    $auth     = $input['keys']['auth'] ?? '';

    if (!$endpoint) {
        echo json_encode(['success' => false, 'error' => 'Missing endpoint']);
        exit;
    }

    // Upsert by endpoint
    $stmt = $db->prepare("SELECT id FROM push_subscriptions WHERE user_id = ? AND type = 'web' AND endpoint = ?");
    $stmt->execute([$userId, $endpoint]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        $db->prepare("UPDATE push_subscriptions SET p256dh=?, auth=?, updated_at=NOW() WHERE id=?")
           ->execute([$p256dh, $auth, $existing]);
    } else {
        $db->prepare("INSERT INTO push_subscriptions (user_id, type, endpoint, p256dh, auth) VALUES (?,?,?,?,?)")
           ->execute([$userId, 'web', $endpoint, $p256dh, $auth]);
    }

} elseif ($type === 'fcm') {
    $token      = $input['token'] ?? '';
    $deviceInfo = $input['device'] ?? '';

    if (!$token) {
        echo json_encode(['success' => false, 'error' => 'Missing FCM token']);
        exit;
    }

    // Upsert by token
    $stmt = $db->prepare("SELECT id FROM push_subscriptions WHERE user_id = ? AND type = 'fcm' AND fcm_token = ?");
    $stmt->execute([$userId, $token]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        $db->prepare("UPDATE push_subscriptions SET device_info=?, updated_at=NOW() WHERE id=?")
           ->execute([$deviceInfo, $existing]);
    } else {
        $db->prepare("INSERT INTO push_subscriptions (user_id, type, fcm_token, device_info) VALUES (?,?,?,?)")
           ->execute([$userId, 'fcm', $token, $deviceInfo]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid type']);
    exit;
}

echo json_encode(['success' => true]);
