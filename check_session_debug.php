<?php
session_start();

echo "<h2>Session Debug Information</h2>";
echo "<pre>";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "ACTIVE" : "INACTIVE") . "\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "\nFull Session Data:\n";
print_r($_SESSION);
echo "</pre>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'><strong>ROOT CAUSE FOUND:</strong> No user_id in session! You need to login first.</p>";
    echo "<p><a href='/ergon/login'>Go to Login Page</a></p>";
} else {
    echo "<p style='color: green;'>Session looks good. Let's check database...</p>";
    
    try {
        require_once __DIR__ . '/app/config/database.php';
        $db = Database::connect();
        $userId = $_SESSION['user_id'];
        $today = date('Y-m-d');
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM daily_tasks WHERE assigned_to = ? AND planned_date = ?");
        $stmt->execute([$userId, $today]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>Tasks in database for user $userId on $today: <strong>{$count['count']}</strong></p>";
        
        if ($count['count'] > 0) {
            $stmt = $db->prepare("SELECT id, title, priority, created_at FROM daily_tasks WHERE assigned_to = ? AND planned_date = ? ORDER BY created_at DESC LIMIT 5");
            $stmt->execute([$userId, $today]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Recent Tasks:</h3><ul>";
            foreach ($tasks as $task) {
                echo "<li>ID: {$task['id']} - {$task['title']} ({$task['priority']}) - {$task['created_at']}</li>";
            }
            echo "</ul>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
    }
}
?>