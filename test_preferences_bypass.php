<?php
// Temporary bypass test for preferences
session_start();

// Simulate logged in user (replace with actual user ID)
if (!isset($_SESSION['user_id'])) {
    echo "Please log in first, then run this test.";
    exit;
}

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/Security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Testing Direct Database Save</h2>";
    
    $userId = $_SESSION['user_id'];
    $preferences = [
        'theme' => Security::sanitizeString($_POST['theme'] ?? 'light'),
        'dashboard_layout' => Security::sanitizeString($_POST['dashboard_layout'] ?? 'default'),
        'language' => Security::sanitizeString($_POST['language'] ?? 'en'),
        'timezone' => Security::sanitizeString($_POST['timezone'] ?? 'UTC'),
        'notifications_email' => isset($_POST['notifications_email']) ? '1' : '0',
        'notifications_browser' => isset($_POST['notifications_browser']) ? '1' : '0'
    ];
    
    echo "User ID: " . $userId . "<br>";
    echo "Preferences: " . json_encode($preferences) . "<br><br>";
    
    try {
        $db = Database::connect();
        
        // Check if record exists
        $checkSql = "SELECT user_id FROM user_preferences WHERE user_id = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$userId]);
        $exists = $checkStmt->fetch();
        
        if ($exists) {
            echo "Updating existing record...<br>";
            $sql = "UPDATE user_preferences SET 
                    theme = ?, dashboard_layout = ?, language = ?, timezone = ?, 
                    notifications_email = ?, notifications_browser = ?, updated_at = NOW() 
                    WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $preferences['theme'],
                $preferences['dashboard_layout'],
                $preferences['language'],
                $preferences['timezone'],
                $preferences['notifications_email'],
                $preferences['notifications_browser'],
                $userId
            ]);
        } else {
            echo "Creating new record...<br>";
            $sql = "INSERT INTO user_preferences (user_id, theme, dashboard_layout, language, timezone, notifications_email, notifications_browser) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $userId,
                $preferences['theme'],
                $preferences['dashboard_layout'],
                $preferences['language'],
                $preferences['timezone'],
                $preferences['notifications_email'],
                $preferences['notifications_browser']
            ]);
        }
        
        if ($result) {
            echo "<div style='color:green;'>✅ SUCCESS: Preferences saved directly to database!</div>";
            echo "<p>This means the database save works. The issue is likely with CSRF validation or session handling.</p>";
        } else {
            echo "<div style='color:red;'>❌ FAILED: Database save failed</div>";
            echo "Error: " . json_encode($stmt->errorInfo());
        }
        
    } catch (Exception $e) {
        echo "<div style='color:red;'>❌ ERROR: " . $e->getMessage() . "</div>";
    }
    
} else {
    ?>
    <h2>Direct Database Test (No CSRF)</h2>
    <p>User ID: <?= $_SESSION['user_id'] ?? 'NOT LOGGED IN' ?></p>
    
    <form method="POST">
        <label>Theme:</label>
        <select name="theme">
            <option value="light">Light</option>
            <option value="dark">Dark</option>
        </select><br><br>
        
        <label>Layout:</label>
        <select name="dashboard_layout">
            <option value="default">Default</option>
            <option value="compact">Compact</option>
        </select><br><br>
        
        <label>
            <input type="checkbox" name="notifications_email" checked>
            Email Notifications
        </label><br><br>
        
        <button type="submit">Test Direct Save</button>
    </form>
    <?php
}
?>