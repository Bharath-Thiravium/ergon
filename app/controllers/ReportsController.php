<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Task.php';

class ReportsController extends Controller {
    private $userModel;
    private $attendanceModel;
    private $taskModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->attendanceModel = new Attendance();
        $this->taskModel = new Task();
    }
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        $data = [
            'attendance_summary' => $this->getAttendanceSummary(),
            'task_summary' => $this->getTaskSummary(),
            'user_performance' => $this->getUserPerformance(),
            'active_page' => 'reports'
        ];
        
        $this->view('reports/index', $data);
    }
    
    public function activity() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        try {
            $activity = $this->getActivityReport();
            $productivity = $this->getProductivitySummary();
        } catch (Exception $e) {
            error_log('Activity report error: ' . $e->getMessage());
            $activity = [];
            $productivity = [];
        }
        
        $data = [
            'activity' => $activity,
            'productivity' => $productivity,
            'active_page' => 'reports'
        ];
        
        $this->view('reports/activity', $data);
    }
    
    public function export() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        $data = [
            'attendance_summary' => $this->getAttendanceSummary(),
            'task_summary' => $this->getTaskSummary(),
            'user_performance' => $this->getUserPerformance()
        ];
        
        $csv = "ERGON Reports Export - " . date('Y-m-d H:i:s') . "\n\n";
        
        $csv .= "ATTENDANCE SUMMARY\n";
        $csv .= "Present Today," . $data['attendance_summary']['total_present'] . "\n";
        $csv .= "Absent Today," . $data['attendance_summary']['total_absent'] . "\n";
        $csv .= "Average Hours," . $data['attendance_summary']['average_hours'] . "\n\n";
        
        $csv .= "TASK SUMMARY\n";
        $csv .= "Completed Tasks," . $data['task_summary']['completed_tasks'] . "\n";
        $csv .= "Pending Tasks," . $data['task_summary']['pending_tasks'] . "\n";
        $csv .= "Overdue Tasks," . $data['task_summary']['overdue_tasks'] . "\n\n";
        
        $csv .= "USER PERFORMANCE\n";
        $csv .= "Employee,Tasks Completed,Attendance Rate\n";
        foreach ($data['user_performance'] as $user) {
            $csv .= $user['name'] . "," . $user['tasks_completed'] . "," . $user['attendance_rate'] . "%\n";
        }
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="ergon_report_' . date('Y-m-d') . '.csv"');
        echo $csv;
        exit;
    }
    
    private function getAttendanceSummary() {
        try {
            $db = Database::connect();
            $sql = "SELECT 
                        COUNT(DISTINCT user_id) as total_present,
                        (SELECT COUNT(*) FROM users WHERE status = 'active') - COUNT(DISTINCT user_id) as total_absent,
                        AVG(TIMESTAMPDIFF(HOUR, check_in, check_out)) as average_hours
                    FROM attendance 
                    WHERE DATE(check_in) = CURDATE() AND check_out IS NOT NULL";
            $stmt = $db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_present' => $result['total_present'] ?? 0,
                'total_absent' => $result['total_absent'] ?? 0,
                'average_hours' => round($result['average_hours'] ?? 0, 1)
            ];
        } catch (Exception $e) {
            error_log('getAttendanceSummary error: ' . $e->getMessage());
            return ['total_present' => 0, 'total_absent' => 0, 'average_hours' => 0];
        }
    }
    
    private function getTaskSummary() {
        try {
            $stats = $this->taskModel->getTaskStats();
            return [
                'completed_tasks' => $stats['completed_tasks'] ?? 0,
                'pending_tasks' => $stats['pending_tasks'] ?? 0,
                'overdue_tasks' => 0,
                'completion_rate' => $stats['total_tasks'] > 0 ? 
                    round(($stats['completed_tasks'] / $stats['total_tasks']) * 100, 1) : 0
            ];
        } catch (Exception $e) {
            error_log('getTaskSummary error: ' . $e->getMessage());
            return ['completed_tasks' => 0, 'pending_tasks' => 0, 'overdue_tasks' => 0, 'completion_rate' => 0];
        }
    }
    
    private function getUserPerformance() {
        try {
            $db = Database::connect();
            $sql = "SELECT 
                        u.name,
                        COUNT(DISTINCT t.id) as tasks_completed,
                        COUNT(DISTINCT a.id) as attendance_days
                    FROM users u
                    LEFT JOIN tasks t ON u.id = t.assigned_to AND t.status = 'completed'
                    LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    WHERE u.status = 'active' AND u.role = 'user'
                    GROUP BY u.id, u.name
                    ORDER BY tasks_completed DESC
                    LIMIT 10";
            $stmt = $db->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as &$result) {
                $result['attendance_rate'] = round(($result['attendance_days'] / 30) * 100, 0);
            }
            
            return $results;
        } catch (Exception $e) {
            error_log('getUserPerformance error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getActivityReport() {
        try {
            $db = Database::connect();
            $sql = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as activities
                    FROM activity_logs 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date DESC";
            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getActivityReport error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getProductivitySummary() {
        try {
            $db = Database::connect();
            $sql = "SELECT 
                        u.name,
                        COUNT(t.id) as total_tasks,
                        AVG(t.progress) as avg_progress
                    FROM users u
                    LEFT JOIN tasks t ON u.id = t.assigned_to
                    WHERE u.status = 'active' AND u.role = 'user'
                    GROUP BY u.id, u.name
                    HAVING total_tasks > 0
                    ORDER BY avg_progress DESC
                    LIMIT 5";
            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getProductivitySummary error: ' . $e->getMessage());
            return [];
        }
    }
}
?>