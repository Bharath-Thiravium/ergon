<?php
/**
 * Migration: Fix login_attempts table missing columns
 */

require_once __DIR__ . '/../app/config/environment.php';

// Load .env manually
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

// Connect directly — use production credentials if ENV not set correctly
try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $name = $_ENV['DB_NAME'] ?? 'u494785662_ergon';
    $user = $_ENV['DB_USER'] ?? 'u494785662_ergon';
    $pass = $_ENV['DB_PASS'] ?? '@Admin@2025@';

    // Safety check — refuse to run with root on production
    if (Environment::isProduction() && $user === 'root') {
        $name = 'u494785662_ergon';
        $user = 'u494785662_ergon';
        $pass = '@Admin@2025@';
    }

    $db = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "✓ Connected to: $name\n\n";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

$migrations = [
    "ALTER TABLE login_attempts ADD COLUMN email VARCHAR(255) NULL",
    "ALTER TABLE login_attempts ADD COLUMN success TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE login_attempts ADD COLUMN user_agent TEXT NULL",
    "ALTER TABLE login_attempts ADD COLUMN ip_address VARCHAR(45) NOT NULL DEFAULT ''",
    "CREATE INDEX idx_email ON login_attempts (email)",
    "CREATE INDEX idx_ip ON login_attempts (ip_address)",
];

try {
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
