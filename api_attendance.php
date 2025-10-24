<?php
/**
 * API Attendance Endpoint
 * Handles clock in/out requests
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $userId = $_SESSION['user_id'];
    
    if ($action === 'clock_in') {
        // Check if already clocked in today
        $stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE() AND check_out IS NULL");
        $stmt->execute([$userId]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Already clocked in today']);
            exit;
        }
        
        // Clock in
        $stmt = $conn->prepare("INSERT INTO attendance (user_id, check_in, location_name, latitude, longitude) VALUES (?, NOW(), ?, ?, ?)");
        $stmt->execute([$userId, $input['location_name'] ?? 'Office', $input['latitude'] ?? 0, $input['longitude'] ?? 0]);
        
        echo json_encode(['success' => true, 'message' => 'Clocked in successfully']);
        
    } elseif ($action === 'clock_out') {
        // Find today's clock in record
        $stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE() AND check_out IS NULL");
        $stmt->execute([$userId]);
        $record = $stmt->fetch();
        
        if (!$record) {
            echo json_encode(['success' => false, 'message' => 'No active clock in found']);
            exit;
        }
        
        // Clock out
        $stmt = $conn->prepare("UPDATE attendance SET check_out = NOW() WHERE id = ?");
        $stmt->execute([$record['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Clocked out successfully']);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>