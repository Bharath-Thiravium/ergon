<?php
// Prevent any output before JSON
ob_start();

require_once __DIR__ . '/../app/config/database.php';

// Clear any previous output
ob_clean();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = Database::connect();
    
    // Get active users excluding owners and company owners
    $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE status = 'active' AND role IN ('admin', 'user') ORDER BY name ASC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'users' => $users]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
