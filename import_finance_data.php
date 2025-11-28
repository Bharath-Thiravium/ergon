<?php
// Finance Data Import Script
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        $db = Database::connect();
        
        // Create finance tables
        $db->exec("CREATE TABLE IF NOT EXISTS finance_tables (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(100) UNIQUE,
            record_count INT,
            last_sync TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            company_prefix VARCHAR(10) DEFAULT 'BKC'
        )");
        
        $db->exec("CREATE TABLE IF NOT EXISTS finance_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(100),
            data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(table_name)
        )");
        
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        
        if (!$handle) {
            throw new Exception('Could not open CSV file');
        }
        
        // Read header
        $headers = fgetcsv($handle);
        $imported = 0;
        
        // Sample finance data based on typical CSV structure
        $financeData = [
            'finance_invoices' => [],
            'finance_quotations' => [],
            'finance_purchase_orders' => [],
            'finance_customers' => [],
            'finance_payments' => []
        ];
        
        // Process CSV rows
        while (($row = fgetcsv($handle)) !== FALSE) {
            if (count($row) < 2) continue;
            
            // Create sample invoice data
            $invoiceData = [
                'invoice_number' => 'BKC-INV-' . str_pad($imported + 1, 3, '0', STR_PAD_LEFT),
                'customer_id' => rand(1, 10),
                'customer_name' => 'Customer ' . rand(1, 10),
                'total_amount' => rand(10000, 100000),
                'outstanding_amount' => rand(0, 50000),
                'due_date' => date('Y-m-d', strtotime('+' . rand(1, 90) . ' days')),
                'payment_status' => rand(0, 1) ? 'paid' : 'unpaid',
                'gst_rate' => 0.18
            ];
            
            $financeData['finance_invoices'][] = $invoiceData;
            
            // Create sample quotation data
            $quotationData = [
                'quotation_number' => 'BKC-Q-' . str_pad($imported + 1, 3, '0', STR_PAD_LEFT),
                'customer_id' => rand(1, 10),
                'customer_name' => 'Customer ' . rand(1, 10),
                'total_amount' => rand(20000, 150000),
                'status' => ['draft', 'revised', 'converted'][rand(0, 2)],
                'valid_until' => date('Y-m-d', strtotime('+30 days'))
            ];
            
            $financeData['finance_quotations'][] = $quotationData;
            
            $imported++;
            if ($imported >= 50) break; // Limit to 50 records
        }
        
        fclose($handle);
        
        // Insert data into database
        foreach ($financeData as $tableName => $records) {
            if (empty($records)) continue;
            
            // Clear existing data
            $stmt = $db->prepare("DELETE FROM finance_data WHERE table_name = ?");
            $stmt->execute([$tableName]);
            
            // Insert new data
            foreach ($records as $record) {
                $stmt = $db->prepare("INSERT INTO finance_data (table_name, data) VALUES (?, ?)");
                $stmt->execute([$tableName, json_encode($record)]);
            }
            
            // Update table count
            $stmt = $db->prepare("INSERT INTO finance_tables (table_name, record_count) VALUES (?, ?) 
                                 ON DUPLICATE KEY UPDATE record_count = ?, last_sync = NOW()");
            $stmt->execute([$tableName, count($records), count($records)]);
        }
        
        echo "<div class='success'>✅ Successfully imported $imported finance records!</div>";
        echo "<p><a href='/ergon/finance'>View Finance Dashboard</a></p>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Finance Data Import</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 600px; margin: 0 auto; }
        .form-group { margin: 20px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ddd; }
        button { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Finance Data Import</h1>
        
        <div class="info">
            <strong>Instructions:</strong>
            <ul>
                <li>Upload your CSV file containing finance data</li>
                <li>The system will generate sample finance records for testing</li>
                <li>Data will be populated in the finance dashboard</li>
            </ul>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="csv_file">Select CSV File:</label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
            </div>
            
            <button type="submit">Import Finance Data</button>
        </form>
        
        <p><a href="/ergon/finance">← Back to Finance Dashboard</a></p>
    </div>
</body>
</html>