<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    require_once __DIR__ . '/../config/database.php';
    $db = Database::connect();
    
    // Get the owner's selected prefix from settings or default to BKGE
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'owner_company_prefix' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $prefix = $result ? $result['setting_value'] : 'BKGE';
    
    echo json_encode(['success' => true, 'prefix' => $prefix]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'prefix' => 'BKGE']);
}
?>
