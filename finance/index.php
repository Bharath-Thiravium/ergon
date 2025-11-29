<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Simple mock data for now since DB connection is failing
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/ergon/finance/', '', $path);
$path = trim($path, '/');

try {
    switch ($path) {
        case '':
        case 'dashboard-stats':
            echo json_encode([
                'company_prefix' => 'BKGE',
                'generated_at' => date('c'),
                'total_revenue' => 2400780.00,
                'invoice_count' => 2,
                'avg_invoice' => 1200390.00,
                'amount_received' => 0,
                'collection_rate' => 0.0,
                'paid_invoices' => 0,
                'outstanding_amount' => 2400780.00,
                'pending_invoices' => 2,
                'customers_pending' => 2,
                'overdue_amount' => 603644.34,
                'outstanding_percentage' => 1.0,
                'igst_liability' => 0.0,
                'cgst_sgst_total' => 363780.00,
                'gst_liability' => 363780.00,
                'po_commitments' => 2688020.32,
                'open_po' => 6,
                'closed_po' => 0,
                'claimable_amount' => 2400780.00,
                'claimable_pos' => 2,
                'claim_rate' => 1.0
            ]);
            break;
        case 'health':
            echo json_encode([
                'status' => 'healthy',
                'timestamp' => date('c'),
                'database' => 'mock_mode',
                'message' => 'API working, database connection needs configuration'
            ]);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>