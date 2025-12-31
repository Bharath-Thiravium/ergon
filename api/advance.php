<?php
// Ensure no output before headers
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header early
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    ob_clean(); // Clear any previous output
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'error' => 'Not authenticated'], 401);
}

try {
    require_once __DIR__ . '/../app/config/database.php';
    
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendJsonResponse(['success' => false, 'error' => 'ID required'], 400);
    }
    
    $db = Database::connect();
    
    // Check if user is admin/owner or owns the advance
    $userRole = $_SESSION['role'] ?? 'user';
    if (in_array($userRole, ['admin', 'owner'])) {
        // Admin/owner can view any advance
        $stmt = $db->prepare("SELECT * FROM advances WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        // Regular user can only view their own advances
        $stmt = $db->prepare("SELECT * FROM advances WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
    }
    
    $advance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$advance) {
        sendJsonResponse(['success' => false, 'error' => 'Advance not found'], 404);
    }
    
    sendJsonResponse(['success' => true, 'advance' => $advance]);
    
} catch (Exception $e) {
    error_log('Advance API error: ' . $e->getMessage());
    sendJsonResponse(['success' => false, 'error' => 'Server error: ' . $e->getMessage()], 500);
}
?>
