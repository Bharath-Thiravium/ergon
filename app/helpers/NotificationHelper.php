<?php
require_once __DIR__ . '/../../config/database.php';

class NotificationHelper {
    private static $db;
    
    private static function getDB() {
        if (!self::$db) {
            $database = new Database();
            self::$db = $database->getConnection();
        }
        return self::$db;
    }
    
    public static function notifyUser($userId, $title, $message, $link = null) {
        $db = self::getDB();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $message, $link]);
        
        // Send push notification
        $deviceToken = self::getDeviceToken($userId);
        if ($deviceToken) {
            self::sendFCM($deviceToken, [
                'title' => $title,
                'body' => $message,
                'click_action' => $link
            ]);
        }
    }
    
    public static function notifyAdmins($title, $message, $link = null) {
        $db = self::getDB();
        $stmt = $db->query("SELECT id FROM users WHERE role IN ('admin', 'owner')");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($admins as $admin) {
            self::notifyUser($admin['id'], $title, $message, $link);
        }
    }
    
    public static function sendFCM($token, $payload) {
        $serverKey = $_ENV['FCM_SERVER_KEY'] ?? 'your-fcm-server-key';
        
        $body = [
            'to' => $token,
            'notification' => [
                'title' => $payload['title'],
                'body' => $payload['body']
            ],
            'data' => [
                'click_action' => $payload['click_action'] ?? ''
            ]
        ];
        
        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($result, true);
    }
    
    private static function getDeviceToken($userId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT fcm_token FROM user_devices WHERE user_id = ? ORDER BY last_active DESC LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['fcm_token'] ?? null;
    }
}
?>