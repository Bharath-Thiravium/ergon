<?php
$title = 'Advance Request Details';
$active_page = 'advances';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>💳</span> Advance Request Details</h1>
        <p>View advance request information</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/advances" class="btn btn--secondary">
            <span>←</span> Back to Advances
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>💳</span> Advance Request
        </h2>
    </div>
    <div class="card__body">
        <div class="detail-grid">
            <div class="detail-item">
                <label>Employee</label>
                <span><?= htmlspecialchars($advance['user_name'] ?? 'Unknown') ?></span>
            </div>
            <div class="detail-item">
                <label>Amount</label>
                <span>$<?= number_format($advance['amount'] ?? 0, 2) ?></span>
            </div>
            <div class="detail-item">
                <label>Reason</label>
                <span><?= htmlspecialchars($advance['reason'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Status</label>
                <span class="badge badge--warning"><?= ucfirst($advance['status'] ?? 'pending') ?></span>
            </div>
            <div class="detail-item">
                <label>Requested</label>
                <span><?= date('M d, Y', strtotime($advance['created_at'] ?? 'now')) ?></span>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>