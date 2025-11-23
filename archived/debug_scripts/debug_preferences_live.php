<?php
// Live debug for preferences issue on Hostinger
session_start();
require_once __DIR__ . '/app/helpers/Security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Debug Results</h2>";
    echo "<strong>POST Data:</strong><br>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    echo "<strong>Session Data:</strong><br>";
    echo "Session ID: " . session_id() . "<br>";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
    echo "Session CSRF: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "<br>";
    
    $submittedToken = $_POST['csrf_token'] ?? '';
    echo "<strong>CSRF Check:</strong><br>";
    echo "Submitted: " . $submittedToken . "<br>";
    echo "Match: " . (Security::validateCSRFToken($submittedToken) ? 'YES' : 'NO') . "<br>";
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "<div style='color:red;'>❌ USER NOT LOGGED IN</div>";
    } else {
        echo "<div style='color:green;'>✅ User logged in: " . $_SESSION['user_id'] . "</div>";
    }
    
} else {
    $token = Security::generateCSRFToken();
    ?>
    <h2>Debug Form</h2>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $token ?>">
        <select name="theme">
            <option value="light">Light</option>
            <option value="dark">Dark</option>
        </select><br><br>
        <input type="checkbox" name="notifications_email" checked> Email<br><br>
        <button type="submit">Test Submit</button>
    </form>
    
    <h3>Current Session:</h3>
    <p>Session ID: <?= session_id() ?></p>
    <p>CSRF Token: <?= $token ?></p>
    <p>User ID: <?= $_SESSION['user_id'] ?? 'NOT SET' ?></p>
    <?php
}
?>