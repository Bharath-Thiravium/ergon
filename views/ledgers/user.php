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
        <button onclick="refreshLedger()" class="btn btn--info" id="refreshBtn">🔄 Refresh</button>
        <button onclick="window.print()" class="btn btn--outline">🖨️ Print</button>
        <button onclick="downloadLedger()" class="btn btn--primary">📥 Download CSV</button>
    </div>
</div>

<!-- Date Filter Section -->
<div class="filter-section">
    <div class="card">
        <div class="card__header">
            <h3 class="card__title">📅 Filter Transactions</h3>
        </div>
        <div class="card__body">
            <form id="dateFilterForm" class="filter-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="dateRange">Quick Filter:</label>
                        <select id="dateRange" onchange="applyQuickFilter()">
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="this_week">This Week</option>
                            <option value="last_week">Last Week</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_quarter">This Quarter</option>
                            <option value="this_year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="fromDate">From Date:</label>
                        <input type="date" id="fromDate" name="from_date" value="<?= $fromDate ?? '' ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="toDate">To Date:</label>
                        <input type="date" id="toDate" name="to_date" value="<?= $toDate ?? '' ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="transactionType">Type:</label>
                        <select id="transactionType" name="transaction_type">
                            <option value="all">All Types</option>
                            <option value="advance" <?= ($transactionType ?? '') === 'advance' ? 'selected' : '' ?>>Advances Only</option>
                            <option value="expense" <?= ($transactionType ?? '') === 'expense' ? 'selected' : '' ?>>Expenses Only</option>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="button" onclick="applyFilter()" class="btn btn--primary">🔍 Filter</button>
                        <button type="button" onclick="clearFilter()" class="btn btn--secondary">🗑️ Clear</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="dashboard-grid" style="margin-bottom: 1.5rem;">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💳</div>
        </div>
        <div class="kpi-card__value <?= $balance >= 0 ? 'text-success' : 'text-danger' ?>">
            ₹<?= number_format(abs($balance), 2) ?>
        </div>
        <div class="kpi-card__label">Current Balance</div>
        <div class="kpi-card__status"><?= $balance >= 0 ? 'Credit' : 'Debit' ?></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📈</div>
        </div>
        <div class="kpi-card__value" style="color:#059669;">₹<?= number_format($totalCredits, 2) ?></div>
        <div class="kpi-card__label">Total Credits</div>
        <div class="kpi-card__status"><?= $advanceCount ?> Advances</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📉</div>
        </div>
        <div class="kpi-card__value" style="color:#dc2626;">₹<?= number_format($totalDebits, 2) ?></div>
        <div class="kpi-card__label">Total Debits</div>
        <div class="kpi-card__status"><?= $expenseCount ?> Expenses</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚖️</div>
        </div>
        <div class="kpi-card__value <?= $netActivity >= 0 ? 'text-success' : 'text-danger' ?>">
            ₹<?= number_format(abs($netActivity), 2) ?>
        </div>
        <div class="kpi-card__label">Net Activity</div>
        <div class="kpi-card__status"><?= $netActivity >= 0 ? 'Net Credit' : 'Net Debit' ?></div>
    </div>
</div>

