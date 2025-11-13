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
        self::notifyOwners(
            $userId,
            'leave',
            'request',
            "{$userName} has submitted a leave request for approval",
            null
        );
    }
    
    public static function notifyExpenseClaim($userId, $userName, $amount) {
        self::notifyOwners(
            $userId,
            'expense',
            'claim',
            "{$userName} submitted expense claim of ₹{$amount} for approval",
            null
        );
    }
    
    public static function notifyAdvanceRequest($userId, $userName, $amount) {
        self::notifyOwners(
            $userId,
            'advance',
            'request',
            "{$userName} requested salary advance of ₹{$amount}",
            null
        );
    }
    
    public static function notifyTaskAssignment($assignedBy, $assignedTo, $taskTitle) {
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
?>