<?php
// Simple check for API vs frontend
$action = $_GET['action'] ?? '';

// Mock data
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

// API requests
if ($action === 'dashboard-stats') {
    header('Content-Type: application/json');
    echo json_encode($mockData);
    exit;
}

if ($action) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'API endpoint not implemented yet']);
    exit;
}

// Frontend - simple HTML
?>
<!DOCTYPE html>
<html>
<head>
    <title>Finance Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .card { background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .amount { font-size: 24px; font-weight: bold; color: #2563eb; }
    </style>
</head>
<body>
    <h1>Finance Dashboard</h1>
    <div id="loading">Loading...</div>
    <div id="dashboard" class="grid" style="display: none;"></div>

    <script>
        fetch('/ergon/finance/?action=dashboard-stats')
            .then(r => r.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('dashboard').style.display = 'grid';
                document.getElementById('dashboard').innerHTML = `
                    <div class="card">
                        <h3>💰 Total Revenue</h3>
                        <div class="amount">₹${data.total_revenue.toLocaleString()}</div>
                        <p>Invoices: ${data.invoice_count}</p>
                    </div>
                    <div class="card">
                        <h3>⏳ Outstanding</h3>
                        <div class="amount">₹${data.outstanding_amount.toLocaleString()}</div>
                        <p>Pending: ${data.pending_invoices}</p>
                    </div>
                    <div class="card">
                        <h3>🏛️ GST Liability</h3>
                        <div class="amount">₹${data.gst_liability.toLocaleString()}</div>
                        <p>CGST+SGST: ₹${data.cgst_sgst_total.toLocaleString()}</p>
                    </div>
                    <div class="card">
                        <h3>🛒 PO Commitments</h3>
                        <div class="amount">₹${data.po_commitments.toLocaleString()}</div>
                        <p>Open: ${data.open_po}</p>
                    </div>
                `;
            })
            .catch(e => {
                document.getElementById('loading').innerHTML = 'Error: ' + e.message;
            });
    </script>
</body>
</html>