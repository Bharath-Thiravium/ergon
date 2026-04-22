<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../app/config/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['authenticated' => false, 'active' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    require_once __DIR__ . '/../app/config/database.php';
    $db = Database::connect();
    $stmt = $db->prepare("SELECT status, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // User row missing — do not destroy session, just report
        echo json_encode(['authenticated' => true, 'active' => true, 'user_id' => $_SESSION['user_id'], 'role' => $_SESSION['role'] ?? 'user']);
        exit;
    }

    if ($user['status'] !== 'active') {
        // Genuinely deactivated — clear session
        session_unset();
        session_destroy();
        http_response_code(401);
        echo json_encode(['authenticated' => false, 'active' => false, 'message' => 'User deactivated']);
        exit;
    }

    if ($user['role'] !== ($_SESSION['role'] ?? '')) {
        // Role changed — clear session so new role takes effect on next login
        session_unset();
        session_destroy();
        http_response_code(401);
        echo json_encode(['authenticated' => false, 'active' => false, 'role_changed' => true, 'message' => 'Role changed']);
        exit;
    }

} catch (Exception $e) {
    // DB error — do NOT log out the user, just return active:true to be safe
    error_log('Auth check failed: ' . $e->getMessage());
    echo json_encode(['authenticated' => true, 'active' => true, 'user_id' => $_SESSION['user_id'], 'role' => $_SESSION['role'] ?? 'user']);
    exit;
}

echo json_encode([
    'authenticated' => true,
    'active' => true,
    'user_id' => $_SESSION['user_id'],
    'role' => $_SESSION['role'] ?? 'user',
    'name' => $_SESSION['user_name'] ?? 'User'
]);
?>
