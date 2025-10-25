<?php
/**
 * Debug Login Script
 * Test authentication components individually
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ERGON Login Debug</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful<br>";
    echo "Environment: " . ($db->getEnvironment()) . "<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: User Model
echo "<h2>2. User Model Test</h2>";
try {
    require_once __DIR__ . '/app/models/User.php';
    $userModel = new User();
    echo "✅ User model loaded successfully<br>";
    
    // Test authentication with known credentials
    $testEmail = 'info@athenas.co.in';
    $testPassword = 'admin123';
    
    echo "Testing authentication with: $testEmail / $testPassword<br>";
    $user = $userModel->authenticate($testEmail, $testPassword);
    
    if ($user) {
        echo "✅ Authentication successful<br>";
        echo "User data: " . json_encode($user, JSON_PRETTY_PRINT) . "<br>";
    } else {
        echo "❌ Authentication failed<br>";
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
        $stmt->execute([$testEmail]);
        $dbUser = $stmt->fetch();
        
        if ($dbUser) {
            echo "User found in database:<br>";
            echo "ID: " . $dbUser['id'] . "<br>";
            echo "Name: " . $dbUser['name'] . "<br>";
            echo "Email: " . $dbUser['email'] . "<br>";
            echo "Role: " . $dbUser['role'] . "<br>";
            echo "Status: " . $dbUser['status'] . "<br>";
            echo "Password hash: " . substr($dbUser['password'], 0, 20) . "...<br>";
            
            // Test password verification
            if (password_verify($testPassword, $dbUser['password'])) {
                echo "✅ Password verification successful<br>";
            } else {
                echo "❌ Password verification failed<br>";
                echo "Trying to reset password...<br>";
                
                // Reset password
                $newHash = password_hash($testPassword, PASSWORD_BCRYPT);
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                if ($updateStmt->execute([$newHash, $testEmail])) {
                    echo "✅ Password reset successful<br>";
                } else {
                    echo "❌ Password reset failed<br>";
                }
            }
        } else {
            echo "❌ User not found in database<br>";
            echo "Creating test user...<br>";
            
            // Create test user
            $hashedPassword = password_hash($testPassword, PASSWORD_BCRYPT);
            $insertStmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
            if ($insertStmt->execute(['Admin User', $testEmail, $hashedPassword, 'admin', 'active'])) {
                echo "✅ Test user created successfully<br>";
            } else {
                echo "❌ Failed to create test user<br>";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ User model error: " . $e->getMessage() . "<br>";
}

// Test 3: Security Helper
echo "<h2>3. Security Helper Test</h2>";
try {
    require_once __DIR__ . '/app/helpers/Security.php';
    echo "✅ Security helper loaded<br>";
    
    // Test password hashing
    $testPass = 'test123';
    $hash = Security::hashPassword($testPass);
    echo "Password hash created: " . substr($hash, 0, 20) . "...<br>";
    
    if (Security::verifyPassword($testPass, $hash)) {
        echo "✅ Password verification works<br>";
    } else {
        echo "❌ Password verification failed<br>";
    }
    
    // Test CSRF token
    session_start();
    $token = Security::generateCSRFToken();
    echo "CSRF token generated: " . substr($token, 0, 20) . "...<br>";
    
    if (Security::validateCSRFToken($token)) {
        echo "✅ CSRF token validation works<br>";
    } else {
        echo "❌ CSRF token validation failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Security helper error: " . $e->getMessage() . "<br>";
}

// Test 4: Session Manager
echo "<h2>4. Session Manager Test</h2>";
try {
    require_once __DIR__ . '/app/helpers/SessionManager.php';
    echo "✅ Session manager loaded<br>";
    
    SessionManager::start();
    echo "✅ Session started successfully<br>";
    
} catch (Exception $e) {
    echo "❌ Session manager error: " . $e->getMessage() . "<br>";
}

// Test 5: Controller Loading
echo "<h2>5. Controller Test</h2>";
try {
    require_once __DIR__ . '/app/core/Controller.php';
    require_once __DIR__ . '/app/controllers/AuthController.php';
    echo "✅ Controllers loaded successfully<br>";
    
    $authController = new AuthController();
    echo "✅ AuthController instantiated<br>";
    
} catch (Exception $e) {
    echo "❌ Controller error: " . $e->getMessage() . "<br>";
}

// Test 6: Login Form Simulation
echo "<h2>6. Login Form Simulation</h2>";
try {
    $_POST['email'] = 'info@athenas.co.in';
    $_POST['password'] = 'admin123';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    echo "Simulating login request...<br>";
    
    // Simulate the login process
    $userModel = new User();
    $user = $userModel->authenticate($_POST['email'], $_POST['password']);
    
    if ($user) {
        echo "✅ Login simulation successful<br>";
        echo "User would be redirected to: " . (($user['role'] === 'admin') ? '/ergon/admin/dashboard' : '/ergon/dashboard') . "<br>";
    } else {
        echo "❌ Login simulation failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Login simulation error: " . $e->getMessage() . "<br>";
}

echo "<h2>Debug Complete</h2>";
echo "<a href='/ergon/login'>Go to Login Page</a>";
?>