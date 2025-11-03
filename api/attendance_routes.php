<?php
/**
 * Attendance API Routes
 * RESTful endpoints for attendance management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../app/controllers/EnhancedAttendanceController.php';

$controller = new EnhancedAttendanceController();
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));

// Route handling
switch ($method) {
    case 'POST':
        switch (end($segments)) {
            case 'clockin':
                $controller->apiClockIn();
                break;
            case 'clockout':
                $controller->apiClockOut();
                break;
            case 'correction':
                $controller->correction();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'GET':
        switch (end($segments)) {
            case 'report':
                $controller->apiReport();
                break;
            case 'status':
                // Get current attendance status
                session_start();
                if (!isset($_SESSION['user_id'])) {
                    echo json_encode(['error' => 'Unauthorized']);
                    exit;
                }
                
                require_once __DIR__ . '/../app/config/database.php';
                $db = Database::connect();
                
                $stmt = $db->prepare("
                    SELECT a.*, s.name as shift_name 
                    FROM attendance a 
                    LEFT JOIN shifts s ON a.shift_id = s.id 
                    WHERE a.user_id = ? AND DATE(a.check_in) = CURDATE()
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $attendance,
                    'is_clocked_in' => $attendance && !$attendance['check_out']
                ]);
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>