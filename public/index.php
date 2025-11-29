<?php
// public/index.php - Dashboard
require_once __DIR__ . '/../inc/functions.php';

$prefix = $_GET['prefix'] ?? 'ERGN';
$pdo = getPdo();

// Get dashboard stats
$stats = $pdo->prepare("SELECT expected_inflow, po_commitments, net_cash_flow, last_computed_at FROM dashboard_stats WHERE company_prefix = :prefix");
$stats->execute([':prefix' => $prefix]);
$st = $stats->fetch(PDO::FETCH_ASSOC) ?: [
    'expected_inflow' => 0, 
    'po_commitments' => 0, 
    'net_cash_flow' => 0,
    'last_computed_at' => null
];

// Get record counts
$counts = $pdo->prepare("SELECT record_type, COUNT(*) as count, SUM(amount) as total FROM finance_consolidated WHERE company_prefix = :prefix GROUP BY record_type");
$counts->execute([':prefix' => $prefix]);
$recordCounts = $counts->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Finance Dashboard - <?= htmlentities($prefix) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-value { font-size: 24px; font-weight: bold; color: #007cba; }
        .stat-label { color: #666; margin-top: 5px; }
        .activities-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .positive { color: #28a745; }
        .negative { color: #dc3545; }
        .neutral { color: #6c757d; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 15px; color: #007cba; text-decoration: none; }
        .nav a:hover { text-decoration: underline; }
        .last-updated { font-size: 12px; color: #666; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Finance Dashboard</h1>
            <p>Company: <strong><?= htmlentities($prefix) ?></strong></p>
            <div class="nav">
                <a href="admin/upload.php">Upload CSV</a>
                <a href="recent_activities.php?prefix=<?= urlencode($prefix) ?>">API (JSON)</a>
                <a href="?prefix=<?= urlencode($prefix) ?>">Refresh</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value positive">₹<?= number_format((float)$st['expected_inflow'], 2) ?></div>
                <div class="stat-label">Expected Inflow</div>
            </div>
            <div class="stat-card">
                <div class="stat-value negative">₹<?= number_format((float)$st['po_commitments'], 2) ?></div>
                <div class="stat-label">PO Commitments</div>
            </div>
            <div class="stat-card">
                <div class="stat-value <?= (float)$st['net_cash_flow'] >= 0 ? 'positive' : 'negative' ?>">
                    ₹<?= number_format((float)$st['net_cash_flow'], 2) ?>
                </div>
                <div class="stat-label">Net Cash Flow</div>
            </div>
        </div>

        <?php if ($st['last_computed_at']): ?>
        <div class="last-updated">
            Last updated: <?= date('Y-m-d H:i:s', strtotime($st['last_computed_at'])) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($recordCounts)): ?>
        <div class="activities-section">
            <h3>Record Summary</h3>
            <table>
                <thead>
                    <tr>
                        <th>Record Type</th>
                        <th>Count</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recordCounts as $count): ?>
                    <tr>
                        <td><?= ucfirst(str_replace('_', ' ', $count['record_type'])) ?></td>
                        <td><?= number_format($count['count']) ?></td>
                        <td>₹<?= number_format((float)$count['total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="activities-section">
            <h3>Recent Activities</h3>
            <div id="activities">Loading...</div>
        </div>
    </div>

    <script>
    fetch('recent_activities.php?prefix=<?= urlencode($prefix) ?>')
    .then(r => r.json())
    .then(data => {
        let html = '<table><thead><tr><th>Type</th><th>Document</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        data.forEach(item => {
            const icon = {
                'invoice': '💰',
                'quotation': '📝', 
                'purchase_order': '🛒',
                'payment': '💳'
            }[item.record_type] || '📄';
            
            html += `<tr>
                <td>${icon} ${item.record_type}</td>
                <td>${item.document_number}</td>
                <td>${item.customer_name || item.customer_id || '-'}</td>
                <td>₹${parseFloat(item.amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                <td>${item.status || '-'}</td>
                <td>${new Date(item.created_at).toLocaleDateString()}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('activities').innerHTML = html;
    })
    .catch(err => {
        document.getElementById('activities').innerHTML = '<p>Error loading activities: ' + err.message + '</p>';
    });
    </script>
</body>
</html>