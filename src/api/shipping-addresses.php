<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::connect();
    
    $sql = "SELECT * FROM finance_customershippingaddress ORDER BY label ASC";
    $stmt = $db->query($sql);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $addresses, 'timestamp' => date('Y-m-d H:i:s')]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'timestamp' => date('Y-m-d H:i:s')]);
}
?>
