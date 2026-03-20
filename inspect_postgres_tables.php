<?php
echo "Inspecting PostgreSQL Table Structures\n";
echo "======================================\n\n";

// Connection parameters
$host = '127.0.0.1';
$port = '5432';
$dbname = 'modernsap';
$user = 'postgres';
$password = 'mango';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=prefer";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Finance tables to inspect
    $finance_tables = [
        'finance_customer',
        'finance_invoices', 
        'finance_quotations',
        'finance_purchase_orders',
        'finance_payments'
    ];
    
    foreach ($finance_tables as $table) {
        echo "Table: $table\n";
        echo str_repeat("-", strlen($table) + 7) . "\n";
        
        try {
            // Get column information
            $stmt = $pdo->query("
                SELECT column_name, data_type, is_nullable, column_default
                FROM information_schema.columns 
                WHERE table_name = '$table' 
                ORDER BY ordinal_position
            ");
            $columns = $stmt->fetchAll();
            
            if (empty($columns)) {
                echo "❌ Table not found or no columns\n\n";
                continue;
            }
            
            echo "Columns:\n";
            foreach ($columns as $col) {
                $nullable = $col['is_nullable'] === 'YES' ? 'NULL' : 'NOT NULL';
                $default = $col['column_default'] ? " DEFAULT: {$col['column_default']}" : '';
                echo "  - {$col['column_name']} ({$col['data_type']}) $nullable$default\n";
            }
            
            // Get sample data (first 3 rows)
            echo "\nSample Data (first 3 rows):\n";
            $stmt = $pdo->query("SELECT * FROM $table LIMIT 3");
            $sample_data = $stmt->fetchAll();
            
            if (empty($sample_data)) {
                echo "  No data found\n";
            } else {
                echo "  Records found: " . count($sample_data) . "\n";
                foreach ($sample_data as $i => $row) {
                    echo "  Row " . ($i + 1) . ": " . json_encode($row, JSON_PRETTY_PRINT) . "\n";
                }
            }
            
        } catch (Exception $e) {
            echo "❌ Error inspecting table: " . $e->getMessage() . "\n";
        }
        
        echo "\n" . str_repeat("=", 60) . "\n\n";
    }
    
    // Also check for any table with 'customer' in the name
    echo "Searching for customer-related tables:\n";
    echo "-------------------------------------\n";
    $stmt = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_name LIKE '%customer%' 
        AND table_schema = 'public'
        ORDER BY table_name
    ");
    $customer_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($customer_tables as $table) {
        echo "✅ $table\n";
        
        // Get column count and sample
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetchColumn();
        echo "   Records: $count\n";
        
        if ($count > 0) {
            $stmt = $pdo->query("SELECT * FROM $table LIMIT 1");
            $sample = $stmt->fetch();
            echo "   Sample columns: " . implode(', ', array_keys($sample)) . "\n";
        }
        echo "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}
?>