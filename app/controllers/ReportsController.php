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
        try {
            $this->userModel = new User();
            $this->attendanceModel = new Attendance();
            $this->taskModel = new Task();
        } catch (Exception $e) {
            error_log('ReportsController init error: ' . $e->getMessage());
            // Initialize with null but create fallback methods
            $this->userModel = null;
            $this->attendanceModel = null;
            $this->taskModel = null;
        }
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
        
        try {
            $this->ensureTablesExist();
            
            $data = [
                'attendance_summary' => $this->getAttendanceSummary(),
                'task_summary' => $this->getTaskSummary(),
                'user_performance' => $this->getUserPerformance()
            ];
            
            $csv = "ERGON Reports Export - " . date('Y-m-d H:i:s') . "\n\n";
            
            $csv .= "ATTENDANCE SUMMARY\n";
            $csv .= "Present Today," . ($data['attendance_summary']['total_present'] ?? 0) . "\n";
            $csv .= "Absent Today," . ($data['attendance_summary']['total_absent'] ?? 0) . "\n";
            $csv .= "Average Hours," . ($data['attendance_summary']['average_hours'] ?? 0) . "\n\n";
            
            $csv .= "TASK SUMMARY\n";
            $csv .= "Completed Tasks," . ($data['task_summary']['completed_tasks'] ?? 0) . "\n";
            $csv .= "Pending Tasks," . ($data['task_summary']['pending_tasks'] ?? 0) . "\n";
            $csv .= "Overdue Tasks," . ($data['task_summary']['overdue_tasks'] ?? 0) . "\n\n";
            
            $csv .= "USER PERFORMANCE\n";
            $csv .= "Employee,Tasks Completed,Attendance Rate\n";
            foreach ($data['user_performance'] as $user) {
                $csv .= ($user['name'] ?? 'N/A') . "," . ($user['tasks_completed'] ?? 0) . "," . ($user['attendance_rate'] ?? 0) . "%\n";
            }
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="ergon_report_' . date('Y-m-d') . '.csv"');
            echo $csv;
        } catch (Exception $e) {
            error_log('Export error: ' . $e->getMessage());
            header('Location: /ergon/reports?error=Export failed');
        }
        exit;
    }
    
    public function approvalsExport() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTablesExist();
            
            $csv = "ERGON Approvals Report - " . date('Y-m-d H:i:s') . "\n\n";
            
            // Leave Requests
            try {
                $stmt = $db->query("SELECT l.*, u.name as user_name FROM leaves l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 100");
                $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $leaves = [];
            }
            
            $csv .= "LEAVE REQUESTS\n";
            $csv .= "Employee,Type,Start Date,End Date,Days,Status,Created Date\n";
            foreach ($leaves as $leave) {
                $csv .= ($leave['user_name'] ?? 'N/A') . "," . ($leave['leave_type'] ?? 'N/A') . "," . ($leave['start_date'] ?? 'N/A') . "," . ($leave['end_date'] ?? 'N/A') . "," . ($leave['days_requested'] ?? 0) . "," . ($leave['status'] ?? 'N/A') . "," . ($leave['created_at'] ?? 'N/A') . "\n";
            }
            
            // Expense Claims
            try {
                $stmt = $db->query("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id ORDER BY e.created_at DESC LIMIT 100");
                $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $expenses = [];
            }
            
            $csv .= "\nEXPENSE CLAIMS\n";
            $csv .= "Employee,Category,Amount,Description,Status,Created Date\n";
            foreach ($expenses as $expense) {
                $csv .= ($expense['user_name'] ?? 'N/A') . "," . ($expense['category'] ?? 'N/A') . "," . ($expense['amount'] ?? 0) . "," . str_replace(',', ';', $expense['description'] ?? '') . "," . ($expense['status'] ?? 'N/A') . "," . ($expense['created_at'] ?? 'N/A') . "\n";
            }
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="ergon_approvals_' . date('Y-m-d') . '.csv"');
            echo $csv;
        } catch (Exception $e) {
            error_log('Approvals export error: ' . $e->getMessage());
            header('Location: /ergon/reports?error=Approvals export failed');
        }
        exit;
    }
    
    private function getAttendanceSummary() {
        try {
            require_once __DIR__ . '/../config/database.php';
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
            if ($this->taskModel) {
                $stats = $this->taskModel->getTaskStats();
            } else {
                // Fallback direct database query
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                $sql = "SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as pending_tasks
                  FROM tasks";
                $stmt = $db->query($sql);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return [
                'completed_tasks' => $stats['completed_tasks'] ?? 0,
                'pending_tasks' => ($stats['pending_tasks'] ?? 0) + ($stats['in_progress_tasks'] ?? 0),
                'overdue_tasks' => 0,
                'completion_rate' => ($stats['total_tasks'] ?? 0) > 0 ? 
                    round((($stats['completed_tasks'] ?? 0) / ($stats['total_tasks'] ?? 1)) * 100, 1) : 0
            ];
        } catch (Exception $e) {
            error_log('getTaskSummary error: ' . $e->getMessage());
            return ['completed_tasks' => 0, 'pending_tasks' => 0, 'overdue_tasks' => 0, 'completion_rate' => 0];
        }
    }
    
    private function getUserPerformance() {
        try {
            require_once __DIR__ . '/../config/database.php';
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
            require_once __DIR__ . '/../config/database.php';
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
            require_once __DIR__ . '/../config/database.php';
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
    
    private function ensureTablesExist() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Ensure attendance table exists
            $stmt = $db->query("SHOW TABLES LIKE 'attendance'");
            if ($stmt->rowCount() == 0) {
                $sql = "CREATE TABLE attendance (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    check_in DATETIME NOT NULL,
                    check_out DATETIME NULL,
                    latitude DECIMAL(10,8) NULL,
                    longitude DECIMAL(11,8) NULL,
                    location_name VARCHAR(255) NULL,
                    status VARCHAR(20) DEFAULT 'present',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $db->exec($sql);
            }
            
            // Ensure tasks table exists
            $stmt = $db->query("SHOW TABLES LIKE 'tasks'");
            if ($stmt->rowCount() == 0) {
                $sql = "CREATE TABLE tasks (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    assigned_by INT NOT NULL,
                    assigned_to INT NOT NULL,
                    priority VARCHAR(20) DEFAULT 'medium',
                    status VARCHAR(20) DEFAULT 'assigned',
                    progress INT DEFAULT 0,
                    deadline DATE NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                $db->exec($sql);
            }
            
            // Ensure leaves table exists
            $stmt = $db->query("SHOW TABLES LIKE 'leaves'");
            if ($stmt->rowCount() == 0) {
                $sql = "CREATE TABLE leaves (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    leave_type VARCHAR(50) NOT NULL,
                    start_date DATE NOT NULL,
                    end_date DATE NOT NULL,
                    days_requested INT NOT NULL,
                    reason TEXT,
                    status VARCHAR(20) DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $db->exec($sql);
            }
            
            // Ensure expenses table exists
            $stmt = $db->query("SHOW TABLES LIKE 'expenses'");
            if ($stmt->rowCount() == 0) {
                $sql = "CREATE TABLE expenses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    category VARCHAR(100) NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    description TEXT,
                    status VARCHAR(20) DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $db->exec($sql);
            }
        } catch (Exception $e) {
            error_log('ensureTablesExist error: ' . $e->getMessage());
        }
    }
}
?>
