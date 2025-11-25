<?php
// Quick database fix for development
echo "<h1>Database Configuration Fix</h1>";

// Check available PDO drivers
$drivers = PDO::getAvailableDrivers();
echo "<h2>Available PDO Drivers:</h2>";
echo "<ul>";
foreach ($drivers as $driver) {
    echo "<li>$driver</li>";
}
echo "</ul>";

// Check if MySQL is available
if (in_array('mysql', $drivers)) {
    echo "<p style='color: green;'>✅ MySQL PDO driver is available</p>";
} else {
    echo "<p style='color: red;'>❌ MySQL PDO driver is NOT available</p>";
    echo "<p><strong>Solution:</strong> Enable php_pdo_mysql extension in php.ini</p>";
}

// Test basic database connection with error details
echo "<h2>Database Connection Test:</h2>";
try {
    $dsn = "mysql:host=localhost;dbname=ergon_db;charset=utf8mb4";
    $username = "root";
    $password = "";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM daily_tasks WHERE user_id = 16");
    $result = $stmt->fetch();
    echo "<p>Found {$result['count']} tasks for user 16</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    
    if (strpos($e->getMessage(), 'could not find driver') !== false) {
        echo "<p><strong>Fix:</strong> Enable MySQL PDO extension in PHP configuration</p>";
    } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<p><strong>Fix:</strong> Check database credentials</p>";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<p><strong>Fix:</strong> Create the 'ergon_db' database</p>";
    }
}

// Show PHP configuration
echo "<h2>PHP Configuration:</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Extensions loaded: " . implode(', ', get_loaded_extensions()) . "</p>";
?>