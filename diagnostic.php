<?php
/**
 * Server Diagnostic Script
 * Run this to identify server/database issues
 */

// Disable error display initially
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Ergon Diagnostics</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5}";
echo ".ok{color:green}.error{color:red}.warning{color:orange}";
echo "h2{border-bottom:2px solid #333;padding-bottom:5px}</style></head><body>";
echo "<h1>🔍 Ergon Server Diagnostics</h1>";

// ── 1. PHP Configuration ──────────────────────────────────────────────────────
echo "<h2>1. PHP Configuration</h2>";
echo "<div class='ok'>✓ PHP Version: " . phpversion() . "</div>";
echo "<div class='ok'>✓ Server: " . $_SERVER['SERVER_SOFTWARE'] . "</div>";
echo "<div class='ok'>✓ Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</div>";
echo "<div class='ok'>✓ Script Path: " . __FILE__ . "</div>";

// ── 2. Required Extensions ────────────────────────────────────────────────────
echo "<h2>2. Required PHP Extensions</h2>";
$required = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='ok'>✓ $ext loaded</div>";
    } else {
        echo "<div class='error'>✗ $ext NOT loaded</div>";
    }
}

// ── 3. File System Checks ─────────────────────────────────────────────────────
echo "<h2>3. File System</h2>";
$basePath = __DIR__;
$criticalFiles = [
    'app/config/database.php',
    'app/config/environment.php',
    'app/core/Router.php',
    'app/controllers/AdminController.php',
    'index.php',
    '.htaccess'
];

foreach ($criticalFiles as $file) {
    $fullPath = $basePath . '/' . $file;
    if (file_exists($fullPath)) {
        $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
        echo "<div class='ok'>✓ $file (perms: $perms)</div>";
    } else {
        echo "<div class='error'>✗ $file NOT FOUND</div>";
    }
}

// ── 4. Database Connection ────────────────────────────────────────────────────
echo "<h2>4. Database Connection</h2>";
try {
    // Try to load environment
    if (file_exists($basePath . '/.env')) {
        echo "<div class='ok'>✓ .env file exists</div>";
        $lines = file($basePath . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    } else {
        echo "<div class='warning'>⚠ .env file not found, using defaults</div>";
    }

    // Database credentials
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'u494785662_ergon';
    $username = $_ENV['DB_USER'] ?? 'u494785662_ergon';
    $password = $_ENV['DB_PASS'] ?? '@Admin@2025@';

    echo "<div>Host: $host</div>";
    echo "<div>Database: $dbname</div>";
    echo "<div>Username: $username</div>";
    echo "<div>Password: " . str_repeat('*', strlen($password)) . "</div>";

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<div class='ok'>✓ Database connection successful</div>";

    // Check critical tables
    $tables = ['users', 'advances', 'expenses', 'projects'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<div class='ok'>✓ Table '$table' exists ($count rows)</div>";
        } else {
            echo "<div class='error'>✗ Table '$table' NOT FOUND</div>";
        }
    }

} catch (PDOException $e) {
    echo "<div class='error'>✗ Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// ── 5. .htaccess Check ────────────────────────────────────────────────────────
echo "<h2>5. .htaccess Configuration</h2>";
if (file_exists($basePath . '/.htaccess')) {
    echo "<div class='ok'>✓ .htaccess exists</div>";
    $htaccess = file_get_contents($basePath . '/.htaccess');
    if (strpos($htaccess, 'RewriteEngine') !== false) {
        echo "<div class='ok'>✓ RewriteEngine directive found</div>";
    } else {
        echo "<div class='warning'>⚠ RewriteEngine not found in .htaccess</div>";
    }
    
    // Check if mod_rewrite is enabled
    if (function_exists('apache_get_modules')) {
        if (in_array('mod_rewrite', apache_get_modules())) {
            echo "<div class='ok'>✓ mod_rewrite is enabled</div>";
        } else {
            echo "<div class='error'>✗ mod_rewrite is NOT enabled</div>";
        }
    } else {
        echo "<div class='warning'>⚠ Cannot check mod_rewrite status</div>";
    }
} else {
    echo "<div class='error'>✗ .htaccess NOT FOUND</div>";
}

// ── 6. Error Logs ─────────────────────────────────────────────────────────────
echo "<h2>6. Recent PHP Errors</h2>";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $errors = array_slice(file($errorLog), -10);
    if (!empty($errors)) {
        echo "<pre style='background:#fff;padding:10px;border:1px solid #ccc;overflow:auto;max-height:200px'>";
        echo htmlspecialchars(implode('', $errors));
        echo "</pre>";
    } else {
        echo "<div class='ok'>✓ No recent errors</div>";
    }
} else {
    echo "<div class='warning'>⚠ Error log not accessible</div>";
}

// ── 7. Session Check ──────────────────────────────────────────────────────────
echo "<h2>7. Session Configuration</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "<div class='ok'>✓ Session started successfully</div>";
} else {
    echo "<div class='ok'>✓ Session already active</div>";
}
echo "<div>Session ID: " . session_id() . "</div>";
echo "<div>Session Save Path: " . session_save_path() . "</div>";

// ── 8. Memory & Limits ────────────────────────────────────────────────────────
echo "<h2>8. PHP Limits</h2>";
echo "<div>Memory Limit: " . ini_get('memory_limit') . "</div>";
echo "<div>Max Execution Time: " . ini_get('max_execution_time') . "s</div>";
echo "<div>Upload Max Filesize: " . ini_get('upload_max_filesize') . "</div>";
echo "<div>Post Max Size: " . ini_get('post_max_size') . "</div>";

echo "<hr><p><strong>Diagnostic complete.</strong> If all checks pass, try accessing: ";
echo "<a href='index.php'>index.php</a> or <a href='app/'>app/</a></p>";
echo "</body></html>";
?>
