<?php
// One-time script - DELETE AFTER RUNNING
$envPath = __DIR__ . '/.env';

$content = '# Production .env - DO NOT OVERWRITE
DB_HOST=localhost
DB_NAME=u494785662_ergon
DB_USER=u494785662_ergon
DB_PASS=@Admin@2025@
DB_PORT=3306

APP_ENV=production
APP_DEBUG=false
SESSION_LIFETIME=1440

# Notification Services
FCM_SERVER_KEY=your_fcm_key_here
SMS_ACCOUNT_SID=your_sms_sid_here

# SAP PostgreSQL Connection
SAP_PG_HOST=72.60.218.167
SAP_PG_PORT=5432
SAP_PG_DB=modernsap
SAP_PG_USER=postgres
SAP_PG_PASS=mango
';

file_put_contents($envPath, $content);

// Flush OPcache
if (function_exists('opcache_invalidate')) opcache_invalidate($envPath, true);
if (function_exists('opcache_reset')) opcache_reset();

// Re-read from disk to confirm
$verify = file_get_contents($envPath);
echo "=== FILE ON DISK ===\n";
echo $verify;
echo "\n=== PARSED $_ENV ===\n";

// Parse and show what PHP will actually read
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
        list($k, $v) = explode('=', $line, 2);
        echo trim($k) . ' = ' . trim($v) . "\n";
    }
}

echo "\nDELETE this file now.\n";
?>
