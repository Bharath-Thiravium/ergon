<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    $user_id = $_SESSION['user_id'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle both JSON and form data
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $action = $input['action'] ?? '';
            $latitude = $input['latitude'] ?? 0;
            $longitude = $input['longitude'] ?? 0;
            $location = $input['location_name'] ?? 'Office Location';
        } else {
            $action = $_POST['action'] ?? '';
            $latitude = $_POST['latitude'] ?? 0;
            $longitude = $_POST['longitude'] ?? 0;
            $location = $_POST['location'] ?? 'Office Location';
        }
        
        if ($action === 'clock_in') {
            $stmt = $conn->prepare("INSERT INTO attendance (user_id, check_in, latitude, longitude, location_name) VALUES (?, NOW(), ?, ?, ?)");
            $result = $stmt->execute([$user_id, $latitude, $longitude, $location]);
            echo json_encode(['success' => $result, 'message' => 'Clock in ' . ($result ? 'successful' : 'failed')]);
            
        } elseif ($action === 'clock_out') {
            $stmt = $conn->prepare("UPDATE attendance SET check_out = NOW() WHERE user_id = ? AND DATE(check_in) = CURDATE() AND check_out IS NULL");
            $stmt->execute([$user_id]);
            $affected = $stmt->rowCount();
            
            if ($affected > 0) {
                echo json_encode(['success' => true, 'message' => 'Clocked out successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No active clock-in found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>