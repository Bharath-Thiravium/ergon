<?php

class NotificationHelper {
    
    public static function createLeaveRequestNotification($leaveId, $userId, $userName, $startDate, $endDate) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $message = "{$userName} has requested leave from {$startDate} to {$endDate}";
            
            $sql = "INSERT INTO notifications (title, message, type, target_role, actor_id, reference_id, reference_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'New Leave Request',
                $message,
                'leave_request',
                'owner',
                $userId,
                $leaveId,
                'leave'
            ]);
            
            // Also notify admin
            $stmt->execute([
                'New Leave Request',
                $message,
                'leave_request',
                'admin',
                $userId,
                $leaveId,
                'leave'
            ]);
        } catch (Exception $e) {
            error_log('Notification creation error: ' . $e->getMessage());
        }
    }
    
    public static function createExpenseClaimNotification($expenseId, $userId, $userName, $amount, $description) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $message = "{$userName} submitted expense claim of ₹{$amount} for {$description}";
            
            $sql = "INSERT INTO notifications (title, message, type, target_role, actor_id, reference_id, reference_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'New Expense Claim',
                $message,
                'expense_claim',
                'owner',
                $userId,
                $expenseId,
                'expense'
            ]);
            
            // Also notify admin
            $stmt->execute([
                'New Expense Claim',
                $message,
                'expense_claim',
                'admin',
                $userId,
                $expenseId,
                'expense'
            ]);
        } catch (Exception $e) {
            error_log('Notification creation error: ' . $e->getMessage());
        }
    }
    
    public static function createTaskOverdueNotification($taskId, $taskTitle, $assignedUserId, $assignedUserName) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $message = "Task '{$taskTitle}' assigned to {$assignedUserName} is overdue";
            
            $sql = "INSERT INTO notifications (title, message, type, target_role, actor_id, reference_id, reference_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            
            // Notify owner and admin
            $stmt->execute([
                'Overdue Task Alert',
                $message,
                'task_overdue',
                'owner',
                $assignedUserId,
                $taskId,
                'task'
            ]);
            
            $stmt->execute([
                'Overdue Task Alert',
                $message,
                'task_overdue',
                'admin',
                $assignedUserId,
                $taskId,
                'task'
            ]);
            
            // Notify the assigned user
            $stmt = $db->prepare("INSERT INTO notifications (title, message, type, target_user_id, actor_id, reference_id, reference_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                'Your Task is Overdue',
                "Your task '{$taskTitle}' is overdue. Please update the status.",
                'task_overdue',
                $assignedUserId,
                $assignedUserId,
                $taskId,
                'task'
            ]);
        } catch (Exception $e) {
            error_log('Notification creation error: ' . $e->getMessage());
        }
    }
    
    public static function createAttendanceAlertNotification($userId, $userName, $clockInTime) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $message = "{$userName} arrived late at " . date('H:i', strtotime($clockInTime));
            
            $sql = "INSERT INTO notifications (title, message, type, target_role, actor_id, reference_id, reference_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            
            // Notify owner and admin
            $stmt->execute([
                'Late Arrival Alert',
                $message,
                'attendance_alert',
                'owner',
                $userId,
                $userId,
                'attendance'
            ]);
            
            $stmt->execute([
                'Late Arrival Alert',
                $message,
                'attendance_alert',
                'admin',
                $userId,
                $userId,
                'attendance'
            ]);
        } catch (Exception $e) {
            error_log('Notification creation error: ' . $e->getMessage());
        }
    }
    
    public static function createWorkflowMissingNotification($userId, $userName, $type = 'morning') {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $title = $type === 'morning' ? 'Missing Morning Plan' : 'Missing Evening Update';
            $message = $type === 'morning' 
                ? "{$userName} hasn't submitted their morning plan for today"
                : "{$userName} hasn't submitted their evening update for today";
            
            $sql = "INSERT INTO notifications (title, message, type, target_role, actor_id, reference_id, reference_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            
            // Notify owner and admin
            $stmt->execute([
                $title,
                $message,
                'workflow_missing',
                'owner',
                $userId,
                $userId,
                'workflow'
            ]);
            
            $stmt->execute([
                $title,
                $message,
                'workflow_missing',
                'admin',
                $userId,
                $userId,
                'workflow'
            ]);
        } catch (Exception $e) {
            error_log('Notification creation error: ' . $e->getMessage());
        }
    }
    
    public static function createApprovalNotification($type, $id, $status, $approverName, $targetUserId) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $typeLabel = ucfirst($type);
            $statusLabel = ucfirst($status);
            $message = "Your {$typeLabel} request has been {$statusLabel} by {$approverName}";
            
            $sql = "INSERT INTO notifications (title, message, type, target_user_id, reference_id, reference_type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                "{$typeLabel} Request {$statusLabel}",
                $message,
                $type . '_' . $status,
                $targetUserId,
                $id,
                $type
            ]);
        } catch (Exception $e) {
            error_log('Notification creation error: ' . $e->getMessage());
        }
    }
    
    public static function createSystemNotification($title, $message, $type = 'info', $targetRole = 'all') {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $sql = "INSERT INTO notifications (title, message, type, target_role, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([$title, $message, $type, $targetRole]);
        } catch (Exception $e) {
            error_log('Notification creation error: ' . $e->getMessage());
        }
    }
}
?>