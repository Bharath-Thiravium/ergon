<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/SyncService.php';

class AutomationScheduler {
    private $db;
    private $syncService;
    
    public function __construct() {
        $this->db = Database::connect();
        $this->syncService = new SyncService();
    }
    
    public function runScheduledTasks() {
        try {
            $this->executeCarryForwardAutomation();
            $this->executeSLAMonitoring();
            $this->executeAutoEscalation();
            $this->executeSmartNotifications();
            $this->cleanupOldRecords();
            
            $this->logExecution('scheduled_tasks', 'success', 'All scheduled tasks completed');
        } catch (Exception $e) {
            $this->logExecution('scheduled_tasks', 'failed', $e->getMessage());
            error_log('Automation scheduler error: ' . $e->getMessage());
        }
    }
    
    private function executeCarryForwardAutomation() {
        // Get all active users
        $stmt = $this->db->prepare("SELECT id FROM users WHERE status = 'active'");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $today = date('Y-m-d');
        $carriedTotal = 0;
        
        foreach ($users as $userId) {
            try {
                $carriedItems = $this->syncService->executeSmartCarryForward($userId, $today);
                $carriedTotal += count($carriedItems);
                
                if (count($carriedItems) > 0) {
                    $this->createNotification($userId, 'carry_forward', 
                        'Daily Carry Forward', 
                        count($carriedItems) . ' items carried forward to today');
                }
            } catch (Exception $e) {
                error_log("Carry forward failed for user $userId: " . $e->getMessage());
            }
        }
        
        $this->logExecution('carry_forward', 'success', "Carried forward $carriedTotal items");
    }
    
