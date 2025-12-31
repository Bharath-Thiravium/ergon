<?php
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/config/database.php';

Session::init();

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['key']) || !isset($input['value'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$key = $input['key'];
$value = $input['value'];
$userId = $_SESSION['user_id'];

// Validate preference key
$allowedKeys = ['theme', 'dashboard_layout', 'language'];
if (!in_array($key, $allowedKeys)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid preference key']);
    exit;
}

try {
    $pdo = Database::getConnection();
    
    // Check if user preferences table exists, create if not
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        preference_key VARCHAR(50) NOT NULL,
        preference_value VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_pref (user_id, preference_key),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Insert or update preference
    $stmt = $pdo->prepare("
        INSERT INTO user_preferences (user_id, preference_key, preference_value) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        preference_value = VALUES(preference_value),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([$userId, $key, $value]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Preference update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
