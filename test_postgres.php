<?php
// Test PostgreSQL connection
$host = '72.60.218.167';
$port = '5432';
$dbname = 'modernsap';
$user = 'postgres';
$password = 'mango';

try {
    // Check if PDO PostgreSQL is available
    if (!extension_loaded('pdo_pgsql')) {
        echo "PDO PostgreSQL extension not loaded\n";
        echo "Available PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
        exit;
    }
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "PostgreSQL connection successful!\n";
    
    // Get table list
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Available tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>