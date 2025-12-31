<?php
require_once __DIR__ . '/../config/database.php';

class NotificationService {
    
    // Smart digest and batching system
    public static function createSmartDigest($userId, $timeframe = '24h') {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("
                SELECT module_name, action_type, COUNT(*) as count, MAX(created_at) as latest
                FROM notifications 
                WHERE receiver_id = ? AND is_read = 0 AND priority = 'low'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                GROUP BY module_name, action_type
                HAVING count > 1
            ");
            $stmt->execute([$userId]);
            $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($batches as $batch) {
                $message = "📊 {$batch['count']} {$batch['module_name']} {$batch['action_type']} notifications";
                self::createNotification(null, $userId, 'system', 'digest', $message, null, 'low');
                
                // Mark original notifications as batched
                $stmt = $db->prepare("
                    UPDATE notifications SET is_batched = 1 
                    WHERE receiver_id = ? AND module_name = ? AND action_type = ? AND is_read = 0
                ");
                $stmt->execute([$userId, $batch['module_name'], $batch['action_type']]);
            }
        } catch (Exception $e) {
            error_log('Smart digest error: ' . $e->getMessage());
        }
    }
    
    // Adaptive reminder system with escalation
    public static function createAdaptiveReminder($userId, $taskId, $taskTitle, $dueDate, $escalationLevel = 1) {
        $priority = match($escalationLevel) {
            1 => 'low',
            2 => 'medium', 
            3 => 'high',
            default => 'critical'
        };
        
        $urgencyIcon = match($escalationLevel) {
            1 => '⏰',
            2 => '⚠️',
            3 => '🔴',
            default => '🚨'
        };
        
        $message = "{$urgencyIcon} Reminder (Level {$escalationLevel}): Task '{$taskTitle}' is due on {$dueDate}";
        
        self::createNotification(null, $userId, 'task', 'reminder', $message, $taskId, $priority, [
            'escalation_level' => $escalationLevel,
            'task_title' => $taskTitle,
            'due_date' => $dueDate
        ]);
    }
    
    // Enhanced notification creation with metadata
    public static function createNotification($senderId, $receiverId, $module, $action, $message, $referenceId = null, $priority = 'medium', $metadata = []) {
        // Duplicate detection
        if (self::isDuplicateNotification($senderId, $receiverId, $module, $action, 5)) {
            return false;
        }
        
        try {
            $db = Database::connect();
            
            // Check if new columns exist, fallback to standard notification creation
            $stmt = $db->query("SHOW COLUMNS FROM notifications LIKE 'module_name'");
            $hasModuleName = $stmt->rowCount() > 0;
            
            if ($hasModuleName) {
                $stmt = $db->prepare("
                    INSERT INTO notifications (sender_id, receiver_id, module_name, action_type, message, reference_id, priority, metadata, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                return $stmt->execute([
                    $senderId,
                    $receiverId, 
                    $module,
                    $action,
                    $message,
                    $referenceId,
                    $priority,
                    json_encode($metadata)
                ]);
            } else {
                // Fallback to standard notification model
                require_once __DIR__ . '/../models/Notification.php';
                $notification = new Notification();
                return $notification->create([
                    'sender_id' => $senderId,
                    'receiver_id' => $receiverId,
                    'title' => ucfirst($module) . ' ' . ucfirst($action),
                    'message' => $message,
                    'reference_type' => $module,
                    'reference_id' => $referenceId,
                    'category' => 'system',
                    'priority' => $priority === 'medium' ? 1 : ($priority === 'high' ? 2 : 1)
                ]);
            }
        } catch (Exception $e) {
            error_log('Notification creation error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Duplicate detection system
    private static function isDuplicateNotification($senderId, $receiverId, $module, $action, $minutesThreshold = 5) {
        try {
            $db = Database::connect();
            
            // Check if module_name column exists
            $stmt = $db->query("SHOW COLUMNS FROM notifications LIKE 'module_name'");
            $hasModuleName = $stmt->rowCount() > 0;
            
            if ($hasModuleName) {
                $stmt = $db->prepare("
                    SELECT COUNT(*) as count FROM notifications 
                    WHERE sender_id = ? AND receiver_id = ? AND module_name = ? AND action_type = ?
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
                ");
                $stmt->execute([$senderId, $receiverId, $module, $action, $minutesThreshold]);
            } else {
                $stmt = $db->prepare("
                    SELECT COUNT(*) as count FROM notifications 
                    WHERE sender_id = ? AND receiver_id = ? AND reference_type = ?
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
                ");
                $stmt->execute([$senderId, $receiverId, $module, $minutesThreshold]);
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false; // Allow notification on error
        }
    }
    
    // Queue system for enhanced notifications
    public static function enqueueEvent($event) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("
                INSERT INTO notification_queue (event_data, priority, created_at, status)
                VALUES (?, ?, NOW(), 'pending')
            ");
            
            return $stmt->execute([
                json_encode($event),
                $event['priority'] ?? 2
            ]);
        } catch (Exception $e) {
            error_log('Queue enqueue error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Process notification queue
    public static function processQueue($limit = 50) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("
                SELECT * FROM notification_queue 
                WHERE status = 'pending' 
                ORDER BY priority DESC, created_at ASC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($events as $event) {
                $eventData = json_decode($event['event_data'], true);
                
                // Process the event
                $success = self::processNotificationEvent($eventData);
                
                // Update queue status
                $status = $success ? 'processed' : 'failed';
                $updateStmt = $db->prepare("UPDATE notification_queue SET status = ?, processed_at = NOW() WHERE id = ?");
                $updateStmt->execute([$status, $event['id']]);
            }
            
            return count($events);
        } catch (Exception $e) {
            error_log('Queue processing error: ' . $e->getMessage());
            return 0;
        }
    }
    
    private static function processNotificationEvent($eventData) {
        try {
            // Create in-app notification
            $message = self::renderTemplate($eventData['template'], $eventData['payload']);
            
            return self::createNotification(
                $eventData['sender_id'],
                $eventData['receiver_id'],
                $eventData['module'],
                $eventData['action'],
                $message,
                $eventData['reference_id'] ?? null,
                self::mapPriority($eventData['priority'] ?? 2),
                $eventData['payload'] ?? []
            );
        } catch (Exception $e) {
            error_log('Event processing error: ' . $e->getMessage());
            return false;
        }
    }
    
    private static function renderTemplate($template, $payload) {
        $templates = [
            'leave.request_submitted' => "📅 {userName} submitted a leave request from {startDate} to {endDate}",
            'expense.claim_submitted' => "💰 {userName} submitted an expense claim of ₹{amount} for {category}",
            'advance.request_submitted' => "💳 {userName} requested a salary advance of ₹{amount}",
        ];
        
        $message = $templates[$template] ?? "Notification from {userName}";
        
        foreach ($payload as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }
        
        return $message;
    }
    
    private static function mapPriority($numericPriority) {
        return match($numericPriority) {
            1 => 'low',
            2 => 'medium',
            3 => 'high',
            4 => 'critical',
            default => 'medium'
        };
    }
}
?>
