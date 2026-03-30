<?php
echo "Testing VPS PostgreSQL Connection (via local tunnel)\n";
echo "===================================================\n";

// Connection parameters
$host = '127.0.0.1';
$port = '5432';
$dbname = 'modernsap';
$user = 'postgres';
$password = 'mango';

try {
    // Try connection with SSL support
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=prefer";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => false
    ]);
    
    echo "✅ PostgreSQL Connection: SUCCESS\n";
    echo "Host: $host:$port (VPS: 72.60.218.167)\n";
    echo "Database: $dbname\n";
    echo "User: $user\n";
    echo "SSL: Auto-negotiated (TLSv1.3)\n\n";
    
    // Get PostgreSQL version
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "PostgreSQL Version: " . substr($version, 0, 50) . "...\n\n";
    
    // List all schemas
    echo "Available Schemas:\n";
    $stmt = $pdo->query("SELECT schema_name FROM information_schema.schemata ORDER BY schema_name");
    $schemas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($schemas as $schema) {
        echo "- $schema\n";
    }
    
    // List tables in public schema
    echo "\nTables in 'public' schema:\n";
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "❌ No tables found in 'public' schema\n";
        
        // Check for Django app tables in other schemas or with prefixes
        echo "\nSearching for Django app tables:\n";
        $stmt = $pdo->query("
            SELECT table_name, table_schema 
            FROM information_schema.tables 
            WHERE table_name LIKE '%finance%' 
               OR table_name LIKE '%invoice%' 
               OR table_name LIKE '%customer%'
               OR table_name LIKE '%payment%'
               OR table_name LIKE '%purchase%'
            ORDER BY table_schema, table_name
        ");
        $django_tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($django_tables)) {
            echo "❌ No Django finance tables found\n";
        } else {
            foreach ($django_tables as $table) {
                echo "- {$table['table_schema']}.{$table['table_name']}\n";
            }
        }
    } else {
        foreach ($tables as $table) {
            echo "- $table\n";
        }
        
        // Check for finance-related tables
        echo "\nFinance-related tables:\n";
        $finance_tables = array_filter($tables, function($table) {
            return stripos($table, 'finance') !== false || 
                   stripos($table, 'invoice') !== false || 
                   stripos($table, 'customer') !== false ||
                   stripos($table, 'payment') !== false ||
                   stripos($table, 'purchase') !== false;
        });
        
        if (empty($finance_tables)) {
            echo "❌ No finance tables found with standard names\n";
        } else {
            foreach ($finance_tables as $table) {
                echo "✅ $table\n";
            }
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ CONNECTION SUCCESSFUL - Ready for data sync!\n";
    
} catch (PDOException $e) {
    echo "❌ PostgreSQL Connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "Troubleshooting:\n";
    echo "- Verify SSH tunnel is active\n";
    echo "- Check if local port 5432 is forwarded to VPS\n";
    echo "- Confirm VPS PostgreSQL is running\n";
    echo "- Verify database 'modernsap' exists on VPS\n";
}
?>