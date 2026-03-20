<?php
/**
 * Migration: Fix login_attempts table missing columns
 */

require_once __DIR__ . '/../app/config/database.php';

$migrations = [
    "ALTER TABLE login_attempts ADD COLUMN email VARCHAR(255) NULL",
    "ALTER TABLE login_attempts ADD COLUMN success TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE login_attempts ADD COLUMN user_agent TEXT NULL",
    "ALTER TABLE login_attempts ADD COLUMN ip_address VARCHAR(45) NOT NULL DEFAULT ''",
    "CREATE INDEX idx_email ON login_attempts (email)",
    "CREATE INDEX idx_ip ON login_attempts (ip_address)",
];

try {
    $db = Database::connect();
    echo "Starting migration: fix_login_attempts_columns\n\n";

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
                stripos($msg, 'Duplicate key name') !== false
            ) {
                echo "– skipped (exists): " . substr($sql, 0, 80) . "\n";
                $skipped++;
            } else {
                throw $e;
            }
        }
    }

    echo "\n✅ Migration complete — applied: $applied, skipped: $skipped\n";

    // Verify
    $cols = $db->query("SHOW COLUMNS FROM login_attempts")->fetchAll(PDO::FETCH_COLUMN);
    $missing = array_diff(['email', 'success', 'ip_address'], $cols);
    echo empty($missing)
        ? "\n✅ login_attempts — all required columns present\n"
        : "\n❌ login_attempts — still missing: " . implode(', ', $missing) . "\n";

} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    exit(1);
}
?>
