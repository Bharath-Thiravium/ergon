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

<div class="expense-compact">
    <div class="card">
        <div class="card__header">
            <div class="expense-title-row">
                <h2 class="expense-title">💰 <?= htmlspecialchars($expense['description'] ?? 'Expense Claim') ?></h2>
                <div class="expense-badges">
                    <?php 
                    $status = $expense['status'] ?? 'pending';
                    $statusClass = match($status) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning'
                    };
                    $statusIcon = match($status) {
                        'approved' => '✅',
                        'rejected' => '❌',
                        default => '⏳'
                    };
                    ?>
                    <span class="badge badge--<?= $statusClass ?>"><?= $statusIcon ?> <?= ucfirst($status) ?></span>
                    <div class="amount-display">
                        <span class="amount-text">₹<?= number_format($expense['amount'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card__body">
            <?php if (($expense['status'] ?? 'pending') === 'rejected' && !empty($expense['rejection_reason'])): ?>
            <div class="description-compact rejection-notice">
                <strong>Rejection Reason:</strong> <?= htmlspecialchars($expense['rejection_reason']) ?>
            </div>
            <?php endif; ?>
            
            <div class="details-compact">
                <div class="detail-group">
                    <h4>👤 Employee Details</h4>
                    <div class="detail-items">
                        <span><strong>Name:</strong> 👤 <?= htmlspecialchars($expense['user_name'] ?? 'Unknown') ?></span>
                        <span><strong>Category:</strong> 🏷️ <?= htmlspecialchars($expense['category'] ?? 'General') ?></span>
                        <span><strong>Amount:</strong> 💰 ₹<?= number_format($expense['amount'] ?? 0, 2) ?></span>
                    </div>
                </div>
                
                <div class="detail-group">
                    <h4>📅 Timeline</h4>
                    <div class="detail-items">
                        <span><strong>Expense Date:</strong> 📅 <?= date('M d, Y', strtotime($expense['expense_date'] ?? 'now')) ?></span>
                        <span><strong>Submitted:</strong> 📅 <?= date('M d, Y', strtotime($expense['created_at'] ?? 'now')) ?></span>
                        <?php if (!empty($expense['approved_at'])): ?>
                        <span><strong><?= ($expense['status'] ?? 'pending') === 'approved' ? 'Approved' : 'Processed' ?>:</strong> 📅 <?= date('M d, Y', strtotime($expense['approved_at'])) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-group">
                    <h4>📋 Details</h4>
                    <div class="detail-items">
                        <span><strong>Description:</strong> <?= nl2br(htmlspecialchars($expense['description'] ?? 'N/A')) ?></span>
                        <span><strong>Status:</strong> 
                            <span class="badge badge--<?= $statusClass ?>"><?= $statusIcon ?> <?= ucfirst($status) ?></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.expense-compact {
    max-width: 1000px;
    margin: 0 auto;
}

.expense-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    width: 100%;
    gap: 1.5rem;
    min-height: 2rem;
}

.expense-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    flex: 1 1 auto;
    min-width: 200px;
    max-width: calc(100% - 200px);
    overflow-wrap: break-word;
    word-break: break-word;
    line-height: 1.3;
}

.expense-badges {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 0 0 auto;
    min-width: 180px;
    justify-content: flex-end;
}

.amount-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.amount-text {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary);
    background: var(--bg-secondary);
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    border: 1px solid var(--border-color);
}

.description-compact {
    background: var(--bg-secondary);
    padding: 0.75rem;
    border-radius: 6px;
    border-left: 3px solid var(--primary);
    margin-bottom: 1rem;
    font-size: 0.9rem;
    line-height: 1.4;
}

.rejection-notice {
    background: #fef2f2;
    border-left-color: #dc2626;
    color: #dc2626;
}

.details-compact {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.detail-group {
    background: var(--bg-secondary);
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.detail-group h4 {
    margin: 0 0 0.75rem 0;
    font-size: 0.9rem;
    color: var(--primary);
    font-weight: 600;
}

.detail-items {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-items span {
    font-size: 0.85rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-items strong {
    color: var(--text-primary);
    min-width: 80px;
    font-size: 0.8rem;
}

@media (max-width: 768px) {
    .expense-title-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        min-height: auto;
    }
    
    .expense-title {
        max-width: 100%;
        min-width: auto;
    }
    
    .expense-badges {
        width: 100%;
        min-width: auto;
        justify-content: flex-start;
        flex-wrap: wrap;
    }
    
    .details-compact {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>