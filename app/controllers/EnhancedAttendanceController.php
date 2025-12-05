<?php
require_once __DIR__ . '/../core/Controller.php';

class EnhancedAttendanceController extends Controller {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = Database::connect();
        $this->ensureAttendanceTable();
    }
    
    protected function requireAuth() {
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
                    SELECT a.*, u.name, u.status as user_status, u.role, s.name as shift_name,
                           COALESCE(CONCAT(FLOOR(a.total_hours), 'h ', FLOOR((a.total_hours - FLOOR(a.total_hours)) * 60), 'm'), '0h 0m') as working_hours,
                           CASE WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 'Present' ELSE 'Absent' END as status,
                           a.id as attendance_id, DATE(a.check_in) as date
                    FROM attendance a 
                    LEFT JOIN users u ON a.user_id = u.id 
                    LEFT JOIN shifts s ON a.shift_id = s.id 
                    WHERE a.user_id = ? 
                    ORDER BY a.check_in DESC LIMIT 30
                ");
                $stmt->execute([$userId]);
                $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->db->prepare("
                    SELECT a.*, u.name, u.status as user_status, u.role, s.name as shift_name,
                           COALESCE(CONCAT(FLOOR(a.total_hours), 'h ', FLOOR((a.total_hours - FLOOR(a.total_hours)) * 60), 'm'), '0h 0m') as working_hours,
                           CASE WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 'Present' ELSE 'Absent' END as status,
                           a.id as attendance_id, DATE(a.check_in) as date
                    FROM attendance a 
                    LEFT JOIN users u ON a.user_id = u.id 
                    LEFT JOIN shifts s ON a.shift_id = s.id 
                    ORDER BY a.check_in DESC LIMIT 100
                ");
                $stmt->execute();
                $allAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Group by role for admin/owner view
                $attendance = [
                    'admin' => array_filter($allAttendance, fn($record) => $record['role'] === 'admin'),
                    'user' => array_filter($allAttendance, fn($record) => $record['role'] === 'user')
                ];
            }
            
            // Get today's stats
            $stats = $this->getTodayStats();
            
            $data = [
                'attendance' => $attendance,
                'stats' => $stats,
                'user_role' => $role,
                'active_page' => 'attendance',
                'is_grouped' => ($role !== 'user')
            ];
            
        } catch (Exception $e) {
            error_log('Attendance index error: ' . $e->getMessage());
            $data = [
                'attendance' => ($role === 'user') ? [] : ['admin' => [], 'user' => []],
                'stats' => ['present_days' => 0, 'total_hours' => 0, 'total_minutes' => 0, 'late' => 0],
                'user_role' => $role,
                'active_page' => 'attendance',
                'is_grouped' => ($role !== 'user')
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
    
    public function report() {
        $this->requireAuth();
        
        $userRole = $_SESSION['role'] ?? 'user';
        
        if (!in_array($userRole, ['owner', 'admin'])) {
            error_log('Access denied for role: ' . $userRole);
            header('Location: /ergon/attendance?error=access_denied');
            exit;
        }
        
        $userId = $_GET['user_id'] ?? null;
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        error_log('Report params - userId: ' . ($userId ?? 'null') . ', startDate: ' . $startDate . ', endDate: ' . $endDate);
        
        if (!$userId) {
            error_log('Attendance report error: Missing user_id parameter');
            header('Location: /ergon/attendance?error=missing_user_id');
            exit;
        }
        
        try {
            // Get user info
            $userStmt = $this->db->prepare("SELECT name, email FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            error_log('User found: ' . ($user ? $user['name'] : 'none'));
            
            if (!$user) {
                error_log('User not found for ID: ' . $userId);
                header('Location: /ergon/attendance?error=user_not_found');
                exit;
            }
            
            // Get attendance records
            $stmt = $this->db->prepare("
                SELECT DATE(check_in) as date, check_in, check_out, 
                       CASE WHEN check_out IS NOT NULL THEN 
                           TIMESTAMPDIFF(MINUTE, check_in, check_out) / 60.0 
                       ELSE 0 END as total_hours,
                       CASE WHEN check_in IS NOT NULL AND check_out IS NOT NULL THEN 'Present' ELSE 'Absent' END as status
                FROM attendance 
                WHERE user_id = ? AND DATE(check_in) BETWEEN ? AND ?
                ORDER BY check_in DESC
            ");
            $stmt->execute([$userId, $startDate, $endDate]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="attendance_report_' . $user['name'] . '_' . $startDate . '_to_' . $endDate . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Date', 'Check In', 'Check Out', 'Total Hours', 'Status']);
            
            foreach ($records as $record) {
                fputcsv($output, [
                    $record['date'],
                    $record['check_in'] ? date('H:i:s', strtotime($record['check_in'])) : 'Not clocked in',
                    $record['check_out'] ? date('H:i:s', strtotime($record['check_out'])) : 'Not clocked out',
                    $record['total_hours'] ? round($record['total_hours'], 2) . 'h' : '0h',
                    $record['status']
                ]);
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            error_log('Attendance report error: ' . $e->getMessage());
            error_log('Report parameters: user_id=' . ($userId ?? 'null') . ', start_date=' . $startDate . ', end_date=' . $endDate);
            header('Location: /ergon/attendance?error=report_failed&details=' . urlencode($e->getMessage()));
            exit;
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
                SUM(CASE WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN a.check_out IS NULL THEN 1 ELSE 0 END) as active,
                COALESCE(SUM(a.total_hours), 0) as total_hours,
                COALESCE(SUM(a.total_hours) * 60, 0) as total_minutes
            FROM attendance a
            WHERE DATE(a.check_in) = CURDATE()
        ");
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'present_days' => 0, 'late' => 0, 'active' => 0, 'total_hours' => 0, 'total_minutes' => 0];
        $stats['total_minutes'] = $stats['total_minutes'] % 60;
        return $stats;
    }
    
    private function ensureAttendanceTable() {
        // Tables are created via schema file
    }
}
?>