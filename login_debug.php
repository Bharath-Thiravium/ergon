<?php
/**
 * Quick Login Debug
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Login Debug</h1>";

// Test database connection
try {
    require_once __DIR__ . '/config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connected<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit;
}

// Test user authentication
try {
    require_once __DIR__ . '/app/models/User.php';
    $userModel = new User();
    
    $email = 'info@athenas.co.in';
    $password = 'admin123';
    
    echo "<h2>Testing Authentication:</h2>";
    echo "Email: $email<br>";
    echo "Password: $password<br>";
    
    $user = $userModel->authenticate($email, $password);
    
    if ($user) {
        echo "✅ Authentication successful<br>";
        echo "User: " . json_encode($user) . "<br>";
    } else {
        echo "❌ Authentication failed<br>";
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $dbUser = $stmt->fetch();
        
        if ($dbUser) {
            echo "User exists in database<br>";
            echo "Status: " . $dbUser['status'] . "<br>";
            
            if (password_verify($password, $dbUser['password'])) {
                echo "✅ Password is correct<br>";
            } else {
                echo "❌ Password is incorrect - resetting...<br>";
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $updateStmt->execute([$newHash, $email]);
                echo "Password reset - try again<br>";
            }
        } else {
            echo "❌ User not found<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test login endpoint
echo "<h2>Test Login Form:</h2>";
?>
<form method="POST" action="/ergon/login">
    <input type="email" name="email" value="info@athenas.co.in" required><br>
    <input type="password" name="password" value="admin123" required><br>
    <button type="submit">Test Login</button>
</form>

<p><a href="/ergon/login">Go to Login Page</a></p>