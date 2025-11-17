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
            
            $this->view('contact_followups/view_contact', [
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
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->storeStandaloneFollowup();
        }
        
        $db = Database::connect();
        
        // Get contacts
        $stmt = $db->prepare("SELECT * FROM contacts ORDER BY name");
        $stmt->execute();
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get available tasks for task-linked follow-ups
        $taskSql = "SELECT id, title, description, deadline as due_date FROM tasks WHERE status != 'completed'";
        if (!in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
            $taskSql .= " AND assigned_to = ?";
            $stmt = $db->prepare($taskSql . " ORDER BY deadline ASC");
            $stmt->execute([$_SESSION['user_id']]);
        } else {
            $stmt = $db->prepare($taskSql . " ORDER BY deadline ASC");
            $stmt->execute();
        }
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->view('contact_followups/create', ['contacts' => $contacts, 'tasks' => $tasks]);
    }
    
    private function storeStandaloneFollowup() {
        try {
            $db = Database::connect();
            
            $title = trim($_POST['title'] ?? '');
            $contact_id = $_POST['contact_id'] ?? null;
            $follow_up_date = $_POST['follow_up_date'] ?? date('Y-m-d');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($title) || !$contact_id) {
                header('Location: /ergon/contacts/followups/create?error=Title and contact required');
                exit;
            }
            
            $stmt = $db->prepare("INSERT INTO followups (user_id, contact_id, title, description, follow_up_date) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$_SESSION['user_id'], $contact_id, $title, $description, $follow_up_date]);
            
            if ($result) {
                $followupId = $db->lastInsertId();
                $this->logHistory($followupId, 'created', null, 'Standalone follow-up created');
                
                NotificationHelper::notifyOwners(
                    $_SESSION['user_id'],
                    'followup',
                    'created',
                    "New follow-up created: {$title}",
                    $followupId
                );
                
                header('Location: /ergon/contacts/followups?success=Follow-up created');
            } else {
                header('Location: /ergon/contacts/followups/create?error=Failed to create');
            }
        } catch (Exception $e) {
            error_log('Store followup error: ' . $e->getMessage());
            header('Location: /ergon/contacts/followups/create?error=Failed to create');
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
            $stmt = $db->prepare("SELECT contact_id FROM followups WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
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
                $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ? AND type = 'followup' AND assigned_to = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);
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
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            $db = Database::connect();
            $reason = trim($_POST['reason'] ?? 'No reason provided');
            
            // Check if it's a standalone followup
            $stmt = $db->prepare("SELECT contact_id FROM followups WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($followup) {
                // Cancel standalone followup
                $stmt = $db->prepare("UPDATE followups SET status = 'cancelled' WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result) {
                    $this->logHistory($id, 'cancelled', 'pending', "Follow-up cancelled. Reason: {$reason}");
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to cancel']);
                }
            } else {
                // Check if it's a task-linked followup
                $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ? AND type = 'followup' AND assigned_to = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);
                $task = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($task) {
                    $stmt = $db->prepare("UPDATE tasks SET status = 'cancelled' WHERE id = ?");
                    $result = $stmt->execute([$id]);
                    
                    if ($result) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to cancel task']);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Follow-up not found']);
                }
            }
        } catch (Exception $e) {
            error_log('Cancel followup error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to cancel']);
        }
        exit;
    }
    
    public function rescheduleFollowup($id) {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ergon/contacts/followups');
            exit;
        }
        
        try {
            $db = Database::connect();
            $newDate = $_POST['new_date'] ?? null;
            $reason = trim($_POST['reason'] ?? 'No reason provided');
            
            if (!$newDate) {
                header('Location: /ergon/contacts/followups?error=New date required');
                exit;
            }
            
            // Check if it's a standalone followup
            $stmt = $db->prepare("SELECT follow_up_date FROM followups WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $oldDate = $stmt->fetchColumn();
            
            if ($oldDate) {
                // Reschedule standalone followup
                $stmt = $db->prepare("UPDATE followups SET follow_up_date = ?, status = 'postponed' WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$newDate, $id, $_SESSION['user_id']]);
                
                if ($result) {
                    $this->logHistory($id, 'rescheduled', $oldDate, "Rescheduled to {$newDate}. Reason: {$reason}");
                    header('Location: /ergon/contacts/followups?success=Rescheduled successfully');
                } else {
                    header('Location: /ergon/contacts/followups?error=Failed to reschedule');
                }
            } else {
                // Check if it's a task-linked followup
                $stmt = $db->prepare("SELECT deadline FROM tasks WHERE id = ? AND type = 'followup' AND assigned_to = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);
                $oldTaskDate = $stmt->fetchColumn();
                
                if ($oldTaskDate) {
                    $stmt = $db->prepare("UPDATE tasks SET deadline = ? WHERE id = ?");
                    $result = $stmt->execute([$newDate, $id]);
                    
                    if ($result) {
                        header('Location: /ergon/contacts/followups?success=Task rescheduled successfully');
                    } else {
                        header('Location: /ergon/contacts/followups?error=Failed to reschedule task');
                    }
                } else {
                    header('Location: /ergon/contacts/followups?error=Follow-up not found');
                }
            }
        } catch (Exception $e) {
            error_log('Reschedule error: ' . $e->getMessage());
            header('Location: /ergon/contacts/followups?error=Failed to reschedule');
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
            $stmt = $db->prepare("SELECT id FROM followups WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
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
                $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ? AND type = 'followup' AND assigned_to = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);
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
        $html = '<div class="history-timeline">';
        foreach ($history as $entry) {
            $html .= '<div class="history-item">';
            $html .= '<div class="history-date">' . date('M d, Y H:i', strtotime($entry['created_at'])) . '</div>';
            $html .= '<div class="history-action">' . ucfirst($entry['action']) . '</div>';
            $html .= '<div class="history-notes">' . htmlspecialchars($entry['notes'] ?? '') . '</div>';
            $html .= '<div class="history-user">By: ' . htmlspecialchars($entry['user_name'] ?? 'Unknown') . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }
    
    private function logHistory($followupId, $action, $oldValue = null, $notes = null) {
        try {
            $db = Database::connect();
            $this->ensureFollowupHistoryTable($db);
            $stmt = $db->prepare("INSERT INTO followup_history (followup_id, action, old_value, notes, created_by) VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([$followupId, $action, $oldValue, $notes, $_SESSION['user_id']]);
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