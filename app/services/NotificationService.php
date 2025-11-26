<?php
require_once __DIR__ . '/../config/database.php';

class NotificationService {
    private static $instance = null;
    private $db;
    private $queue;
    
    private function __construct() {
        $this->db = Database::connect();
        $this->queue = new NotificationQueue();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function enqueueEvent($event) {
        $service = self::getInstance();
        return $service->enqueue($event);
    }
    
    private function enqueue($event) {
        // Generate UUID if not provided
        if (empty($event['uuid'])) {
            $event['uuid'] = $this->generateUuid($event);
        }
        
        // Check idempotency
        if ($this->eventExists($event['uuid'])) {
            return ['success' => true, 'message' => 'Event already processed'];
        }
        
        // Validate event
        if (!$this->validateEvent($event)) {
            throw new InvalidArgumentException('Invalid notification event');
        }
        
        // Enrich event with defaults
        $event = $this->enrichEvent($event);
        
        // Add to queue
        return $this->queue->push($event);
    }
    
    private function generateUuid($event) {
        $data = $event['module'] . '_' . $event['action'] . '_' . 
                ($event['reference_id'] ?? '') . '_' . time();
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    private function eventExists($uuid) {
        $stmt = $this->db->prepare("SELECT id FROM notifications WHERE uuid = ?");
        $stmt->execute([$uuid]);
        return $stmt->fetch() !== false;
    }
    
    private function validateEvent($event) {
        $required = ['sender_id', 'module', 'action', 'payload'];
        foreach ($required as $field) {
            if (empty($event[$field])) {
                return false;
            }
        }
        return true;
    }
    
    private function enrichEvent($event) {
        return array_merge([
            'channels' => ['inapp'],
            'priority' => 2,
            'template' => $event['module'] . '.' . $event['action'],
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
        ], $event);
    }
}

class NotificationQueue {
    private $redis;
    private $queueDir;
    
    public function __construct() {
        // Simple file-based queue for now, replace with Redis/RabbitMQ
        $this->queueDir = __DIR__ . '/../../storage/queue/';
        if (!is_dir($this->queueDir)) {
            mkdir($this->queueDir, 0755, true);
        }
    }
    
    public function push($event) {
        $filename = $this->queueDir . 'notification_' . time() . '_' . uniqid() . '.json';
        $success = file_put_contents($filename, json_encode($event));
        return ['success' => $success !== false, 'queued_at' => time()];
    }
    
    public function pop() {
        $files = glob($this->queueDir . 'notification_*.json');
        if (empty($files)) {
            return null;
        }
        
        sort($files);
        $file = $files[0];
        $event = json_decode(file_get_contents($file), true);
        unlink($file);
        
        return $event;
    }
}
?>