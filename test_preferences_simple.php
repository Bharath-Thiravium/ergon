<?php
// Simple test to isolate the preferences saving issue
session_start();

// Include required files
require_once __DIR__ . '/app/helpers/Security.php';

echo "<h2>Simple Preferences Test</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Form Submitted!</h3>";
    
    // Check CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    echo "CSRF Token validation: ";
    
    if (Security::validateCSRFToken($csrfToken)) {
        echo "✅ PASSED<br>";
        
        // Process form data
        $preferences = [
            'theme' => Security::sanitizeString($_POST['theme'] ?? 'light'),
            'dashboard_layout' => Security::sanitizeString($_POST['dashboard_layout'] ?? 'default'),
            'language' => Security::sanitizeString($_POST['language'] ?? 'en'),
            'timezone' => Security::sanitizeString($_POST['timezone'] ?? 'UTC'),
            'notifications_email' => isset($_POST['notifications_email']) ? '1' : '0',
            'notifications_browser' => isset($_POST['notifications_browser']) ? '1' : '0'
        ];
        
        echo "<h3>Processed Preferences:</h3>";
        echo "<pre>" . print_r($preferences, true) . "</pre>";
        
        // Simulate successful save
        echo "<div style='color: green; font-weight: bold;'>✅ Preferences would be saved successfully!</div>";
        
    } else {
        echo "❌ FAILED<br>";
        echo "This is likely the cause of your 302 redirect with error=1<br>";
    }
    
} else {
    // Show form
    $csrfToken = Security::generateCSRFToken();
    ?>
    <form method="POST" style="max-width: 500px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        
        <h3>Theme</h3>
        <select name="theme">
            <option value="light">Light</option>
            <option value="dark">Dark</option>
            <option value="auto">Auto</option>
        </select>
        
        <h3>Dashboard Layout</h3>
        <select name="dashboard_layout">
            <option value="default">Default</option>
            <option value="compact">Compact</option>
            <option value="expanded">Expanded</option>
        </select>
        
        <h3>Language</h3>
        <select name="language">
            <option value="en">English</option>
            <option value="hi">Hindi</option>
            <option value="es">Spanish</option>
        </select>
        
        <h3>Timezone</h3>
        <select name="timezone">
            <option value="UTC">UTC</option>
            <option value="Asia/Kolkata">India (IST)</option>
            <option value="America/New_York">Eastern Time</option>
        </select>
        
        <h3>Notifications</h3>
        <label>
            <input type="checkbox" name="notifications_email" checked>
            Email Notifications
        </label><br>
        <label>
            <input type="checkbox" name="notifications_browser" checked>
            Browser Notifications
        </label><br><br>
        
        <button type="submit" style="padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 4px;">
            Save Preferences
        </button>
    </form>
    <?php
}
?>