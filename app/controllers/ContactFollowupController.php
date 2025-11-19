<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/NotificationHelper.php';

class ContactFollowupController extends Controller {
    
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            $contacts = $this->getContactsWithFollowups($db);
            $this->view('contact_followups/index', ['contacts' => $contacts]);
        } catch (Exception $e) {
            error_log('Contact followups error: ' . $e->getMessage());
            $this->view('contact_followups/index', ['contacts' => [], 'error' => $e->getMessage()]);
        }
    }
    
    public function viewGeneric() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            
            // Get all followups for the current user or all if admin/owner
            $sql = "
                SELECT f.*, c.name as contact_name, c.phone as contact_phone, c.email as contact_email, c.company as contact_company,
                       CASE WHEN f.task_id IS NOT NULL THEN 'task' ELSE 'standalone' END as followup_type,
                       t.title as task_title
                FROM followups f 
                LEFT JOIN contacts c ON f.contact_id = c.id 
                LEFT JOIN tasks t ON f.task_id = t.id
                WHERE 1=1
            ";
            
            if (!in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
                $sql .= " AND (f.user_id = ? OR t.assigned_to = ?)";
                $stmt = $db->prepare($sql . " ORDER BY f.follow_up_date DESC LIMIT 50");
                $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
            } else {
                $stmt = $db->prepare($sql . " ORDER BY f.follow_up_date DESC LIMIT 50");
                $stmt->execute();
            }
            
            $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create a dummy contact for the view
            $contact = [
                'id' => 0,
                'name' => 'All Follow-ups',
                'phone' => '',
                'email' => '',
                'company' => ''
            ];
            
            $this->view('contact_followups/view', [
                'contact' => $contact,
                'followups' => $followups
            ]);
        } catch (Exception $e) {
            error_log('View generic followups error: ' . $e->getMessage());
            header('Location: /ergon/contacts/followups?error=Error loading follow-ups');
            exit;
        }
    }
    
    public function viewContactFollowups($contact_id) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
            $stmt->execute([$contact_id]);
            $contact = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contact) {
                header('Location: /ergon/contacts/followups?error=Contact not found');
                exit;
            }
            
            $followups = $this->getContactFollowups($db, $contact_id);
            
            $this->view('contact_followups/view', [
                'contact' => $contact,
                'followups' => $followups
            ]);
        } catch (Exception $e) {
            error_log('View contact followups error: ' . $e->getMessage());
            header('Location: /ergon/contacts/followups?error=Error loading contact');
            exit;
        }
    }
    
    public function createStandaloneFollowup() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->storeStandaloneFollowup();
        }
        
        try {
            $pdo = Database::connect();
            
            // Get contacts
            $contacts = $pdo->query("SELECT * FROM contacts ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
            
            // Get available tasks
            $tasks = $pdo->query("SELECT id, title, description, deadline as due_date FROM tasks WHERE status != 'completed' ORDER BY deadline ASC")->fetchAll(PDO::FETCH_ASSOC);
            
            $this->view('contact_followups/create', ['contacts' => $contacts, 'tasks' => $tasks]);
        } catch (Exception $e) {
            $this->view('contact_followups/create', ['contacts' => [], 'tasks' => [], 'error' => $e->getMessage()]);
        }
    }
    
    private function storeStandaloneFollowup() {
        try {
            $db = Database::connect();
            
            $title = trim($_POST['title'] ?? '');
            $follow_up_date = $_POST['follow_up_date'] ?? date('Y-m-d');
            $description = trim($_POST['description'] ?? '');
            $task_id = !empty($_POST['task_id']) ? intval($_POST['task_id']) : null;
            
            if (empty($title)) {
                $redirectUrl = $task_id ? "/ergon/tasks/view/$task_id?error=Title required" : '/ergon/contacts/followups/create?error=Title required';
                header("Location: $redirectUrl");
                exit;
            }
            
            // Handle contact creation/selection
            $contact_id = null;
            if (!empty($_POST['contact_name']) || !empty($_POST['contact_company'])) {
                $contact_id = $this->createOrFindContact($db, $_POST);
            }
            
            // Create follow-up
            $followup_type = $task_id ? 'task' : 'standalone';
            $user_id = $task_id ? $this->getTaskAssignedUser($db, $task_id) : $_SESSION['user_id'];
            
            $stmt = $db->prepare("INSERT INTO followups (contact_id, user_id, task_id, followup_type, title, description, follow_up_date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())");
            $result = $stmt->execute([$contact_id, $user_id, $task_id, $followup_type, $title, $description, $follow_up_date]);
            
            if ($result) {
                $redirectUrl = $task_id ? "/ergon/tasks/view/$task_id?success=Follow-up added" : '/ergon/contacts/followups?success=Follow-up created';
                header("Location: $redirectUrl");
            } else {
                $redirectUrl = $task_id ? "/ergon/tasks/view/$task_id?error=Failed to add follow-up" : '/ergon/contacts/followups/create?error=Failed to create';
                header("Location: $redirectUrl");
            }
        } catch (Exception $e) {
            error_log('Store followup error: ' . $e->getMessage());
            $task_id = !empty($_POST['task_id']) ? intval($_POST['task_id']) : null;
            $redirectUrl = $task_id ? "/ergon/tasks/view/$task_id?error=" . urlencode($e->getMessage()) : '/ergon/contacts/followups/create?error=' . urlencode($e->getMessage());
            header("Location: $redirectUrl");
        }
        exit;
    }
    
    private function createOrFindContact($db, $postData) {
        $contactName = trim($postData['contact_name'] ?? '');
        $contactCompany = trim($postData['contact_company'] ?? '');
        $contactPhone = trim($postData['contact_phone'] ?? '');
        
        if (empty($contactName) && empty($contactCompany)) {
            return null;
        }
        
        // Check if contact exists
        $stmt = $db->prepare("SELECT id FROM contacts WHERE name = ? OR company = ?");
        $stmt->execute([$contactName, $contactCompany]);
        $existingContact = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingContact) {
            return $existingContact['id'];
        }
        
        // Create new contact
        $stmt = $db->prepare("INSERT INTO contacts (name, company, phone, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$contactName, $contactCompany, $contactPhone]);
        return $db->lastInsertId();
    }
    
    private function getTaskAssignedUser($db, $task_id) {
        $stmt = $db->prepare("SELECT assigned_to FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        return $task ? $task['assigned_to'] : $_SESSION['user_id'];
    }
    
    public function completeFollowup($id) {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            $db = Database::connect();
            
            // Get followup details including task_id
            $stmt = $db->prepare("SELECT id, contact_id, task_id, status FROM followups WHERE id = ?");
            $stmt->execute([$id]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($followup) {
                // Complete the followup
                $stmt = $db->prepare("UPDATE followups SET status = 'completed', completed_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result) {
                    $this->logHistory($id, 'completed', $followup['status'], 'Follow-up completed');
                    
                    // If this followup is linked to a task, update the task status as well
                    if ($followup['task_id']) {
                        $this->updateLinkedTaskStatus($db, $followup['task_id'], 'completed');
                    }
                    
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to complete']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Follow-up not found']);
            }
        } catch (Exception $e) {
            error_log('Complete followup error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to complete']);
        }
        exit;
    }
    
    public function cancelFollowup($id) {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            $db = Database::connect();
            $reason = trim($_POST['reason'] ?? 'No reason provided');
            
            if (empty($reason)) {
                echo json_encode(['success' => false, 'error' => 'Reason required']);
                exit;
            }
            
            if (!is_numeric($id) || $id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid follow-up ID']);
                exit;
            }
            
            $stmt = $db->prepare("SELECT id, status, contact_id FROM followups WHERE id = ?");
            $stmt->execute([$id]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) {
                echo json_encode(['success' => false, 'error' => 'Follow-up not found']);
                exit;
            }
            
            if ($followup['status'] === 'cancelled') {
                echo json_encode(['success' => false, 'error' => 'Follow-up is already cancelled']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE followups SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logHistory($id, 'cancelled', $followup['status'], "Follow-up cancelled. Reason: {$reason}");
                echo json_encode(['success' => true, 'message' => 'Follow-up cancelled successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Follow-up not found or no changes made']);
            }
            
        } catch (Exception $e) {
            error_log('Cancel error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Database error occurred']);
        }
        exit;
    }
    
    public function rescheduleFollowup($id) {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            $db = Database::connect();
            $newDate = $_POST['new_date'] ?? null;
            $reason = trim($_POST['reason'] ?? 'No reason provided');
            
            if (!$newDate) {
                echo json_encode(['success' => false, 'error' => 'New date required']);
                exit;
            }
            
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate)) {
                echo json_encode(['success' => false, 'error' => 'Invalid date format']);
                exit;
            }
            
            if (!is_numeric($id) || $id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid follow-up ID']);
                exit;
            }
            
            $stmt = $db->prepare("SELECT id, follow_up_date, status, contact_id FROM followups WHERE id = ?");
            $stmt->execute([$id]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) {
                echo json_encode(['success' => false, 'error' => 'Follow-up not found']);
                exit;
            }
            
            if (in_array($followup['status'], ['completed', 'cancelled'])) {
                echo json_encode(['success' => false, 'error' => "Cannot reschedule {$followup['status']} follow-up"]);
                exit;
            }
            
            $oldDate = $followup['follow_up_date'];
            
            if ($oldDate === $newDate) {
                echo json_encode(['success' => false, 'error' => 'New date must be different from current date']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE followups SET follow_up_date = ?, status = 'postponed', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$newDate, $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logHistory($id, 'rescheduled', $oldDate, "Rescheduled from {$oldDate} to {$newDate}. Reason: {$reason}");
                echo json_encode(['success' => true, 'message' => 'Follow-up rescheduled successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Follow-up not found or no changes made']);
            }
            
        } catch (Exception $e) {
            error_log('Reschedule error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Database error occurred']);
        }
        exit;
    }
    
    public function getFollowupHistory($id) {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT id FROM followups WHERE id = ?");
            $stmt->execute([$id]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($followup) {
                $stmt = $db->prepare("SELECT h.*, u.name as user_name FROM followup_history h LEFT JOIN users u ON h.created_by = u.id WHERE h.followup_id = ? ORDER BY h.created_at DESC");
                $stmt->execute([$id]);
                $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $html = empty($history) ? '<p>No history available for this follow-up.</p>' : $this->renderHistory($history);
                echo json_encode(['success' => true, 'html' => $html]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Follow-up not found']);
            }
        } catch (Exception $e) {
            error_log('History error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function checkReminders() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("
                SELECT f.*, c.name as contact_name, c.phone as contact_phone 
                FROM followups f 
                LEFT JOIN contacts c ON f.contact_id = c.id 
                WHERE f.follow_up_date = CURDATE() 
                AND f.status IN ('pending', 'in_progress')
            ");
            $stmt->execute();
            $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'reminders' => $reminders, 'count' => count($reminders)]);
        } catch (Exception $e) {
            error_log('Check reminders error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage(), 'reminders' => [], 'count' => 0]);
        }
        exit;
    }
    
    public function createContact() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $name = trim($input['name'] ?? '');
            if (empty($name)) {
                echo json_encode(['success' => false, 'error' => 'Name required']);
                exit;
            }
            
            $db = Database::connect();
            $stmt = $db->prepare("INSERT INTO contacts (name, phone, email, company) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([
                $name, 
                trim($input['phone'] ?? '') ?: null, 
                trim($input['email'] ?? '') ?: null, 
                trim($input['company'] ?? '') ?: null
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'contact_id' => $db->lastInsertId()]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to create']);
            }
        } catch (Exception $e) {
            error_log('Create contact error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to create']);
        }
        exit;
    }
    
    private function getContactsWithFollowups($db) {
        $sql = "
            SELECT c.*, 
                   COUNT(f.id) as total_followups,
                   SUM(CASE WHEN f.status = 'pending' AND f.follow_up_date < CURDATE() THEN 1 ELSE 0 END) as overdue_count,
                   SUM(CASE WHEN f.status = 'pending' AND f.follow_up_date = CURDATE() THEN 1 ELSE 0 END) as today_count,
                   MAX(f.follow_up_date) as next_followup_date
            FROM contacts c
            LEFT JOIN followups f ON c.id = f.contact_id
            LEFT JOIN tasks t ON f.task_id = t.id
        ";
        
        if (!in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
            $sql .= " WHERE (f.user_id = ? OR t.assigned_to = ? OR f.id IS NULL)";
            $stmt = $db->prepare($sql . " GROUP BY c.id HAVING total_followups > 0 ORDER BY next_followup_date ASC");
            $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
        } else {
            $stmt = $db->prepare($sql . " GROUP BY c.id HAVING total_followups > 0 ORDER BY next_followup_date ASC");
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getContactFollowups($db, $contact_id) {
        $sql = "
            SELECT f.*, 
                   CASE WHEN f.task_id IS NOT NULL THEN 'task' ELSE 'standalone' END as followup_type,
                   t.title as task_title
            FROM followups f 
            LEFT JOIN tasks t ON f.task_id = t.id
            WHERE f.contact_id = ?
        ";
        
        if (!in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
            $sql .= " AND (f.user_id = ? OR t.assigned_to = ?)";
            $stmt = $db->prepare($sql . " ORDER BY f.follow_up_date DESC");
            $stmt->execute([$contact_id, $_SESSION['user_id'], $_SESSION['user_id']]);
        } else {
            $stmt = $db->prepare($sql . " ORDER BY f.follow_up_date DESC");
            $stmt->execute([$contact_id]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function renderHistory($history) {
        $html = '<div class="modern-history-timeline">';
        foreach ($history as $entry) {
            $actionIcon = match($entry['action']) {
                'created' => '✨',
                'rescheduled' => '📅',
                'completed' => '✅',
                'cancelled' => '❌',
                'postponed' => '🔄',
                default => '📝'
            };
            
            $actionColor = match($entry['action']) {
                'created' => '#3b82f6',
                'rescheduled' => '#f59e0b',
                'completed' => '#10b981',
                'cancelled' => '#ef4444',
                'postponed' => '#f59e0b',
                default => '#6b7280'
            };
            
            $html .= '<div class="history-entry">';
            $html .= '<div class="history-icon" style="background: ' . $actionColor . '">' . $actionIcon . '</div>';
            $html .= '<div class="history-content">';
            $html .= '<div class="history-header">';
            $html .= '<span class="history-action">' . ucfirst($entry['action']) . '</span>';
            $html .= '<span class="history-date">' . date('M d, Y \a\t H:i', strtotime($entry['created_at'])) . '</span>';
            $html .= '</div>';
            if (!empty($entry['notes'])) {
                $html .= '<div class="history-notes">' . nl2br(htmlspecialchars($entry['notes'])) . '</div>';
            }
            $html .= '<div class="history-user">👤 ' . htmlspecialchars($entry['user_name'] ?? 'System') . '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }
    
    private function logHistory($followupId, $action, $oldValue = null, $notes = null) {
        try {
            $db = Database::connect();
            $this->ensureFollowupHistoryTable($db);
            
            $stmt = $db->query("SHOW TABLES LIKE 'followup_history'");
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("INSERT INTO followup_history (followup_id, action, old_value, notes, created_by) VALUES (?, ?, ?, ?, ?)");
                return $stmt->execute([$followupId, $action, $oldValue, $notes, $_SESSION['user_id'] ?? null]);
            }
            return true;
        } catch (Exception $e) {
            error_log('History log error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function ensureFollowupHistoryTable($db) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS followup_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                followup_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                old_value TEXT,
                notes TEXT,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_followup_id (followup_id)
            )");
        } catch (Exception $e) {
            error_log('ensureFollowupHistoryTable error: ' . $e->getMessage());
        }
    }
    
    private function updateLinkedTaskStatus($db, $taskId, $status) {
        try {
            $stmt = $db->prepare("SELECT status, progress FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($task) {
                $oldStatus = $task['status'];
                $newProgress = ($status === 'completed') ? 100 : $task['progress'];
                
                $stmt = $db->prepare("UPDATE tasks SET status = ?, progress = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$status, $newProgress, $taskId]);
                
                if ($result) {
                    error_log("Successfully updated linked task {$taskId} status from {$oldStatus} to {$status}");
                }
            }
        } catch (Exception $e) {
            error_log('Update linked task status error: ' . $e->getMessage());
        }
    }
    
    public function getStatusBadgeClass($status) {
        return match($status) {
            'completed' => 'success',
            'pending' => 'warning',
            'in_progress' => 'info',
            'postponed' => 'warning',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }
}
?>