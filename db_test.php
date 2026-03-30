<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

echo "Step 1: PHP works\n";

// Check .env files
$envProd = __DIR__ . '/.env.production';
$env     = __DIR__ . '/.env';
echo "Step 2: .env.production exists: " . (file_exists($envProd) ? 'YES' : 'NO') . "\n";
echo "Step 3: .env exists: "            . (file_exists($env)     ? 'YES' : 'NO') . "\n";

// Load whichever env file exists
$envFile = file_exists($envProd) ? $envProd : $env;
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v);
        }
    }
    echo "Step 4: Env loaded from: $envFile\n";
} else {
    echo "Step 4: No env file found\n";
}

echo "Step 5: DB_HOST=" . ($_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
echo "Step 6: DB_NAME=" . ($_ENV['DB_NAME'] ?? 'NOT SET') . "\n";
echo "Step 7: DB_USER=" . ($_ENV['DB_USER'] ?? 'NOT SET') . "\n";

// Try MySQL connection
echo "Step 8: Connecting to MySQL...\n";
try {
    $pdo = new PDO(
        "mysql:host=" . ($_ENV['DB_HOST'] ?? 'localhost') . ";dbname=" . ($_ENV['DB_NAME'] ?? '') . ";charset=utf8mb4",
        $_ENV['DB_USER'] ?? '',
        $_ENV['DB_PASS'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "Step 9: MySQL connected OK\n";
    echo "Step 10: Tables:\n";
    foreach ($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) as $t) {
        echo "  - $t\n";
    }
} catch (Exception $e) {
    echo "Step 9: MySQL FAILED — " . $e->getMessage() . "\n";
}

echo "DONE\n";
