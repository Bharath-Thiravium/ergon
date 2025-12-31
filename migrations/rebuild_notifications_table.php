<?php
/**
 * Notification Table Rebuild Migration
 * Optimizes notification table structure for better performance
 */

require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Starting notification table rebuild...\n";
    
    // Backup existing data
    echo "Backing up existing notifications...\n";
    $backupData = [];
    try {
        $stmt = $db->query("SELECT * FROM notifications ORDER BY created_at DESC");
        $backupData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Backed up " . count($backupData) . " notifications\n";
    } catch (Exception $e) {
        echo "No existing notifications table found or empty\n";
    }
    
    // Drop existing table
    echo "Dropping old notifications table...\n";
    $db->exec("DROP TABLE IF EXISTS notifications");
    
    // Create optimized table structure
    echo "Creating optimized notifications table...\n";
    $sql = "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error', 'urgent') DEFAULT 'info',
        category ENUM('task', 'approval', 'system', 'reminder', 'announcement') DEFAULT 'system',
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        action_url VARCHAR(500) DEFAULT NULL,
        action_text VARCHAR(100) DEFAULT NULL,
        reference_type VARCHAR(50) DEFAULT NULL,
        reference_id INT DEFAULT NULL,
        metadata JSON DEFAULT NULL,
        priority TINYINT(1) DEFAULT 1,
        is_read BOOLEAN DEFAULT FALSE,
        read_at TIMESTAMP NULL DEFAULT NULL,
        expires_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_receiver_unread (receiver_id, is_read, created_at),
        INDEX idx_receiver_priority (receiver_id, priority, created_at),
        INDEX idx_category_type (category, type),
        INDEX idx_reference (reference_type, reference_id),
        INDEX idx_expires (expires_at),
        INDEX idx_created (created_at),
        
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    
    // Migrate existing data if any
    if (!empty($backupData)) {
        echo "Migrating existing notifications...\n";
        $stmt = $db->prepare("
            INSERT INTO notifications (sender_id, receiver_id, type, category, title, message, reference_id, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $migrated = 0;
        foreach ($backupData as $notification) {
            // Map old structure to new
            $type = 'info';
            $category = 'system';
            $title = substr($notification['message'], 0, 100);
            
            // Determine category from module_name
            if (isset($notification['module_name'])) {
                switch ($notification['module_name']) {
                    case 'tasks':
                        $category = 'task';
                        break;
                    case 'leaves':
                    case 'expenses':
                    case 'advances':
                        $category = 'approval';
                        break;
                    default:
                        $category = 'system';
                }
            }
            
            // Determine type from action_type
            if (isset($notification['action_type'])) {
                switch ($notification['action_type']) {
                    case 'approved':
                        $type = 'success';
                        break;
                    case 'rejected':
                        $type = 'error';
                        break;
                    case 'pending':
                        $type = 'warning';
                        break;
                    case 'urgent':
                        $type = 'urgent';
                        break;
                    default:
                        $type = 'info';
                }
            }
            
            $stmt->execute([
                $notification['sender_id'],
                $notification['receiver_id'],
                $type,
                $category,
                $title,
                $notification['message'],
                $notification['reference_id'] ?? null,
                $notification['is_read'] ?? 0,
                $notification['created_at']
            ]);
            $migrated++;
        }
        echo "Migrated $migrated notifications\n";
    }
    
    echo "Notification table rebuild completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error rebuilding notification table: " . $e->getMessage() . "\n";
    exit(1);
}
?>
