<?php
session_start();

// Destroy session completely
$_SESSION = array();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Clear any auth cookies
if (isset($_COOKIE['jwt_token'])) {
    setcookie('jwt_token', '', time() - 3600, '/', '', false, true);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Logged Out - ERGON</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin: 50px; }
        .logout-message { background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px auto; max-width: 400px; }
    </style>
</head>
<body>
    <div class="logout-message">
        <h2>✅ Successfully Logged Out</h2>
        <p>You have been logged out of ERGON system.</p>
        <p><a href="/ergon/">Login Again</a></p>
    </div>
    
    <script>
        // Clear any cached data
        if ('caches' in window) {
            caches.keys().then(names => {
                names.forEach(name => caches.delete(name));
            });
        }
        
        // Prevent back button access
        window.history.pushState(null, null, window.location.href);
        window.addEventListener('popstate', function() {
            window.history.pushState(null, null, window.location.href);
        });
    </script>
</body>
</html>