<?php
// Fix missing user preferences
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please log in first";
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = Database::connect();
    
    // Check if preferences exist
    $stmt = $db->prepare("SELECT user_id FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    if (!$stmt->fetch()) {
        // Create default preferences for user
        $stmt = $db->prepare("INSERT INTO user_preferences (user_id, theme, dashboard_layout, language, timezone, notifications_email, notifications_browser) VALUES (?, 'light', 'default', 'en', 'Asia/Kolkata', 1, 1)");
        $result = $stmt->execute([$userId]);
        
        if ($result) {
            echo "✅ Created default preferences for user $userId with Asia/Kolkata timezone";
        } else {
            echo "❌ Failed to create preferences";
        }
    } else {
        echo "✅ User preferences already exist";
    }
    
    // Verify
    $stmt = $db->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $prefs = $stmt->fetch();
    
    echo "<br><br>Current preferences: " . json_encode($prefs);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>