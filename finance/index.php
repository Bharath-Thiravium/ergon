<?php
// Check if this is an API request or frontend request
$action = $_GET['action'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('/ergon/finance/', '', $path);
$path = trim($path, '/');

// Mock data for API responses
$mockData = [
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
];

// Handle API requests
if ($action === 'dashboard-stats' || $path === 'dashboard-stats') {
    header('Content-Type: application/json');
    echo json_encode($mockData);
    exit;
}

if ($action === 'health' || $path === 'health') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'healthy',
        'timestamp' => date('c'),
        'database' => 'mock_mode',
        'message' => 'API working, database connection needs configuration'
    ]);
    exit;
}

// Handle other API actions the frontend expects
if ($action) {
    header('Content-Type: application/json');
    switch ($action) {
        case 'funnel-containers':
            echo json_encode(['success' => true, 'containers' => []]);
            break;
        case 'customers':
            echo json_encode(['customers' => []]);
            break;
        case 'company-prefix':
            echo json_encode(['prefix' => 'BKGE']);
            break;
        default:
            echo json_encode(['error' => 'API endpoint not implemented yet']);
    }
    exit;
}

// If no action, show the frontend dashboard
if (empty($path)) {
    require_once __DIR__ . '/../views/finance/dashboard.php';
    exit;
}

// Default JSON response for direct API calls
header('Content-Type: application/json');
echo json_encode($mockData);
?>