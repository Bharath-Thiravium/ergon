<?php
/**
 * Login Test Script
 * Comprehensive test of login functionality
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ERGON Login Test</h1>";

// Test database connection
echo "<h2>1. Database Connection</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit;
}

// Test user model
echo "<h2>2. User Model</h2>";
try {
    require_once __DIR__ . '/app/models/User.php';
    $userModel = new User();
    echo "✅ User model loaded<br>";
} catch (Exception $e) {
    echo "❌ User model error: " . $e->getMessage() . "<br>";
    exit;
}

// Test authentication
echo "<h2>3. Authentication Test</h2>";
$testEmail = 'info@athenas.co.in';
$testPassword = 'admin123';

echo "Testing with: $testEmail / $testPassword<br>";
$user = $userModel->authenticate($testEmail, $testPassword);

if ($user) {
    echo "✅ Authentication successful<br>";
    echo "User: " . json_encode($user, JSON_PRETTY_PRINT) . "<br>";
} else {
    echo "❌ Authentication failed<br>";
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dbUser) {
        echo "User exists in database:<br>";
        echo "Status: " . $dbUser['status'] . "<br>";
        echo "Password hash: " . substr($dbUser['password'], 0, 20) . "...<br>";
        
        if (password_verify($testPassword, $dbUser['password'])) {
            echo "✅ Password verification works<br>";
        } else {
            echo "❌ Password verification failed - updating password<br>";
            $newHash = password_hash($testPassword, PASSWORD_BCRYPT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $updateStmt->execute([$newHash, $testEmail]);
            echo "Password updated, try again<br>";
        }
    } else {
        echo "User not found - creating user<br>";
        $hashedPassword = password_hash($testPassword, PASSWORD_BCRYPT);
        $insertStmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->execute(['Admin User', $testEmail, $hashedPassword, 'admin', 'active']);
        echo "User created<br>";
    }
}

// Test controller
echo "<h2>4. Controller Test</h2>";
try {
    require_once __DIR__ . '/app/core/Controller.php';
    require_once __DIR__ . '/app/controllers/AuthController.php';
    
    $authController = new AuthController();
    echo "✅ AuthController loaded<br>";
} catch (Exception $e) {
    echo "❌ Controller error: " . $e->getMessage() . "<br>";
}

// Test login simulation
echo "<h2>5. Login Simulation</h2>";
$_POST['email'] = $testEmail;
$_POST['password'] = $testPassword;
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
try {
    $authController = new AuthController();
    $authController->login();
} catch (Exception $e) {
    echo "❌ Login simulation error: " . $e->getMessage() . "<br>";
}
$output = ob_get_clean();

if (strpos($output, '"success":true') !== false) {
    echo "✅ Login simulation successful<br>";
    echo "Response: " . htmlspecialchars($output) . "<br>";
} else {
    echo "❌ Login simulation failed<br>";
    echo "Response: " . htmlspecialchars($output) . "<br>";
}

echo "<h2>Test Complete</h2>";
echo "<a href='/ergon/login'>Go to Login Page</a> | ";
echo "<a href='/ergon/setup_user.php'>Setup User</a>";
?>