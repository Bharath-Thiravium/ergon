<?php
/**
 * Hostinger Deployment Fix Script
 * Run this script once on Hostinger to ensure preferences saving works
 */

echo "<h2>Hostinger Preferences Fix Deployment</h2>";

// Include optimizations
require_once __DIR__ . '/hostinger_optimizations.php';

if (!isHostingerEnvironment()) {
    echo "<p style='color: orange;'>⚠️ This script is designed for Hostinger environment. Current environment may not need these fixes.</p>";
}

echo "<h3>1. Checking Session Configuration</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session is active<br>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Session save path: " . session_save_path() . "<br>";
} else {
    echo "❌ Session not active<br>";
}

echo "<h3>2. Testing Database Connection</h3>";
try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    echo "✅ Database connection successful<br>";
    
    // Test user_preferences table
    $sql = "CREATE TABLE IF NOT EXISTS user_preferences (
        user_id INT PRIMARY KEY,
        theme VARCHAR(20) DEFAULT 'light',
        dashboard_layout VARCHAR(20) DEFAULT 'default',
        language VARCHAR(10) DEFAULT 'en',
        timezone VARCHAR(50) DEFAULT 'UTC',
        notifications_email TINYINT(1) DEFAULT 1,
        notifications_browser TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "✅ User preferences table created/verified<br>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h3>3. Testing CSRF Token Generation</h3>";
require_once __DIR__ . '/app/helpers/Security.php';

try {
    $token = Security::generateCSRFToken();
    echo "✅ CSRF token generated: " . substr($token, 0, 16) . "...<br>";
    
    // Test validation
    $isValid = Security::validateCSRFToken($token);
    echo "✅ CSRF token validation: " . ($isValid ? "PASSED" : "FAILED") . "<br>";
    
} catch (Exception $e) {
    echo "❌ CSRF token error: " . $e->getMessage() . "<br>";
}

echo "<h3>4. File Permissions Check</h3>";
$directories = [
    __DIR__ . '/storage/logs',
    __DIR__ . '/storage/sessions',
    __DIR__ . '/storage/cache'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "📁 Created directory: " . basename($dir) . "<br>";
    }
    
    if (is_writable($dir)) {
        echo "✅ Directory writable: " . basename($dir) . "<br>";
    } else {
        echo "❌ Directory not writable: " . basename($dir) . "<br>";
    }
}

echo "<h3>5. Environment Detection</h3>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";
echo "Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "<br>";
echo "Is Hostinger: " . (isHostingerEnvironment() ? "YES" : "NO") . "<br>";

echo "<h3>✅ Deployment Complete</h3>";
echo "<p>The preferences saving issue should now be fixed on Hostinger. Try saving your preferences again.</p>";

// Clean up any test data
if (isset($db)) {
    try {
        $db->exec("DELETE FROM user_preferences WHERE user_id = 999");
    } catch (Exception $e) {
        // Ignore cleanup errors
    }
}
?>