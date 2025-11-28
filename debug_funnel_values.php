<?php
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::connect();
    
    // Debug quotations
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations' LIMIT 3");
    $stmt->execute();
    $quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $quotation_samples = [];
    foreach ($quotations as $row) {
        $data = json_decode($row['data'], true);
        if ($data) {
            $quotation_samples[] = [
                'fields' => array_keys($data),
                'sample_data' => array_slice($data, 0, 10, true)
            ];
        }
    }
    
    // Debug purchase orders
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders' LIMIT 3");
    $stmt->execute();
    $pos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $po_samples = [];
    foreach ($pos as $row) {
        $data = json_decode($row['data'], true);
        if ($data) {
            $po_samples[] = [
                'fields' => array_keys($data),
                'sample_data' => array_slice($data, 0, 10, true)
            ];
        }
    }
    
    // Debug invoices
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices' LIMIT 3");
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $invoice_samples = [];
    foreach ($invoices as $row) {
        $data = json_decode($row['data'], true);
        if ($data) {
            $invoice_samples[] = [
                'fields' => array_keys($data),
                'sample_data' => array_slice($data, 0, 10, true)
            ];
        }
    }
    
    echo json_encode([
        'quotations' => [
            'count' => count($quotations),
            'samples' => $quotation_samples
        ],
        'purchase_orders' => [
            'count' => count($pos),
            'samples' => $po_samples
        ],
        'invoices' => [
            'count' => count($invoices),
            'samples' => $invoice_samples
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>