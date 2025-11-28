<?php
// Finance Debug Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Finance Dashboard Debug</h2>";

// Test database connection
try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    echo "<p>✅ MySQL Database connection: SUCCESS</p>";
} catch (Exception $e) {
    echo "<p>❌ MySQL Database connection: FAILED - " . $e->getMessage() . "</p>";
}

// Test PostgreSQL connection (used by finance module)
try {
    if (function_exists('pg_connect')) {
        $conn = @pg_connect("host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=disable connect_timeout=10");
        if ($conn) {
            echo "<p>✅ PostgreSQL connection: SUCCESS</p>";
            pg_close($conn);
        } else {
            echo "<p>❌ PostgreSQL connection: FAILED</p>";
        }
    } else {
        echo "<p>❌ PostgreSQL extension not available</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ PostgreSQL connection error: " . $e->getMessage() . "</p>";
}

// Test finance tables
try {
    $db = Database::connect();
    $stmt = $db->query("SHOW TABLES LIKE 'finance_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p>⚠️ No finance tables found in database</p>";
    } else {
        echo "<p>✅ Finance tables found: " . implode(', ', $tables) . "</p>";
        
        // Check data in tables
        foreach ($tables as $table) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM `$table`");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            echo "<p>📊 $table: $count records</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Finance tables check failed: " . $e->getMessage() . "</p>";
}

// Test API endpoints
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/ergon/finance';
$endpoints = [
    'dashboard-stats',
    'tables',
    'customers',
    'company-prefix'
];

echo "<h3>API Endpoint Tests:</h3>";
foreach ($endpoints as $endpoint) {
    $url = $baseUrl . '/' . $endpoint;
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p>✅ $endpoint: SUCCESS</p>";
        } else {
            echo "<p>⚠️ $endpoint: Invalid JSON response</p>";
        }
    } else {
        echo "<p>❌ $endpoint: FAILED</p>";
    }
}

echo "<h3>Environment Info:</h3>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'Available' : 'Not Available') . "</p>";
echo "<p>PostgreSQL: " . (extension_loaded('pgsql') ? 'Available' : 'Not Available') . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Server Name: " . $_SERVER['SERVER_NAME'] . "</p>";
?>