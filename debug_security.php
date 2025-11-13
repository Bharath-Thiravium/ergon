<?php
/**
 * Debug Security Implementation
 * Check if security services are working
 */

echo "<h1>🔍 Security Debug</h1>";

// Check if security files exist
$files = [
    'app/services/SecurityService.php',
    'app/services/EmailService.php',
    'app/controllers/AuthController.php'
];

echo "<h2>📁 File Check</h2>";
foreach ($files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo "<div style='color: " . ($exists ? 'green' : 'red') . "'>";
    echo ($exists ? '✅' : '❌') . " $file";
    echo "</div>";
}

// Check database tables
echo "<h2>🗄️ Database Check</h2>";
try {
    require_once __DIR__ . '/app/config/database.php';
    $conn = Database::connect();
    
    $tables = ['login_attempts', 'rate_limit_log', 'password_change_log'];
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM $table");
            echo "<div style='color: green'>✅ Table '$table' exists</div>";
        } catch (Exception $e) {
            echo "<div style='color: red'>❌ Table '$table' missing</div>";
        }
    }
    
    // Check users table columns
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['reset_token', 'reset_token_expires', 'locked_until', 'failed_attempts'];
    foreach ($requiredColumns as $col) {
        $exists = in_array($col, $columns);
        echo "<div style='color: " . ($exists ? 'green' : 'red') . "'>";
        echo ($exists ? '✅' : '❌') . " Column 'users.$col'";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red'>❌ Database connection failed: " . $e->getMessage() . "</div>";
}

// Test security service
echo "<h2>🛡️ Security Service Test</h2>";
try {
    require_once __DIR__ . '/app/services/SecurityService.php';
    $security = new SecurityService();
    echo "<div style='color: green'>✅ SecurityService loaded successfully</div>";
    
    // Test rate limiting
    $canProceed = $security->checkRateLimit('127.0.0.1', 'test');
    echo "<div style='color: green'>✅ Rate limiting check: " . ($canProceed ? 'Allowed' : 'Blocked') . "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red'>❌ SecurityService error: " . $e->getMessage() . "</div>";
}

// Test email service
echo "<h2>📧 Email Service Test</h2>";
try {
    require_once __DIR__ . '/app/services/EmailService.php';
    $email = new EmailService();
    echo "<div style='color: green'>✅ EmailService loaded successfully</div>";
} catch (Exception $e) {
    echo "<div style='color: red'>❌ EmailService error: " . $e->getMessage() . "</div>";
}

echo "<h2>🔧 Quick Fix</h2>";
echo "<p>If tables are missing, run this SQL:</p>";
echo "<pre style='background: #f5f5f5; padding: 10px;'>";
echo file_get_contents(__DIR__ . '/quick_setup.sql');
echo "</pre>";
?>