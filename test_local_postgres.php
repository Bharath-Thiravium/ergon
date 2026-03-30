<?php
echo "Testing LOCAL PostgreSQL Connection...\n";
echo "=====================================\n";

// Test connection parameters
$host = '127.0.0.1';
$port = '5432';
$dbname = 'modernsap';
$user = 'postgres';
$password = 'mango';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ PostgreSQL Connection: SUCCESS\n";
    echo "Host: $host:$port\n";
    echo "Database: $dbname\n";
    echo "User: $user\n";
    
    // Test basic query
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "PostgreSQL Version: $version\n";
    
    // List available tables
    echo "\nAvailable Tables:\n";
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "❌ No tables found in 'public' schema\n";
        
        // Check all schemas
        echo "\nChecking all schemas:\n";
        $stmt = $pdo->query("SELECT schema_name FROM information_schema.schemata ORDER BY schema_name");
        $schemas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($schemas as $schema) {
            echo "- $schema\n";
        }
    } else {
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ PostgreSQL Connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    
    // Additional diagnostics
    echo "\nDiagnostics:\n";
    echo "- Check if PostgreSQL service is running\n";
    echo "- Verify database 'modernsap' exists\n";
    echo "- Confirm user 'postgres' has access\n";
}
?>