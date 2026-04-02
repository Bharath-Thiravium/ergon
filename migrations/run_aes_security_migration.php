<?php
/**
 * Migration: Add missing security columns for AES database
 * Run once: https://aes.athenas.co.in/ergon/migrations/run_aes_security_migration.php
 * DELETE after running.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/environment.php';
require_once __DIR__ . '/../app/config/database.php';

$pdo = Database::connect();
$results = [];

function run($pdo, $label, $sql) {
    try {
        $pdo->exec($sql);
        return "✅ $label";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // Duplicate column / already exists = fine
        if (strpos($msg, 'Duplicate column') !== false || strpos($msg, 'already exists') !== false) {
            return "⏭️  $label (already exists)";
        }
        return "❌ $label — $msg";
    }
}

// 1. Add locked_until to users
$results[] = run($pdo, 'users.locked_until column',
    "ALTER TABLE users ADD COLUMN locked_until DATETIME DEFAULT NULL"
);

// 2. Add failed_attempts to users
$results[] = run($pdo, 'users.failed_attempts column',
    "ALTER TABLE users ADD COLUMN failed_attempts INT DEFAULT 0"
);

// 3. Create rate_limit_log table
$results[] = run($pdo, 'rate_limit_log table',
    "CREATE TABLE IF NOT EXISTS rate_limit_log (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        identifier  VARCHAR(255) NOT NULL,
        action      VARCHAR(50)  NOT NULL DEFAULT 'login',
        attempted_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        success     TINYINT(1)   NOT NULL DEFAULT 0,
        ip_address  VARCHAR(45)  DEFAULT NULL,
        INDEX idx_identifier_action (identifier, action),
        INDEX idx_attempted_at (attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// 4. Ensure login_attempts table has all needed columns
$results[] = run($pdo, 'login_attempts table',
    "CREATE TABLE IF NOT EXISTS login_attempts (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        email        VARCHAR(255) NOT NULL,
        success      TINYINT(1)   NOT NULL DEFAULT 0,
        attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip_address   VARCHAR(45)  DEFAULT NULL,
        user_agent   TEXT         DEFAULT NULL,
        INDEX idx_email (email),
        INDEX idx_attempted_at (attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

echo "<pre style='font-family:monospace;background:#0f172a;color:#e2e8f0;padding:2rem;'>";
echo "<b>AES Security Migration</b>\n";
echo "DB: " . ($_ENV['DB_NAME'] ?? 'unknown') . "\n\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\n<b>Done.</b> Delete this file after running.\n";
echo "</pre>";
?>
