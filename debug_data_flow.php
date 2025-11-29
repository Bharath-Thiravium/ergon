<?php
// Debug data flow from PostgreSQL to MySQL to Visualization

require_once __DIR__ . '/app/config/database.php';

echo "<h2>Data Flow Debug Report</h2>\n";

// Test MySQL Connection
echo "<h3>1. MySQL Connection Test</h3>\n";
try {
    $mysql = Database::connect();
    echo "✅ MySQL connection successful<br>\n";
    
    // Check finance_consolidated table
    $stmt = $mysql->query("SELECT COUNT(*) as count FROM finance_consolidated");
    $count = $stmt->fetch()['count'];
    echo "📊 finance_consolidated records: {$count}<br>\n";
    
    if ($count > 0) {
        $stmt = $mysql->query("SELECT record_type, COUNT(*) as count FROM finance_consolidated GROUP BY record_type");
        echo "📋 Record types:<br>\n";
        while ($row = $stmt->fetch()) {
            echo "&nbsp;&nbsp;- {$row['record_type']}: {$row['count']}<br>\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ MySQL connection failed: " . $e->getMessage() . "<br>\n";
}

// Test PostgreSQL Connection (if configured)
echo "<h3>2. PostgreSQL Connection Test</h3>\n";
try {
    $pgHost = $_ENV['PG_HOST'] ?? 'localhost';
    $pgDb = $_ENV['PG_DATABASE'] ?? 'sap_source';
    $pgUser = $_ENV['PG_USER'] ?? 'postgres';
    $pgPass = $_ENV['PG_PASS'] ?? '';
    
    $pgsql = new PDO("pgsql:host={$pgHost};dbname={$pgDb}", $pgUser, $pgPass);
    echo "✅ PostgreSQL connection successful<br>\n";
    
    // Check for finance tables
    $stmt = $pgsql->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name LIKE '%finance%' OR table_name LIKE '%invoice%' OR table_name LIKE '%quotation%'");
    $tables = $stmt->fetchAll();
    
    if (count($tables) > 0) {
        echo "📋 Available finance tables:<br>\n";
        foreach ($tables as $table) {
            echo "&nbsp;&nbsp;- {$table['table_name']}<br>\n";
        }
    } else {
        echo "⚠️ No finance tables found in PostgreSQL<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ PostgreSQL connection failed: " . $e->getMessage() . "<br>\n";
    echo "💡 This is expected if PostgreSQL is not configured<br>\n";
}

// Test API Endpoints
echo "<h3>3. API Endpoints Test</h3>\n";

$apiTests = [
    'dashboard' => '/ergon/src/api/?action=dashboard&prefix=ERGN',
    'activities' => '/ergon/src/api/?action=activities&prefix=ERGN',
    'funnel-containers' => '/ergon/src/api/?action=funnel-containers&prefix=ERGN',
    'visualization' => '/ergon/src/api/?action=visualization&type=quotations&prefix=ERGN'
];

foreach ($apiTests as $name => $url) {
    $fullUrl = "http://localhost{$url}";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($fullUrl, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            if ($data['success']) {
                echo "✅ {$name} API: Working<br>\n";
                if (isset($data['data']) && is_array($data['data'])) {
                    $dataCount = is_countable($data['data']) ? count($data['data']) : 'object';
                    echo "&nbsp;&nbsp;Data items: {$dataCount}<br>\n";
                }
            } else {
                echo "⚠️ {$name} API: Error - " . ($data['error'] ?? 'Unknown') . "<br>\n";
            }
        } else {
            echo "❌ {$name} API: Invalid JSON response<br>\n";
        }
    } else {
        echo "❌ {$name} API: Connection failed<br>\n";
    }
}

// Test Sample Data Generation
echo "<h3>4. Sample Data Test</h3>\n";
try {
    // Insert sample data if table is empty
    $stmt = $mysql->query("SELECT COUNT(*) as count FROM finance_consolidated WHERE company_prefix = 'ERGN'");
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        echo "📝 Inserting sample data...<br>\n";
        
        $sampleData = [
            [
                'record_type' => 'quotation',
                'document_number' => 'ERGN-Q001',
                'customer_id' => 'CUST001',
                'customer_name' => 'Test Customer 1',
                'amount' => 50000.00,
                'taxable_amount' => 42372.88,
                'status' => 'pending',
                'company_prefix' => 'ERGN'
            ],
            [
                'record_type' => 'invoice',
                'document_number' => 'ERGN-INV001',
                'customer_id' => 'CUST001',
                'customer_name' => 'Test Customer 1',
                'amount' => 45000.00,
                'taxable_amount' => 38135.59,
                'amount_paid' => 20000.00,
                'outstanding_amount' => 18135.59,
                'igst' => 6864.41,
                'due_date' => '2024-02-15',
                'invoice_date' => '2024-01-15',
                'status' => 'pending',
                'company_prefix' => 'ERGN'
            ],
            [
                'record_type' => 'purchase_order',
                'document_number' => 'ERGN-PO001',
                'customer_id' => 'SUPP001',
                'customer_name' => 'Test Supplier 1',
                'amount' => 30000.00,
                'taxable_amount' => 25423.73,
                'status' => 'open',
                'company_prefix' => 'ERGN'
            ]
        ];
        
        $insertSql = "INSERT INTO finance_consolidated 
            (record_type, document_number, customer_id, customer_name, amount, taxable_amount, amount_paid, outstanding_amount, igst, due_date, invoice_date, status, company_prefix, raw_data) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $mysql->prepare($insertSql);
        $inserted = 0;
        
        foreach ($sampleData as $data) {
            $result = $stmt->execute([
                $data['record_type'],
                $data['document_number'],
                $data['customer_id'],
                $data['customer_name'],
                $data['amount'],
                $data['taxable_amount'],
                $data['amount_paid'] ?? 0,
                $data['outstanding_amount'] ?? 0,
                $data['igst'] ?? 0,
                $data['due_date'] ?? null,
                $data['invoice_date'] ?? null,
                $data['status'],
                $data['company_prefix'],
                json_encode($data)
            ]);
            
            if ($result) $inserted++;
        }
        
        echo "✅ Inserted {$inserted} sample records<br>\n";
    } else {
        echo "✅ Sample data already exists ({$count} records)<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Sample data insertion failed: " . $e->getMessage() . "<br>\n";
}

// Test Data Visualization Pipeline
echo "<h3>5. Data Visualization Pipeline Test</h3>\n";
try {
    // Test dashboard stats calculation
    $stmt = $mysql->prepare("
        SELECT 
            (SELECT COALESCE(SUM(amount), 0) FROM finance_consolidated WHERE record_type = 'invoice' AND company_prefix = 'ERGN') as total_invoice,
            (SELECT COALESCE(SUM(amount_paid), 0) FROM finance_consolidated WHERE record_type = 'invoice' AND company_prefix = 'ERGN') as total_received,
            (SELECT COALESCE(SUM(outstanding_amount), 0) FROM finance_consolidated WHERE record_type = 'invoice' AND outstanding_amount > 0 AND company_prefix = 'ERGN') as total_outstanding,
            (SELECT COALESCE(SUM(amount), 0) FROM finance_consolidated WHERE record_type = 'purchase_order' AND company_prefix = 'ERGN') as total_po
    ");
    $stmt->execute();
    $stats = $stmt->fetch();
    
    echo "📊 Calculated Stats:<br>\n";
    echo "&nbsp;&nbsp;Total Invoice Amount: ₹" . number_format($stats['total_invoice'], 2) . "<br>\n";
    echo "&nbsp;&nbsp;Amount Received: ₹" . number_format($stats['total_received'], 2) . "<br>\n";
    echo "&nbsp;&nbsp;Outstanding Amount: ₹" . number_format($stats['total_outstanding'], 2) . "<br>\n";
    echo "&nbsp;&nbsp;PO Commitments: ₹" . number_format($stats['total_po'], 2) . "<br>\n";
    
    $netCashFlow = $stats['total_outstanding'] - $stats['total_po'];
    echo "&nbsp;&nbsp;Net Cash Flow: ₹" . number_format($netCashFlow, 2) . "<br>\n";
    
} catch (Exception $e) {
    echo "❌ Stats calculation failed: " . $e->getMessage() . "<br>\n";
}

echo "<h3>6. Recommendations</h3>\n";
echo "🔧 <strong>Data Flow Issues Found:</strong><br>\n";

// Check for common issues
$issues = [];

try {
    $stmt = $mysql->query("SELECT COUNT(*) as count FROM finance_consolidated");
    $totalRecords = $stmt->fetch()['count'];
    
    if ($totalRecords == 0) {
        $issues[] = "No data in finance_consolidated table - ETL sync needed";
    }
    
    $stmt = $mysql->query("SELECT COUNT(DISTINCT company_prefix) as count FROM finance_consolidated");
    $prefixCount = $stmt->fetch()['count'];
    
    if ($prefixCount == 0) {
        $issues[] = "No company prefixes found - data filtering will fail";
    }
    
    $stmt = $mysql->query("SELECT COUNT(*) as count FROM finance_consolidated WHERE amount IS NULL OR amount = 0");
    $nullAmounts = $stmt->fetch()['count'];
    
    if ($nullAmounts > 0) {
        $issues[] = "{$nullAmounts} records have null/zero amounts - affects calculations";
    }
    
} catch (Exception $e) {
    $issues[] = "Database query failed: " . $e->getMessage();
}

if (empty($issues)) {
    echo "✅ No major issues detected<br>\n";
} else {
    foreach ($issues as $issue) {
        echo "⚠️ {$issue}<br>\n";
    }
}

echo "<br>💡 <strong>Next Steps:</strong><br>\n";
echo "1. Run ETL sync to populate data from PostgreSQL<br>\n";
echo "2. Verify API endpoints return correct data<br>\n";
echo "3. Test dashboard visualization updates<br>\n";
echo "4. Check frontend JavaScript console for errors<br>\n";

?>