<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';

class UserController extends Controller {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function dashboard() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['user', 'admin', 'owner'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        $user_id = $_SESSION['user_id'];
        $stats = $this->getUserStats($user_id);
        $tasks = $this->getUserTasks($user_id);
        $attendance_status = $this->getTodayAttendanceStatus($user_id);
        
        $data = [
            'stats' => $stats,
            'tasks' => $tasks,
            'attendance_status' => $attendance_status,
            'user_name' => $_SESSION['user_name'],
            'role' => $_SESSION['role'],
            'active_page' => 'dashboard'
        ];
        
        $this->view('dashboard/user', $data);
    }
    
    public function requests() {
        AuthMiddleware::requireAuth();
        
        $user_id = $_SESSION['user_id'];
        $stats = $this->getUserStats($user_id);
        $leaves = $this->getUserLeaves($user_id);
        $expenses = $this->getUserExpenses($user_id);
        
        $data = [
            'stats' => $stats,
            'leaves' => $leaves,
            'expenses' => $expenses,
            'active_page' => 'requests'
        ];
        
        $this->view('user/requests', $data);
    }
    
    public function tasks() {
        AuthMiddleware::requireAuth();
        
        $user_id = $_SESSION['user_id'];
        $tasks = $this->getAllUserTasks($user_id);
        
        $data = [
            'tasks' => $tasks,
            'active_page' => 'tasks'
        ];
        
        $this->view('tasks/index', $data);
    }
    
    public function attendance() {
        AuthMiddleware::requireAuth();
        
        $user_id = $_SESSION['user_id'];
        $attendance = $this->getUserAttendance($user_id);
        
        $data = [
            'attendance' => $attendance,
            'active_page' => 'attendance'
        ];
        
        $this->view('attendance/index', $data);
    }
    
    private function getUserStats($user_id) {
        try {
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status != 'completed') as active_tasks,
                        (SELECT COUNT(*) FROM leaves WHERE user_id = ? AND status = 'pending') as pending_leaves,
                        (SELECT COUNT(*) FROM expenses WHERE user_id = ? AND status = 'pending') as pending_expenses";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $user_id, $user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getUserStats error: ' . $e->getMessage());
            return ['active_tasks' => 0, 'pending_leaves' => 0, 'pending_expenses' => 0];
        }
    }
    
    private function getUserTasks($user_id) {
        try {
            $sql = "SELECT * FROM tasks WHERE assigned_to = ? AND status != 'completed' ORDER BY deadline ASC LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getUserTasks error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getAllUserTasks($user_id) {
        try {
            $sql = "SELECT * FROM tasks WHERE assigned_to = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getAllUserTasks error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getUserLeaves($user_id) {
        try {
            $sql = "SELECT * FROM leaves WHERE user_id = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getUserLeaves error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getUserExpenses($user_id) {
        try {
            $sql = "SELECT * FROM expenses WHERE user_id = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getUserExpenses error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getUserAttendance($user_id) {
        try {
            $sql = "SELECT * FROM attendance WHERE user_id = ? ORDER BY date DESC LIMIT 30";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getUserAttendance error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getTodayAttendanceStatus($user_id) {
        try {
            $sql = "SELECT clock_out FROM attendance WHERE user_id = ? AND DATE(date) = CURDATE() ORDER BY date DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? ($result['clock_out'] === null ? 'Clocked In' : 'Clocked Out') : 'Not Clocked In';
        } catch (Exception $e) {
            error_log('getTodayAttendanceStatus error: ' . $e->getMessage());
            return 'Not Clocked In';
        }
    }
}
?>
