<?php
require_once __DIR__ . '/../core/Controller.php';

class TestController extends Controller {
    
    public function index() {
        $this->json(['message' => 'Test endpoint working', 'timestamp' => time()]);
    }
    
    public function status() {
        $this->json([
            'status' => 'OK',
            'version' => '2.0.0',
            'environment' => 'development'
        ]);
    }
    
    public function testNotifications() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $_SESSION['user_id'] = 1;
                $_SESSION['user_name'] = 'Test User';
                $_SESSION['role'] = 'admin';
            }
            
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Add missing columns to existing table
            try {
                $db->exec("ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT 'info'");
            } catch (Exception $e) {
                // Column might already exist
            }
            try {
                $db->exec("ALTER TABLE notifications ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            } catch (Exception $e) {
                // Column might already exist
            }
            
            // Insert test data
            $db->exec("DELETE FROM notifications WHERE title LIKE 'TEST:%'");
            $stmt = $db->prepare("INSERT INTO notifications (title, message, type, is_read, user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['TEST: New Task', 'You have a new task', 'info', 0, 1]);
            $stmt->execute(['TEST: Leave Approved', 'Leave approved', 'success', 0, 1]);
            $stmt->execute(['TEST: Expense Pending', 'Expense pending', 'warning', 0, 1]);
            $stmt->execute(['TEST: System Update', 'System updated', 'info', 1, 1]);
            
            echo 'Test data created successfully. <a href="/ergon/notifications">View Notifications</a>';
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
}
?>
