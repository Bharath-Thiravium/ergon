<?php
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::connect();
    
    // Get prefix
    $prefix = $_GET['prefix'] ?? '';
    if (!$prefix) {
        $stmt = $db->prepare("SELECT company_prefix FROM finance_tables WHERE table_name = 'settings' LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $prefix = $result ? strtoupper(trim($result['company_prefix'])) : '';
    }
    
    // Unified matching function
    function matchesPrefix($field, $prefix) {
        if (!$prefix) return true;
        return stripos($field, $prefix) === 0;
    }
    
    $quotation_count = 0; $quotation_value = 0;
    $po_count = 0; $po_value = 0;
    $invoice_count = 0; $invoice_value = 0;
    $payment_count = 0; $payment_value = 0;
    
    // Quotations
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $data = json_decode($row['data'], true);
        if (!$data) continue;
        
        $numbers = [
            $data['quotation_number'] ?? '',
            $data['quote_number'] ?? '',
            $data['number'] ?? ''
        ];
        
        $matched = false;
        foreach ($numbers as $number) {
            if ($number && matchesPrefix($number, $prefix)) {
                $matched = true;
                break;
            }
        }
        
        if ($matched) {
            $quotation_count++;
            $quotation_value += floatval($data['total_amount'] ?? $data['amount'] ?? $data['value'] ?? 0);
        }
    }
    
    // Purchase Orders
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $data = json_decode($row['data'], true);
        if (!$data) continue;
        
        $numbers = [
            $data['po_number'] ?? '',
            $data['internal_po_number'] ?? '',
            $data['purchase_order_number'] ?? '',
            $data['number'] ?? ''
        ];
        
        $matched = false;
        foreach ($numbers as $number) {
            if ($number && matchesPrefix($number, $prefix)) {
                $matched = true;
                break;
            }
        }
        
        if ($matched) {
            $po_count++;
            $po_value += floatval($data['total_amount'] ?? $data['amount'] ?? $data['value'] ?? 0);
        }
    }
    
    // Invoices
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $data = json_decode($row['data'], true);
        if (!$data) continue;
        
        $numbers = [
            $data['invoice_number'] ?? '',
            $data['number'] ?? '',
            $data['invoice_no'] ?? ''
        ];
        
        $matched = false;
        foreach ($numbers as $number) {
            if ($number && matchesPrefix($number, $prefix)) {
                $matched = true;
                break;
            }
        }
        
        if ($matched) {
            $invoice_count++;
            $total = floatval($data['total_amount'] ?? $data['amount'] ?? $data['value'] ?? 0);
            $outstanding = floatval($data['outstanding_amount'] ?? $data['balance'] ?? 0);
            $paid = $total - $outstanding;
            
            $invoice_value += $total;
            $payment_value += $paid;
            if ($paid > 0) $payment_count++;
        }
    }
    
    $po_conversion_rate = $quotation_count > 0 ? round(($po_count / $quotation_count) * 100, 2) : 0;
    $invoice_conversion_rate = $po_count > 0 ? round(($invoice_count / $po_count) * 100, 2) : 0;
    $payment_conversion_rate = $invoice_count > 0 ? round(($payment_count / $invoice_count) * 100, 2) : 0;
    
    echo json_encode([
        'success' => true,
        'prefix' => $prefix,
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