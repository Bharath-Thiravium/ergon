<?php
echo "Testing DIRECT VPS PostgreSQL Connection\n";
echo "=======================================\n";

// Direct VPS connection parameters
$host = '72.60.218.167';
$port = '5432';
$dbname = 'modernsap';
$user = 'postgres';
$password = 'mango';

try {
    // Try direct connection to VPS
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Direct VPS Connection: SUCCESS\n";
    echo "VPS IP: $host:$port\n";
    echo "Database: $dbname\n";
    echo "User: $user\n";
    echo "SSL: Required\n\n";
    
    // Get basic info
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "PostgreSQL Version: " . substr($version, 0, 50) . "...\n\n";
    
    // List tables
    echo "Tables in 'public' schema:\n";
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "❌ No tables in public schema\n";
    } else {
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    }
    
    echo "\n✅ DIRECT VPS CONNECTION WORKS!\n";
    echo "Update .env to use: SAP_PG_HOST=72.60.218.167\n";
    
} catch (PDOException $e) {
    echo "❌ Direct VPS Connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'timeout') !== false) {
        echo "Issue: Connection timeout - VPS firewall may be blocking port 5432\n";
    } elseif (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "Issue: PostgreSQL not accepting external connections\n";
    } elseif (strpos($e->getMessage(), 'authentication') !== false) {
        echo "Issue: Wrong username/password\n";
    }
    
    echo "\nNext steps:\n";
    echo "1. Check VPS firewall settings\n";
    echo "2. Configure PostgreSQL to accept external connections\n";
    echo "3. Or use SSH tunnel method\n";
}
?>