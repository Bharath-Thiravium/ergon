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
            $pdo = new PDO('mysql:host=localhost;dbname=ergon_db;charset=utf8mb4', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            $contacts = $pdo->query("
                SELECT c.*, 
                       COUNT(f.id) as total_followups,
                       SUM(CASE WHEN f.status = 'pending' AND f.follow_up_date < CURDATE() THEN 1 ELSE 0 END) as overdue_count,
                       SUM(CASE WHEN f.status = 'pending' AND f.follow_up_date = CURDATE() THEN 1 ELSE 0 END) as today_count,
                       MAX(f.follow_up_date) as next_followup_date
                FROM contacts c
                LEFT JOIN followups f ON c.id = f.contact_id
                GROUP BY c.id
                HAVING total_followups > 0
                ORDER BY next_followup_date ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
            
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
                       'standalone' as followup_type, NULL as task_title
                FROM followups f 
                LEFT JOIN contacts c ON f.contact_id = c.id 
                WHERE 1=1
            ";
            
            if (!in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
                $sql .= " AND f.user_id = ?";
                $stmt = $db->prepare($sql . " ORDER BY f.follow_up_date DESC LIMIT 50");
                $stmt->execute([$_SESSION['user_id']]);
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
            $pdo = new PDO('mysql:host=localhost;dbname=ergon_db;charset=utf8mb4', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            $title = trim($_POST['title'] ?? '');
            $contact_id = $_POST['contact_id'] ?? null;
            $follow_up_date = $_POST['follow_up_date'] ?? date('Y-m-d');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($title) || !$contact_id) {
                header('Location: /ergon/contacts/followups/create?error=Title and contact required');
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO followups (contact_id, user_id, title, description, follow_up_date) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$contact_id, $_SESSION['user_id'], $title, $description, $follow_up_date]);
            
            if ($result) {
                header('Location: /ergon/contacts/followups?success=Follow-up created');
            } else {
                header('Location: /ergon/contacts/followups/create?error=Failed to create');
            }
        } catch (Exception $e) {
            error_log('Store followup error: ' . $e->getMessage());
            header('Location: /ergon/contacts/followups/create?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
    
    public function completeFollowup($id) {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            $db = Database::connect();
            
            // Check if it's a standalone followup
            $stmt = $db->prepare("SELECT contact_id FROM followups WHERE id = ?");
            $stmt->execute([$id]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($followup) {
                // Complete standalone followup
                $stmt = $db->prepare("UPDATE followups SET status = 'completed', completed_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result) {
                    $this->logHistory($id, 'completed', 'pending', 'Follow-up completed');
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to complete']);
                }
            } else {
                // Check if it's a task-linked followup
                $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ? AND type = 'followup'");
                $stmt->execute([$id]);
                $task = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($task) {
                    $stmt = $db->prepare("UPDATE tasks SET status = 'completed' WHERE id = ?");
                    $result = $stmt->execute([$id]);
                    
                    if ($result) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to complete task']);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Follow-up not found']);
                }
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
        // Get contacts with standalone followups
        $sql = "
            SELECT c.*, 
                   COUNT(f.id) as standalone_followups,
                   SUM(CASE WHEN f.status = 'pending' AND f.follow_up_date < CURDATE() THEN 1 ELSE 0 END) as overdue_count,
                   SUM(CASE WHEN f.status = 'pending' AND f.follow_up_date = CURDATE() THEN 1 ELSE 0 END) as today_count,
                   MAX(f.follow_up_date) as next_followup_date
            FROM contacts c
            LEFT JOIN followups f ON c.id = f.contact_id
        ";
        
        if (!in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
            $sql .= " WHERE f.user_id = ? OR f.user_id IS NULL";
            $stmt = $db->prepare($sql . " GROUP BY c.id");
            $stmt->execute([$_SESSION['user_id']]);
        } else {
            $stmt = $db->prepare($sql . " GROUP BY c.id");
            $stmt->execute();
        }
        
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add task-linked followup counts
        foreach ($contacts as &$contact) {
            $taskSql = "
                SELECT COUNT(*) as task_followups,
                       SUM(CASE WHEN t.status != 'completed' AND t.deadline < CURDATE() THEN 1 ELSE 0 END) as task_overdue,
                       SUM(CASE WHEN t.status != 'completed' AND t.deadline = CURDATE() THEN 1 ELSE 0 END) as task_today
                FROM tasks t 
                WHERE t.type = 'followup'
                  AND (t.title LIKE ? OR t.description LIKE ? OR t.description LIKE ?)
            ";
            
            $searchTerms = [
                '%' . $contact['name'] . '%',
                '%' . $contact['name'] . '%', 
                '%' . $contact['phone'] . '%'
            ];
            
            if (!in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
                $taskSql .= " AND t.assigned_to = ?";
                $searchTerms[] = $_SESSION['user_id'];
            }
            
            $taskStmt = $db->prepare($taskSql);
            $taskStmt->execute($searchTerms);
            $taskData = $taskStmt->fetch(PDO::FETCH_ASSOC);
            
            $contact['total_followups'] = ($contact['standalone_followups'] ?? 0) + ($taskData['task_followups'] ?? 0);
            $contact['overdue_count'] += ($taskData['task_overdue'] ?? 0);
            $contact['today_count'] += ($taskData['task_today'] ?? 0);
        }
        
        // Filter out contacts with no followups
        $contacts = array_filter($contacts, function($contact) {
            return $contact['total_followups'] > 0;
        });
        
        // Sort by next followup date
        usort($contacts, function($a, $b) {
            $dateA = $a['next_followup_date'] ?? '9999-12-31';
            $dateB = $b['next_followup_date'] ?? '9999-12-31';
            return strcmp($dateA, $dateB);
        });
        
        return $contacts;
    }
    
    private function getContactFollowups($db, $contact_id) {
        // Get standalone followups
        $sql = "SELECT f.*, 'standalone' as followup_type, NULL as task_title FROM followups f WHERE f.contact_id = ?";
        
        if (!in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
            $sql .= " AND f.user_id = ?";
            $stmt = $db->prepare($sql . " ORDER BY f.follow_up_date DESC");
            $stmt->execute([$contact_id, $_SESSION['user_id']]);
        } else {
            $stmt = $db->prepare($sql . " ORDER BY f.follow_up_date DESC");
            $stmt->execute([$contact_id]);
        }
        
        $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get task-linked followups - tasks with type='followup' that match contact info
        $contact = $this->getContactById($db, $contact_id);
        if ($contact) {
            $taskSql = "
                SELECT t.id, t.title, t.description, t.deadline as follow_up_date, t.status, 
                       t.created_at, t.updated_at, 
                       CASE WHEN t.status = 'completed' THEN t.updated_at ELSE NULL END as completed_at,
                       'task-linked' as followup_type, t.title as task_title
                FROM tasks t 
                WHERE t.type = 'followup'
                  AND (t.title LIKE ? OR t.description LIKE ? OR t.description LIKE ?)
            ";
            
            $searchTerms = [
                '%' . $contact['name'] . '%',
                '%' . $contact['name'] . '%', 
                '%' . $contact['phone'] . '%'
            ];
            
            if (!in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
                $taskSql .= " AND t.assigned_to = ?";
                $searchTerms[] = $_SESSION['user_id'];
            }
            
            $taskStmt = $db->prepare($taskSql . " ORDER BY t.deadline DESC");
            $taskStmt->execute($searchTerms);
            $taskFollowups = $taskStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $followups = array_merge($followups, $taskFollowups);
        }
        
        // Sort by date
        usort($followups, function($a, $b) {
            return strtotime($b['follow_up_date']) - strtotime($a['follow_up_date']);
        });
        
        return $followups;
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