<?php
// Sync all user preferences with owner's preferences
session_start();
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Syncing User Preferences with Owner</h2>";
    
    // 1. Find owner's preferences
    $stmt = $db->prepare("SELECT u.id, up.* FROM users u LEFT JOIN user_preferences up ON u.id = up.user_id WHERE u.role = 'owner' LIMIT 1");
    $stmt->execute();
    $owner = $stmt->fetch();
    
    if (!$owner || !$owner['timezone']) {
        echo "❌ Owner preferences not found. Creating default owner preferences...<br>";
        
        // Get owner ID
        $stmt = $db->prepare("SELECT id FROM users WHERE role = 'owner' LIMIT 1");
        $stmt->execute();
        $ownerUser = $stmt->fetch();
        
        if ($ownerUser) {
            $ownerId = $ownerUser['id'];
            // Create owner preferences
            $stmt = $db->prepare("INSERT INTO user_preferences (user_id, theme, dashboard_layout, language, timezone, notifications_email, notifications_browser) VALUES (?, 'light', 'default', 'en', 'Asia/Kolkata', 1, 1)");
            $stmt->execute([$ownerId]);
            echo "✅ Created owner preferences<br>";
            
            // Re-fetch owner preferences
            $stmt = $db->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
            $stmt->execute([$ownerId]);
            $owner = $stmt->fetch();
        }
    }
    
    if ($owner) {
        echo "✅ Owner preferences found:<br>";
        echo "- Timezone: {$owner['timezone']}<br>";
        echo "- Theme: {$owner['theme']}<br>";
        echo "- Language: {$owner['language']}<br><br>";
        
        // 2. Get all users (admin and user roles)
        $stmt = $db->prepare("SELECT id, name, role FROM users WHERE role IN ('admin', 'user')");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        echo "<h3>Syncing " . count($users) . " users with owner preferences:</h3>";
        
        foreach ($users as $user) {
            // Check if user has preferences
            $stmt = $db->prepare("SELECT user_id FROM user_preferences WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Update existing preferences
                $stmt = $db->prepare("UPDATE user_preferences SET theme = ?, dashboard_layout = ?, language = ?, timezone = ?, notifications_email = ?, notifications_browser = ? WHERE user_id = ?");
                $stmt->execute([
                    $owner['theme'],
                    $owner['dashboard_layout'], 
                    $owner['language'],
                    $owner['timezone'],
                    $owner['notifications_email'],
                    $owner['notifications_browser'],
                    $user['id']
                ]);
                echo "✅ Updated {$user['name']} ({$user['role']})<br>";
            } else {
                // Create new preferences
                $stmt = $db->prepare("INSERT INTO user_preferences (user_id, theme, dashboard_layout, language, timezone, notifications_email, notifications_browser) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $user['id'],
                    $owner['theme'],
                    $owner['dashboard_layout'],
                    $owner['language'], 
                    $owner['timezone'],
                    $owner['notifications_email'],
                    $owner['notifications_browser']
                ]);
                echo "✅ Created preferences for {$user['name']} ({$user['role']})<br>";
            }
        }
        
        echo "<br><h3>✅ All users now have owner's timezone: {$owner['timezone']}</h3>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>