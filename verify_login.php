<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Verification - ERGON</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🧭 ERGON Login Verification</h1>
    
    <?php
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "<h2>System Status Check</h2>";
    
    // 1. Database Connection
    echo "<h3>1. Database Connection</h3>";
    try {
        require_once __DIR__ . '/config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        echo "<span class='success'>✅ Database connected successfully</span><br>";
        echo "<span class='info'>Environment: " . $db->getEnvironment() . "</span><br>";
    } catch (Exception $e) {
        echo "<span class='error'>❌ Database connection failed: " . $e->getMessage() . "</span><br>";
        exit;
    }
    
    // 2. Check users table
    echo "<h3>2. Users Table</h3>";
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "<span class='success'>✅ Users table exists with " . $result['count'] . " users</span><br>";
    } catch (Exception $e) {
        echo "<span class='error'>❌ Users table error: " . $e->getMessage() . "</span><br>";
    }
    
    // 3. Check admin user
    echo "<h3>3. Admin User Check</h3>";
    $adminEmail = 'info@athenas.co.in';
    try {
        $stmt = $conn->prepare("SELECT id, name, email, role, status FROM users WHERE email = ?");
        $stmt->execute([$adminEmail]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<span class='success'>✅ Admin user found</span><br>";
            echo "<span class='info'>Name: " . $user['name'] . "</span><br>";
            echo "<span class='info'>Role: " . $user['role'] . "</span><br>";
            echo "<span class='info'>Status: " . $user['status'] . "</span><br>";
        } else {
            echo "<span class='error'>❌ Admin user not found</span><br>";
            echo "<span class='info'>Creating admin user...</span><br>";
            
            $password = 'admin123';
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            $insertStmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
            if ($insertStmt->execute(['Admin User', $adminEmail, $hashedPassword, 'admin', 'active'])) {
                echo "<span class='success'>✅ Admin user created</span><br>";
            } else {
                echo "<span class='error'>❌ Failed to create admin user</span><br>";
            }
        }
    } catch (Exception $e) {
        echo "<span class='error'>❌ Admin user check error: " . $e->getMessage() . "</span><br>";
    }
    
    // 4. Test authentication
    echo "<h3>4. Authentication Test</h3>";
    try {
        require_once __DIR__ . '/app/models/User.php';
        $userModel = new User();
        
        $testUser = $userModel->authenticate($adminEmail, 'admin123');
        if ($testUser) {
            echo "<span class='success'>✅ Authentication successful</span><br>";
            echo "<pre>" . json_encode($testUser, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<span class='error'>❌ Authentication failed</span><br>";
        }
    } catch (Exception $e) {
        echo "<span class='error'>❌ Authentication test error: " . $e->getMessage() . "</span><br>";
    }
    
    // 5. Test login controller
    echo "<h3>5. Login Controller Test</h3>";
    try {
        require_once __DIR__ . '/app/core/Controller.php';
        require_once __DIR__ . '/app/controllers/AuthController.php';
        
        $authController = new AuthController();
        echo "<span class='success'>✅ AuthController loaded successfully</span><br>";
    } catch (Exception $e) {
        echo "<span class='error'>❌ AuthController error: " . $e->getMessage() . "</span><br>";
    }
    ?>
    
    <h2>Test Login Form</h2>
    <form method="POST" action="">
        <input type="hidden" name="test_login" value="1">
        <div>
            <label>Email:</label>
            <input type="email" name="email" value="info@athenas.co.in" required>
        </div>
        <div>
            <label>Password:</label>
            <input type="password" name="password" value="admin123" required>
        </div>
        <button type="submit">Test Login</button>
    </form>
    
    <?php
    if (isset($_POST['test_login'])) {
        echo "<h3>Login Test Result</h3>";
        
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        try {
            $userModel = new User();
            $user = $userModel->authenticate($email, $password);
            
            if ($user) {
                echo "<span class='success'>✅ Login test successful!</span><br>";
                echo "<span class='info'>User would be redirected to dashboard</span><br>";
                
                // Set session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                
                echo "<span class='info'>Session data set successfully</span><br>";
            } else {
                echo "<span class='error'>❌ Login test failed</span><br>";
            }
        } catch (Exception $e) {
            echo "<span class='error'>❌ Login test error: " . $e->getMessage() . "</span><br>";
        }
    }
    ?>
    
    <h2>Navigation</h2>
    <a href="/ergon/login">Go to Login Page</a> | 
    <a href="/ergon/dashboard">Go to Dashboard</a> |
    <a href="/ergon/setup_user.php">Setup User</a>
    
</body>
</html>