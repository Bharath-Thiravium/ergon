<?php
/**
 * User Controller
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Circular.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/SessionManager.php';

class UserController extends Controller {
    private $db;
    
    public function __construct() {
        SessionManager::start();
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function dashboard() {
        AuthMiddleware::requireAuth();
        
        // Ensure user has appropriate role
        if (!in_array($_SESSION['role'], ['user', 'admin', 'owner'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        $user_id = $_SESSION['user_id'];
        $stats = $this->getUserStats($user_id);
        $tasks = $this->getUserTasks($user_id);
        $circulars = $this->getRecentCirculars();
        $attendance_status = $this->getTodayAttendanceStatus($user_id);
        
        $data = [
            'stats' => $stats,
            'tasks' => $tasks,
            'circulars' => $circulars,
            'attendance_status' => $attendance_status
        ];
        
        $this->view('user/dashboard', $data);
    }
    
    private function getUserStats($user_id) {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status != 'completed') as active_tasks,
                    (SELECT COUNT(*) FROM leaves WHERE employee_id = ? AND status = 'Pending') as pending_leaves,
                    (SELECT COUNT(*) FROM expenses WHERE user_id = ? AND status = 'pending') as pending_expenses,
                    (SELECT COUNT(*) FROM advances WHERE user_id = ? AND status = 'pending') as pending_advances";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getUserTasks($user_id) {
        $sql = "SELECT * FROM tasks WHERE assigned_to = ? AND status != 'completed' ORDER BY deadline ASC LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getRecentCirculars() {
        $circular = new Circular();
        return $circular->getRecent($_SESSION['role'] ?? 'user', 3);
    }
    
    public function requests() {
        AuthMiddleware::requireAuth();
        
        $user_id = $_SESSION['user_id'];
        $stats = $this->getUserStats($user_id);
        $leaves = $this->getUserLeaves($user_id);
        $expenses = $this->getUserExpenses($user_id);
        $advances = $this->getUserAdvances($user_id);
        
        $data = [
            'stats' => $stats,
            'leaves' => $leaves,
            'expenses' => $expenses,
            'advances' => $advances
        ];
        
        $this->view('user/requests', $data);
    }
    
    private function getUserLeaves($user_id) {
        $sql = "SELECT * FROM leaves WHERE employee_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUserExpenses($user_id) {
        $sql = "SELECT * FROM expenses WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUserAdvances($user_id) {
        $sql = "SELECT * FROM advances WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getTodayAttendanceStatus($user_id) {
        $sql = "SELECT check_out FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE() ORDER BY check_in DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['check_out'] === null;
    }
}