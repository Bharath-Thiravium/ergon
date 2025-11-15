<?php
// debug-remove-action.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$requestPayload = file_get_contents('php://input');
$data = json_decode($requestPayload, true);

file_put_contents('debug-log.txt', "🟡 Incoming Request: " . print_r($data, true), FILE_APPEND);

if (empty($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id']);
    exit;
}

$userId = $data['user_id'];

require_once __DIR__ . '/app/config/database.php';
$db = Database::connect();

if (!$db) {
    file_put_contents('debug-log.txt', "❌ DB Connection Error\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$stmt = $db->prepare("SELECT id, status FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    file_put_contents('debug-log.txt', "❌ User not found: ID $userId\n", FILE_APPEND);
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

file_put_contents('debug-log.txt', "✅ User Found: " . print_r($user, true), FILE_APPEND);

$newStatus = 'removed';
$update = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
$result = $update->execute([$newStatus, $userId]);

if ($result) {
    file_put_contents('debug-log.txt', "✅ Status updated to '$newStatus' for user ID $userId\n", FILE_APPEND);
    echo json_encode(['success' => true, 'user_id' => $userId, 'new_status' => $newStatus]);
} else {
    file_put_contents('debug-log.txt', "❌ Failed to update status\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update user']);
}
?>