<?php
session_start();

echo "<h1>Session Audit</h1>";

// Check current session
echo "<h2>Current Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check database user data
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT id, name, email, role, status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    echo "<h2>Database User Data:</h2>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    if ($user && $user['role'] !== $_SESSION['role']) {
        echo "<h2>❌ ROLE MISMATCH DETECTED!</h2>";
        echo "Session role: " . $_SESSION['role'] . "<br>";
        echo "Database role: " . $user['role'] . "<br>";
    }
}

// Check for role changes in recent requests
echo "<h2>Recent Activity:</h2>";
echo "Last activity: " . ($_SESSION['last_activity'] ?? 'Not set') . "<br>";
echo "Current time: " . time() . "<br>";
?>