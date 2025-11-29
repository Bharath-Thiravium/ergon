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
    
    // Single function to get all data
    function getData($db, $table, $prefix) {
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = ?");
        $stmt->execute([$table]);
        
        $records = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data = json_decode($row['data'], true);
            if (!$data) continue;
            
            // Get all possible number fields
            $numbers = array_filter([
                $data['quotation_number'] ?? '',
                $data['quote_number'] ?? '',
                $data['po_number'] ?? '',
                $data['internal_po_number'] ?? '',
                $data['invoice_number'] ?? '',
                $data['number'] ?? ''
            ]);
            
            // Check if any number matches prefix
            $matches = false;
            if (!$prefix) {
                $matches = true;
            } else {
                foreach ($numbers as $num) {
                    if (stripos($num, $prefix) === 0) {
                        $matches = true;
                        break;
                    }
                }
            }
            
            if ($matches) {
                $records[] = $data;
            }
        }
        
        return $records;
    }
    
    // Get data for each type
    $quotations = getData($db, 'finance_quotations', $prefix);
    $pos = getData($db, 'finance_purchase_orders', $prefix);
    $invoices = getData($db, 'finance_invoices', $prefix);
    
    // Calculate totals
    $quotation_count = count($quotations);
    $quotation_value = array_sum(array_map(fn($q) => floatval($q['total_amount'] ?? $q['amount'] ?? 0), $quotations));
    
    $po_count = count($pos);
    $po_value = array_sum(array_map(fn($p) => floatval($p['total_amount'] ?? $p['amount'] ?? 0), $pos));
    
    $invoice_count = count($invoices);
    $invoice_value = array_sum(array_map(fn($i) => floatval($i['total_amount'] ?? $i['amount'] ?? 0), $invoices));
    
    $payment_value = array_sum(array_map(function($i) {
        $total = floatval($i['total_amount'] ?? $i['amount'] ?? 0);
        $outstanding = floatval($i['outstanding_amount'] ?? 0);
        return $total - $outstanding;
    }, $invoices));
    
    $payment_count = count(array_filter($invoices, function($i) {
        $total = floatval($i['total_amount'] ?? $i['amount'] ?? 0);
        $outstanding = floatval($i['outstanding_amount'] ?? 0);
        return ($total - $outstanding) > 0;
    }));
    
    // Conversion rates
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