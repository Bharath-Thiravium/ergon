<?php
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../config/database.php';

class NotificationHelper {
    
    public static function notifyOwners($senderId, $module, $action, $message, $referenceId = null) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT id FROM users WHERE role = 'owner' AND status = 'active'");
            $stmt->execute();
            $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $notification = new Notification();
            foreach ($owners as $owner) {
                $notification->create([
                    'sender_id' => $senderId,
                    'receiver_id' => $owner['id'],
                    'module_name' => $module,
                    'action_type' => $action,
                    'message' => $message,
                    'reference_id' => $referenceId
                ]);
            }
            
            // Also notify admins for all owner notifications
            self::notifyAdmins($senderId, $module, $action, $message, $referenceId);
        } catch (Exception $e) {
            error_log('NotificationHelper error: ' . $e->getMessage());
        }
    }
    
    public static function notifyUser($senderId, $receiverId, $module, $action, $message, $referenceId = null) {
        try {
            $notification = new Notification();
            $notification->create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'module_name' => $module,
                'action_type' => $action,
                'message' => $message,
                'reference_id' => $referenceId
            ]);
        } catch (Exception $e) {
            error_log('NotificationHelper error: ' . $e->getMessage());
        }
    }
    
    public static function notifyAdmins($senderId, $module, $action, $message, $referenceId = null) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $notification = new Notification();
            foreach ($admins as $admin) {
                $notification->create([
                    'sender_id' => $senderId,
                    'receiver_id' => $admin['id'],
                    'module_name' => $module,
                    'action_type' => $action,
                    'message' => $message,
                    'reference_id' => $referenceId
                ]);
            }
        } catch (Exception $e) {
            error_log('NotificationHelper error: ' . $e->getMessage());
        }
    }
    
    // Specific notification methods for common events
    public static function notifyLeaveRequest($userId, $userName) {
        // Only notify approvers, not the requester
        self::notifyApprovalRequest($userId, $userName, 'leave', 'a leave request');
    }
    
    public static function notifyExpenseClaim($userId, $userName, $amount) {
        // Only notify approvers, not the claimant
        self::notifyApprovalRequest($userId, $userName, 'expense', "an expense claim of ₹{$amount}");
    }
    
    public static function notifyAdvanceRequest($userId, $userName, $amount) {
        // Only notify approvers, not the requester
        self::notifyApprovalRequest($userId, $userName, 'advance', "a salary advance request of ₹{$amount}");
    }
    
    public static function notifyApprovalDecision($approverId, $userId, $module, $decision, $itemDescription) {
        // Notify user about approval decision (only if different users)
        if ($approverId != $userId) {
            $message = "Your {$itemDescription} has been {$decision}";
            self::notifyUser($approverId, $userId, $module, $decision, $message, null);
        }
    }
    
    public static function notifyTaskAssignment($assignedBy, $assignedTo, $taskTitle) {
        // Only notify if assigning to someone else (not self-assignment)
        if ($assignedBy != $assignedTo) {
            self::notifyUser(
                $assignedBy,
                $assignedTo,
                'task',
                'assigned',
                "You have been assigned a new task: {$taskTitle}",
                null
            );
        }
    }
    
    // Smart notification methods with proper logic
    public static function notifyTaskReminder($userId, $taskTitle, $dueDate) {
        // Always notify user about their own task reminders
        self::notifyUser(
            null, // System notification
            $userId,
            'task',
            'reminder',
            "Reminder: Task '{$taskTitle}' is due on {$dueDate}",
            null
        );
    }
    
    public static function notifyFromOthers($senderId, $receiverId, $module, $action, $message, $referenceId = null) {
        // Only notify if sender is different from receiver
        if ($senderId != $receiverId) {
            self::notifyUser($senderId, $receiverId, $module, $action, $message, $referenceId);
        }
    }
    
    public static function notifyApprovalRequest($userId, $userName, $module, $itemDescription) {
        // Don't notify the requester, only notify approvers (owners/admins)
        self::notifyOwners(
            $userId,
            $module,
            'approval_request',
            "{$userName} submitted {$itemDescription} for approval",
            null
        );
    }
}
?>