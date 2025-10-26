<?php
class NotificationHelper {
    
    public static function notifyUser($userId, $title, $message, $link = null) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, title, message, link, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([$userId, $title, $message, $link]);
        } catch (Exception $e) {
            error_log('NotificationHelper::notifyUser error: ' . $e->getMessage());
            return false;
        }
    }
    
    public static function notifyAdmins($title, $message, $link = null) {
        try {
            $db = Database::connect();
            
            // Get all admin and owner users
            $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('admin', 'owner') AND status = 'active'");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($admins as $admin) {
                self::notifyUser($admin['id'], $title, $message, $link);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('NotificationHelper::notifyAdmins error: ' . $e->getMessage());
            return false;
        }
    }
    
    public static function notifyRole($role, $title, $message, $link = null) {
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT id FROM users WHERE role = ? AND status = 'active'");
            $stmt->execute([$role]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($users as $user) {
                self::notifyUser($user['id'], $title, $message, $link);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('NotificationHelper::notifyRole error: ' . $e->getMessage());
            return false;
        }
    }
    
    public static function getUnreadCount($userId) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (Exception $e) {
            error_log('NotificationHelper::getUnreadCount error: ' . $e->getMessage());
            return 0;
        }
    }
    
    public static function markAsRead($notificationId, $userId) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?");
            return $stmt->execute([$notificationId, $userId]);
        } catch (Exception $e) {
            error_log('NotificationHelper::markAsRead error: ' . $e->getMessage());
            return false;
        }
    }
    
    public static function getUserNotifications($userId, $limit = 10) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('NotificationHelper::getUserNotifications error: ' . $e->getMessage());
            return [];
        }
    }
}
?>