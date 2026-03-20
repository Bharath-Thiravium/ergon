<?php
/**
 * Migration: Fix missing columns in users and login_attempts tables
 */

require_once __DIR__ . '/../app/config/database.php';

$migrations = [
    // users — SecurityService needs these
    "ALTER TABLE users ADD COLUMN locked_until DATETIME NULL",
    "ALTER TABLE users ADD COLUMN failed_attempts INT NOT NULL DEFAULT 0",
    "ALTER TABLE users ADD COLUMN last_ip VARCHAR(45) NULL",
    "ALTER TABLE users ADD COLUMN last_login DATETIME NULL",
    "ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL",
    "ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL",
    "ALTER TABLE users ADD COLUMN reset_token_expires DATETIME NULL",

    // login_attempts — create with all required columns
    "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NULL,
        ip_address VARCHAR(45) NOT NULL DEFAULT '',
        user_agent TEXT NULL,
        success TINYINT(1) DEFAULT 0,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_ip (ip_address),
        INDEX idx_attempted_at (attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // rate_limit_log — SecurityService::checkRateLimit() needs this
    "CREATE TABLE IF NOT EXISTS rate_limit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        identifier VARCHAR(255) NOT NULL,
        action VARCHAR(64) NOT NULL DEFAULT 'login',
        success TINYINT(1) DEFAULT 0,
        ip_address VARCHAR(45) NULL,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_identifier (identifier),
        INDEX idx_action (action),
        INDEX idx_attempted_at (attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

try {
    $db = Database::connect();
    echo "Starting migration: fix_login_columns\n\n";

    $applied = 0;
    $skipped = 0;

    foreach ($migrations as $sql) {
        try {
            $db->exec($sql);
            echo "✓ " . substr(preg_replace('/\s+/', ' ', $sql), 0, 80) . "\n";
            $applied++;
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (
                stripos($msg, 'Duplicate column name') !== false ||
                stripos($msg, 'already exists') !== false
            ) {
                echo "– skipped (exists): " . substr(preg_replace('/\s+/', ' ', $sql), 0, 80) . "\n";
                $skipped++;
            } else {
                throw $e;
            }
        }
    }

    echo "\n✅ Migration complete — applied: $applied, skipped: $skipped\n";

    // Verification
    echo "\nVerifying...\n";
    $checks = [
        'users'           => ['locked_until', 'failed_attempts', 'last_ip', 'last_login', 'reset_token'],
        'login_attempts'  => ['email', 'ip_address', 'success'],
        'rate_limit_log'  => ['identifier', 'action', 'success'],
    ];
    foreach ($checks as $table => $cols) {
        $existing = $db->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
        $missing  = array_diff($cols, $existing);
        echo empty($missing)
            ? "✅ $table — OK\n"
            : "❌ $table — missing: " . implode(', ', $missing) . "\n";
    }

} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . " line " . $e->getLine() . "\n";
    exit(1);
}
?>
