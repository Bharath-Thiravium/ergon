<?php
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::connect();
    
    // Get prefix
    $prefix = '';
    try {
        $stmt = $db->prepare("SELECT company_prefix FROM finance_tables WHERE table_name = 'settings' LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $prefix = $result ? strtoupper(trim($result['company_prefix'])) : '';
    } catch (Exception $e) {}
    
    // Unified prefix filtering function
    function matchesPrefix($numberField, $prefix) {
        if (!$prefix || empty($prefix)) return true;
        return stripos($numberField, $prefix) === 0;
    }
    
    // Initialize counters
    $quotation_count = 0; $quotation_value = 0;
    $po_count = 0; $po_value = 0;
    $invoice_count = 0; $invoice_value = 0;
    $payment_count = 0; $payment_value = 0;
    
    // Quotations - consistent filtering
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $data = json_decode($row['data'], true);
        if (!$data) continue;
        
        $number = $data['quotation_number'] ?? $data['quote_number'] ?? '';
        if (matchesPrefix($number, $prefix)) {
            $quotation_count++;
            $quotation_value += floatval($data['total_amount'] ?? $data['amount'] ?? 0);
        }
    }
    
    // Purchase Orders - consistent filtering
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $data = json_decode($row['data'], true);
        if (!$data) continue;
        
        $number = $data['po_number'] ?? $data['internal_po_number'] ?? '';
        if (matchesPrefix($number, $prefix)) {
            $po_count++;
            $po_value += floatval($data['total_amount'] ?? $data['amount'] ?? 0);
        }
    }
    
    // Invoices - consistent filtering
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $data = json_decode($row['data'], true);
        if (!$data) continue;
        
        $number = $data['invoice_number'] ?? '';
        if (matchesPrefix($number, $prefix)) {
            $invoice_count++;
            $total = floatval($data['total_amount'] ?? $data['amount'] ?? 0);
            $outstanding = floatval($data['outstanding_amount'] ?? 0);
            $paid = $total - $outstanding;
            
            $invoice_value += $total;
            $payment_value += $paid;
            if ($paid > 0) $payment_count++;
        }
    }
    
    // Calculate conversion rates
    $po_conversion_rate = $quotation_count > 0 ? round(($po_count / $quotation_count) * 100, 2) : 0;
    $invoice_conversion_rate = $po_count > 0 ? round(($invoice_count / $po_count) * 100, 2) : 0;
    $payment_conversion_rate = $invoice_count > 0 ? round(($payment_count / $invoice_count) * 100, 2) : 0;
    
    echo json_encode([
        'success' => true,
        'prefix' => $prefix,
        'debug' => [
            'quotations_found' => $quotation_count,
            'pos_found' => $po_count,
            'invoices_found' => $invoice_count
        ],
        'containers' => [
            'container1' => [
                'title' => 'Quotations',
                'quotations_count' => $quotation_count,
                'quotations_total_value' => $quotation_value
            ],
            'container2' => [
                'title' => 'Purchase Orders',
                'po_count' => $po_count,
                'po_total_value' => $po_value,
                'po_conversion_rate' => $po_conversion_rate
            ],
            'container3' => [
                'title' => 'Invoices',
                'invoice_count' => $invoice_count,
                'invoice_total_value' => $invoice_value,
                'invoice_conversion_rate' => $invoice_conversion_rate
            ],
            'container4' => [
                'title' => 'Payments',
                'payment_count' => $payment_count,
                'total_payment_received' => $payment_value,
                'payment_conversion_rate' => $payment_conversion_rate
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>