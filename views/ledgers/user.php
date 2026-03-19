<?php
$title = 'User Ledger - ' . ($user['name'] ?? 'Unknown User');
$active_page = 'ledgers';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1>💰 User Ledger</h1>
        <p>Financial transaction history for <strong><?= htmlspecialchars($user['name'] ?? 'Unknown User') ?></strong> (<?= htmlspecialchars($user['role'] ?? 'N/A') ?>)</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/users" class="btn btn--secondary">← Back to Users</a>
        <button onclick="window.print()" class="btn btn--outline">🖨️ Print</button>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid">
    <div class="stat-card stat-card--<?= $balance >= 0 ? 'success' : 'danger' ?>">
        <div class="stat-card__icon">💳</div>
        <div class="stat-card__content">
            <h3>Current Balance</h3>
            <p class="stat-card__value">₹<?= number_format(abs($balance), 2) ?></p>
            <span class="stat-card__label"><?= $balance >= 0 ? 'Credit Balance' : 'Debit Balance' ?></span>
        </div>
    </div>
    
    <div class="stat-card stat-card--info">
        <div class="stat-card__icon">📈</div>
        <div class="stat-card__content">
            <h3>Total Credits</h3>
            <p class="stat-card__value">₹<?= number_format($totalCredits, 2) ?></p>
            <span class="stat-card__label"><?= $advanceCount ?> Advances Received</span>
        </div>
    </div>
    
    <div class="stat-card stat-card--warning">
        <div class="stat-card__icon">📉</div>
        <div class="stat-card__content">
            <h3>Total Debits</h3>
            <p class="stat-card__value">₹<?= number_format($totalDebits, 2) ?></p>
            <span class="stat-card__label"><?= $expenseCount ?> Expenses Deducted</span>
        </div>
    </div>
    
    <div class="stat-card stat-card--<?= $netActivity >= 0 ? 'success' : 'danger' ?>">
        <div class="stat-card__icon">⚖️</div>
        <div class="stat-card__content">
            <h3>Net Activity</h3>
            <p class="stat-card__value">₹<?= number_format(abs($netActivity), 2) ?></p>
            <span class="stat-card__label"><?= $netActivity >= 0 ? 'Net Credit' : 'Net Debit' ?></span>
        </div>
    </div>
</div>

<!-- Ledger Entries -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">📋 Transaction History</h2>
        <div class="card__actions">
            <span class="badge badge--info"><?= count($entries) ?> Total Entries</span>
        </div>
    </div>
    <div class="card__body">
        <?php if (empty($entries)): ?>
            <div class="empty-state">
                <div class="empty-state__icon">📝</div>
                <h3>No Transactions Found</h3>
                <p>This user has no ledger entries yet. Transactions will appear here once advances are paid or expenses are processed.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table--striped">
                    <thead>
                        <tr>
                            <th>📅 Date</th>
                            <th>🏷️ Type</th>
                            <th>📄 Reference</th>
                            <th>📝 Description</th>
                            <th>📊 Category</th>
                            <th>💰 Amount</th>
                            <th>⚖️ Balance</th>
                            <th>📈 Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                        <tr class="ledger-entry ledger-entry--<?= $entry['direction'] ?>">
                            <td class="ledger-date">
                                <strong><?= date('M d, Y', strtotime($entry['created_at'])) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge--<?= $entry['reference_type'] === 'advance' ? 'success' : 'warning' ?>">
                                    <?= $entry['reference_type'] === 'advance' ? '💸 Advance' : '💳 Expense' ?>
                                </span>
                            </td>
                            <td class="ledger-reference">
                                <strong><?= strtoupper($entry['reference_type']) ?> #<?= $entry['reference_id'] ?></strong>
                            </td>
                            <td class="ledger-description">
                                <div class="description-text"><?= htmlspecialchars($entry['description'] ?? 'N/A') ?></div>
                            </td>
                            <td>
                                <span class="category-tag"><?= htmlspecialchars($entry['category'] ?? 'N/A') ?></span>
                            </td>
                            <td class="ledger-amount">
                                <span class="amount amount--<?= $entry['direction'] ?>">
                                    <?= $entry['direction'] === 'credit' ? '+' : '-' ?>₹<?= number_format($entry['amount'], 2) ?>
                                </span>
                            </td>
                            <td class="ledger-balance">
                                <strong class="balance-amount <?= $entry['balance_after'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    ₹<?= number_format($entry['balance_after'], 2) ?>
                                </strong>
                            </td>
                            <td>
                                <span class="status-badge status-badge--<?= strtolower($entry['status'] ?? 'unknown') ?>">
                                    <?= ucfirst($entry['status'] ?? 'Unknown') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary Footer -->
            <div class="ledger-summary">
                <div class="summary-row">
                    <span class="summary-label">Total Credits:</span>
                    <span class="summary-value text-success">+₹<?= number_format($totalCredits, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Debits:</span>
                    <span class="summary-value text-danger">-₹<?= number_format($totalDebits, 2) ?></span>
                </div>
                <div class="summary-row summary-row--total">
                    <span class="summary-label"><strong>Final Balance:</strong></span>
                    <span class="summary-value <?= $balance >= 0 ? 'text-success' : 'text-danger' ?>">
                        <strong>₹<?= number_format($balance, 2) ?></strong>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Ledger-specific styles */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #e0e0e0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-card--success { border-left-color: #28a745; }
.stat-card--danger { border-left-color: #dc3545; }
.stat-card--warning { border-left-color: #ffc107; }
.stat-card--info { border-left-color: #17a2b8; }

.stat-card__icon {
    font-size: 2rem;
    opacity: 0.8;
}

.stat-card__content h3 {
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card__value {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0;
    color: #333;
}

.stat-card__label {
    font-size: 0.8rem;
    color: #888;
}

.ledger-entry--credit {
    background-color: rgba(40, 167, 69, 0.05);
}

.ledger-entry--debit {
    background-color: rgba(220, 53, 69, 0.05);
}

.ledger-date {
    white-space: nowrap;
}

.ledger-reference strong {
    font-family: monospace;
    font-size: 0.9rem;
}

.ledger-description {
    max-width: 200px;
}

.description-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.category-tag {
    background: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    border: 1px solid #dee2e6;
}

.amount--credit {
    color: #28a745;
    font-weight: bold;
}

.amount--debit {
    color: #dc3545;
    font-weight: bold;
}

.balance-amount {
    font-family: monospace;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: bold;
}

.status-badge--paid { background: #d4edda; color: #155724; }
.status-badge--approved { background: #cce5ff; color: #004085; }
.status-badge--pending { background: #fff3cd; color: #856404; }
.status-badge--rejected { background: #f8d7da; color: #721c24; }
.status-badge--unknown { background: #e2e3e5; color: #383d41; }

.ledger-summary {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px solid #dee2e6;
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.summary-row--total {
    border-top: 1px solid #dee2e6;
    padding-top: 0.5rem;
    margin-top: 0.5rem;
}

.text-success { color: #28a745 !important; }
.text-danger { color: #dc3545 !important; }
.text-muted { color: #6c757d !important; }

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-state__icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    margin-bottom: 0.5rem;
    color: #495057;
}

@media print {
    .page-actions, .card__actions {
        display: none !important;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-card {
        break-inside: avoid;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
