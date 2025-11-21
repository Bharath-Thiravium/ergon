<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['authenticated' => false, 'active' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    // Check cache first (cache for 30 seconds to reduce DB load)
    $cacheKey = 'user_status_' . $_SESSION['user_id'];
    $cachedData = null;
    if (function_exists('apcu_fetch')) {
        $cachedData = apcu_fetch($cacheKey);
    }
    
    if (!$cachedData) {
        require_once __DIR__ . '/../app/config/database.php';
        $db = Database::connect();
        $stmt = $db->prepare("SELECT status, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Cache the result for 30 seconds
        if (function_exists('apcu_store') && $user) {
            apcu_store($cacheKey, $user, 30);
        }
    } else {
        $user = $cachedData;
    }
    
    if (!$user || $user['status'] !== 'active') {
        session_unset();
        session_destroy();
        http_response_code(401);
        echo json_encode(['authenticated' => false, 'active' => false, 'message' => 'User deactivated']);
        exit;
    }
    
    if ($user['role'] !== $_SESSION['role']) {
        session_unset();
        session_destroy();
        http_response_code(401);
        echo json_encode(['authenticated' => false, 'active' => false, 'role_changed' => true, 'message' => 'Role changed']);
        exit;
    }
} catch (Exception $e) {
    error_log('Auth check failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['authenticated' => false, 'active' => false, 'message' => 'Server error']);
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
