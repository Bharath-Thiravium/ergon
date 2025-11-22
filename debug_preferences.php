<?php
// Debug script to check what's happening with preferences form
session_start();

echo "<h2>Debug Preferences Form Submission</h2>";

// Check if we have a session
echo "<h3>Session Info:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "CSRF Token: " . ($_SESSION['csrf_token'] ?? 'Not set') . "<br>";

// Check POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    // Check CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    echo "<h3>CSRF Validation:</h3>";
    echo "Submitted token: " . $csrfToken . "<br>";
    echo "Session token: " . ($_SESSION['csrf_token'] ?? 'Not set') . "<br>";
    
    if (isset($_SESSION['csrf_token'])) {
        echo "Tokens match: " . (hash_equals($_SESSION['csrf_token'], $csrfToken) ? 'YES' : 'NO') . "<br>";
    }
} else {
    // Generate CSRF token for testing
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    echo "<h3>Test Form:</h3>";
    echo '<form method="POST">';
    echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
    echo '<select name="theme">';
    echo '<option value="light">Light</option>';
    echo '<option value="dark">Dark</option>';
    echo '</select><br><br>';
    echo '<input type="checkbox" name="notifications_email" checked> Email Notifications<br><br>';
    echo '<button type="submit">Test Submit</button>';
    echo '</form>';
}
?>