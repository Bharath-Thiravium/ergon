<?php
require_once __DIR__ . '/../core/Controller.php';

class SimpleAttendanceController extends Controller {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = Database::connect();
    }
    
    public function index() {
        $this->requireAuth();
        
        $role = $_SESSION['role'] ?? 'user';
        $userId = $_SESSION['user_id'];
        $selectedDate = $_GET['date'] ?? date('Y-m-d');
        
        // Simple query to get attendance data
        $stmt = $this->db->prepare("
            SELECT 
                u.id as user_id,
                u.name,
                u.email,
                u.role,
                a.id as attendance_id,
                a.check_in,
                a.check_out,
                CASE 
                    WHEN a.check_in IS NOT NULL THEN 'Present'
                    ELSE 'Absent'
                END as status,
                COALESCE(TIME_FORMAT(a.check_in, '%H:%i'), '00:00') as check_in_time,
                COALESCE(TIME_FORMAT(a.check_out, '%H:%i'), '00:00') as check_out_time,
                CASE 
                    WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                        CONCAT(TIMESTAMPDIFF(HOUR, a.check_in, a.check_out), 'h ', 
                               TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) % 60, 'm')
                    ELSE '0h 0m'
                END as working_hours
            FROM users u
            LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
            WHERE u.status != 'removed'
            ORDER BY u.role DESC, u.name
        ");
        $stmt->execute([$selectedDate]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by role for owner view
        if ($role === 'owner') {
            $attendance = ['admin' => [], 'user' => []];
            foreach ($records as $record) {
                $userRole = $record['role'] === 'admin' ? 'admin' : 'user';
                $attendance[$userRole][] = $record;
            }
            $isGrouped = true;
        } else {
            $attendance = $records;
            $isGrouped = false;
        }
        
        $this->view('attendance/index', [
            'attendance' => $attendance,
            'stats' => ['total_hours' => 0, 'total_minutes' => 0, 'present_days' => 0],
            'current_filter' => 'today',
            'selected_date' => $selectedDate,
            'user_role' => $role,
            'active_page' => 'attendance',
            'is_grouped' => $isGrouped
        ]);
    }
}
?>