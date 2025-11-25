<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/TemplateRenderer.php';
require_once __DIR__ . '/ChannelDispatcher.php';

class NotificationWorker {
    private $db;
    private $queue;
    private $renderer;
    private $dispatcher;
    
    public function __construct() {
        $this->db = Database::connect();
        $this->queue = new NotificationQueue();
        $this->renderer = new TemplateRenderer();
        $this->dispatcher = new ChannelDispatcher();
    }
    
    public function run() {
        echo "Notification worker started...\n";
        
        while (true) {
            try {
                $event = $this->queue->pop();
                if ($event) {
                    $this->processEvent($event);
                } else {
                    sleep(5); // Wait 5 seconds if no events
                }
            } catch (Exception $e) {
                error_log("Worker error: " . $e->getMessage());
                sleep(10); // Wait longer on error
            }
        }
    }
    
    private function processEvent($event) {
        echo "Processing event: {$event['uuid']}\n";
        
        // Check if already processed
        if ($this->notificationExists($event['uuid'])) {
            echo "Event already processed: {$event['uuid']}\n";
            return;
        }
        
        // Get user preferences
        $preferences = $this->getUserPreferences($event['receiver_id'] ?? null);
        
        // Check if notification is allowed
        if (!$this->isNotificationAllowed($preferences, $event)) {
            echo "Notification blocked by user preferences\n";
            return;
        }
        
        // Render message using template
        $rendered = $this->renderer->render($event);
        
        // Create notification record
        $notificationId = $this->createNotification($event, $rendered);
        
        // Dispatch to channels
        $this->dispatchToChannels($event, $rendered, $notificationId);
        
        echo "Event processed successfully: {$event['uuid']}\n";
    }
    
    private function notificationExists($uuid) {
        $stmt = $this->db->prepare("SELECT id FROM notifications WHERE uuid = ?");
        $stmt->execute([$uuid]);
        return $stmt->fetch() !== false;
    }
    
    private function getUserPreferences($userId) {
        if (!$userId) return [];
        
        $stmt = $this->db->prepare("
            SELECT channel, enabled, frequency, dnd_start, dnd_end 
            FROM notification_preferences 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function isNotificationAllowed($preferences, $event) {
        // Check DND (Do Not Disturb) hours
        $now = new DateTime();
        $currentTime = $now->format('H:i:s');
        
        foreach ($preferences as $pref) {
            if (in_array($pref['channel'], $event['channels'])) {
                if (!$pref['enabled']) {
                    return false;
                }
                
                if ($pref['dnd_start'] && $pref['dnd_end']) {
                    if ($currentTime >= $pref['dnd_start'] && $currentTime <= $pref['dnd_end']) {
                        // Schedule for digest instead
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    private function createNotification($event, $rendered) {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (
                uuid, sender_id, receiver_id, module_name, action_type, 
                template_key, message, payload, link, delivery_channel_set, 
                priority, status, expires_at, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'delivered', ?, NOW())
        ");
        
        $stmt->execute([
            $event['uuid'],
            $event['sender_id'],
            $event['receiver_id'] ?? null,
            $event['module'],
            $event['action'],
            $event['template'] ?? null,
            $rendered['text'],
            json_encode($event['payload']),
            $rendered['link'] ?? null,
            implode(',', $event['channels']),
            $event['priority'],
            $event['expires_at']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    private function dispatchToChannels($event, $rendered, $notificationId) {
        foreach ($event['channels'] as $channel) {
            try {
                $result = $this->dispatcher->dispatch($channel, $event, $rendered);
                $this->logDeliveryAttempt($event['uuid'], $channel, 'success', $result);
            } catch (Exception $e) {
                $this->logDeliveryAttempt($event['uuid'], $channel, 'failed', null, $e->getMessage());
                error_log("Channel dispatch failed: {$channel} - " . $e->getMessage());
            }
        }
    }
    
    private function logDeliveryAttempt($uuid, $channel, $status, $response = null, $error = null) {
        $stmt = $this->db->prepare("
            INSERT INTO notification_audit_logs 
            (notification_uuid, channel, status, response, error_message, attempt_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $uuid,
            $channel,
            $status,
            $response ? json_encode($response) : null,
            $error
        ]);
    }
}

// CLI runner
if (php_sapi_name() === 'cli') {
    $worker = new NotificationWorker();
    $worker->run();
}
?>