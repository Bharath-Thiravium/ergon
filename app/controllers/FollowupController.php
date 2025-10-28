<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class FollowupController extends Controller {
    
    public function index() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            $stmt = $db->prepare("SELECT * FROM followups WHERE user_id = ? ORDER BY follow_up_date ASC");
            $stmt->execute([$_SESSION['user_id']]);
            $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
        } catch (Exception $e) {
            error_log('Followups error: ' . $e->getMessage());
            $data = ['followups' => [], 'error' => 'Unable to load follow-ups'];
        }
        
        $this->view('followups/index', $data);
    }
    
    public function handlePost() {
        session_start();
        
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
            default:
                header('Location: /ergon/followups');
                exit;
        }
    }
    
    public function create() {
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            $stmt = $db->prepare("INSERT INTO followups (user_id, title, company_name, contact_person, contact_phone, project_name, follow_up_date, original_date, description, next_reminder, reminder_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $result = $stmt->execute([
                $_SESSION['user_id'],
                trim($_POST['title'] ?? ''),
                trim($_POST['company_name'] ?? ''),
                trim($_POST['contact_person'] ?? ''),
                trim($_POST['contact_phone'] ?? ''),
                trim($_POST['project_name'] ?? ''),
                $_POST['follow_up_date'] ?? date('Y-m-d'),
                $_POST['follow_up_date'] ?? date('Y-m-d'),
                trim($_POST['description'] ?? ''),
                !empty($_POST['next_reminder']) ? $_POST['next_reminder'] : null,
                !empty($_POST['reminder_time']) ? $_POST['reminder_time'] : null
            ]);
            
            if ($result) {
                $followupId = $db->lastInsertId();
                $this->logHistory($followupId, 'created', null, 'Follow-up created', 'Initial creation');
                header('Location: /ergon/followups?success=Follow-up created successfully');
            } else {
                header('Location: /ergon/followups?error=Failed to create follow-up');
            }
        } catch (Exception $e) {
            error_log('Create error: ' . $e->getMessage());
            header('Location: /ergon/followups?error=Failed to create follow-up');
        }
        exit;
    }
    
    public function complete() {
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            $followupId = $_POST['id'] ?? $_POST['followup_id'];
            
            $stmt = $db->prepare("UPDATE followups SET status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([$followupId, $_SESSION['user_id']]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logHistory($followupId, 'completed', 'pending', 'completed', 'Follow-up marked as completed');
                header('Location: /ergon/followups?success=Follow-up completed successfully');
            } else {
                header('Location: /ergon/followups?error=Failed to complete follow-up');
            }
        } catch (Exception $e) {
            error_log('Complete error: ' . $e->getMessage());
            header('Location: /ergon/followups?error=Failed to complete follow-up');
        }
        exit;
    }
    
    public function reschedule() {
        session_start();
        
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
            if (!empty($_POST['hour']) && !empty($_POST['minute']) && !empty($_POST['ampm'])) {
                $hour = str_pad($_POST['hour'], 2, '0', STR_PAD_LEFT);
                $minute = str_pad($_POST['minute'], 2, '0', STR_PAD_LEFT);
                $timeNote = " at {$hour}:{$minute} {$_POST['ampm']}";
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
            
            // Log history with detailed debugging
            $historyNote = "Rescheduled from {$oldDate} to {$newDate}{$timeNote}. Reason: {$reason}";
            
            error_log("Attempting to insert history: followup_id=$followupId, user_id={$_SESSION['user_id']}");
            
            $stmt = $db->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $historyResult = $stmt->execute([$followupId, 'postponed', $oldDate, $newDate . $timeNote, $historyNote, $_SESSION['user_id']]);
            
            if ($historyResult) {
                $historyId = $db->lastInsertId();
                error_log("History inserted successfully with ID: $historyId");
                
                // Verify insertion
                $verify = $db->prepare("SELECT COUNT(*) FROM followup_history WHERE id = ?");
                $verify->execute([$historyId]);
                $exists = $verify->fetchColumn();
                error_log("History record verification: " . ($exists ? 'EXISTS' : 'NOT FOUND'));
                
                $db->commit();
                header('Location: /ergon/followups?success=Follow-up rescheduled successfully');
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("History insertion failed: " . implode(', ', $errorInfo));
                $db->rollBack();
                header('Location: /ergon/followups?error=Failed to log history: ' . $errorInfo[2]);
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
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo '<p>Unauthorized access</p>';
            return;
        }
        
        try {
            $db = Database::connect();
            $this->ensureTables($db);
            
            $stmt = $db->prepare("SELECT * FROM followups WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) {
                echo '<p>Follow-up not found</p>';
                return;
            }
            
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!DOCTYPE html>';
            echo '<div class="followup-details">';
            echo '<div class="detail-grid">';
            echo '<div class="detail-item"><strong>Title:</strong> ' . htmlspecialchars($followup['title']) . '</div>';
            echo '<div class="detail-item"><strong>Company:</strong> ' . htmlspecialchars($followup['company_name'] ?? '-') . '</div>';
            echo '<div class="detail-item"><strong>Contact:</strong> ' . htmlspecialchars($followup['contact_person'] ?? '-') . '</div>';
            echo '<div class="detail-item"><strong>Phone:</strong> ' . htmlspecialchars($followup['contact_phone'] ?? '-') . '</div>';
            echo '<div class="detail-item"><strong>Project:</strong> ' . htmlspecialchars($followup['project_name'] ?? '-') . '</div>';
            echo '<div class="detail-item"><strong>Due Date:</strong> ' . date('M d, Y', strtotime($followup['follow_up_date'])) . '</div>';
            echo '<div class="detail-item"><strong>Status:</strong> <span class="badge badge--' . $this->getStatusBadge($followup['status']) . '">' . ucfirst($followup['status']) . '</span></div>';
            echo '<div class="detail-item"><strong>Created:</strong> ' . date('M d, Y H:i', strtotime($followup['created_at'])) . '</div>';
            echo '</div>';
            if ($followup['description']) {
                echo '<div class="detail-description"><strong>Description:</strong><p>' . htmlspecialchars($followup['description']) . '</p></div>';
            }
            echo '</div>';
        } catch (Exception $e) {
            error_log('View error: ' . $e->getMessage());
            echo '<p>Error loading follow-up details</p>';
        }
    }
    
    public function getHistory($id) {
        session_start();
        
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
                $html = '<div class="timeline">';
                foreach ($history as $entry) {
                    $html .= '<div class="timeline-item">';
                    $html .= '<div class="timeline-marker"></div>';
                    $html .= '<div class="timeline-content">';
                    $html .= '<h4>' . ucfirst($entry['action']) . '</h4>';
                    $html .= '<p>' . htmlspecialchars($entry['notes'] ?? '') . '</p>';
                    if ($entry['old_value'] && $entry['new_value']) {
                        $html .= '<small>Changed from: ' . htmlspecialchars($entry['old_value']) . ' to: ' . htmlspecialchars($entry['new_value']) . '</small><br>';
                    }
                    $html .= '<small>By: ' . htmlspecialchars($entry['user_name'] ?? 'Unknown') . ' on ' . date('M d, Y H:i', strtotime($entry['created_at'])) . '</small>';
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
            // Create followups table
            $db->exec("CREATE TABLE IF NOT EXISTS followups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                company_name VARCHAR(255),
                contact_person VARCHAR(255),
                contact_phone VARCHAR(20),
                project_name VARCHAR(255),
                follow_up_date DATE NOT NULL,
                original_date DATE,
                description TEXT,
                status ENUM('pending','in_progress','completed','postponed','cancelled','rescheduled') DEFAULT 'pending',
                completed_at TIMESTAMP NULL,
                reminder_sent BOOLEAN DEFAULT FALSE,
                next_reminder DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_follow_date (follow_up_date),
                INDEX idx_status (status)
            )");
            
            // Create history table
            $db->exec("CREATE TABLE IF NOT EXISTS followup_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                followup_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                old_value TEXT,
                new_value TEXT,
                notes TEXT,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_followup_id (followup_id)
            )");
            
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