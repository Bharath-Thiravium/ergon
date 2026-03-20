<?php
/**
 * Migration: Fix missing columns in users and login_attempts tables
 */

require_once __DIR__ . '/../app/config/database.php';

$migrations = [
    // Add locked_until to users table (for account lockout after failed logins)
    "ALTER TABLE users ADD COLUMN locked_until DATETIME NULL AFTER status",
    
    // Add email column to login_attempts table if it exists
    "ALTER TABLE login_attempts ADD COLUMN email VARCHAR(255) NULL AFTER user_id",
    
    // If login_attempts doesn't exist, create it
    "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        email VARCHAR(255) NULL,
        ip_address VARCHAR(45) NOT NULL,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        success TINYINT(1) DEFAULT 0,
        INDEX idx_email (email),
        INDEX idx_ip (ip_address),
        INDEX idx_attempted_at (attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

try {
    $db = Database::connect();
    echo "Starting migration: fix_login_columns\n\n";

    $applied = 0;
    $skipped = 0;

    foreach ($migrations as $sql) {
        try {
            $db->exec($sql);
            echo "✓ " . substr($sql, 0, 80) . "\n";
            $applied++;
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (
                stripos($msg, 'Duplicate column name') !== false ||
                stripos($msg, 'already exists') !== false ||
                stripos($msg, 'Table') !== false && stripos($msg, 'already exists') !== false
            ) {
                echo "– skipped (already exists): " . substr($sql, 0, 80) . "\n";
                $skipped++;
            } else {
                throw $e;
            }
        }
    }

    echo "\n✅ Migration complete — applied: $applied, skipped: $skipped\n";

    // Verification
    echo "\nVerifying columns...\n";
    
    $usersColumns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('locked_until', $usersColumns)) {
        echo "✅ users.locked_until exists\n";
    } else {
        echo "❌ users.locked_until missing\n";
    }

    $stmt = $db->query("SHOW TABLES LIKE 'login_attempts'");
    if ($stmt->rowCount() > 0) {
        $loginColumns = $db->query("SHOW COLUMNS FROM login_attempts")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('email', $loginColumns)) {
            echo "✅ login_attempts.email exists\n";
        } else {
            echo "❌ login_attempts.email missing\n";
        }
    } else {
        echo "⚠ login_attempts table doesn't exist (will be created on first login)\n";
    }

} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . " line " . $e->getLine() . "\n";
    exit(1);
}
?>
