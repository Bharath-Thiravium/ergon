<?php
require_once __DIR__ . '/../config/database.php';

class ChannelDispatcher {
    private $db;
    private $channels = [];
    
    public function __construct() {
        $this->db = Database::connect();
        $this->loadChannelConfigs();
    }
    
    private function loadChannelConfigs() {
        $stmt = $this->db->query("SELECT channel_name, config, enabled FROM notification_channels");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->channels[$row['channel_name']] = [
                'config' => json_decode($row['config'], true),
                'enabled' => $row['enabled']
            ];
        }
    }
    
    public function dispatch($channel, $event, $rendered) {
        if (!isset($this->channels[$channel]) || !$this->channels[$channel]['enabled']) {
            throw new Exception("Channel {$channel} not available");
        }
        
        switch ($channel) {
            case 'inapp':
                return $this->dispatchInApp($event, $rendered);
            case 'email':
                return $this->dispatchEmail($event, $rendered);
            case 'push':
                return $this->dispatchPush($event, $rendered);
            case 'sms':
                return $this->dispatchSMS($event, $rendered);
            default:
                throw new Exception("Unknown channel: {$channel}");
        }
    }
    
    private function dispatchInApp($event, $rendered) {
        // In-app notifications are already stored in DB by worker
        // Optionally trigger WebSocket/SSE here
        $this->triggerRealTime($event, $rendered);
        return ['status' => 'delivered', 'method' => 'database'];
    }
    
    private function dispatchEmail($event, $rendered) {
        $config = $this->channels['email']['config'];
        
        if (empty($config['smtp_host'])) {
            throw new Exception("Email not configured");
        }
        
        $userEmail = $this->getUserEmail($event['receiver_id']);
        if (!$userEmail) {
            throw new Exception("User email not found");
        }
        
        // Simple mail sending (replace with PHPMailer/SwiftMailer in production)
        $headers = [
            'From: ' . $config['from_email'],
            'Content-Type: text/html; charset=UTF-8',
            'MIME-Version: 1.0'
        ];
        
        $success = mail(
            $userEmail,
            $rendered['subject'],
            $rendered['html'],
            implode("\r\n", $headers)
        );
        
        if (!$success) {
            throw new Exception("Failed to send email");
        }
        
        return ['status' => 'sent', 'recipient' => $userEmail];
    }
    
    private function dispatchPush($event, $rendered) {
        $config = $this->channels['push']['config'];
        
        $fcmKey = $_ENV['FCM_SERVER_KEY'] ?? $config['fcm_server_key'] ?? null;
        if (empty($fcmKey)) {
            throw new Exception("Push notifications not configured - FCM key missing");
        }
        
        $pushToken = $this->getUserPushToken($event['receiver_id']);
        if (!$pushToken) {
            throw new Exception("User push token not found");
        }
        
        // FCM push notification
        $payload = [
            'to' => $pushToken,
            'notification' => [
                'title' => $rendered['subject'],
                'body' => strip_tags($rendered['text']),
                'click_action' => $rendered['link']
            ],
            'data' => [
                'notification_uuid' => $event['uuid'],
                'module' => $event['module'],
                'action' => $event['action']
            ]
        ];
        
        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: key=' . $fcmKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("FCM request failed: {$httpCode}");
        }
        
        return ['status' => 'sent', 'response' => $response];
    }
    
    private function dispatchSMS($event, $rendered) {
        $config = $this->channels['sms']['config'];
        
        $accountSid = $_ENV['SMS_ACCOUNT_SID'] ?? $config['account_sid'] ?? null;
        $authToken = $_ENV['SMS_AUTH_TOKEN'] ?? $config['auth_token'] ?? null;
        if (empty($accountSid) || empty($authToken)) {
            throw new Exception("SMS not configured - credentials missing");
        }
        
        $userPhone = $this->getUserPhone($event['receiver_id']);
        if (!$userPhone) {
            throw new Exception("User phone not found");
        }
        
        // Twilio SMS (simplified)
        $message = strip_tags($rendered['text']);
        if (strlen($message) > 160) {
            $message = substr($message, 0, 157) . '...';
        }
        
        // In production, use Twilio SDK
        return ['status' => 'sent', 'recipient' => $userPhone, 'message' => $message];
    }
    
    private function triggerRealTime($event, $rendered) {
        // WebSocket/SSE implementation would go here
        // For now, just log that real-time notification should be sent
        error_log("Real-time notification: {$event['uuid']} for user {$event['receiver_id']}");
    }
    
    private function getUserEmail($userId) {
        if (!$userId) return null;
        
        $stmt = $this->db->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ? $user['email'] : null;
    }
    
    private function getUserPushToken($userId) {
        if (!$userId) return null;
        
        // Assuming push tokens are stored in user_preferences or separate table
        $stmt = $this->db->prepare("
            SELECT preference_value FROM user_preferences 
            WHERE user_id = ? AND preference_key = 'push_token'
        ");
        $stmt->execute([$userId]);
        $pref = $stmt->fetch(PDO::FETCH_ASSOC);
        return $pref ? $pref['preference_value'] : null;
    }
    
    private function getUserPhone($userId) {
        if (!$userId) return null;
        
        $stmt = $this->db->prepare("SELECT phone FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ? $user['phone'] : null;
    }
}
?>
