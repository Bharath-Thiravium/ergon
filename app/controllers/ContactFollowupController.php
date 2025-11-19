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
            $pdo = new PDO('mysql:host=localhost;dbname=ergon_db;charset=utf8mb4', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
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
        
        // Enhanced debugging and validation
        error_log("Cancel request received - ID: $id, Method: {$_SERVER['REQUEST_METHOD']}, Session User: " . ($_SESSION['user_id'] ?? 'none'));
        
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log('Cancel failed: Unauthorized access');
            echo json_encode(['success' => false, 'error' => 'Unauthorized', 'debug' => 'Session or method check failed']);
            exit;
        }
        
        try {
            $db = Database::connect();
            $reason = trim($_POST['reason'] ?? 'No reason provided');
            
            error_log("Cancel data - Reason: $reason");
            
            // Validate input
            if (empty($reason)) {
                error_log('Cancel failed: Reason required');
                echo json_encode(['success' => false, 'error' => 'Reason required', 'debug' => 'Missing reason parameter']);
                exit;
            }
            
            // Validate ID
            if (!is_numeric($id) || $id <= 0) {
                error_log('Cancel failed: Invalid ID');
                echo json_encode(['success' => false, 'error' => 'Invalid follow-up ID', 'debug' => "ID: $id is not valid"]);
                exit;
            }
            
            // Check if followup exists and get current data
            $stmt = $db->prepare("SELECT id, status, contact_id FROM followups WHERE id = ?");
            $stmt->execute([$id]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) {
                error_log("Cancel failed: Follow-up not found with ID: $id");
                echo json_encode(['success' => false, 'error' => 'Follow-up not found', 'debug' => "No followup found with ID: $id"]);
                exit;
            }
            
            error_log("Found followup - ID: {$followup['id']}, Status: {$followup['status']}");
            
            // Check if followup can be cancelled
            if ($followup['status'] === 'cancelled') {
                error_log('Cancel failed: Follow-up already cancelled');
                echo json_encode(['success' => false, 'error' => 'Follow-up is already cancelled', 'debug' => "Status: {$followup['status']}"]);
                exit;
            }
            
            // Perform the update
            error_log("Executing UPDATE query - Setting status to cancelled for ID: $id");
            $stmt = $db->prepare("UPDATE followups SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$id]);
            $rowsAffected = $stmt->rowCount();
            
            error_log("Update result - Success: " . ($result ? 'true' : 'false') . ", Rows affected: $rowsAffected");
            
            if ($result && $rowsAffected > 0) {
                // Verify the update
                $stmt = $db->prepare("SELECT status FROM followups WHERE id = ?");
                $stmt->execute([$id]);
                $updated = $stmt->fetch(PDO::FETCH_ASSOC);
                
                error_log("Verification - New Status: {$updated['status']}");
                
                // Log history
                $historyLogged = $this->logHistory($id, 'cancelled', $followup['status'], "Follow-up cancelled. Reason: {$reason}");
                error_log('History logged: ' . ($historyLogged ? 'success' : 'failed'));
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Follow-up cancelled successfully',
                    'debug' => [
                        'old_status' => $followup['status'],
                        'new_status' => $updated['status'],
                        'reason' => $reason,
                        'history_logged' => $historyLogged
                    ]
                ]);
            } else {
                error_log('Cancel failed: No rows affected by UPDATE query');
                echo json_encode([
                    'success' => false, 
                    'error' => 'Follow-up not found or no changes made',
                    'debug' => [
                        'query_result' => $result,
                        'rows_affected' => $rowsAffected,
                        'followup_id' => $id
                    ]
                ]);
            }
            
        } catch (Exception $e) {
            error_log('Cancel error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            echo json_encode([
                'success' => false, 
                'error' => 'Database error occurred',
                'debug' => [
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
        exit;
    }
    
    public function rescheduleFollowup($id) {
        header('Content-Type: application/json');
        
        // Enhanced debugging and validation
        error_log("Reschedule request received - ID: $id, Method: {$_SERVER['REQUEST_METHOD']}, Session User: " . ($_SESSION['user_id'] ?? 'none'));
        
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log('Reschedule failed: Unauthorized access');
            echo json_encode(['success' => false, 'error' => 'Unauthorized', 'debug' => 'Session or method check failed']);
            exit;
        }
        
        try {
            $db = Database::connect();
            $newDate = $_POST['new_date'] ?? null;
            $reason = trim($_POST['reason'] ?? 'No reason provided');
            
            error_log("Reschedule data - New Date: $newDate, Reason: $reason");
            
            // Validate input
            if (!$newDate) {
                error_log('Reschedule failed: New date required');
                echo json_encode(['success' => false, 'error' => 'New date required', 'debug' => 'Missing new_date parameter']);
                exit;
            }
            
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate)) {
                error_log('Reschedule failed: Invalid date format');
                echo json_encode(['success' => false, 'error' => 'Invalid date format', 'debug' => 'Date must be YYYY-MM-DD']);
                exit;
            }
            
            // Validate ID
            if (!is_numeric($id) || $id <= 0) {
                error_log('Reschedule failed: Invalid ID');
                echo json_encode(['success' => false, 'error' => 'Invalid follow-up ID', 'debug' => "ID: $id is not valid"]);
                exit;
            }
            
            // Check if followup exists and get current data
            $stmt = $db->prepare("SELECT id, follow_up_date, status, contact_id FROM followups WHERE id = ?");
            $stmt->execute([$id]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) {
                error_log("Reschedule failed: Follow-up not found with ID: $id");
                echo json_encode(['success' => false, 'error' => 'Follow-up not found', 'debug' => "No followup found with ID: $id"]);
                exit;
            }
            
            error_log("Found followup - ID: {$followup['id']}, Current Date: {$followup['follow_up_date']}, Status: {$followup['status']}");
            
            // Check if followup can be rescheduled
            if (in_array($followup['status'], ['completed', 'cancelled'])) {
                error_log("Reschedule failed: Cannot reschedule {$followup['status']} follow-up");
                echo json_encode(['success' => false, 'error' => "Cannot reschedule {$followup['status']} follow-up", 'debug' => "Status: {$followup['status']}"]);
                exit;
            }
            
            $oldDate = $followup['follow_up_date'];
            
            // Check if the new date is different from current date
            if ($oldDate === $newDate) {
                error_log('Reschedule failed: New date same as current date');
                echo json_encode(['success' => false, 'error' => 'New date must be different from current date', 'debug' => "Both dates are: $newDate"]);
                exit;
            }
            
            // Perform the update
            error_log("Executing UPDATE query - Setting date to: $newDate for ID: $id");
            $stmt = $db->prepare("UPDATE followups SET follow_up_date = ?, status = 'postponed', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$newDate, $id]);
            $rowsAffected = $stmt->rowCount();
            
            error_log("Update result - Success: " . ($result ? 'true' : 'false') . ", Rows affected: $rowsAffected");
            
            if ($result && $rowsAffected > 0) {
                // Verify the update
                $stmt = $db->prepare("SELECT follow_up_date, status FROM followups WHERE id = ?");
                $stmt->execute([$id]);
                $updated = $stmt->fetch(PDO::FETCH_ASSOC);
                
                error_log("Verification - New Date: {$updated['follow_up_date']}, New Status: {$updated['status']}");
                
                // Log history
                $historyLogged = $this->logHistory($id, 'rescheduled', $oldDate, "Rescheduled from {$oldDate} to {$newDate}. Reason: {$reason}");
                error_log('History logged: ' . ($historyLogged ? 'success' : 'failed'));
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Follow-up rescheduled successfully',
                    'debug' => [
                        'old_date' => $oldDate,
                        'new_date' => $newDate,
                        'new_status' => $updated['status'],
                        'history_logged' => $historyLogged
                    ]
                ]);
            } else {
                error_log('Reschedule failed: No rows affected by UPDATE query');
                echo json_encode([
                    'success' => false, 
                    'error' => 'Follow-up not found or no changes made',
                    'debug' => [
                        'query_result' => $result,
                        'rows_affected' => $rowsAffected,
                        'followup_id' => $id,
                        'new_date' => $newDate
                    ]
                ]);
            }
            
        } catch (Exception $e) {
            error_log('Reschedule error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            echo json_encode([
                'success' => false, 
                'error' => 'Database error occurred',
                'debug' => [
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
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
            
            // Check if it's a standalone followup first
            $stmt = $db->prepare("SELECT id FROM followups WHERE id = ?");
            $stmt->execute([$id]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($followup) {
                // Get standalone followup history
                $stmt = $db->prepare("SELECT h.*, u.name as user_name FROM followup_history h LEFT JOIN users u ON h.created_by = u.id WHERE h.followup_id = ? ORDER BY h.created_at DESC");
                $stmt->execute([$id]);
                $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $html = empty($history) ? '<p>No history available for this follow-up.</p>' : $this->renderHistory($history);
                echo json_encode(['success' => true, 'html' => $html]);
            } else {
                // Check if it's a task-linked followup
                $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ? AND type = 'followup'");
                $stmt->execute([$id]);
                $task = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($task) {
                    $html = '<div class="task-history">';
                    $html .= '<div class="history-item">';
                    $html .= '<div class="history-date">' . date('M d, Y H:i', strtotime($task['created_at'])) . '</div>';
                    $html .= '<div class="history-action">Task Created</div>';
                    $html .= '<div class="history-notes">Follow-up task created</div>';
                    $html .= '</div>';
                    if ($task['status'] === 'completed') {
                        $html .= '<div class="history-item">';
                        $html .= '<div class="history-date">' . date('M d, Y H:i', strtotime($task['updated_at'])) . '</div>';
                        $html .= '<div class="history-action">Completed</div>';
                        $html .= '<div class="history-notes">Task marked as completed</div>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                    echo json_encode(['success' => true, 'html' => $html]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Follow-up not found']);
                }
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
    
    public function createTaskFollowup() {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ergon/contacts/followups');
            exit;
        }
        
        try {
            $db = Database::connect();
            
            $title = trim($_POST['title'] ?? '');
            $contact_id = $_POST['contact_id'] ?? null;
            $deadline = $_POST['deadline'] ?? date('Y-m-d');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($title) || !$contact_id) {
                header('Location: /ergon/contacts/followups?error=Title and contact required');
                exit;
            }
            
            // Get contact info to include in task
            $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
            $stmt->execute([$contact_id]);
            $contact = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contact) {
                header('Location: /ergon/contacts/followups?error=Contact not found');
                exit;
            }
            
            // Create follow-up task
            $taskDescription = "Follow-up with {$contact['name']}";
            if ($contact['company']) $taskDescription .= " from {$contact['company']}";
            if ($description) $taskDescription .= "\n\n" . $description;
            
            $stmt = $db->prepare("
                INSERT INTO tasks (title, description, assigned_by, assigned_to, type, priority, deadline, status) 
                VALUES (?, ?, ?, ?, 'followup', 'medium', ?, 'assigned')
            ");
            
            $result = $stmt->execute([
                $title,
                $taskDescription,
                $_SESSION['user_id'],
                $_SESSION['user_id'],
                $deadline
            ]);
            
            if ($result) {
                $taskId = $db->lastInsertId();
                
                NotificationHelper::notifyOwners(
                    $_SESSION['user_id'],
                    'task',
                    'created',
                    "New follow-up task created: {$title}",
                    $taskId
                );
                
                header('Location: /ergon/contacts/followups?success=Follow-up task created');
            } else {
                header('Location: /ergon/contacts/followups?error=Failed to create task');
            }
        } catch (Exception $e) {
            error_log('Create task followup error: ' . $e->getMessage());
            header('Location: /ergon/contacts/followups?error=Failed to create task');
        }
        exit;
    }
    
    private function getContactsWithFollowups($db) {
        // Get contacts with followups (both standalone and task-linked)
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
        // Get all followups (both standalone and task-linked) for this contact
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
    
    private function getContactById($db, $contact_id) {
        $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
        $stmt->execute([$contact_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
            
            // Check if table exists before inserting
            $stmt = $db->query("SHOW TABLES LIKE 'followup_history'");
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("INSERT INTO followup_history (followup_id, action, old_value, notes, created_by) VALUES (?, ?, ?, ?, ?)");
                return $stmt->execute([$followupId, $action, $oldValue, $notes, $_SESSION['user_id'] ?? null]);
            }
            return true; // Skip logging if table doesn't exist
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
    
    /**
     * Update the status of a linked task when followup status changes
     */
    private function updateLinkedTaskStatus($db, $taskId, $status) {
        try {
            // Get current task status
            $stmt = $db->prepare("SELECT status, progress FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($task) {
                $oldStatus = $task['status'];
                $newProgress = ($status === 'completed') ? 100 : $task['progress'];
                
                // Update task status and progress
                $stmt = $db->prepare("UPDATE tasks SET status = ?, progress = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$status, $newProgress, $taskId]);
                
                if ($result) {
                    // Log task history
                    $this->logTaskHistory($db, $taskId, 'status_changed', $oldStatus, $status, 'Status updated from linked follow-up completion');
                    if ($status === 'completed' && $task['progress'] != 100) {
                        $this->logTaskHistory($db, $taskId, 'progress_updated', $task['progress'] . '%', '100%', 'Progress updated from linked follow-up completion');
                    }
                    error_log("Successfully updated linked task {$taskId} status from {$oldStatus} to {$status}");
                } else {
                    error_log("Failed to update linked task {$taskId} status");
                }
            }
        } catch (Exception $e) {
            error_log('Update linked task status error: ' . $e->getMessage());
        }
    }
    
    /**
     * Log task history for linked task updates
     */
    private function logTaskHistory($db, $taskId, $action, $oldValue = null, $newValue = null, $notes = null) {
        try {
            // Ensure task history table exists
            $db->exec("CREATE TABLE IF NOT EXISTS task_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                task_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                old_value TEXT,
                new_value TEXT,
                notes TEXT,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_task_id (task_id)
            )");
            
            $stmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$taskId, $action, $oldValue, $newValue, $notes, $_SESSION['user_id']]);
        } catch (Exception $e) {
            error_log('Task history log error: ' . $e->getMessage());
            return false;
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
    
    /**
     * Update followup status when linked task status changes (called from TasksController)
     */
    public static function updateLinkedFollowupStatus($taskId, $status) {
        try {
            $db = Database::connect();
            
            // Find followups linked to this task
            $stmt = $db->prepare("SELECT id, status FROM followups WHERE task_id = ?");
            $stmt->execute([$taskId]);
            $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($followups as $followup) {
                $oldStatus = $followup['status'];
                $newStatus = ($status === 'completed') ? 'completed' : 'pending';
                
                if ($oldStatus !== $newStatus) {
                    // Update followup status
                    $stmt = $db->prepare("UPDATE followups SET status = ?, updated_at = NOW() WHERE id = ?");
                    $result = $stmt->execute([$newStatus, $followup['id']]);
                    
                    if ($result) {
                        // Log followup history
                        $stmt = $db->prepare("INSERT INTO followup_history (followup_id, action, old_value, notes, created_by) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $followup['id'], 
                            'status_changed', 
                            $oldStatus, 
                            "Status updated from linked task completion", 
                            $_SESSION['user_id'] ?? null
                        ]);
                        
                        error_log("Successfully updated linked followup {$followup['id']} status from {$oldStatus} to {$newStatus}");
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Update linked followup status error: ' . $e->getMessage());
        }
    }
}
?>