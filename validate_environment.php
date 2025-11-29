<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

echo "=== PHP Environment Validation ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";

// Check required extensions
$requiredExtensions = ['bcmath', 'pdo', 'pdo_mysql', 'pdo_pgsql', 'openssl'];
echo "\n=== Required Extensions ===\n";
foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    echo sprintf("%-12s: %s\n", $ext, $loaded ? '✓ Loaded' : '✗ Missing');
}

// Load environment variables
echo "\n=== Loading Environment Variables ===\n";
try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "✓ .env file loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Error loading .env: " . $e->getMessage() . "\n";
    exit(1);
}

// Test PostgreSQL connection
echo "\n=== PostgreSQL Connection Test ===\n";
try {
    $pgDsn = $_ENV['PG_DSN'];
    $pgUser = $_ENV['PG_USER'];
    $pgPass = $_ENV['PG_PASS'];
    
    echo "Connecting to: $pgDsn\n";
    echo "Username: $pgUser\n";
    
    $pdo = new PDO($pgDsn, $pgUser, $pgPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    // Test query
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "✓ PostgreSQL connection successful\n";
    echo "PostgreSQL version: $version\n";
    
} catch (Exception $e) {
    echo "✗ PostgreSQL connection failed: " . $e->getMessage() . "\n";
    echo "Note: This is expected if PostgreSQL is not set up yet\n";
}

// Test MySQL connection
echo "\n=== MySQL Connection Test ===\n";
try {
    $mysqlDsn = $_ENV['MYSQL_DSN'];
    $mysqlUser = $_ENV['MYSQL_USER'];
    $mysqlPass = $_ENV['MYSQL_PASS'];
    
    echo "Connecting to: $mysqlDsn\n";
    echo "Username: $mysqlUser\n";
    
    $pdo = new PDO($mysqlDsn, $mysqlUser, $mysqlPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    // Test query
    $stmt = $pdo->query("SELECT VERSION()");
    $version = $stmt->fetchColumn();
    echo "✓ MySQL connection successful\n";
    echo "MySQL version: $version\n";
    
} catch (Exception $e) {
    echo "✗ MySQL connection failed: " . $e->getMessage() . "\n";
}

// Test autoloader
echo "\n=== Autoloader Test ===\n";
try {
    // Test if we can load our classes
    if (class_exists('Dotenv\Dotenv')) {
        echo "✓ Dotenv class loaded\n";
    }
    if (class_exists('Monolog\Logger')) {
        echo "✓ Monolog class loaded\n";
    }
    if (class_exists('PHPUnit\Framework\TestCase')) {
        echo "✓ PHPUnit class loaded\n";
    }
} catch (Exception $e) {
    echo "✗ Autoloader test failed: " . $e->getMessage() . "\n";
}

// Test BCMath
echo "\n=== BCMath Test ===\n";
try {
    $result = bcadd('100.50', '25.25', 2);
    echo "✓ BCMath working: 100.50 + 25.25 = $result\n";
} catch (Exception $e) {
    echo "✗ BCMath test failed: " . $e->getMessage() . "\n";
}

echo "\n=== Validation Complete ===\n";
?>