    private function executeSLAMonitoring() {
        // Monitor tasks approaching SLA breach
        $stmt = $this->db->prepare("
            SELECT t.*, u.name as user_name, 
                   TIMESTAMPDIFF(HOUR, t.created_at, NOW()) as hours_elapsed,
                   (t.sla_hours * 0.8) as warning_threshold
            FROM tasks t
            JOIN users u ON t.assigned_to = u.id
            WHERE t.status IN ('assigned', 'in_progress')
            AND TIMESTAMPDIFF(HOUR, t.created_at, NOW()) >= (t.sla_hours * 0.8)
            AND t.sla_hours > 0
        ");
        $stmt->execute();
        $slaWarnings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($slaWarnings as $task) {
            $hoursRemaining = $task['sla_hours'] - $task['hours_elapsed'];
            
            if ($hoursRemaining <= 2 && $hoursRemaining > 0) {
                // Critical SLA warning
                $this->createNotification($task['assigned_to'], 'sla_critical',
                    'SLA Breach Warning',
                    "Task '{$task['title']}' will breach SLA in {$hoursRemaining} hours");
                
                // Notify manager if exists
                $this->notifyManager($task['assigned_to'], 'sla_warning', $task);
            } elseif ($hoursRemaining <= 0) {
                // SLA already breached
                $this->createNotification($task['assigned_to'], 'sla_breach',
                    'SLA Breached',
                    "Task '{$task['title']}' has breached SLA by " . abs($hoursRemaining) . " hours");
                
                // Auto-escalate priority
                $this->db->prepare("UPDATE tasks SET priority = 'high', auto_escalated = TRUE WHERE id = ?")
                         ->execute([$task['id']]);
            }
        }
        
        $this->logExecution('sla_monitoring', 'success', count($slaWarnings) . ' SLA warnings processed');
    }
    
    private function executeAutoEscalation() {
        // Auto-escalate overdue tasks
        $stmt = $this->db->prepare("
            UPDATE tasks SET 
                priority = CASE 
                    WHEN priority = 'low' THEN 'medium'
                    WHEN priority = 'medium' THEN 'high'
                    ELSE priority
                END,
                auto_escalated = TRUE,
                last_escalation_date = CURDATE()
            WHERE deadline < CURDATE()
            AND status IN ('assigned', 'in_progress')
            AND (last_escalation_date IS NULL OR last_escalation_date < CURDATE())
        ");
        $escalatedTasks = $stmt->execute();
        $escalatedCount = $stmt->rowCount();
        
        // Auto-escalate overdue follow-ups
        $stmt = $this->db->prepare("
            UPDATE followups SET 
                escalation_level = escalation_level + 1,
                status = CASE 
                    WHEN status = 'pending' THEN 'in_progress'
                    ELSE status
                END
            WHERE follow_up_date < CURDATE()
            AND status IN ('pending', 'in_progress')
            AND escalation_level < 3
        ");
        $stmt->execute();
        $escalatedFollowups = $stmt->rowCount();
        
        $this->logExecution('auto_escalation', 'success', 
            "Escalated $escalatedCount tasks and $escalatedFollowups follow-ups");
    }
    
    private function executeSmartNotifications() {
        // Send daily digest notifications
        $this->sendDailyDigest();
        
        // Send overdue reminders
        $this->sendOverdueReminders();
        
        // Send upcoming deadline alerts
        $this->sendUpcomingDeadlineAlerts();
    }
    
    private function sendDailyDigest() {
        $stmt = $this->db->prepare("
            SELECT u.id, u.name, u.email,
                   COUNT(CASE WHEN t.status IN ('assigned', 'in_progress') THEN 1 END) as pending_tasks,
                   COUNT(CASE WHEN f.status IN ('pending', 'in_progress') THEN 1 END) as pending_followups,
                   COUNT(CASE WHEN t.deadline = CURDATE() THEN 1 END) as due_today
            FROM users u
            LEFT JOIN tasks t ON u.id = t.assigned_to
            LEFT JOIN followups f ON u.id = f.user_id
            WHERE u.status = 'active'
            GROUP BY u.id, u.name, u.email
            HAVING pending_tasks > 0 OR pending_followups > 0 OR due_today > 0
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            $message = "Daily Summary: {$user['pending_tasks']} pending tasks, " .
                      "{$user['pending_followups']} follow-ups, {$user['due_today']} due today";
            
            $this->createNotification($user['id'], 'daily_digest', 'Daily Summary', $message);
        }
    }
    
    private function sendOverdueReminders() {
        // Tasks overdue reminders
        $stmt = $this->db->prepare("
            SELECT t.*, u.name as user_name
            FROM tasks t
            JOIN users u ON t.assigned_to = u.id
            WHERE t.deadline < CURDATE()
            AND t.status IN ('assigned', 'in_progress')
            AND DATEDIFF(CURDATE(), t.deadline) IN (1, 3, 7)
        ");
        $stmt->execute();
        $overdueTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($overdueTasks as $task) {
            $daysOverdue = (new DateTime())->diff(new DateTime($task['deadline']))->days;
            $this->createNotification($task['assigned_to'], 'overdue_reminder',
                'Overdue Task Reminder',
                "Task '{$task['title']}' is {$daysOverdue} days overdue");
        }
        
        // Follow-up overdue reminders
        $stmt = $this->db->prepare("
            SELECT f.*, u.name as user_name
            FROM followups f
            JOIN users u ON f.user_id = u.id
            WHERE f.follow_up_date < CURDATE()
            AND f.status IN ('pending', 'in_progress')
            AND DATEDIFF(CURDATE(), f.follow_up_date) IN (1, 3, 7)
        ");
        $stmt->execute();
        $overdueFollowups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($overdueFollowups as $followup) {
            $daysOverdue = (new DateTime())->diff(new DateTime($followup['follow_up_date']))->days;
            $this->createNotification($followup['user_id'], 'overdue_reminder',
                'Overdue Follow-up Reminder',
                "Follow-up '{$followup['title']}' is {$daysOverdue} days overdue");
        }
    }
    
    private function sendUpcomingDeadlineAlerts() {
        // Tasks due tomorrow
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $stmt = $this->db->prepare("
            SELECT t.*, u.name as user_name
            FROM tasks t
            JOIN users u ON t.assigned_to = u.id
            WHERE DATE(t.deadline) = ?
            AND t.status IN ('assigned', 'in_progress')
        ");
        $stmt->execute([$tomorrow]);
        $upcomingTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($upcomingTasks as $task) {
            $this->createNotification($task['assigned_to'], 'deadline_alert',
                'Task Due Tomorrow',
                "Task '{$task['title']}' is due tomorrow");
        }
        
        // Follow-ups due tomorrow
        $stmt = $this->db->prepare("
            SELECT f.*, u.name as user_name
            FROM followups f
            JOIN users u ON f.user_id = u.id
            WHERE f.follow_up_date = ?
            AND f.status IN ('pending', 'in_progress')
        ");
        $stmt->execute([$tomorrow]);
        $upcomingFollowups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($upcomingFollowups as $followup) {
            $this->createNotification($followup['user_id'], 'deadline_alert',
                'Follow-up Due Tomorrow',
                "Follow-up '{$followup['title']}' is scheduled for tomorrow");
        }
    }
    
    private function cleanupOldRecords() {
        // Clean up old sync logs (older than 30 days)
        $stmt = $this->db->prepare("
            DELETE FROM sync_log 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $cleanedLogs = $stmt->rowCount();
        
        // Clean up old notifications (older than 90 days)
        $stmt = $this->db->prepare("
            DELETE FROM notifications 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
            AND is_read = TRUE
        ");
        $stmt->execute();
        $cleanedNotifications = $stmt->rowCount();
        
        // Archive completed tasks older than 6 months
        $stmt = $this->db->prepare("
            UPDATE tasks SET status = 'archived'
            WHERE status = 'completed'
            AND updated_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)
        ");
        $stmt->execute();
        $archivedTasks = $stmt->rowCount();
        
        $this->logExecution('cleanup', 'success', 
            "Cleaned $cleanedLogs logs, $cleanedNotifications notifications, archived $archivedTasks tasks");
    }
    
    private function createNotification($userId, $type, $title, $message) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, type, title, message, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $type, $title, $message]);
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
        }
    }
    
    private function notifyManager($userId, $type, $taskData) {
        // Get user's manager (assuming department head is the manager)
        $stmt = $this->db->prepare("
            SELECT d.head_id
            FROM users u
            JOIN departments d ON u.department_id = d.id
            WHERE u.id = ? AND d.head_id IS NOT NULL
        ");
        $stmt->execute([$userId]);
        $managerId = $stmt->fetchColumn();
        
        if ($managerId) {
            $message = "Team member task '{$taskData['title']}' requires attention";
            $this->createNotification($managerId, $type, 'Team Alert', $message);
        }
    }
    
    private function logExecution($operation, $status, $details) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO automation_log (operation, status, details, executed_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    operation = VALUES(operation),
                    status = VALUES(status),
                    details = VALUES(details),
                    executed_at = VALUES(executed_at)
            ");
            $stmt->execute([$operation, $status, $details]);
        } catch (Exception $e) {
            // Create table if it doesn't exist
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS automation_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    operation VARCHAR(100) NOT NULL,
                    status ENUM('success','failed','warning') NOT NULL,
                    details TEXT,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_operation (operation),
                    INDEX idx_executed_at (executed_at)
                )
            ");
            
            // Retry the insert
            $stmt = $this->db->prepare("
                INSERT INTO automation_log (operation, status, details, executed_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$operation, $status, $details]);
        }
    }
    
    public function getAutomationStats() {
        $stats = [];
        
        // Get recent execution stats
        $stmt = $this->db->prepare("
            SELECT operation, status, COUNT(*) as count, MAX(executed_at) as last_run
            FROM automation_log
            WHERE executed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY operation, status
            ORDER BY last_run DESC
        ");
        $stmt->execute();
        $stats['recent_executions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get carry forward stats
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_carried,
                   COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_carried
            FROM daily_planner
            WHERE status = 'carried_forward'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $stats['carry_forward'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get escalation stats
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as auto_escalated_tasks,
                   COUNT(CASE WHEN last_escalation_date = CURDATE() THEN 1 END) as today_escalated
            FROM tasks
            WHERE auto_escalated = TRUE
        ");
        $stmt->execute();
        $stats['escalations'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}
?>