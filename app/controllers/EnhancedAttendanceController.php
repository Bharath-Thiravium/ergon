<?php
require_once __DIR__ . '/../core/Controller.php';

class EnhancedAttendanceController extends Controller {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = Database::connect();
        $this->ensureAttendanceTable();
    }
    
    private function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
    }
    
    public function index() {
        $this->requireAuth();
        
        $role = $_SESSION['role'] ?? 'user';
        $userId = $_SESSION['user_id'];
        
        try {
            // Get attendance data
            if ($role === 'user') {
                $stmt = $this->db->prepare("
                    SELECT a.*, u.name as user_name, s.name as shift_name 
                    FROM attendance a 
                    LEFT JOIN users u ON a.user_id = u.id 
                    LEFT JOIN shifts s ON a.shift_id = s.id 
                    WHERE a.user_id = ? 
                    ORDER BY a.check_in DESC LIMIT 30
                ");
                $stmt->execute([$userId]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT a.*, u.name as user_name, s.name as shift_name 
                    FROM attendance a 
                    LEFT JOIN users u ON a.user_id = u.id 
                    LEFT JOIN shifts s ON a.shift_id = s.id 
                    ORDER BY a.check_in DESC LIMIT 100
                ");
                $stmt->execute();
            }
            $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get today's stats
            $stats = $this->getTodayStats();
            
            $data = [
                'attendance' => $attendance,
                'stats' => $stats,
                'user_role' => $role,
                'active_page' => 'attendance'
            ];
            
        } catch (Exception $e) {
            error_log('Attendance index error: ' . $e->getMessage());
            $data = [
                'attendance' => [],
                'stats' => ['present' => 0, 'absent' => 0, 'late' => 0],
                'user_role' => $role,
                'active_page' => 'attendance'
            ];
        }
        
        $this->view('attendance/index', $data);
    }
    
    public function clock() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                $type = $_POST['type'] ?? '';
                $latitude = floatval($_POST['latitude'] ?? 0);
                $longitude = floatval($_POST['longitude'] ?? 0);
                $userId = $_SESSION['user_id'];
                
                // Get attendance rules
                $rules = $this->getAttendanceRules();
                
                // GPS Validation - Always required
                if (!$latitude || !$longitude) {
                    echo json_encode([
                        'success' => false, 
                        'error' => 'Location is required for attendance. Please enable GPS.'
                    ]);
                    exit;
                }
                
                if ($rules['is_gps_required']) {
                    $distance = $this->calculateDistance(
                        $latitude, $longitude,
                        $rules['office_latitude'], $rules['office_longitude']
                    );
                    
                    if ($distance > $rules['office_radius_meters']) {
                        echo json_encode([
                            'success' => false, 
                            'error' => "Please move within the allowed area to continue. You are {$distance}m away from office."
                        ]);
                        exit;
                    }
                }
                
                if ($type === 'in') {
                    echo json_encode($this->clockIn($userId, $latitude, $longitude));
                } elseif ($type === 'out') {
                    echo json_encode($this->clockOut($userId));
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid action']);
                }
                
            } catch (Exception $e) {
                error_log('Attendance clock error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Server error occurred']);
            }
            exit;
        }
        
        // GET request - show clock page
        try {
            $todayAttendance = $this->getTodayAttendance($_SESSION['user_id']);
            $rules = $this->getAttendanceRules();
            
            $data = [
                'today_attendance' => $todayAttendance,
                'rules' => $rules,
                'active_page' => 'attendance'
            ];
        } catch (Exception $e) {
            error_log('Clock page error: ' . $e->getMessage());
            $data = ['today_attendance' => null, 'rules' => [], 'active_page' => 'attendance'];
        }
        
        $this->view('attendance/clock', $data);
    }
    
    // API Endpoints
    public function apiClockIn() {
        header('Content-Type: application/json');
        $this->requireAuth();
        
        $latitude = floatval($_POST['latitude'] ?? 0);
        $longitude = floatval($_POST['longitude'] ?? 0);
        
        echo json_encode($this->clockIn($_SESSION['user_id'], $latitude, $longitude));
    }
    
    public function apiClockOut() {
        header('Content-Type: application/json');
        $this->requireAuth();
        
        echo json_encode($this->clockOut($_SESSION['user_id']));
    }
    
    public function apiReport() {
        header('Content-Type: application/json');
        $this->requireAuth();
        
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $userId = $_GET['user_id'] ?? $_SESSION['user_id'];
        
        // Check permissions
        if ($userId != $_SESSION['user_id'] && !in_array($_SESSION['role'], ['admin', 'owner'])) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.name as user_name, s.name as shift_name
                FROM attendance a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN shifts s ON a.shift_id = s.id
                WHERE a.user_id = ? AND DATE(a.check_in) BETWEEN ? AND ?
                ORDER BY a.check_in DESC
            ");
            $stmt->execute([$userId, $startDate, $endDate]);
            $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $attendance]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    public function correction() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                $data = [
                    'user_id' => $_SESSION['user_id'],
                    'correction_date' => $_POST['correction_date'],
                    'requested_check_in' => $_POST['requested_check_in'] ?? null,
                    'requested_check_out' => $_POST['requested_check_out'] ?? null,
                    'reason' => $_POST['reason']
                ];
                
                $stmt = $this->db->prepare("
                    INSERT INTO attendance_corrections 
                    (user_id, correction_date, requested_check_in, requested_check_out, reason, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
                ");
                
                $result = $stmt->execute([
                    $data['user_id'], $data['correction_date'], 
                    $data['requested_check_in'], $data['requested_check_out'], 
                    $data['reason']
                ]);
                
                echo json_encode([
                    'success' => $result,
                    'message' => $result ? 'Correction request submitted' : 'Failed to submit request'
                ]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
        
        // Show correction form
        $this->view('attendance/correction', ['active_page' => 'attendance']);
    }
    
    private function clockIn($userId, $latitude, $longitude) {
        // Check if already clocked in today
        $stmt = $this->db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE() AND check_out IS NULL");
        $stmt->execute([$userId]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Already clocked in today'];
        }
        
        // Get user's shift
        $shift = $this->getUserShift($userId);
        $status = $this->determineStatus($shift);
        
        // Insert attendance record
        $stmt = $this->db->prepare("
            INSERT INTO attendance (user_id, shift_id, check_in, latitude, longitude, 
                                  location_name, ip_address, device_info, status, created_at) 
            VALUES (?, ?, NOW(), ?, ?, 'Office', ?, ?, ?, NOW())
        ");
        
        $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $result = $stmt->execute([
            $userId, $shift['id'] ?? null, $latitude, $longitude, 
            $ipAddress, $deviceInfo, $status
        ]);
        
        if ($result) {
            return [
                'success' => true, 
                'message' => 'Clocked in successfully',
                'status' => $status,
                'time' => date('H:i:s')
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to clock in'];
    }
    
    private function clockOut($userId) {
        // Find today's clock in record
        $stmt = $this->db->prepare("
            SELECT id, check_in FROM attendance 
            WHERE user_id = ? AND DATE(check_in) = CURDATE() AND check_out IS NULL
        ");
        $stmt->execute([$userId]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$attendance) {
            return ['success' => false, 'error' => 'No clock in record found for today'];
        }
        
        // Calculate total hours
        $checkIn = new DateTime($attendance['check_in']);
        $checkOut = new DateTime();
        $totalHours = $checkOut->diff($checkIn)->h + ($checkOut->diff($checkIn)->i / 60);
        
        // Update attendance record
        $stmt = $this->db->prepare("
            UPDATE attendance 
            SET check_out = NOW(), total_hours = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        
        $result = $stmt->execute([round($totalHours, 2), $attendance['id']]);
        
        if ($result) {
            return [
                'success' => true, 
                'message' => 'Clocked out successfully',
                'total_hours' => round($totalHours, 2),
                'time' => date('H:i:s')
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to clock out'];
    }
    
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return round($earthRadius * $c);
    }
    
    private function getAttendanceRules() {
        $stmt = $this->db->query("SELECT * FROM attendance_rules LIMIT 1");
        $rules = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rules) {
            return [
                'office_latitude' => 0,
                'office_longitude' => 0,
                'office_radius_meters' => 200,
                'is_gps_required' => 1
            ];
        }
        
        return $rules;
    }
    
    private function getUserShift($userId) {
        $stmt = $this->db->prepare("SELECT s.* FROM shifts s JOIN users u ON u.shift_id = s.id WHERE u.id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['id' => 1, 'start_time' => '09:00:00'];
    }
    
    private function determineStatus($shift) {
        $currentTime = date('H:i:s');
        $shiftStart = $shift['start_time'];
        $graceMinutes = $shift['grace_period'] ?? 15;
        
        $shiftStartWithGrace = date('H:i:s', strtotime($shiftStart . ' +' . $graceMinutes . ' minutes'));
        
        return $currentTime > $shiftStartWithGrace ? 'late' : 'present';
    }
    
    private function getTodayAttendance($userId) {
        $stmt = $this->db->prepare("
            SELECT a.*, s.name as shift_name 
            FROM attendance a 
            LEFT JOIN shifts s ON a.shift_id = s.id 
            WHERE a.user_id = ? AND DATE(a.check_in) = CURDATE()
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getTodayStats() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN check_out IS NULL THEN 1 ELSE 0 END) as active
            FROM attendance 
            WHERE DATE(check_in) = CURDATE()
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'present' => 0, 'late' => 0, 'active' => 0];
    }
    
    private function ensureAttendanceTable() {
        // Tables are created via schema file
    }
}
?>