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
    
    public static function escalateTask($taskId, $reason = 'SLA Breach') {
        $db = self::getDB();
        
        // Get task details
        $stmt = $db->prepare("SELECT t.*, u.name as assigned_to_name FROM tasks t JOIN users u ON t.assigned_to = u.id WHERE t.id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task) return false;
        
        // Notify managers
        $managers = $db->query("SELECT id FROM users WHERE role IN ('admin', 'owner')")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($managers as $manager) {
            self::notifyUser(
                $manager['id'],
                "Task Escalation: {$reason}",
                "Task '{$task['title']}' assigned to {$task['assigned_to_name']} requires attention.",
                "/ergon/tasks/{$taskId}"
            );
        }
        
        // Log escalation
        $stmt = $db->prepare("INSERT INTO task_escalations (task_id, reason, escalated_at) VALUES (?, ?, NOW())");
        return $stmt->execute([$taskId, $reason]);
    }
    
    public static function sendEmail($to, $subject, $body) {
        // Email implementation using PHPMailer or similar
        $headers = "From: " . ($_ENV['MAIL_FROM'] ?? 'noreply@ergon.local') . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        return mail($to, $subject, $body, $headers);
    }
    
    public static function sendSlackWebhook($webhookUrl, $message) {
        $payload = json_encode(['text' => $message]);
        
        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return $result;
    }
    
    public static function processEscalationRules() {
        $db = self::getDB();
        
        // SLA breach escalation
        $stmt = $db->query("
            SELECT t.id, t.title, TIMESTAMPDIFF(HOUR, t.created_at, NOW()) as hours_elapsed
            FROM tasks t 
            WHERE t.status != 'completed' 
              AND TIMESTAMPDIFF(HOUR, t.created_at, NOW()) > t.sla_hours
              AND t.id NOT IN (SELECT task_id FROM task_escalations WHERE DATE(escalated_at) = CURDATE())
        ");
        
        $breachedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($breachedTasks as $task) {
            self::escalateTask($task['id'], 'SLA Breach - ' . $task['hours_elapsed'] . ' hours elapsed');
        }
        
        return count($breachedTasks);
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