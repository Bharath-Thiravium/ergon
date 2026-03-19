<?php
/**
 * PostgreSQL Connection Test Script
 * Run this to diagnose PostgreSQL sync issues
 */

require_once __DIR__ . '/app/config/database.php';

echo "=== PostgreSQL Connection Test ===\n\n";

try {
    $config = Database::getPostgreSQLConfig();
    $pg = $config['postgresql'];
    
    echo "Configuration:\n";
    echo "Host: {$pg['host']}\n";
    echo "Port: {$pg['port']}\n";
    echo "Database: {$pg['database']}\n";
    echo "Username: {$pg['username']}\n";
    echo "Password: " . (empty($pg['password']) ? 'EMPTY' : 'SET') . "\n\n";
    
    echo "Testing PostgreSQL connection...\n";
    
    $pdo = new PDO(
        "pgsql:host={$pg['host']};port={$pg['port']};dbname={$pg['database']}",
        $pg['username'],
        $pg['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10
        ]
    );
    
    echo "✅ PostgreSQL connection successful!\n\n";
    
    // Test query
    echo "Testing query execution...\n";
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetch();
    echo "PostgreSQL Version: " . $version['version'] . "\n\n";
    
    // List tables
    echo "Available tables:\n";
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll();
    
    if (empty($tables)) {
        echo "❌ No tables found in public schema\n";
    } else {
        foreach ($tables as $table) {
            echo "- " . $table['table_name'] . "\n";
        }
    }
    
    echo "\n=== Testing MySQL Connection ===\n";
    $mysql = Database::connect();
    echo "✅ MySQL connection successful!\n";
    
    // Test sync tables exist
    echo "\nChecking sync tables in MySQL...\n";
    $stmt = $mysql->query("SHOW TABLES LIKE 'finance_%'");
    $mysqlTables = $stmt->fetchAll();
    
    if (empty($mysqlTables)) {
        echo "❌ No finance tables found in MySQL\n";
        echo "Run the schema creation script first\n";
    } else {
        echo "Finance tables found:\n";
        foreach ($mysqlTables as $table) {
            echo "- " . array_values($table)[0] . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "\nPossible issues:\n";
    echo "1. PostgreSQL server is not running\n";
    echo "2. Wrong host/port/database name\n";
    echo "3. Invalid credentials\n";
    echo "4. Firewall blocking connection\n";
    echo "5. PostgreSQL not accepting remote connections\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Environment Variables ===\n";
echo "PG_HOST: " . ($_ENV['PG_HOST'] ?? 'NOT SET') . "\n";
echo "PG_PORT: " . ($_ENV['PG_PORT'] ?? 'NOT SET') . "\n";
echo "PG_DATABASE: " . ($_ENV['PG_DATABASE'] ?? 'NOT SET') . "\n";
echo "PG_USER: " . ($_ENV['PG_USER'] ?? 'NOT SET') . "\n";
echo "PG_PASS: " . (empty($_ENV['PG_PASS']) ? 'NOT SET' : 'SET') . "\n";

echo "\n=== Recommendations ===\n";
echo "1. Create a .env file with PostgreSQL credentials:\n";
echo "   PG_HOST=72.60.218.167\n";
echo "   PG_PORT=5432\n";
echo "   PG_DATABASE=modernsap\n";
echo "   PG_USER=postgres\n";
echo "   PG_PASS=mango\n\n";
echo "2. Ensure PostgreSQL server allows remote connections\n";
echo "3. Check if pg_hba.conf allows connections from your IP\n";
echo "4. Verify firewall settings on both client and server\n";
?>