<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class FollowupController extends Controller {
    
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        $active_page = 'followups';
        $title = 'Follow-ups Management';
        
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM followups WHERE user_id = ? OR assigned_to = ? ORDER BY follow_date ASC");
            $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
            $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = ['followups' => $followups];
        } catch (Exception $e) {
            $data = ['followups' => []];
        }
        
        $this->view('followups/index', $data);
    }
    
    public function create() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                
                $stmt = $db->prepare("INSERT INTO followups (user_id, title, description, follow_date, priority, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
                $result = $stmt->execute([
                    $_SESSION['user_id'],
                    trim($_POST['title'] ?? ''),
                    trim($_POST['description'] ?? ''),
                    $_POST['follow_date'] ?? date('Y-m-d'),
                    $_POST['priority'] ?? 'medium'
                ]);
                
                if ($result) {
                    header('Location: /ergon/followups?success=Follow-up created successfully');
                } else {
                    header('Location: /ergon/followups?error=Failed to create follow-up');
                }
                exit;
            } catch (Exception $e) {
                header('Location: /ergon/followups?error=Failed to create follow-up');
                exit;
            }
        }
        
        header('Location: /ergon/followups');
        exit;
    }
    
    public function viewFollowup($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM followups WHERE id = ? AND (user_id = ? OR assigned_to = ?)");
            $stmt->execute([$id, $_SESSION['user_id'], $_SESSION['user_id']]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) {
                header('Location: /ergon/followups?error=Follow-up not found');
                exit;
            }
            
            $data = ['followup' => $followup];
            $active_page = 'followups';
            $title = 'Follow-up Details';
            
            $this->view('followups/view', $data);
            
        } catch (Exception $e) {
            header('Location: /ergon/followups?error=Failed to load follow-up');
            exit;
        }
    }
    
    public function update() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                
                $stmt = $db->prepare("UPDATE followups SET title = ?, description = ?, follow_date = ?, priority = ?, status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([
                    trim($_POST['title'] ?? ''),
                    trim($_POST['description'] ?? ''),
                    $_POST['follow_date'] ?? date('Y-m-d'),
                    $_POST['priority'] ?? 'medium',
                    $_POST['status'] ?? 'pending',
                    $_POST['followup_id'],
                    $_SESSION['user_id']
                ]);
                
                if ($result) {
                    header('Location: /ergon/followups?success=Follow-up updated successfully');
                } else {
                    header('Location: /ergon/followups?error=Failed to update follow-up');
                }
                exit;
            } catch (Exception $e) {
                header('Location: /ergon/followups?error=Failed to update follow-up');
                exit;
            }
        }
        
        header('Location: /ergon/followups');
        exit;
    }
    
    public function complete() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                
                $stmt = $db->prepare("UPDATE followups SET status = 'completed', completed_at = NOW(), updated_at = NOW() WHERE id = ? AND (user_id = ? OR assigned_to = ?)");
                $result = $stmt->execute([
                    $_POST['followup_id'],
                    $_SESSION['user_id'],
                    $_SESSION['user_id']
                ]);
                
                if ($result) {
                    header('Location: /ergon/followups?success=Follow-up marked as completed');
                } else {
                    header('Location: /ergon/followups?error=Failed to complete follow-up');
                }
                exit;
            } catch (Exception $e) {
                header('Location: /ergon/followups?error=Failed to complete follow-up');
                exit;
            }
        }
        
        header('Location: /ergon/followups');
        exit;
    }
    
    public function reschedule() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                
                $stmt = $db->prepare("UPDATE followups SET follow_date = ?, status = 'rescheduled', updated_at = NOW() WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([
                    $_POST['new_date'],
                    $_POST['followup_id'],
                    $_SESSION['user_id']
                ]);
                
                if ($result) {
                    header('Location: /ergon/followups?success=Follow-up rescheduled successfully');
                } else {
                    header('Location: /ergon/followups?error=Failed to reschedule follow-up');
                }
                exit;
            } catch (Exception $e) {
                header('Location: /ergon/followups?error=Failed to reschedule follow-up');
                exit;
            }
        }
        
        header('Location: /ergon/followups');
        exit;
    }
    
    public function delete() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                
                $stmt = $db->prepare("DELETE FROM followups WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([
                    $_POST['followup_id'],
                    $_SESSION['user_id']
                ]);
                
                if ($result) {
                    header('Location: /ergon/followups?success=Follow-up deleted successfully');
                } else {
                    header('Location: /ergon/followups?error=Failed to delete follow-up');
                }
                exit;
            } catch (Exception $e) {
                header('Location: /ergon/followups?error=Failed to delete follow-up');
                exit;
            }
        }
        
        header('Location: /ergon/followups');
        exit;
    }
    
    public function updateItem() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                
                $stmt = $db->prepare("UPDATE followups SET status = ?, updated_at = NOW() WHERE id = ? AND (user_id = ? OR assigned_to = ?)");
                $result = $stmt->execute([
                    $_POST['status'],
                    $_POST['followup_id'],
                    $_SESSION['user_id'],
                    $_SESSION['user_id']
                ]);
                
                echo json_encode(['success' => $result]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }
    
    public function createFromTask() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::connect();
                
                $stmt = $db->prepare("INSERT INTO followups (user_id, title, description, follow_date, priority, status, task_id, created_at) VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())");
                $result = $stmt->execute([
                    $_SESSION['user_id'],
                    'Follow-up: ' . $_POST['task_title'],
                    $_POST['description'] ?? '',
                    $_POST['follow_date'] ?? date('Y-m-d', strtotime('+1 day')),
                    $_POST['priority'] ?? 'medium',
                    $_POST['task_id'] ?? null
                ]);
                
                echo json_encode(['success' => $result, 'followup_id' => $db->lastInsertId()]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }
}
?>