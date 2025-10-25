<?php
/**
 * Session Test - Debug session issues
 */

// Start session with proper configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 3600);
session_start();

echo "<h1>Session Debug Test</h1>";

// Show session configuration
echo "<h2>Session Configuration:</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";
echo "Session Name: " . session_name() . "<br>";

// Show current session data
echo "<h2>Current Session Data:</h2>";
if (empty($_SESSION)) {
    echo "❌ Session is empty<br>";
} else {
    echo "✅ Session contains data:<br>";
    foreach ($_SESSION as $key => $value) {
        echo "$key: " . htmlspecialchars($value) . "<br>";
    }
}

// Test setting session data
if (isset($_POST['set_session'])) {
    $_SESSION['test_user_id'] = 123;
    $_SESSION['test_role'] = 'admin';
    $_SESSION['test_time'] = time();
    
    // Force write
    session_write_close();
    session_start();
    
    echo "<span style='color: green;'>✅ Test session data set</span><br>";
}

// Test clearing session
if (isset($_POST['clear_session'])) {
    session_unset();
    session_destroy();
    echo "<span style='color: red;'>❌ Session cleared</span><br>";
    echo "<a href='?'>Refresh to see changes</a><br>";
}

// Manual login test
if (isset($_POST['manual_login'])) {
    require_once __DIR__ . '/app/models/User.php';
    
    try {
        $userModel = new User();
        $user = $userModel->authenticate('info@athenas.co.in', 'admin123');
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            
            // Force write
            session_write_close();
            session_start();
            
            echo "<span style='color: green;'>✅ Manual login successful</span><br>";
            echo "<a href='?'>Refresh to see session data</a><br>";
        } else {
            echo "<span style='color: red;'>❌ Manual login failed</span><br>";
        }
    } catch (Exception $e) {
        echo "<span style='color: red;'>❌ Login error: " . $e->getMessage() . "</span><br>";
    }
}

?>

<h2>Test Actions:</h2>
<form method="POST" style="margin: 10px 0;">
    <button type="submit" name="set_session">Set Test Session Data</button>
</form>

<form method="POST" style="margin: 10px 0;">
    <button type="submit" name="manual_login">Manual Login Test</button>
</form>

<form method="POST" style="margin: 10px 0;">
    <button type="submit" name="clear_session">Clear Session</button>
</form>

<h2>API Test:</h2>
<button onclick="testAuthAPI()">Test Auth API</button>
<div id="api-result"></div>

<script>
function testAuthAPI() {
    fetch('/ergon/api/check-auth.php')
    .then(response => response.json())
    .then(data => {
        document.getElementById('api-result').innerHTML = 
            '<strong>API Result:</strong><br>' + 
            'Authenticated: ' + (data.authenticated ? '✅ Yes' : '❌ No') + '<br>' +
            'User: ' + JSON.stringify(data.user) + '<br>' +
            'Timestamp: ' + data.timestamp;
    })
    .catch(error => {
        document.getElementById('api-result').innerHTML = 
            '<span style="color: red;">❌ API Error: ' + error.message + '</span>';
    });
}
</script>

<p><a href="/ergon/test_auth.php">Back to Auth Test</a></p>