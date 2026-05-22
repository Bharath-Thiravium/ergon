<?php
/**
 * TEMPORARY DEBUG — delete after fixing.
 * Visit: https://yourdomain.com/ergon/cron/backup_debug.php
 */

$envFile = __DIR__ . '/../.env';
$env = [];
$rawLines = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $rawLines[] = bin2hex($line); // show exact bytes including hidden chars
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v);
        }
    }
} else {
    die(json_encode(['error' => '.env file NOT FOUND at: ' . $envFile]));
}

$token = $env['BACKUP_WEBHOOK_TOKEN'] ?? 'NOT SET';

echo json_encode([
    'env_file_found'  => file_exists($envFile),
    'env_file_path'   => $envFile,
    'token_value'     => $token,
    'token_hex'       => bin2hex($token),   // reveals hidden chars/BOM/spaces
    'token_length'    => strlen($token),
    'db_name'         => $env['DB_NAME'] ?? 'NOT SET',
    'app_env'         => $env['APP_ENV'] ?? 'NOT SET',
], JSON_PRETTY_PRINT);
