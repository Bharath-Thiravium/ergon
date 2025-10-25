<?php
/**
 * Quick Authentication Test
 */

session_start();

echo "<h1>Quick Auth Test</h1>";

// Test 1: Check current session
echo "<h2>Current Session:</h2>";
if (isset($_SESSION['user_id'])) {
    echo "✅ Logged in as: " . $_SESSION['user_name'] . " (" . $_SESSION['role'] . ")<br>";
} else {
    echo "❌ Not logged in<br>";
}

// Test 2: Try to access dashboard controller
echo "<h2>Dashboard Access Test:</h2>";
try {
    require_once __DIR__ . '/app/middlewares/AuthMiddleware.php';
    
    // This should redirect if not authenticated
    ob_start();
    AuthMiddleware::requireAuth();
    $output = ob_get_clean();
    
    echo "✅ Dashboard access allowed<br>";
} catch (Exception $e) {
    echo "❌ Dashboard access error: " . $e->getMessage() . "<br>";
}

// Test 3: Manual login test
if (!isset($_SESSION['user_id'])) {
    echo "<h2>Manual Login Test:</h2>";
    echo "<form method='POST'>";
    echo "<input type='email' name='email' value='info@athenas.co.in' placeholder='Email'><br>";
    echo "<input type='password' name='password' value='admin123' placeholder='Password'><br>";
    echo "<button type='submit' name='login'>Login</button>";
    echo "</form>";
    
    if (isset($_POST['login'])) {
        require_once __DIR__ . '/app/models/User.php';
        $userModel = new User();
        $user = $userModel->authenticate($_POST['email'], $_POST['password']);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            
            echo "✅ Manual login successful - <a href='?'>Refresh</a><br>";
        } else {
            echo "❌ Manual login failed<br>";
        }
    }
}

// Test 4: Logout test
if (isset($_SESSION['user_id'])) {
    echo "<h2>Logout Test:</h2>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='logout'>Logout</button>";
    echo "</form>";
    
    if (isset($_POST['logout'])) {
        session_unset();
        session_destroy();
        echo "✅ Logged out - <a href='?'>Refresh</a><br>";
    }
}

echo "<br><a href='/ergon/dashboard'>Test Dashboard</a> | <a href='/ergon/login'>Login Page</a>";
?>