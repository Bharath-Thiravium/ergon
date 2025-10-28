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
            
            $this->ensureFollowupsTable($db);
            $this->ensureFollowupHistoryTable($db);
            
            $stmt = $db->prepare("SELECT * FROM followups WHERE user_id = ? ORDER BY follow_up_date ASC");
            $stmt->execute([$_SESSION['user_id']]);
            $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate KPIs
            $today = date('Y-m-d');
            $overdue = 0;
            $today_count = 0;
            $completed = 0;
            
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
            error_log('Followups index error: ' . $e->getMessage());
            $data = ['followups' => [], 'error' => 'Unable to load follow-ups'];
        }
        
        $this->view('followups/index', $data);
    }
    
    private function ensureFollowupsTable($db) {
        try {
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
                status ENUM('pending', 'in_progress', 'completed', 'postponed', 'cancelled', 'rescheduled') DEFAULT 'pending',
                completed_at TIMESTAMP NULL,
                reminder_sent BOOLEAN DEFAULT FALSE,
                next_reminder DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_follow_date (follow_up_date),
                INDEX idx_status (status),
                INDEX idx_reminder (next_reminder)
            )");
            
            // Add missing columns if they don't exist
            try {
                $columns = $db->query("SHOW COLUMNS FROM followups")->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('reminder_sent', $columns)) {
                    $db->exec("ALTER TABLE followups ADD COLUMN reminder_sent BOOLEAN DEFAULT FALSE");
                }
                if (!in_array('next_reminder', $columns)) {
                    $db->exec("ALTER TABLE followups ADD COLUMN next_reminder DATE NULL");
                }
            } catch (Exception $e) {
                // Columns might already exist
            }
            
            // Ensure followup_history table exists
            $db->exec("CREATE TABLE IF NOT EXISTS followup_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                followup_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                old_value TEXT,
                new_value TEXT,
                notes TEXT,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_followup_id (followup_id),
                INDEX idx_created_at (created_at)
            )");
        } catch (Exception $e) {
            error_log('Failed to create followups table: ' . $e->getMessage());
        }
    }
    
    private function ensureFollowupHistoryTable($db) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS followup_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                followup_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                old_value TEXT,
                new_value TEXT,
                notes TEXT,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_followup_id (followup_id),
                INDEX idx_created_at (created_at)
            )");
        } catch (Exception $e) {
            error_log('Failed to create followup_history table: ' . $e->getMessage());
        }
    }
    
    private function logHistory($followupId, $action, $oldValue = null, $newValue = null, $notes = null) {
        try {
            $db = Database::connect();
            $this->ensureFollowupHistoryTable($db);
            
            error_log('Attempting to log history - Followup ID: ' . $followupId . ', Action: ' . $action . ', User: ' . $_SESSION['user_id']);
            
            $stmt = $db->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$followupId, $action, $oldValue, $newValue, $notes, $_SESSION['user_id']]);
            
            error_log('History insert result: ' . ($result ? 'SUCCESS' : 'FAILED') . ' for followup ID: ' . $followupId);
            
            if (!$result) {
                error_log('History insert failed - SQL Error: ' . implode(', ', $stmt->errorInfo()));
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Failed to log followup history: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    public function handlePost() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        } else {
            header('Location: /ergon/followups');
            exit;
        }
    }
    
    public function create() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $db = Database::connect();
            $this->ensureFollowupsTable($db);
            
            $stmt = $db->prepare("INSERT INTO followups (user_id, title, company_name, contact_person, contact_phone, project_name, follow_up_date, original_date, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $result = $stmt->execute([
                $_SESSION['user_id'],
                trim($_POST['title'] ?? ''),
                trim($_POST['company_name'] ?? ''),
                trim($_POST['contact_person'] ?? ''),
                trim($_POST['contact_phone'] ?? ''),
                trim($_POST['project_name'] ?? ''),
                $_POST['follow_up_date'] ?? date('Y-m-d'),
                $_POST['follow_up_date'] ?? date('Y-m-d'),
                trim($_POST['description'] ?? '')
            ]);
            
            if ($result) {
                header('Location: /ergon/followups?success=Follow-up created successfully');
            } else {
                header('Location: /ergon/followups?error=Failed to create follow-up');
            }
            exit;
        } catch (Exception $e) {
            error_log('Followup create error: ' . $e->getMessage());
            header('Location: /ergon/followups?error=Failed to create follow-up');
            exit;
        }
        
        header('Location: /ergon/followups');
        exit;
    }
    
    public function viewFollowup($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo '<p>Unauthorized access</p>';
            return;
        }
        
        try {
            $db = Database::connect();
            $this->ensureFollowupsTable($db);
            
            $stmt = $db->prepare("SELECT * FROM followups WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$followup) {
                echo '<p>Follow-up not found</p>';
                return;
            }
            
            // Return HTML content for modal
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
            echo '<div class="detail-item"><strong>Status:</strong> <span class="badge badge--' . ($followup['status'] === 'completed' ? 'success' : (in_array($followup['status'], ['postponed', 'rescheduled']) ? 'warning' : 'info')) . '">' . ucfirst($followup['status']) . '</span></div>';
            echo '<div class="detail-item"><strong>Created:</strong> ' . date('M d, Y H:i', strtotime($followup['created_at'])) . '</div>';
            echo '</div>';
            if ($followup['description']) {
                echo '<div class="detail-description"><strong>Description:</strong><p>' . htmlspecialchars($followup['description']) . '</p></div>';
            }
            echo '</div>';
            
        } catch (Exception $e) {
            error_log('Followup viewFollowup error: ' . $e->getMessage());
            echo '<p>Error loading follow-up details</p>';
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
                $this->ensureFollowupsTable($db);
                
                $stmt = $db->prepare("UPDATE followups SET title = ?, company_name = ?, contact_person = ?, contact_phone = ?, project_name = ?, follow_up_date = ?, description = ?, status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([
                    trim($_POST['title'] ?? ''),
                    trim($_POST['company_name'] ?? ''),
                    trim($_POST['contact_person'] ?? ''),
                    trim($_POST['contact_phone'] ?? ''),
                    trim($_POST['project_name'] ?? ''),
                    $_POST['follow_up_date'] ?? date('Y-m-d'),
                    trim($_POST['description'] ?? ''),
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
                error_log('Followup update error: ' . $e->getMessage());
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
        
        try {
            $db = Database::connect();
            $this->ensureFollowupsTable($db);
            
            $stmt = $db->prepare("UPDATE followups SET status = 'completed', completed_at = NOW(), updated_at = NOW() WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([
                $_POST['id'] ?? $_POST['followup_id'],
                $_SESSION['user_id']
            ]);
            
            if ($result) {
                header('Location: /ergon/followups?success=Follow-up marked as completed');
            } else {
                header('Location: /ergon/followups?error=Failed to complete follow-up');
            }
            exit;
        } catch (Exception $e) {
            error_log('Followup complete error: ' . $e->getMessage());
            header('Location: /ergon/followups?error=Failed to complete follow-up');
            exit;
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
                $this->ensureFollowupsTable($db);
                $this->ensureFollowupHistoryTable($db);
                
                $followupId = $_POST['followup_id'];
                $newDate = $_POST['new_date'];
                
                // Build time string if provided
                $timeNote = '';
                if (!empty($_POST['hour']) && !empty($_POST['minute']) && !empty($_POST['ampm'])) {
                    $timeNote = ' at ' . $_POST['hour'] . ':' . str_pad($_POST['minute'], 2, '0', STR_PAD_LEFT) . ' ' . $_POST['ampm'];
                }
                
                // Get old date for history
                $stmt = $db->prepare("SELECT follow_up_date FROM followups WHERE id = ? AND user_id = ?");
                $stmt->execute([$followupId, $_SESSION['user_id']]);
                $oldDate = $stmt->fetchColumn();
                
                if (!$oldDate) {
                    header('Location: /ergon/followups?error=Follow-up not found');
                    exit;
                }
                
                // Debug logging
                error_log('Reschedule attempt - ID: ' . $followupId . ', New Date: ' . $newDate . ', User: ' . $_SESSION['user_id']);
                
                // Update followup
                $stmt = $db->prepare("UPDATE followups SET follow_up_date = ?, status = 'postponed', updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$newDate, $followupId, $_SESSION['user_id']]);
                
                error_log('Update result: ' . ($result ? 'SUCCESS' : 'FAILED') . ', Rows affected: ' . $stmt->rowCount());
                
                if ($result && $stmt->rowCount() > 0) {
                    $reason = $_POST['reason'] ?? 'No reason provided';
                    $historyNote = 'Rescheduled from ' . $oldDate . ' to ' . $newDate . $timeNote . '. Reason: ' . $reason;
                    
                    // Log history
                    $historyResult = $this->logHistory($followupId, 'postponed', $oldDate, $newDate . $timeNote, $historyNote);
                    error_log('History logging result: ' . ($historyResult ? 'SUCCESS' : 'FAILED'));
                    
                    header('Location: /ergon/followups?success=Follow-up rescheduled successfully');
                } else {
                    error_log('Reschedule failed - no rows affected or query failed');
                    header('Location: /ergon/followups?error=Failed to reschedule follow-up - record not found or no changes made');
                }
                exit;
            } catch (Exception $e) {
                error_log('Followup reschedule error: ' . $e->getMessage());
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
                $this->ensureFollowupsTable($db);
                
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
                error_log('Followup delete error: ' . $e->getMessage());
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
                $this->ensureFollowupsTable($db);
                
                $stmt = $db->prepare("UPDATE followups SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([
                    $_POST['status'],
                    $_POST['followup_id'],
                    $_SESSION['user_id']
                ]);
                
                echo json_encode(['success' => $result]);
            } catch (Exception $e) {
                error_log('Followup updateItem error: ' . $e->getMessage());
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
                $this->ensureFollowupsTable($db);
                
                $stmt = $db->prepare("INSERT INTO followups (user_id, title, description, follow_up_date, original_date, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
                $result = $stmt->execute([
                    $_SESSION['user_id'],
                    'Follow-up: ' . ($_POST['task_title'] ?? 'Task'),
                    $_POST['description'] ?? '',
                    $_POST['follow_date'] ?? date('Y-m-d', strtotime('+1 day')),
                    $_POST['follow_date'] ?? date('Y-m-d', strtotime('+1 day'))
                ]);
                
                echo json_encode(['success' => $result, 'followup_id' => $db->lastInsertId()]);
            } catch (Exception $e) {
                error_log('Followup createFromTask error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }
    
    public function getHistory($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        try {
            $db = Database::connect();
            $this->ensureFollowupHistoryTable($db);
            
            $stmt = $db->prepare("SELECT h.*, u.name as user_name FROM followup_history h LEFT JOIN users u ON h.created_by = u.id WHERE h.followup_id = ? ORDER BY h.created_at DESC");
            $stmt->execute([$id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $html = '';
            if (empty($history)) {
                $html = '<p>No history available for this follow-up.</p>';
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
            error_log('Followup getHistory error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
?>