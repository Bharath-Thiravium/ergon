<?php
// Hostinger Compatibility Check
echo "<h1>Hostinger Compatibility Check</h1>";

// Check PHP version
echo "<h2>PHP Version:</h2>";
echo "Current: " . phpversion() . "<br>";
echo "Required: 7.4+<br>";

// Check database connection
echo "<h2>Database Connection:</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    echo "✅ Database connected successfully<br>";
    echo "Environment: " . $database->getEnvironment() . "<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Check file permissions
echo "<h2>File Permissions:</h2>";
$paths = [
    __DIR__ . '/storage',
    __DIR__ . '/logs',
    __DIR__ . '/public/uploads'
];

foreach ($paths as $path) {
    if (is_dir($path)) {
        echo "✅ $path exists and is writable<br>";
    } else {
        echo "❌ $path missing or not writable<br>";
    }
}

// Check required extensions
echo "<h2>PHP Extensions:</h2>";
$extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext loaded<br>";
    } else {
        echo "❌ $ext missing<br>";
    }
}

// Check .htaccess
echo "<h2>URL Rewriting:</h2>";
if (file_exists(__DIR__ . '/.htaccess')) {
    echo "✅ .htaccess exists<br>";
    echo "<pre>" . htmlspecialchars(file_get_contents(__DIR__ . '/.htaccess')) . "</pre>";
} else {
    echo "❌ .htaccess missing<br>";
}
?>