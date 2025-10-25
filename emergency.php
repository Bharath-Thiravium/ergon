<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERGON - Emergency Access</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .btn { padding: 10px 20px; margin: 5px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🧭 ERGON - Emergency System Access</h1>
    
    <?php
    session_start();
    
    // Database test
    try {
        require_once __DIR__ . '/config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        echo '<div class="status success">✅ Database connection: OK</div>';
    } catch (Exception $e) {
        echo '<div class="status error">❌ Database connection: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    // Session test
    echo '<div class="status info">📋 Session Status: ' . (isset($_SESSION['user_id']) ? 'Logged in as ' . htmlspecialchars($_SESSION['user_name'] ?? 'Unknown') : 'Not logged in') . '</div>';
    
    // Login form
    if (!isset($_SESSION['user_id'])) {
        if ($_POST['login'] ?? false) {
            require_once __DIR__ . '/app/models/User.php';
            $user = new User();
            $result = $user->authenticate($_POST['email'] ?? '', $_POST['password'] ?? '');
            
            if ($result) {
                $_SESSION['user_id'] = $result['id'];
                $_SESSION['user_name'] = $result['name'];
                $_SESSION['role'] = $result['role'];
                echo '<div class="status success">✅ Login successful! <a href="emergency.php">Refresh</a></div>';
            } else {
                echo '<div class="status error">❌ Login failed</div>';
            }
        }
        ?>
        <form method="post" style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3>Emergency Login</h3>
            <p><input type="email" name="email" placeholder="Email" required style="width: 100%; padding: 8px; margin: 5px 0;"></p>
            <p><input type="password" name="password" placeholder="Password" required style="width: 100%; padding: 8px; margin: 5px 0;"></p>
            <p><button type="submit" name="login" value="1" class="btn">Login</button></p>
            <small>Default: info@athenas.co.in / admin123</small>
        </form>
        <?php
    } else {
        echo '<div class="status success">✅ Logged in as: ' . htmlspecialchars($_SESSION['user_name']) . ' (' . htmlspecialchars($_SESSION['role']) . ')</div>';
        echo '<p><a href="emergency.php?logout=1" class="btn" style="background: #dc3545;">Logout</a></p>';
        
        if ($_GET['logout'] ?? false) {
            session_destroy();
            echo '<script>window.location.href="emergency.php";</script>';
        }
    }
    ?>
    
    <h3>System Links</h3>
    <p>
        <a href="/ergon/" class="btn">Main System</a>
        <a href="/ergon/login" class="btn">Login Page</a>
        <a href="/ergon/dashboard" class="btn">Dashboard</a>
    </p>
    
    <h3>System Status</h3>
    <div class="status info">
        <strong>Current Issues:</strong><br>
        • Quirks Mode: Fixed with proper DOCTYPE<br>
        • 500 Errors: Complex dependencies causing failures<br>
        • 404 Errors: Router configuration issues<br>
        <br>
        <strong>Working Features:</strong><br>
        • Database connection ✅<br>
        • User authentication ✅<br>
        • Session management ✅<br>
    </div>
</body>
</html>