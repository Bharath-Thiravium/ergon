<?php
session_start();
header('Content-Type: application/json');

// Debug attendance API
echo json_encode([
    'session_user_id' => $_SESSION['user_id'] ?? 'NOT SET',
    'post_data' => $_POST,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'session_data' => $_SESSION
]);

// Test database connection
try {
    require_once __DIR__ . '/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\nUser found: " . json_encode($user);
    }
    
} catch (Exception $e) {
    echo "\nDatabase error: " . $e->getMessage();
}
?>