<?php
require_once __DIR__ . '/../core/Controller.php';

class TestController extends Controller {
    
    public function index() {
        echo "<!DOCTYPE html><html><head><title>ERGON Test</title></head><body>";
        echo "<h1>🧭 ERGON System Test</h1>";
        
        // Test database connection
        try {
            require_once __DIR__ . '/../../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            echo "<p>✅ Database connection: OK</p>";
        } catch (Exception $e) {
            echo "<p>❌ Database connection: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        // Test session
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            echo "<p>✅ Session: OK</p>";
        } catch (Exception $e) {
            echo "<p>❌ Session: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        // Test authentication
        if (isset($_SESSION['user_id'])) {
            echo "<p>✅ User logged in: " . htmlspecialchars($_SESSION['user_name'] ?? 'Unknown') . " (" . htmlspecialchars($_SESSION['role'] ?? 'Unknown') . ")</p>";
        } else {
            echo "<p>⚠️ No user logged in</p>";
        }
        
        echo "<p><a href='/ergon/login'>Login</a> | <a href='/ergon/dashboard'>Dashboard</a></p>";
        echo "</body></html>";
    }
    
    public function status() {
        header('Content-Type: application/json');
        
        $status = [
            'system' => 'ERGON Employee Tracker',
            'status' => 'operational',
            'timestamp' => date('Y-m-d H:i:s'),
            'session' => isset($_SESSION['user_id']) ? 'authenticated' : 'guest',
            'user' => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null,
            'role' => isset($_SESSION['role']) ? $_SESSION['role'] : null
        ];
        
        echo json_encode($status, JSON_PRETTY_PRINT);
    }
}
?>