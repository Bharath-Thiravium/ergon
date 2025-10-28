<?php
$title = 'Expense Claim Details';
$active_page = 'expenses';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>💰</span> Expense Claim Details</h1>
        <p>View expense claim information</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/expenses" class="btn btn--secondary">
            <span>←</span> Back to Expenses
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>💰</span> Expense Claim
        </h2>
    </div>
    <div class="card__body">
        <div class="detail-grid">
            <div class="detail-item">
                <label>Employee</label>
                <span><?= htmlspecialchars($expense['user_name'] ?? 'Unknown') ?></span>
            </div>
            <div class="detail-item">
                <label>Description</label>
                <span><?= htmlspecialchars($expense['description'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Amount</label>
                <span>$<?= number_format($expense['amount'] ?? 0, 2) ?></span>
            </div>
            <div class="detail-item">
                <label>Category</label>
                <span><?= htmlspecialchars($expense['category'] ?? 'General') ?></span>
            </div>
            <div class="detail-item">
                <label>Expense Date</label>
                <span><?= date('M d, Y', strtotime($expense['expense_date'] ?? 'now')) ?></span>
            </div>
            <div class="detail-item">
                <label>Status</label>
                <span class="badge badge--warning"><?= ucfirst($expense['status'] ?? 'pending') ?></span>
            </div>
            <div class="detail-item">
                <label>Submitted</label>
                <span><?= date('M d, Y', strtotime($expense['created_at'] ?? 'now')) ?></span>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>