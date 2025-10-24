<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../helpers/Security.php';

class ApiController {
    private $userModel;
    private $attendanceModel;
    private $taskModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->attendanceModel = new Attendance();
        $this->taskModel = new Task();
    }
    
    /**
     * API Login - Returns JWT token
     */
    public function login() {
        $this->apiLogin();
    }
    
    public function attendance() {
        $this->apiAttendance();
    }
    
    public function tasks() {
        $this->apiTasks();
    }
    
    public function updateTask() {
        $this->apiTaskUpdate();
    }
    
    public function apiLogin() {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password required']);
            return;
        }
        
        $user = $this->userModel->authenticate($email, $password);
        
        if ($user) {
            $token = Security::generateJWT($user['id'], $user['role']);
            
            echo json_encode([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    }
    
    /**
     * API Attendance - Clock in/out via mobile
     */
    public function apiAttendance() {
        header('Content-Type: application/json');
        
        // Verify JWT token
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing authorization token']);
            return;
        }
        
        $token = $matches[1];
        $payload = Security::verifyJWT($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $latitude = $input['latitude'] ?? 0;
        $longitude = $input['longitude'] ?? 0;
        
        try {
            if ($action === 'checkin') {
                $result = $this->attendanceModel->checkIn(
                    $payload->user_id,
                    $latitude,
                    $longitude,
                    "Mobile Location: {$latitude}, {$longitude}"
                );
            } elseif ($action === 'checkout') {
                $result = $this->attendanceModel->checkOut($payload->user_id);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
                return;
            }
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Attendance recorded']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to record attendance']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
        }
    }
    
    /**
     * API Tasks - Get user tasks
     */
    public function apiTasks() {
        header('Content-Type: application/json');
        
        // Verify JWT token
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing authorization token']);
            return;
        }
        
        $token = $matches[1];
        $payload = Security::verifyJWT($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            return;
        }
        
        try {
            $tasks = $this->taskModel->getUserTasks($payload->user_id);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
        }
    }
    
    /**
     * API Task Update - Update task progress
     */
    public function apiTaskUpdate() {
        header('Content-Type: application/json');
        
        // Verify JWT token
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing authorization token']);
            return;
        }
        
        $token = $matches[1];
        $payload = Security::verifyJWT($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $taskId = $input['task_id'] ?? 0;
        $progress = $input['progress'] ?? 0;
        $comment = $input['comment'] ?? '';
        
        try {
            $result = $this->taskModel->updateProgress($taskId, $payload->user_id, $progress, $comment);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Task updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update task']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
        }
    }
    
    public function generateEmployeeId() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            // Get company name from settings
            $stmt = $conn->prepare("SELECT company_name FROM settings LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch();
            $companyName = $settings['company_name'] ?? 'ERGON';
            
            // Generate prefix
            $prefix = $this->getCompanyPrefix($companyName);
            
            // Get next employee number
            $stmt = $conn->prepare("SELECT COUNT(*) + 1 as next_num FROM users WHERE employee_id IS NOT NULL");
            $stmt->execute();
            $result = $stmt->fetch();
            $nextNum = str_pad($result['next_num'], 3, '0', STR_PAD_LEFT);
            
            $employeeId = $prefix . $nextNum;
            
            echo json_encode([
                'success' => true,
                'employee_id' => $employeeId
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to generate Employee ID: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    private function getCompanyPrefix($companyName) {
        $words = explode(' ', strtoupper($companyName));
        $prefix = '';
        
        foreach ($words as $word) {
            if (in_array($word, ['THE', 'AND', 'OF', 'FOR', 'TO', 'IN', 'ON', 'AT', 'BY'])) {
                continue;
            }
            
            $cleanWord = preg_replace('/[^A-Z0-9]/', '', $word);
            
            if (strlen($cleanWord) >= 2) {
                $prefix .= substr($cleanWord, 0, 2);
            } elseif (strlen($cleanWord) == 1) {
                $prefix .= $cleanWord;
            }
        }
        
        if (empty($prefix)) {
            $prefix = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($companyName)), 0, 2);
        }
        
        return $prefix ?: 'EMP';
    }
    
    public function updatePreference() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $key = $input['key'] ?? '';
        $value = $input['value'] ?? '';
        
        if (empty($key)) {
            echo json_encode(['success' => false, 'error' => 'Invalid key']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../models/UserPreference.php';
            $preferenceModel = new UserPreference();
            $result = $preferenceModel->updatePreference($_SESSION['user_id'], $key, $value);
            
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Server error']);
        }
        exit;
    }
    
    public function registerDevice() {
        header('Content-Type: application/json');
        
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing authorization token']);
            return;
        }
        
        $token = $matches[1];
        $payload = Security::verifyJWT($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $fcmToken = $input['fcm_token'] ?? '';
        $deviceType = $input['device_type'] ?? 'android';
        $deviceInfo = $input['device_info'] ?? '';
        
        if (empty($fcmToken)) {
            echo json_encode(['success' => false, 'error' => 'FCM token required']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            $stmt = $conn->prepare(
                "INSERT INTO user_devices (user_id, fcm_token, device_type, device_info) 
                 VALUES (?, ?, ?, ?) 
                 ON DUPLICATE KEY UPDATE device_info = VALUES(device_info), last_active = CURRENT_TIMESTAMP"
            );
            $stmt->execute([$payload->user_id, $fcmToken, $deviceType, $deviceInfo]);
            
            echo json_encode(['success' => true, 'message' => 'Device registered']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Registration failed']);
        }
    }
    
    public function syncOfflineData() {
        header('Content-Type: application/json');
        
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing authorization token']);
            return;
        }
        
        $token = $matches[1];
        $payload = Security::verifyJWT($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $queueData = $input['queue_data'] ?? [];
        
        $results = [];
        
        foreach ($queueData as $item) {
            $result = $this->processSyncItem($payload->user_id, $item);
            $results[] = $result;
        }
        
        echo json_encode(['success' => true, 'results' => $results]);
    }
    
    private function processSyncItem($userId, $item) {
        $type = $item['type'] ?? '';
        $data = $item['data'] ?? [];
        $clientUuid = $item['client_uuid'] ?? '';
        
        try {
            require_once __DIR__ . '/../../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            // Check if already synced
            $stmt = $conn->prepare("SELECT id FROM sync_queue WHERE client_uuid = ? AND synced = 1");
            $stmt->execute([$clientUuid]);
            if ($stmt->fetch()) {
                return ['client_uuid' => $clientUuid, 'status' => 'already_synced'];
            }
            
            switch ($type) {
                case 'attendance':
                    $result = $this->syncAttendance($userId, $data, $clientUuid);
                    break;
                case 'task_update':
                    $result = $this->syncTaskUpdate($userId, $data, $clientUuid);
                    break;
                default:
                    $result = ['status' => 'unknown_type'];
            }
            
            // Mark as synced
            $stmt = $conn->prepare(
                "INSERT INTO sync_queue (user_id, action_type, data, client_uuid, synced, synced_at) 
                 VALUES (?, ?, ?, ?, 1, NOW()) 
                 ON DUPLICATE KEY UPDATE synced = 1, synced_at = NOW()"
            );
            $stmt->execute([$userId, $type, json_encode($data), $clientUuid]);
            
            return array_merge(['client_uuid' => $clientUuid], $result);
            
        } catch (Exception $e) {
            return ['client_uuid' => $clientUuid, 'status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    private function syncAttendance($userId, $data, $clientUuid) {
        $latitude = $data['latitude'] ?? 0;
        $longitude = $data['longitude'] ?? 0;
        $action = $data['action'] ?? '';
        
        // Validate geofence
        $distance = $this->calculateDistance($latitude, $longitude);
        $isValid = $distance <= 200; // 200m radius
        
        if ($action === 'checkin') {
            $result = $this->attendanceModel->checkIn(
                $userId, $latitude, $longitude, 
                "Mobile: {$latitude}, {$longitude}", $clientUuid, $distance, $isValid
            );
        } elseif ($action === 'checkout') {
            $result = $this->attendanceModel->checkOut($userId, $clientUuid);
        } else {
            return ['status' => 'invalid_action'];
        }
        
        return ['status' => $result ? 'success' : 'failed'];
    }
    
    private function syncTaskUpdate($userId, $data, $clientUuid) {
        $taskId = $data['task_id'] ?? 0;
        $progress = $data['progress'] ?? 0;
        $comment = $data['comment'] ?? '';
        
        $result = $this->taskModel->updateProgress($taskId, $userId, $progress, $comment);
        return ['status' => $result ? 'success' : 'failed'];
    }
    
    private function calculateDistance($lat, $lng) {
        // Get office location from settings
        require_once __DIR__ . '/../../config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->query("SELECT latitude, longitude FROM geofence_locations WHERE is_active = 1 LIMIT 1");
        $office = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$office) return 999999; // No office location set
        
        return $this->haversineDistance($lat, $lng, $office['latitude'], $office['longitude']);
    }
    
    private function haversineDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }
    
    public function activityLog() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $activityType = $input['activity_type'] ?? '';
        $description = $input['description'] ?? '';
        $isActive = $input['is_active'] ?? true;
        
        try {
            require_once __DIR__ . '/../../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            $stmt = $conn->prepare(
                "INSERT INTO activity_logs (user_id, activity_type, description, is_active, created_at) 
                 VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$_SESSION['user_id'], $activityType, $description, $isActive ? 1 : 0]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Failed to log activity']);
        }
        
        exit;
    }
    
    public function sessionFromJWT() {
        header('Content-Type: application/json');
        
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Missing authorization token']);
            exit;
        }
        
        $token = $matches[1];
        $payload = Security::verifyJWT($token);
        
        if (!$payload) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            exit;
        }
        
        // Start session and set user data
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        try {
            $user = $this->userModel->getById($payload->user_id);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_department'] = $user['department'];
                
                echo json_encode(['success' => true, 'message' => 'Session established']);
            } else {
                echo json_encode(['success' => false, 'error' => 'User not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        
        exit;
    }
    
    public function test() {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        echo json_encode([
            'success' => true,
            'message' => 'Test API endpoint working',
            'data' => $input
        ]);
        exit;
    }
}
?>