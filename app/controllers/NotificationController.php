<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class NotificationController extends Controller {
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureNotificationTable($db);
            
            $userId = $_SESSION['user_id'];
            $role = $_SESSION['role'];
            
            // Get notifications based on role
            if ($role === 'owner') {
                // Owner sees all notifications
                $notifications = $this->getOwnerNotifications($db);
            } elseif ($role === 'admin') {
                // Admin sees department-specific notifications
                $notifications = $this->getAdminNotifications($db, $userId);
            } else {
                // User sees personal notifications
                $notifications = $this->getUserNotifications($db, $userId);
            }
            
            // Generate real-time notifications if none exist
            if (empty($notifications)) {
                $this->generateRealTimeNotifications($db, $userId, $role);
                $notifications = $this->getNotificationsByRole($db, $userId, $role);
            }
            
            $data = [
                'notifications' => $notifications,
                'user_role' => $role,
                'active_page' => 'notifications'
            ];
            
            $this->view('notifications/index', $data);
        } catch (Exception $e) {
            error_log('Notification index error: ' . $e->getMessage());
            $data = [
                'notifications' => [],
                'user_role' => $_SESSION['role'],
                'active_page' => 'notifications',
                'error' => 'Unable to load notifications'
            ];
            $this->view('notifications/index', $data);
        }
    }
    
    private function getOwnerNotifications($db) {
        $sql = "SELECT n.*, u.name as actor_name 
                FROM notifications n 
                LEFT JOIN users u ON n.actor_id = u.id 
                WHERE n.target_role IN ('owner', 'all') OR n.target_user_id IS NULL
                ORDER BY n.created_at DESC LIMIT 50";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getAdminNotifications($db, $userId) {
        $sql = "SELECT n.*, u.name as actor_name 
                FROM notifications n 
                LEFT JOIN users u ON n.actor_id = u.id 
                WHERE n.target_role IN ('admin', 'all') OR n.target_user_id = ?
                ORDER BY n.created_at DESC LIMIT 50";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUserNotifications($db, $userId) {
        $sql = "SELECT n.*, u.name as actor_name 
                FROM notifications n 
                LEFT JOIN users u ON n.actor_id = u.id 
                WHERE n.target_user_id = ? OR n.target_role = 'all'
                ORDER BY n.created_at DESC LIMIT 50";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function generateRealTimeNotifications($db, $userId, $role) {
        // Generate notifications based on recent activities
        
        // 1. Leave requests notifications
        $this->generateLeaveNotifications($db, $role);
        
        // 2. Expense requests notifications
        $this->generateExpenseNotifications($db, $role);
        
        // 3. Attendance notifications
        $this->generateAttendanceNotifications($db, $role);
        
        // 4. Task notifications
        $this->generateTaskNotifications($db, $role);
        
        // 5. Daily workflow notifications
        $this->generateWorkflowNotifications($db, $role);
    }
    
    private function generateLeaveNotifications($db, $role) {
        if ($role === 'owner' || $role === 'admin') {
            // Pending leave requests
            $stmt = $db->query("SELECT l.*, u.name as user_name FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.status = 'pending' ORDER BY l.created_at DESC LIMIT 10");
            $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($leaves as $leave) {
                $this->createNotification($db, [
                    'title' => 'New Leave Request',
                    'message' => "{$leave['user_name']} has requested leave from {$leave['start_date']} to {$leave['end_date']}",
                    'type' => 'leave_request',
                    'target_role' => $role,
                    'actor_id' => $leave['user_id'],
                    'reference_id' => $leave['id'],
                    'reference_type' => 'leave'
                ]);
            }
        }
    }
    
    private function generateExpenseNotifications($db, $role) {
        if ($role === 'owner' || $role === 'admin') {
            // Pending expense claims
            $stmt = $db->query("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.status = 'pending' ORDER BY e.created_at DESC LIMIT 10");
            $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($expenses as $expense) {
                $this->createNotification($db, [
                    'title' => 'New Expense Claim',
                    'message' => "{$expense['user_name']} submitted expense claim of ₹{$expense['amount']} for {$expense['description']}",
                    'type' => 'expense_claim',
                    'target_role' => $role,
                    'actor_id' => $expense['user_id'],
                    'reference_id' => $expense['id'],
                    'reference_type' => 'expense'
                ]);
            }
        }
    }
    
    private function generateAttendanceNotifications($db, $role) {
        if ($role === 'owner' || $role === 'admin') {
            // Late arrivals today
            $today = date('Y-m-d');
            $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM attendance a JOIN users u ON a.user_id = u.id WHERE DATE(a.clock_in) = ? AND TIME(a.clock_in) > '09:30:00' ORDER BY a.clock_in DESC LIMIT 5");
            $stmt->execute([$today]);
            $lateArrivals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($lateArrivals as $attendance) {
                $this->createNotification($db, [
                    'title' => 'Late Arrival Alert',
                    'message' => "{$attendance['user_name']} arrived late at " . date('H:i', strtotime($attendance['clock_in'])),
                    'type' => 'attendance_alert',
                    'target_role' => $role,
                    'actor_id' => $attendance['user_id'],
                    'reference_id' => $attendance['id'],
                    'reference_type' => 'attendance'
                ]);
            }
        }
    }
    
    private function generateTaskNotifications($db, $role) {
        // Overdue tasks
        $stmt = $db->query("SELECT t.*, u.name as user_name FROM tasks t JOIN users u ON t.assigned_to = u.id WHERE t.due_date < CURDATE() AND t.status != 'completed' ORDER BY t.due_date DESC LIMIT 10");
        $overdueTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($overdueTasks as $task) {
            $targetRole = ($role === 'owner' || $role === 'admin') ? $role : null;
            $targetUser = ($role === 'user') ? $task['assigned_to'] : null;
            
            $this->createNotification($db, [
                'title' => 'Overdue Task Alert',
                'message' => "Task '{$task['title']}' assigned to {$task['user_name']} is overdue",
                'type' => 'task_overdue',
                'target_role' => $targetRole,
                'target_user_id' => $targetUser,
                'actor_id' => $task['assigned_to'],
                'reference_id' => $task['id'],
                'reference_type' => 'task'
            ]);
        }
    }
    
    private function generateWorkflowNotifications($db, $role) {
        // Check if daily_workflow_status table exists
        try {
            $today = date('Y-m-d');
            $stmt = $db->prepare("SELECT u.name as user_name, u.id as user_id FROM users u LEFT JOIN daily_workflow_status dws ON u.id = dws.user_id AND dws.workflow_date = ? WHERE u.role = 'user' AND dws.morning_planned_at IS NULL");
            $stmt->execute([$today]);
            $missingPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($missingPlans as $user) {
                if ($role === 'owner' || $role === 'admin') {
                    $this->createNotification($db, [
                        'title' => 'Missing Morning Plan',
                        'message' => "{$user['user_name']} hasn't submitted their morning plan for today",
                        'type' => 'workflow_missing',
                        'target_role' => $role,
                        'actor_id' => $user['user_id'],
                        'reference_id' => $user['user_id'],
                        'reference_type' => 'workflow'
                    ]);
                }
            }
        } catch (Exception $e) {
            // Table doesn't exist, skip workflow notifications
        }
    }
    
    private function createNotification($db, $data) {
        // Check if similar notification already exists (prevent duplicates)
        $stmt = $db->prepare("SELECT id FROM notifications WHERE title = ? AND actor_id = ? AND reference_id = ? AND reference_type = ? AND DATE(created_at) = CURDATE()");
        $stmt->execute([$data['title'], $data['actor_id'], $data['reference_id'], $data['reference_type']]);
        
        if (!$stmt->fetch()) {
            $sql = "INSERT INTO notifications (title, message, type, target_role, target_user_id, actor_id, reference_id, reference_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['title'],
                $data['message'],
                $data['type'],
                $data['target_role'] ?? null,
                $data['target_user_id'] ?? null,
                $data['actor_id'] ?? null,
                $data['reference_id'] ?? null,
                $data['reference_type'] ?? null
            ]);
        }
    }
    
    public function getUnreadCount() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $userId = $_SESSION['user_id'];
            $role = $_SESSION['role'];
            
            if ($role === 'owner') {
                $stmt = $db->query("SELECT COUNT(*) as count FROM notifications WHERE (target_role IN ('owner', 'all') OR target_user_id IS NULL) AND is_read = 0");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            } elseif ($role === 'admin') {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE (target_role IN ('admin', 'all') OR target_user_id = ?) AND is_read = 0");
                $stmt->execute([$userId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE (target_user_id = ? OR target_role = 'all') AND is_read = 0");
                $stmt->execute([$userId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            header('Content-Type: application/json');
            echo json_encode(['count' => $result['count'] ?? 0]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['count' => 0]);
        }
        exit;
    }
    
    public function markAsRead() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $id = $_POST['id'] ?? 0;
                if ($id) {
                    $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?");
                    $stmt->execute([$id]);
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
    }
    
    public function markAllAsRead() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $userId = $_SESSION['user_id'];
                $role = $_SESSION['role'];
                
                if ($role === 'owner') {
                    $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE (target_role IN ('owner', 'all') OR target_user_id IS NULL) AND is_read = 0");
                    $stmt->execute();
                } elseif ($role === 'admin') {
                    $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE (target_role IN ('admin', 'all') OR target_user_id = ?) AND is_read = 0");
                    $stmt->execute([$userId]);
                } else {
                    $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE (target_user_id = ? OR target_role = 'all') AND is_read = 0");
                    $stmt->execute([$userId]);
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
    }
    
    private function ensureNotificationTable($db) {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type VARCHAR(50) DEFAULT 'info',
                target_role VARCHAR(20) NULL,
                target_user_id INT NULL,
                actor_id INT NULL,
                reference_id INT NULL,
                reference_type VARCHAR(50) NULL,
                is_read TINYINT(1) DEFAULT 0,
                read_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_target_role (target_role),
                INDEX idx_target_user (target_user_id),
                INDEX idx_is_read (is_read),
                INDEX idx_created_at (created_at)
            )";
            $db->exec($sql);
        } catch (Exception $e) {
            error_log('Notification table creation error: ' . $e->getMessage());
        }
    }
    
    private function getNotificationsByRole($db, $userId, $role) {
        if ($role === 'owner') {
            return $this->getOwnerNotifications($db);
        } elseif ($role === 'admin') {
            return $this->getAdminNotifications($db, $userId);
        } else {
            return $this->getUserNotifications($db, $userId);
        }
    }
}
?>