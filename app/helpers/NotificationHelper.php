<?php
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/NotificationService.php';

class NotificationHelper {
    
    public static function notifyOwners($senderId, $module, $action, $message, $referenceId = null, $actionUrl = null) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT id FROM users WHERE role = 'owner' AND (status = 'active' OR status IS NULL OR status = '')");
            $stmt->execute();
            $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("NotificationHelper: Found " . count($owners) . " owners for notification");
            
            $notification = new Notification();
            foreach ($owners as $owner) {
                $result = $notification->create([
                    'sender_id' => $senderId,
                    'receiver_id' => $owner['id'],
                    'title' => ucfirst($module) . ' ' . ucfirst(str_replace('_', ' ', $action)),
                    'message' => $message,
                    'reference_type' => $module,
                    'reference_id' => $referenceId,
                    'action_url' => $actionUrl,
                    'category' => 'approval'
                ]);
                error_log("NotificationHelper: Created notification for owner {$owner['id']}: " . ($result ? 'SUCCESS' : 'FAILED'));
            }
            
            // Also notify admins for all owner notifications
            self::notifyAdmins($senderId, $module, $action, $message, $referenceId, $actionUrl);
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
                'title' => ucfirst($module) . ' ' . ucfirst($action),
                'message' => $message,
                'reference_type' => $module,
                'reference_id' => $referenceId,
                'category' => 'system'
            ]);
        } catch (Exception $e) {
            error_log('NotificationHelper error: ' . $e->getMessage());
        }
    }
    
    public static function notifyAdmins($senderId, $module, $action, $message, $referenceId = null, $actionUrl = null) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' AND (status = 'active' OR status IS NULL OR status = '')");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("NotificationHelper: Found " . count($admins) . " admins for notification");
            
            $notification = new Notification();
            foreach ($admins as $admin) {
                $result = $notification->create([
                    'sender_id' => $senderId,
                    'receiver_id' => $admin['id'],
                    'title' => ucfirst($module) . ' ' . ucfirst(str_replace('_', ' ', $action)),
                    'message' => $message,
                    'reference_type' => $module,
                    'reference_id' => $referenceId,
                    'action_url' => $actionUrl,
                    'category' => 'approval'
                ]);
                error_log("NotificationHelper: Created notification for admin {$admin['id']}: " . ($result ? 'SUCCESS' : 'FAILED'));
            }
        } catch (Exception $e) {
            error_log('NotificationHelper error: ' . $e->getMessage());
        }
    }
    
    // Specific notification methods for common events
    public static function notifyLeaveRequest($userId, $userName, $leaveId = null) {
        $message = "{$userName} submitted a leave request for approval";
        $actionUrl = $leaveId ? "/ergon/leaves/view/{$leaveId}" : null;
        
        self::notifyOwners(
            $userId,
            'leave',
            'approval_request',
            $message,
            $leaveId,
            $actionUrl
        );
    }
    
    public static function notifyExpenseClaim($userId, $userName, $amount, $expenseId = null) {
        $message = "{$userName} submitted an expense claim of ₹{$amount} for approval";
        $actionUrl = $expenseId ? "/ergon/expenses/view/{$expenseId}" : null;
        
        self::notifyOwners(
            $userId,
            'expense',
            'approval_request',
            $message,
            $expenseId,
            $actionUrl
        );
    }
    
    public static function notifyAdvanceRequest($userId, $userName, $amount, $advanceId = null) {
        $message = "{$userName} submitted a salary advance request of ₹{$amount} for approval";
        $actionUrl = $advanceId ? "/ergon/advances/view/{$advanceId}" : null;
        
        self::notifyOwners(
            $userId,
            'advance',
            'approval_request',
            $message,
            $advanceId,
            $actionUrl
        );
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
    
    private static function sendToRoles($event, $roles) {
        try {
            $db = Database::connect();
            $placeholders = str_repeat('?,', count($roles) - 1) . '?';
            $stmt = $db->prepare("SELECT id FROM users WHERE role IN ({$placeholders}) AND status = 'active'");
            $stmt->execute($roles);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($users as $user) {
                $userEvent = $event;
                $userEvent['receiver_id'] = $user['id'];
                NotificationService::enqueueEvent($userEvent);
            }
        } catch (Exception $e) {
            error_log('Enhanced notification failed: ' . $e->getMessage());
        }
    }
}
?>