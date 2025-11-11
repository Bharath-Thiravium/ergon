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
                    'title' => ucfirst($module) . ' ' . ucfirst($action),
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
                'title' => ucfirst($module) . ' ' . ucfirst($action),
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
                    'title' => ucfirst($module) . ' ' . ucfirst($action),
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
}
?>