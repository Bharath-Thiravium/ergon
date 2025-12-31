<?php
/**
 * Enhanced Task History Helper
 * Provides comprehensive methods for logging detailed task-related activities
 * Supports progress tracking, status changes, assignments, and user actions
 */

class TaskHistoryHelper {
    
    /**
     * Log planner-related task actions
     */
    public static function logPlannerAction($taskId, $action, $details = '', $userId = null) {
        try {
            require_once __DIR__ . '/../controllers/TasksController.php';
            return TasksController::logPlannerAction($taskId, $action, $details, $userId);
        } catch (Exception $e) {
            error_log('TaskHistoryHelper::logPlannerAction error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log followup-related task actions
     */
    public static function logFollowupAction($taskId, $action, $followupDetails = '', $userId = null) {
        try {
            require_once __DIR__ . '/../controllers/TasksController.php';
            return TasksController::logFollowupAction($taskId, $action, $followupDetails, $userId);
        } catch (Exception $e) {
            error_log('TaskHistoryHelper::logFollowupAction error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log general task action with enhanced details
     */
    public static function logTaskAction($taskId, $action, $oldValue = '', $newValue = '', $notes = '', $userId = null) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Ensure task_history table exists
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
            
            $createdBy = $userId ?? ($_SESSION['user_id'] ?? 1);
            $timestamp = date('Y-m-d H:i:s');
            
            // Enhanced notes with more context
            $enhancedNotes = $notes;
            if ($action && $oldValue && $newValue && $oldValue !== $newValue) {
                $enhancedNotes = ($notes ? $notes . ' | ' : '') . sprintf('Changed from "%s" to "%s"', $oldValue, $newValue);
            } elseif ($action && $newValue && !$oldValue) {
                $enhancedNotes = ($notes ? $notes . ' | ' : '') . sprintf('Set to "%s"', $newValue);
            }
            
            $enhancedNotes .= ($enhancedNotes ? ' | ' : '') . 'Logged at: ' . $timestamp;
            
            $stmt = $db->prepare("INSERT INTO task_history (task_id, action, old_value, new_value, notes, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$taskId, $action, $oldValue, $newValue, $enhancedNotes, $createdBy]);
            
            if ($result) {
                error_log("Enhanced task history logged: Task {$taskId} - {$action} - {$enhancedNotes}");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('TaskHistoryHelper::logTaskAction error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log task comment or note
     */
    public static function logTaskComment($taskId, $comment, $userId = null) {
        return self::logTaskAction($taskId, 'commented', '', 'Comment added', $comment, $userId);
    }
    
    /**
     * Log task completion
     */
    public static function logTaskCompletion($taskId, $completionNotes = '', $userId = null) {
        return self::logTaskAction($taskId, 'completed', 'in_progress', 'completed', $completionNotes ?: 'Task marked as completed', $userId);
    }
    
    /**
     * Log task cancellation
     */
    public static function logTaskCancellation($taskId, $reason = '', $userId = null) {
        return self::logTaskAction($taskId, 'cancelled', '', 'cancelled', $reason ?: 'Task cancelled', $userId);
    }
}
?>
