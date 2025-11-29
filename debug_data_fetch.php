<?php
/**
 * Debug Data Fetching Issues
 * Check what data is actually being fetched vs displayed
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Data Fetch</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .error { color: red; }
        .success { color: green; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🔍 Debug Data Fetching</h1>
    
    <?php
    try {
        require_once __DIR__ . '/app/config/database.php';
        $db = Database::connect();
        
        echo "<h2>1️⃣ Raw ETL Data Check</h2>";
        
        // Check consolidated table
        echo "<h3>finance_consolidated table:</h3>";
        $stmt = $db->query("SELECT * FROM finance_consolidated ORDER BY created_at DESC LIMIT 10");
        $consolidated = $stmt->fetchAll();
        
        if (empty($consolidated)) {
            echo "<div class='error'>❌ No data in finance_consolidated table</div>";
        } else {
            echo "<table>";
            echo "<tr><th>Type</th><th>Document</th><th>Customer</th><th>Amount</th><th>Outstanding</th><th>Prefix</th></tr>";
            foreach ($consolidated as $row) {
                echo "<tr>";
                echo "<td>{$row['record_type']}</td>";
                echo "<td>{$row['document_number']}</td>";
                echo "<td>{$row['customer_name']}</td>";
                echo "<td>₹{$row['amount']}</td>";
                echo "<td>₹{$row['outstanding_amount']}</td>";
                echo "<td>{$row['company_prefix']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<h2>2️⃣ Dashboard Stats Check</h2>";
        
        // Check dashboard stats
        $stmt = $db->query("SELECT * FROM dashboard_stats ORDER BY generated_at DESC");
        $stats = $stmt->fetchAll();
        
        if (empty($stats)) {
            echo "<div class='error'>❌ No data in dashboard_stats table</div>";
        } else {
            echo "<table>";
            echo "<tr><th>Prefix</th><th>Revenue</th><th>Outstanding</th><th>PO Commitments</th><th>Generated</th></tr>";
            foreach ($stats as $row) {
                echo "<tr>";
                echo "<td>{$row['company_prefix']}</td>";
                echo "<td>₹{$row['total_revenue']}</td>";
                echo "<td>₹{$row['outstanding_amount']}</td>";
                echo "<td>₹{$row['po_commitments']}</td>";
                echo "<td>{$row['generated_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<h2>3️⃣ API Response Test</h2>";
        
        // Test dashboard-stats API
        echo "<h3>Testing /finance/dashboard-stats API:</h3>";
        
        // Simulate API call
        ob_start();
        $_GET = []; // Clear any existing GET params
        
        require_once __DIR__ . '/app/controllers/FinanceController.php';
        $controller = new FinanceController();
        
        // Capture the JSON output
        ob_start();
        $controller->getDashboardStats();
        $apiResponse = ob_get_clean();
        
        echo "<pre>";
        echo "API Response:\n";
        echo htmlspecialchars($apiResponse);
        echo "</pre>";
        
        // Parse and analyze
        $data = json_decode($apiResponse, true);
        if ($data) {
            echo "<h3>Parsed API Data:</h3>";
            echo "<table>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                echo "<tr><td>$key</td><td>$value</td></tr>";
            }
            echo "</table>";
        }
        
        echo "<h2>4️⃣ Legacy Data Check</h2>";
        
        // Check if still using legacy finance_data
        $stmt = $db->query("SELECT table_name, COUNT(*) as count FROM finance_data GROUP BY table_name");
        $legacyData = $stmt->fetchAll();
        
        if (!empty($legacyData)) {
            echo "<h3>Legacy finance_data table:</h3>";
            echo "<table>";
            echo "<tr><th>Table</th><th>Records</th></tr>";
            foreach ($legacyData as $row) {
                echo "<tr><td>{$row['table_name']}</td><td>{$row['count']}</td></tr>";
            }
            echo "</table>";
        }
        
        echo "<h2>5️⃣ Company Prefix Check</h2>";
        
        // Check current prefix setting
        $stmt = $db->query("SELECT company_prefix FROM finance_tables WHERE table_name = 'settings'");
        $prefixRow = $stmt->fetch();
        $currentPrefix = $prefixRow ? $prefixRow['company_prefix'] : 'Not Set';
        
        echo "<p><strong>Current Company Prefix:</strong> $currentPrefix</p>";
        
        // Check data by prefix
        $stmt = $db->query("SELECT company_prefix, COUNT(*) as count FROM finance_consolidated GROUP BY company_prefix");
        $prefixData = $stmt->fetchAll();
        
        if (!empty($prefixData)) {
            echo "<h3>Data by Company Prefix:</h3>";
            echo "<table>";
            echo "<tr><th>Prefix</th><th>Records</th></tr>";
            foreach ($prefixData as $row) {
                echo "<tr><td>{$row['company_prefix']}</td><td>{$row['count']}</td></tr>";
            }
            echo "</table>";
        }
        
        echo "<h2>🎯 Diagnosis</h2>";
        
        if (empty($consolidated)) {
            echo "<div class='error'>❌ Problem: No data in ETL consolidated table</div>";
            echo "<p><strong>Solution:</strong> Run ETL sync to populate data</p>";
        } elseif (empty($stats)) {
            echo "<div class='error'>❌ Problem: ETL data exists but no dashboard stats calculated</div>";
            echo "<p><strong>Solution:</strong> ETL analytics calculation failed</p>";
        } else {
            echo "<div class='success'>✅ ETL data and stats exist - check API response above</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    ?>
    
    <hr>
    <p><em>Debug Data Fetch - ETL Troubleshooting</em></p>
</body>
</html>