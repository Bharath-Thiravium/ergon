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
                <span>₹<?= number_format($expense['amount'] ?? 0, 2) ?></span>
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
                <?php 
                $status = $expense['status'] ?? 'pending';
                $badgeClass = 'badge--warning';
                if ($status === 'approved') $badgeClass = 'badge--success';
                elseif ($status === 'rejected') $badgeClass = 'badge--danger';
                ?>
                <span class="badge <?= $badgeClass ?>"><?= ucfirst($status) ?></span>
            </div>
            <?php if (($expense['status'] ?? 'pending') === 'rejected' && !empty($expense['rejection_reason'])): ?>
            <div class="detail-item">
                <label>Rejection Reason</label>
                <span class="rejection-reason"><?= htmlspecialchars($expense['rejection_reason']) ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-item">
                <label>Submitted</label>
                <span><?= date('M d, Y', strtotime($expense['created_at'] ?? 'now')) ?></span>
            </div>
            <?php if (!empty($expense['approved_at'])): ?>
            <div class="detail-item">
                <label><?= ($expense['status'] ?? 'pending') === 'approved' ? 'Approved' : 'Processed' ?> Date</label>
                <span><?= date('M d, Y', strtotime($expense['approved_at'])) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}
.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.detail-item label {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}
.detail-item span {
    color: #6b7280;
    font-size: 0.95rem;
}
.rejection-reason {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 6px;
    padding: 0.75rem;
    color: #dc2626 !important;
    font-style: italic;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>