<!-- Ledger Entries -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">📋 Transaction History</h2>
        <div class="card__actions">
            <span class="badge badge--info" id="entryCount"><?= count($filteredEntries ?? $entries) ?> Entries</span>
            <?php if (isset($fromDate) || isset($toDate) || isset($transactionType)): ?>
                <span class="badge badge--warning">Filtered</span>
            <?php endif; ?>
            <button onclick="refreshLedger()" class="btn btn--sm btn--info" id="refreshBtnInline" title="Fetch latest transactions">🔄 Refresh</button>
        </div>
    </div>
    <div class="card__body">
        <?php if (empty($filteredEntries ?? $entries)): ?>
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
                        <?php foreach (($filteredEntries ?? $entries) as $entry): ?>
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
.btn--info { background: #0ea5e9; color: #fff; border: none; cursor: pointer; }
.btn--info:hover { background: #0284c7; }
.btn--info:disabled { opacity: 0.6; cursor: not-allowed; }
.btn--sm { padding: 0.25rem 0.6rem; font-size: 0.8rem; }
/* Ledger-specific styles */
.filter-section { margin-bottom: 2rem; }
.filter-form { margin: 0; }
.filter-row { display: flex; flex-wrap: wrap; gap: 1rem; align-items: end; }
.filter-group { display: flex; flex-direction: column; min-width: 150px; }
.filter-group label { font-size: 0.9rem; font-weight: 500; margin-bottom: 0.25rem; color: #495057; }
.filter-group input, .filter-group select { padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; font-size: 0.9rem; }
.filter-group input:focus, .filter-group select:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 2px rgba(0,123,255,0.25); }
.filter-actions { display: flex; gap: 0.5rem; align-items: end; }
.filter-actions .btn { white-space: nowrap; }
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

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .filter-actions {
        justify-content: center;
        margin-top: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

@media print {
    .page-actions, .card__actions, .filter-section {
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

<script>
// Date filter functionality
function applyQuickFilter() {
    const range = document.getElementById('dateRange').value;
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');
    
    const today = new Date();
    let from, to;
    
    switch(range) {
        case 'today':
            from = to = today.toISOString().split('T')[0];
            break;
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            from = to = yesterday.toISOString().split('T')[0];
            break;
        case 'this_week':
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay());
            from = startOfWeek.toISOString().split('T')[0];
            to = today.toISOString().split('T')[0];
            break;
        case 'last_week':
            const lastWeekEnd = new Date(today);
            lastWeekEnd.setDate(today.getDate() - today.getDay() - 1);
            const lastWeekStart = new Date(lastWeekEnd);
            lastWeekStart.setDate(lastWeekEnd.getDate() - 6);
            from = lastWeekStart.toISOString().split('T')[0];
            to = lastWeekEnd.toISOString().split('T')[0];
            break;
        case 'this_month':
            from = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            to = today.toISOString().split('T')[0];
            break;
        case 'last_month':
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
            from = lastMonth.toISOString().split('T')[0];
            to = lastMonthEnd.toISOString().split('T')[0];
            break;
        case 'this_quarter':
            const quarter = Math.floor(today.getMonth() / 3);
            from = new Date(today.getFullYear(), quarter * 3, 1).toISOString().split('T')[0];
            to = today.toISOString().split('T')[0];
            break;
        case 'this_year':
            from = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
            to = today.toISOString().split('T')[0];
            break;
        case 'all':
            from = to = '';
            break;
        case 'custom':
            return; // Don't auto-fill for custom
    }
    
    fromDate.value = from;
    toDate.value = to;
    
    if (range !== 'custom' && range !== 'all') {
        applyFilter();
    } else if (range === 'all') {
        clearFilter();
    }
}

function applyFilter() {
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    const transactionType = document.getElementById('transactionType').value;
    
    const params = new URLSearchParams(window.location.search);
    
    if (fromDate) params.set('from_date', fromDate);
    else params.delete('from_date');
    
    if (toDate) params.set('to_date', toDate);
    else params.delete('to_date');
    
    if (transactionType && transactionType !== 'all') params.set('transaction_type', transactionType);
    else params.delete('transaction_type');
    
    window.location.search = params.toString();
}

function clearFilter() {
    const params = new URLSearchParams(window.location.search);
    params.delete('from_date');
    params.delete('to_date');
    params.delete('transaction_type');
    
    document.getElementById('dateRange').value = 'all';
    document.getElementById('fromDate').value = '';
    document.getElementById('toDate').value = '';
    document.getElementById('transactionType').value = 'all';
    
    window.location.search = params.toString();
}

function refreshLedger() {
    const btn = document.getElementById('refreshBtn');
    const btnInline = document.getElementById('refreshBtnInline');
    if (btn) { btn.disabled = true; btn.textContent = '⏳ Refreshing...'; }
    if (btnInline) { btnInline.disabled = true; btnInline.textContent = '⏳...'; }
    // Strip any existing refresh param and reload — controller re-fetches all source data
    const url = new URL(window.location.href);
    url.searchParams.delete('_r');
    url.searchParams.set('_r', Date.now());
    window.location.href = url.toString();
}

function downloadLedger() {
    const params = new URLSearchParams(window.location.search);
    params.set('download', 'csv');
    
    const downloadUrl = window.location.pathname + '?' + params.toString();
    window.open(downloadUrl, '_blank');
}

// Initialize date range selector based on current URL params
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const fromDate = params.get('from_date');
    const toDate = params.get('to_date');
    const transactionType = params.get('transaction_type');
    
    if (fromDate) document.getElementById('fromDate').value = fromDate;
    if (toDate) document.getElementById('toDate').value = toDate;
    if (transactionType) document.getElementById('transactionType').value = transactionType;
    
    // Set quick filter if it matches a predefined range
    if (fromDate || toDate) {
        document.getElementById('dateRange').value = 'custom';
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
