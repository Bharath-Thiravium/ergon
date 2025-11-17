<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class FollowupController extends Controller {
    
    public function index() {
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            // Debug: Log the query being executed
            error_log('FollowupController: Fetching followups for user_id: ' . $_SESSION['user_id']);
            
            // Remove backup system check entries and test entries
            $db->prepare("DELETE FROM followups WHERE title LIKE '%Backup System Check%' OR title LIKE 'Follow-up:%' OR company_name IN ('Tech Solutions Inc', 'ABC Corporation', 'XYZ Ltd', 'HR Department', 'Marketing Team')")->execute();
            
            // Admin/Owner can see all follow-ups, regular users see only their own
            try {
                if (in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
                    $stmt = $db->prepare("SELECT f.*, u.name as assigned_user FROM followups f LEFT JOIN users u ON f.user_id = u.id ORDER BY f.follow_up_date ASC");
                    $stmt->execute();
                } else {
                    $stmt = $db->prepare("SELECT * FROM followups WHERE user_id = ? ORDER BY follow_up_date ASC");
                    $stmt->execute([$_SESSION['user_id']]);
                }
                $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('Followups JOIN query failed, using fallback: ' . $e->getMessage());
                if (in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
                    $stmt = $db->prepare("SELECT * FROM followups ORDER BY follow_up_date ASC");
                    $stmt->execute();
                } else {
                    $stmt = $db->prepare("SELECT * FROM followups WHERE user_id = ? ORDER BY follow_up_date ASC");
                    $stmt->execute([$_SESSION['user_id']]);
                }
                $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Debug: Log the number of followups found
            error_log('FollowupController: Found ' . count($followups) . ' followups');
            
            // Calculate KPIs
            $today = date('Y-m-d');
            $overdue = $today_count = $completed = 0;
            
            foreach ($followups as $followup) {
                if ($followup['status'] === 'completed') {
                    $completed++;
                } elseif ($followup['follow_up_date'] < $today) {
                    $overdue++;
                } elseif ($followup['follow_up_date'] === $today) {
                    $today_count++;
                }
            }
            
            $data = [
                'followups' => $followups,
                'overdue' => $overdue,
                'today_count' => $today_count,
                'completed' => $completed
            ];
            
            // Debug: Log the KPIs
            error_log('FollowupController KPIs - Total: ' . count($followups) . ', Overdue: ' . $overdue . ', Today: ' . $today_count . ', Completed: ' . $completed);
            
        } catch (Exception $e) {
            error_log('Followups error: ' . $e->getMessage());
            error_log('Followups error trace: ' . $e->getTraceAsString());
            $data = ['followups' => [], 'error' => 'Unable to load follow-ups: ' . $e->getMessage()];
        }
        
        $this->view('followups/index', $data);
    }
    
    public function handlePost() {
        
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ergon/followups');
            exit;
        }
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $this->create();
                break;
            case 'complete':
                $this->complete();
                break;
            case 'delete':
                $this->delete();
                break;
            default:
                header('Location: /ergon/followups');
                exit;
        }
    }
    
    public function create() {
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        // Show create form
        $this->view('followups/create');
    }
    
    public function store() {
        
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ergon/followups');
            exit;
        }
        
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            // Validate required fields
            $title = trim($_POST['title'] ?? '');
            $followUpDate = $_POST['follow_up_date'] ?? date('Y-m-d');
            $status = $_POST['status'] ?? 'pending';
            
            if (empty($title)) {
                header('Location: /ergon/followups/create?error=Title is required');
                exit;
            }
            
            $stmt = $db->prepare("INSERT INTO followups (user_id, title, company_name, contact_person, contact_phone, project_name, follow_up_date, original_date, description, status, reminder_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([
                $_SESSION['user_id'],
                $title,
                trim($_POST['company_name'] ?? ''),
                trim($_POST['contact_person'] ?? ''),
                trim($_POST['contact_phone'] ?? ''),
                trim($_POST['project_name'] ?? ''),
                $followUpDate,
                $followUpDate,
                trim($_POST['description'] ?? ''),
                $status,
                $_POST['reminder_time'] ?? null
            ]);
            
            if ($result) {
                $followupId = $db->lastInsertId();
                $this->logHistory($followupId, 'created', null, 'Follow-up created', 'Initial creation');
                
                // Notify owners about new followup
                require_once __DIR__ . '/../helpers/NotificationHelper.php';
                $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    NotificationHelper::notifyOwners(
                        $_SESSION['user_id'],
                        'followup',
                        'created',
                        "{$user['name']} created follow-up: {$title}",
                        $followupId
                    );
                }
                
                header('Location: /ergon/followups?success=Follow-up created successfully');
            } else {
                $errorInfo = $stmt->errorInfo();
                header('Location: /ergon/followups/create?error=Failed to create follow-up: ' . $errorInfo[2]);
            }
        } catch (Exception $e) {
            error_log('Store error: ' . $e->getMessage());
            header('Location: /ergon/followups/create?error=Failed to create follow-up: ' . $e->getMessage());
        }
        exit;
    }
    
    public function createFromPost() {
        // Legacy method for modal form submission
        $this->store();
    }
    
    public function complete() {
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            $followupId = $_POST['id'] ?? $_POST['followup_id'];
            
            // Get follow-up details including task_id
            $stmt = $db->prepare("SELECT task_id FROM followups WHERE id = ? AND user_id = ?");
            $stmt->execute([$followupId, $_SESSION['user_id']]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) {
                header('Location: /ergon/followups?error=Follow-up not found');
                exit;
            }
            
            $db->beginTransaction();
            
            // Update follow-up status
            $stmt = $db->prepare("UPDATE followups SET status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = ?");
            $result = $stmt->execute([$followupId]);
            
            if ($result && $stmt->rowCount() > 0) {
                // If follow-up is linked to a task, update the task as well
                if ($followup['task_id']) {
                    $taskStmt = $db->prepare("UPDATE tasks SET status = 'completed', progress = 100 WHERE id = ?");
                    $taskStmt->execute([$followup['task_id']]);
                    error_log('Task ' . $followup['task_id'] . ' marked as completed due to follow-up completion');
                }
                
                $this->logHistory($followupId, 'completed', 'pending', 'completed', 'Follow-up marked as completed');
                $db->commit();
                header('Location: /ergon/followups?success=Follow-up completed successfully');
            } else {
                $db->rollBack();
                header('Location: /ergon/followups?error=Failed to complete follow-up');
            }
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            error_log('Complete error: ' . $e->getMessage());
            header('Location: /ergon/followups?error=Failed to complete follow-up');
        }
        exit;
    }
    

    
    public function reschedule() {
        
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ergon/followups');
            exit;
        }
        
        $followupId = $_POST['followup_id'] ?? null;
        $newDate = $_POST['new_date'] ?? null;
        $reason = trim($_POST['reason'] ?? 'No reason provided');
        
        if (!$followupId || !$newDate) {
            header('Location: /ergon/followups?error=Missing required data');
            exit;
        }
        
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            // Build time string
            $timeNote = '';
            $newTime = null;
            if (!empty($_POST['hour']) && !empty($_POST['minute']) && !empty($_POST['ampm'])) {
                $hour = str_pad($_POST['hour'], 2, '0', STR_PAD_LEFT);
                $minute = str_pad($_POST['minute'], 2, '0', STR_PAD_LEFT);
                $timeNote = " at {$hour}:{$minute} {$_POST['ampm']}";
                
                // Convert to 24-hour format for database storage
                $hour12 = (int)$_POST['hour'];
                $hour24 = $_POST['ampm'] === 'PM' && $hour12 !== 12 ? $hour12 + 12 : ($hour12 === 12 && $_POST['ampm'] === 'AM' ? 0 : $hour12);
                $newTime = sprintf('%02d:%02d:00', $hour24, (int)$_POST['minute']);
            }
            
            // Get old date
            $stmt = $db->prepare("SELECT follow_up_date FROM followups WHERE id = ? AND user_id = ?");
            $stmt->execute([$followupId, $_SESSION['user_id']]);
            $oldDate = $stmt->fetchColumn();
            
            if (!$oldDate) {
                header('Location: /ergon/followups?error=Follow-up not found');
                exit;
            }
            
            // Start transaction
            $db->beginTransaction();
            
            // Update followup
            $stmt = $db->prepare("UPDATE followups SET follow_up_date = ?, status = 'postponed', updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
            $updateResult = $stmt->execute([$newDate, $followupId, $_SESSION['user_id']]);
            
            if (!$updateResult || $stmt->rowCount() === 0) {
                $db->rollBack();
                header('Location: /ergon/followups?error=Failed to update follow-up');
                exit;
            }
            
            // Update reminder time if provided
            if ($newTime) {
                $updateTimeStmt = $db->prepare("UPDATE followups SET reminder_time = ? WHERE id = ? AND user_id = ?");
                $updateTimeStmt->execute([$newTime, $followupId, $_SESSION['user_id']]);
            }
            
            // Log history
            $historyNote = "Rescheduled from {$oldDate} to {$newDate}{$timeNote}. Reason: {$reason}";
            
            $stmt = $db->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $historyResult = $stmt->execute([$followupId, 'postponed', $oldDate, $newDate . $timeNote, $historyNote, $_SESSION['user_id']]);
            
            if ($historyResult) {
                $db->commit();
                header('Location: /ergon/followups?success=Follow-up rescheduled successfully');
            } else {
                $db->rollBack();
                header('Location: /ergon/followups?error=Failed to log history');
            }
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            error_log('Reschedule error: ' . $e->getMessage());
            header('Location: /ergon/followups?error=Failed to reschedule follow-up: ' . $e->getMessage());
        }
        exit;
    }
    
    public function viewFollowup($id) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            // Admin/Owner can view all follow-ups, regular users see only their own
            if (in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
                $stmt = $db->prepare("SELECT * FROM followups WHERE id = ?");
                $stmt->execute([$id]);
            } else {
                $stmt = $db->prepare("SELECT * FROM followups WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);
            }
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) {
                header('Location: /ergon/followups?error=Follow-up not found');
                exit;
            }
            
            $this->view('followups/view_detail', ['followup' => $followup]);
        } catch (Exception $e) {
            error_log('View error: ' . $e->getMessage());
            header('Location: /ergon/followups?error=Error loading follow-up');
            exit;
        }
    }
    
    public function showReschedule($id) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            $stmt = $db->prepare("SELECT * FROM followups WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) {
                header('Location: /ergon/followups?error=Follow-up not found');
                exit;
            }
            
            $this->view('followups/reschedule', ['followup' => $followup]);
        } catch (Exception $e) {
            error_log('Reschedule view error: ' . $e->getMessage());
            header('Location: /ergon/followups?error=Error loading follow-up');
            exit;
        }
    }
    
    public function showHistory($id) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            $stmt = $db->prepare("SELECT * FROM followups WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) {
                header('Location: /ergon/followups?error=Follow-up not found');
                exit;
            }
            
            $stmt = $db->prepare("SELECT h.*, u.name as user_name FROM followup_history h LEFT JOIN users u ON h.created_by = u.id WHERE h.followup_id = ? ORDER BY h.created_at DESC");
            $stmt->execute([$id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->view('followups/history', ['followup' => $followup, 'history' => $history]);
        } catch (Exception $e) {
            error_log('History view error: ' . $e->getMessage());
            header('Location: /ergon/followups?error=Error loading history');
            exit;
        }
    }
    
    public function getHistory($id) {
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            // Debug: Check if history table exists and has data
            $tableExists = $db->query("SHOW TABLES LIKE 'followup_history'")->fetchColumn();
            error_log("History table exists: " . ($tableExists ? 'YES' : 'NO'));
            
            if ($tableExists) {
                $totalCount = $db->query("SELECT COUNT(*) FROM followup_history")->fetchColumn();
                error_log("Total history records: $totalCount");
                
                $followupCount = $db->prepare("SELECT COUNT(*) FROM followup_history WHERE followup_id = ?");
                $followupCount->execute([$id]);
                $count = $followupCount->fetchColumn();
                error_log("History records for followup $id: $count");
            }
            
            $stmt = $db->prepare("SELECT h.*, u.name as user_name FROM followup_history h LEFT JOIN users u ON h.created_by = u.id WHERE h.followup_id = ? ORDER BY h.created_at DESC");
            $stmt->execute([$id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Retrieved history count: " . count($history));
            
            $html = '';
            if (empty($history)) {
                // Show debug info in development
                $html = '<div class="debug-info">';
                $html .= '<p>No history available for this follow-up.</p>';
                $html .= '<small>Debug: Followup ID = ' . $id . '</small><br>';
                $html .= '<small>Total history records in system: ' . ($totalCount ?? 0) . '</small>';
                $html .= '</div>';
            } else {
                $html = '<div class="history-horizontal">';
                foreach ($history as $entry) {
                    $html .= '<div class="history-card">';
                    $html .= '<div class="history-header">';
                    $html .= '<h4>' . ucfirst($entry['action']) . '</h4>';
                    $html .= '<span class="history-date">' . date('M d, Y H:i', strtotime($entry['created_at'])) . '</span>';
                    $html .= '</div>';
                    $html .= '<div class="history-content">';
                    $html .= '<p>' . htmlspecialchars($entry['notes'] ?? '') . '</p>';
                    if ($entry['old_value'] && $entry['new_value']) {
                        $html .= '<div class="history-change">Changed from: <strong>' . htmlspecialchars($entry['old_value']) . '</strong> to: <strong>' . htmlspecialchars($entry['new_value']) . '</strong></div>';
                    }
                    $html .= '<div class="history-user">By: ' . htmlspecialchars($entry['user_name'] ?? 'Unknown') . '</div>';
                    $html .= '</div></div>';
                }
                $html .= '</div>';
            }
            
            echo json_encode(['success' => true, 'html' => $html]);
        } catch (Exception $e) {
            error_log('History error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    private function ensureTables($db) {
        try {
            // Create followups table with simpler structure for Hostinger
            $createFollowupsSQL = "CREATE TABLE IF NOT EXISTS followups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                task_id INT NULL,
                title VARCHAR(255) NOT NULL,
                company_name VARCHAR(255),
                contact_person VARCHAR(255),
                contact_phone VARCHAR(20),
                project_name VARCHAR(255),
                follow_up_date DATE NOT NULL,
                original_date DATE,
                reminder_time TIME NULL,
                description TEXT,
                status VARCHAR(20) DEFAULT 'pending',
                completed_at TIMESTAMP NULL,
                reminder_sent TINYINT(1) DEFAULT 0,
                next_reminder DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $db->exec($createFollowupsSQL);
            
            // Add indexes separately to avoid issues
            try {
                $db->exec("CREATE INDEX IF NOT EXISTS idx_user_id ON followups (user_id)");
                $db->exec("CREATE INDEX IF NOT EXISTS idx_task_id ON followups (task_id)");
                $db->exec("CREATE INDEX IF NOT EXISTS idx_follow_date ON followups (follow_up_date)");
                $db->exec("CREATE INDEX IF NOT EXISTS idx_status ON followups (status)");
            } catch (Exception $e) {
                error_log('Index creation error (non-critical): ' . $e->getMessage());
            }
            
            // Add task_id column if it doesn't exist
            try {
                $columns = $db->query("SHOW COLUMNS FROM followups")->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('task_id', $columns)) {
                    $db->exec("ALTER TABLE followups ADD COLUMN task_id INT NULL AFTER user_id");
                    $db->exec("ALTER TABLE followups ADD INDEX idx_task_id (task_id)");
                }
            } catch (Exception $e) {
                error_log('Task ID column addition error: ' . $e->getMessage());
            }
            
            // Add reminder_time column if it doesn't exist
            try {
                $columns = $db->query("SHOW COLUMNS FROM followups")->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('reminder_time', $columns)) {
                    $db->exec("ALTER TABLE followups ADD COLUMN reminder_time TIME NULL AFTER original_date");
                }
            } catch (Exception $e) {
                error_log('Column addition error: ' . $e->getMessage());
            }
            
            // Create history table with simpler structure
            $createHistorySQL = "CREATE TABLE IF NOT EXISTS followup_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                followup_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                old_value TEXT,
                new_value TEXT,
                notes TEXT,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $db->exec($createHistorySQL);
            
            // Add index separately
            try {
                $db->exec("CREATE INDEX IF NOT EXISTS idx_followup_id ON followup_history (followup_id)");
            } catch (Exception $e) {
                error_log('History index creation error (non-critical): ' . $e->getMessage());
            }
            
            // Fix column name if it exists as action_type
            try {
                $columns = $db->query("SHOW COLUMNS FROM followup_history")->fetchAll(PDO::FETCH_COLUMN);
                if (in_array('action_type', $columns) && !in_array('action', $columns)) {
                    $db->exec("ALTER TABLE followup_history CHANGE action_type action VARCHAR(50) NOT NULL");
                    error_log('Fixed followup_history column: action_type -> action');
                }
            } catch (Exception $e) {
                error_log('Column fix error: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            error_log('Table creation error: ' . $e->getMessage());
        }
    }
    
    private function logHistory($followupId, $action, $oldValue = null, $newValue = null, $notes = null) {
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            error_log("Logging history: ID={$followupId}, Action={$action}, User={$_SESSION['user_id']}");
            
            $stmt = $db->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$followupId, $action, $oldValue, $newValue, $notes, $_SESSION['user_id']]);
            
            if ($result) {
                error_log("History logged successfully: ID={$db->lastInsertId()}");
            } else {
                error_log("History logging failed: " . implode(', ', $stmt->errorInfo()));
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('History log error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            // Check if user owns this follow-up or is admin/owner
            $stmt = $db->prepare("SELECT user_id FROM followups WHERE id = ?");
            $stmt->execute([$id]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) {
                echo json_encode(['success' => false, 'message' => 'Follow-up not found']);
                exit;
            }
            
            // Allow deletion if user owns it or is admin/owner
            $canDelete = ($followup['user_id'] == $_SESSION['user_id']) || 
                        in_array($_SESSION['role'] ?? '', ['admin', 'owner']);
            
            if (!$canDelete) {
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit;
            }
            
            // Delete the follow-up
            $stmt = $db->prepare("DELETE FROM followups WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                // Log the deletion
                $this->logHistory($id, 'deleted', null, null, 'Follow-up deleted by user ' . $_SESSION['user_id']);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Delete failed']);
            }
        } catch (Exception $e) {
            error_log('Follow-up delete error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
        exit;
    }
    
    public function phoneConsolidated() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            // Remove backup system check entries and test entries
            $db->prepare("DELETE FROM followups WHERE title LIKE '%Backup System Check%' OR title LIKE 'Follow-up:%' OR company_name IN ('Tech Solutions Inc', 'ABC Corporation', 'XYZ Ltd', 'HR Department', 'Marketing Team')")->execute();
            
            // Get followups for phone consolidation
            if (in_array($_SESSION['role'] ?? '', ['admin', 'owner'])) {
                $stmt = $db->prepare("SELECT f.*, u.name as assigned_user FROM followups f LEFT JOIN users u ON f.user_id = u.id ORDER BY f.follow_up_date ASC");
                $stmt->execute();
            } else {
                $stmt = $db->prepare("SELECT * FROM followups WHERE user_id = ? ORDER BY follow_up_date ASC");
                $stmt->execute([$_SESSION['user_id']]);
            }
            $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = ['followups' => $followups];
            $this->view('followups/phone_consolidated', $data);
        } catch (Exception $e) {
            error_log('Phone consolidated error: ' . $e->getMessage());
            $this->view('followups/phone_consolidated', ['followups' => []]);
        }
    }
    
    public function checkReminders() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            // Get today's reminders
            $stmt = $db->prepare("
                SELECT f.*, u.name as user_name 
                FROM followups f 
                LEFT JOIN users u ON f.user_id = u.id 
                WHERE f.follow_up_date = CURDATE() 
                AND f.status IN ('pending', 'in_progress')
                ORDER BY f.reminder_time ASC
            ");
            $stmt->execute();
            $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'reminders' => $reminders,
                'count' => count($reminders)
            ]);
        } catch (Exception $e) {
            error_log('Check reminders error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'reminders' => [],
                'count' => 0
            ]);
        }
        exit;
    }
    
    private function getStatusBadge($status) {
        switch ($status) {
            case 'completed':
                return 'success';
            case 'postponed':
            case 'rescheduled':
                return 'warning';
            case 'cancelled':
                return 'danger';
            default:
                return 'info';
        }
    }
}
?>