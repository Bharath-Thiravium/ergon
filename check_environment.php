<?php
require_once __DIR__ . '/config/database.php';

echo "<h1>🌍 Environment Configuration Check</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .dev{color:blue;} .prod{color:red;}</style>";

try {
    $database = new Database();
    $config = $database->getConfig();
    
    echo "<h2>Current Environment: <span class='" . ($config['environment'] === 'development' ? 'dev' : 'prod') . "'>" . strtoupper($config['environment']) . "</span></h2>";
    
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    echo "<tr><td>Environment</td><td>" . $config['environment'] . "</td></tr>";
    echo "<tr><td>Host</td><td>" . $config['host'] . "</td></tr>";
    echo "<tr><td>Database</td><td>" . $config['database'] . "</td></tr>";
    echo "<tr><td>Username</td><td>" . $config['username'] . "</td></tr>";
    echo "<tr><td>Server Name</td><td>" . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td>HTTP Host</td><td>" . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "</td></tr>";
    echo "</table>";
    
    // Test connection
    echo "<h3>Database Connection Test:</h3>";
    $conn = $database->getConnection();
    echo "<p style='color:green;'>✅ Connection successful!</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Connection failed: " . $e->getMessage() . "</p>";
}

echo "<br><a href='/ergon/login'>Go to Login</a>";
?>