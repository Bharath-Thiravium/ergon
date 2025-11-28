<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    $prefix = 'BKC'; // Change this to your actual prefix
    
    echo "<h2>PO Debug Test</h2>";
    
    // Check if finance_data table exists and has PO data
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM finance_data WHERE table_name = 'finance_purchase_orders'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo "<p>Total PO records in finance_data: $count</p>";
    
    if ($count == 0) {
        echo "<p style='color: red;'>No PO data found. Need to sync data first.</p>";
        exit;
    }
    
    // Get sample PO data
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders' LIMIT 3");
    $stmt->execute();
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Sample PO Data:</h3>";
    foreach ($samples as $i => $row) {
        $data = json_decode($row['data'], true);
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<h4>PO Record " . ($i + 1) . ":</h4>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";
    }
    
    // Test prefix filtering
    echo "<h3>Prefix Filtering Test (Prefix: $prefix):</h3>";
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $matchCount = 0;
    $totalCount = 0;
    
    foreach ($results as $row) {
        $data = json_decode($row['data'], true);
        $totalCount++;
        
        $poNumber = $data['po_number'] ?? $data['internal_po_number'] ?? '';
        $amount = floatval($data['total_amount'] ?? $data['amount'] ?? 0);
        
        $matches = false;
        if (!$prefix || stripos($poNumber, $prefix) !== false) {
            $matches = true;
            $matchCount++;
        }
        
        if ($totalCount <= 5) { // Show first 5 for debugging
            echo "<p>PO: $poNumber | Amount: $amount | Matches: " . ($matches ? 'YES' : 'NO') . "</p>";
        }
    }
    
    echo "<p><strong>Total POs: $totalCount | Matching prefix '$prefix': $matchCount</strong></p>";
    
    // Test the actual calculation method
    echo "<h3>Testing calculatePurchaseOrderOverview:</h3>";
    
    $purchaseOrders = [];
    foreach ($results as $row) {
        $data = json_decode($row['data'], true);
        $poNumber = $data['po_number'] ?? $data['internal_po_number'] ?? '';
        
        if (!$prefix || stripos($poNumber, $prefix) !== false) {
            $purchaseOrders[] = [
                'po_number' => $poNumber,
                'po_amount' => floatval($data['total_amount'] ?? $data['amount'] ?? 0),
                'company_prefix' => $prefix
            ];
        }
    }
    
    echo "<p>Filtered POs for calculation: " . count($purchaseOrders) . "</p>";
    
    // Get invoice claims
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
    $stmt->execute();
    $invoiceResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $invoiceClaims = [];
    foreach ($invoiceResults as $row) {
        $data = json_decode($row['data'], true);
        $poNumber = $data['po_number'] ?? '';
        $invoiceAmount = floatval($data['total_amount'] ?? $data['amount'] ?? 0);
        
        if ($poNumber && $invoiceAmount > 0) {
            if (!isset($invoiceClaims[$poNumber])) {
                $invoiceClaims[$poNumber] = 0;
            }
            $invoiceClaims[$poNumber] += $invoiceAmount;
        }
    }
    
    echo "<p>Invoice claims found: " . count($invoiceClaims) . "</p>";
    
    // Calculate fulfillment
    $count_high = 0;
    $count_mid = 0; 
    $count_low = 0;
    
    foreach ($purchaseOrders as $po) {
        $claimed = $invoiceClaims[$po['po_number']] ?? 0;
        $fulfillment = $po['po_amount'] > 0 ? ($claimed / $po['po_amount']) * 100 : 0;
        
        if ($fulfillment > 80) $count_high++;
        elseif ($fulfillment > 50) $count_mid++;
        else $count_low++;
        
        if (count($purchaseOrders) <= 10) { // Show details for small datasets
            echo "<p>PO: {$po['po_number']} | Amount: {$po['po_amount']} | Claimed: $claimed | Fulfillment: " . round($fulfillment, 2) . "%</p>";
        }
    }
    
    echo "<h3>Final Results:</h3>";
    echo "<p>High Fulfillment (>80%): $count_high</p>";
    echo "<p>Mid Fulfillment (>50%): $count_mid</p>";
    echo "<p>Low Fulfillment (<50%): $count_low</p>";
    echo "<p>Total POs: " . count($purchaseOrders) . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>