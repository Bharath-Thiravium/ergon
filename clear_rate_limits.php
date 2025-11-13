<?php
/**
 * Clear Rate Limits - Development Tool Only
 * Access: http://localhost/ergon/clear_rate_limits.php
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $conn = Database::connect();
    
    // Clear rate limit logs
    $stmt = $conn->prepare("DELETE FROM rate_limit_log WHERE attempted_at < NOW()");
    $stmt->execute();
    $cleared1 = $stmt->rowCount();
    
    // Clear login attempts
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE attempted_at < NOW()");
    $stmt->execute();
    $cleared2 = $stmt->rowCount();
    
    // Unlock any locked accounts
    $stmt = $conn->prepare("UPDATE users SET locked_until = NULL, failed_attempts = 0 WHERE locked_until IS NOT NULL");
    $stmt->execute();
    $unlocked = $stmt->rowCount();
    
    echo "<h1>✅ Rate Limits Cleared</h1>";
    echo "<p>Cleared {$cleared1} rate limit entries</p>";
    echo "<p>Cleared {$cleared2} login attempt entries</p>";
    echo "<p>Unlocked {$unlocked} accounts</p>";
    echo "<p><a href='/ergon/user/dashboard'>← Back to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h1>❌ Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>