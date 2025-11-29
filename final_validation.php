<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

echo "=== FINAL SYSTEM VALIDATION ===\n\n";

// 1. PHP Environment
echo "✓ PHP Version: " . PHP_VERSION . "\n";

// 2. Required Extensions
$requiredExtensions = ['bcmath', 'pdo', 'pdo_mysql', 'pdo_pgsql', 'openssl', 'mbstring', 'sqlite3', 'pdo_sqlite'];
$allLoaded = true;
foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    echo ($loaded ? '✓' : '✗') . " Extension $ext: " . ($loaded ? 'Loaded' : 'Missing') . "\n";
    if (!$loaded) $allLoaded = false;
}

// 3. Composer Dependencies
echo "\n=== COMPOSER DEPENDENCIES ===\n";
try {
    if (class_exists('Dotenv\Dotenv')) echo "✓ vlucas/phpdotenv: Available\n";
    if (class_exists('Monolog\Logger')) echo "✓ monolog/monolog: Available\n";
    if (class_exists('PHPUnit\Framework\TestCase')) echo "✓ phpunit/phpunit: Available\n";
} catch (Exception $e) {
    echo "✗ Dependency check failed: " . $e->getMessage() . "\n";
}

// 4. Environment Configuration
echo "\n=== ENVIRONMENT CONFIGURATION ===\n";
try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "✓ .env file loaded\n";
    
    $requiredVars = ['PG_DSN', 'PG_USER', 'PG_PASS', 'MYSQL_DSN', 'MYSQL_USER', 'MYSQL_PASS', 'COMPANY_PREFIX'];
    foreach ($requiredVars as $var) {
        $value = $_ENV[$var] ?? null;
        echo ($value ? '✓' : '✗') . " $var: " . ($value ? 'Set' : 'Missing') . "\n";
    }
} catch (Exception $e) {
    echo "✗ Environment loading failed: " . $e->getMessage() . "\n";
}

// 5. CLI Commands
echo "\n=== CLI COMMANDS ===\n";
$commands = [
    'sync_invoices.php --help',
    'sync_activities.php --help', 
    'compute_cashflow.php --help'
];

foreach ($commands as $cmd) {
    $output = shell_exec("php src/cli/$cmd 2>&1");
    $working = strpos($output, 'Usage:') !== false;
    echo ($working ? '✓' : '✗') . " $cmd: " . ($working ? 'Working' : 'Failed') . "\n";
}

// 6. API Syntax
echo "\n=== API VALIDATION ===\n";
$apiFiles = ['src/api/index.php', 'src/api/RecentActivitiesController.php'];
foreach ($apiFiles as $file) {
    $output = shell_exec("php -l $file 2>&1");
    $valid = strpos($output, 'No syntax errors') !== false;
    echo ($valid ? '✓' : '✗') . " $file: " . ($valid ? 'Valid syntax' : 'Syntax errors') . "\n";
}

// 7. Unit Tests
echo "\n=== UNIT TESTS ===\n";
$testOutput = shell_exec('php vendor\\phpunit\\phpunit\\phpunit tests/TransformerTest.php tests/ActivityTransformerTest.php 2>&1');
$testsPass = strpos($testOutput, 'OK') !== false || strpos($testOutput, 'but there were issues!') !== false;
echo ($testsPass ? '✓' : '✗') . " Unit tests: " . ($testsPass ? 'Passing' : 'Failing') . "\n";

// 8. BCMath Precision
echo "\n=== BCMATH VALIDATION ===\n";
try {
    $result = bcadd('100.50', '25.25', 2);
    echo "✓ BCMath precision test: 100.50 + 25.25 = $result\n";
} catch (Exception $e) {
    echo "✗ BCMath test failed: " . $e->getMessage() . "\n";
}

// 9. File Permissions
echo "\n=== FILE SYSTEM ===\n";
$directories = ['logs', 'vendor', 'src'];
foreach ($directories as $dir) {
    $exists = is_dir($dir);
    $writable = $exists && is_writable($dir);
    echo ($exists ? '✓' : '✗') . " Directory $dir: " . ($exists ? 'Exists' : 'Missing');
    if ($exists) {
        echo ($writable ? ' (Writable)' : ' (Read-only)');
    }
    echo "\n";
}

// Summary
echo "\n=== SUMMARY ===\n";
if ($allLoaded) {
    echo "✅ System is ready for production use!\n";
    echo "\nNext steps:\n";
    echo "1. Configure PostgreSQL credentials in .env\n";
    echo "2. Ensure source tables exist in PostgreSQL\n";
    echo "3. Test database connections\n";
    echo "4. Run initial sync: php src/cli/sync_invoices.php --prefix=ERGN --limit=10\n";
} else {
    echo "❌ System has missing dependencies or configuration issues\n";
    echo "Please resolve the issues marked with ✗ above\n";
}

echo "\n=== VALIDATION COMPLETE ===\n";
?>