<?php
$title = 'User Ledger - ' . ($user['name'] ?? 'Unknown User');
$active_page = 'ledgers';
$csvParams = array_filter(['from_date' => $fromDate ?? '', 'to_date' => $toDate ?? '', 'transaction_type' => $transactionType ?? '']);
$csvUrl = '/ergon/ledgers/user/' . (int)$user_id . '/download-csv' . ($csvParams ? '?' . http_build_query($csvParams) : '');
ob_start();
?>

<div class="page-header" id="ledger-header">
    <div class="page-title">
        <h1>💰 User Ledger</h1>
        <p>Financial transaction history for <strong><?= htmlspecialchars($user['name'] ?? 'Unknown User') ?></strong> (<?= htmlspecialchars($user['role'] ?? 'N/A') ?>)</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/users" class="btn btn--secondary">← Back to Users</a>
        <button onclick="refreshLedger()" class="btn btn--info" id="refreshBtn">🔄 Refresh</button>
        <button onclick="window.print()" class="btn btn--outline">🖨️ Print</button>
        <a href="<?= htmlspecialchars($csvUrl) ?>" class="btn btn--primary">📥 Download CSV</a>
    </div>
</div>

<!-- Date Filter Section -->
<div id="ledger-section">
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
                        <input type="date" id="fromDate" name="from_date" value="<?= htmlspecialchars($fromDate ?? '') ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="toDate">To Date:</label>
                        <input type="date" id="toDate" name="to_date" value="<?= htmlspecialchars($toDate ?? '') ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="transactionType">Type:</label>
                        <select id="transactionType" name="transaction_type">
                            <option value="all">All Types</option>
                            <option value="advance" <?= ($transactionType ?? '') === 'advance' ? 'selected' : '' ?>>Advances Only</option>
                            <option value="expense" <?= ($transactionType ?? '') === 'expense' ? 'selected' : '' ?>>Expenses Only</option>
                            <option value="manual" <?= ($transactionType ?? '') === 'manual' ? 'selected' : '' ?>>Manual Entries Only</option>
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

<!-- Period Summary (when filtered) -->
<?php if ($isFiltered ?? false): ?>
<div class="card" style="background: #f8f9fa; margin-bottom: 1.5rem;">
    <div class="card__body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
            <div>
                <small style="color: #6c757d; font-weight: 500;">📍 Opening Balance</small>
                <div style="font-size: 1.3rem; font-weight: 600; color: <?= $openingBalance >= 0 ? '#059669' : '#dc2626' ?>;">
                    <?= $openingBalance < 0 ? '-' : '' ?>₹<?= number_format(abs($openingBalance), 2) ?>
                </div>
            </div>
            <div>
                <small style="color: #6c757d; font-weight: 500;">📊 Net Activity</small>
                <div style="font-size: 1.3rem; font-weight: 600; color: <?= $netActivity >= 0 ? '#059669' : '#dc2626' ?>;">
                    <?= $netActivity >= 0 ? '+' : '-' ?>₹<?= number_format(abs($netActivity), 2) ?>
                </div>
            </div>
            <div>
                <small style="color: #6c757d; font-weight: 500;">🎯 Closing Balance</small>
                <div style="font-size: 1.3rem; font-weight: 600; color: <?= $balance >= 0 ? '#059669' : '#dc2626' ?>;">
                    <?= $balance < 0 ? '-' : '' ?>₹<?= number_format(abs($balance), 2) ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="dashboard-grid" style="margin-bottom: 1.5rem;">

    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon">💸</div></div>
        <div class="kpi-card__value" style="color:#059669;">₹<?= number_format($advancesGiven, 2) ?></div>
        <div class="kpi-card__label">Advances Received</div>
        <div class="kpi-card__status"><?= $advanceCount ?> advance<?= $advanceCount !== 1 ? 's' : '' ?></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon">🧾</div></div>
        <div class="kpi-card__value" style="color:#dc2626;">₹<?= number_format($expensesIncurred, 2) ?></div>
        <div class="kpi-card__label">Expenses Incurred</div>
        <div class="kpi-card__status"><?= $expenseCount ?> expense<?= $expenseCount !== 1 ? 's' : '' ?></div>
    </div>

    <div class="kpi-card" style="border:2px solid <?= $outstanding == 0 ? '#6b7280' : ($outstanding > 0 ? '#dc2626' : '#059669') ?>;">
        <div class="kpi-card__header"><div class="kpi-card__icon">⚖️</div></div>
        <div class="kpi-card__value <?= $outstanding == 0 ? 'text-muted' : ($outstanding > 0 ? 'text-danger' : 'text-success') ?>">
            ₹<?= number_format(abs($outstanding), 2) ?>
        </div>
        <div class="kpi-card__label">Outstanding</div>
        <div class="kpi-card__status">
            <?php if ($outstanding == 0): ?>
                ✅ Fully settled
            <?php elseif ($outstanding > 0): ?>
                🔴 Employee owes company
            <?php else: ?>
                🟢 Company owes employee
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Ledger Entries -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">📋 Transaction History</h2>
        <div class="card__actions">
            <span class="badge badge--info" id="entryCount"><?= count($entries) ?> Entries</span>
            <?php if ($isFiltered ?? false): ?>
                <span class="badge badge--warning">Filtered</span>
            <?php endif; ?>
            <button onclick="refreshLedger()" class="btn btn--sm btn--info" id="refreshBtnInline" title="Fetch latest transactions">🔄 Refresh</button>
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
            <!-- Ledger Logic Explanation -->
            <div style="background: linear-gradient(135deg, #e7f3ff 0%, #f0f8ff 100%); border-left: 4px solid #0ea5e9; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 4px;">
                <strong style="color: #0284c7; display: block; margin-bottom: 1rem; font-size: 1rem;">📖 How This Ledger Works</strong>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-size: 0.9rem;">
                    <div style="padding: 0.75rem; background: rgba(255,255,255,0.8); border-radius: 4px; border-left: 3px solid #dc2626;">
                        <strong style="color: #dc2626;">🧾 Expense (Pending)</strong><br/>
                        <small>Employee paid out of pocket</small><br/>
                        <span style="color: #dc2626; font-weight: 600;">-DEBIT</span>
                    </div>
                    <div style="padding: 0.75rem; background: rgba(255,255,255,0.8); border-radius: 4px; border-left: 3px solid #059669;">
                        <strong style="color: #059669;">✅ Reimbursed</strong><br/>
                        <small>Company paid them back</small><br/>
                        <span style="color: #059669; font-weight: 600;">+CREDIT</span>
                    </div>
                    <div style="padding: 0.75rem; background: rgba(255,255,255,0.8); border-radius: 4px; border-left: 3px solid #059669;">
                        <strong style="color: #059669;">💸 Advance</strong><br/>
                        <small>Company gave them upfront</small><br/>
                        <span style="color: #059669; font-weight: 600;">+CREDIT</span>
                    </div>
                    <div style="padding: 0.75rem; background: rgba(255,255,255,0.8); border-radius: 4px; border-left: 3px solid #0ea5e9;">
                        <strong style="color: #0284c7;">⚖️ Balance</strong><br/>
                        <small>₹0 = Settled</small><br/>
                        <small>+ = Company owes them</small><br/>
                        <small>- = They owe company</small>
                    </div>
                </div>
            </div>
            <div class="ledger-entries">
                <?php foreach ($entries as $entry): ?>
                <div class="ledger-card ledger-card--<?= $entry['direction'] ?>">
                    <div class="ledger-card__left">
                        <div class="ledger-card__date"><?= date('d M Y', strtotime($entry['date'])) ?></div>
                        <div class="ledger-card__ref"><?= strtoupper($entry['reference_type']) ?> #<?= $entry['reference_id'] ?></div>
                        <div class="ledger-card__desc"><?= htmlspecialchars($entry['description'] ?? 'N/A') ?></div>
                        <div class="ledger-card__meta">
                            <?php if ($entry['reference_type'] === 'advance'): ?>
                                <span class="lc-badge lc-badge--advance">💸 Advance</span>
                            <?php elseif ($entry['entry_type'] === 'expense_reimbursement'): ?>
                                <span class="lc-badge lc-badge--reimburse">✅ Reimbursed</span>
                            <?php elseif ($entry['reference_type'] === 'expense'): ?>
                                <span class="lc-badge lc-badge--expense">🧾 Expense</span>
                            <?php else: ?>
                                <span class="lc-badge lc-badge--manual">✏️ Manual</span>
                            <?php endif; ?>
                            <span class="lc-cat"><?= htmlspecialchars($entry['category'] ?? '') ?></span>
                            <span class="lc-status lc-status--<?= strtolower($entry['status'] ?? 'unknown') ?>">
                                <?= $entry['status'] === 'paid' ? '✅ Paid' : ($entry['status'] === 'approved' ? '⏳ Approved' : ucfirst($entry['status'] ?? '')) ?>
                            </span>
                        </div>
                    </div>
                    <div class="ledger-card__right">
                        <div class="ledger-card__amount ledger-card__amount--<?= $entry['direction'] ?>">
                            <?= $entry['direction'] === 'credit' ? '+' : '-' ?>₹<?= number_format($entry['amount'], 2) ?>
                        </div>
                        <div class="ledger-card__balance <?= $entry['balance_after'] >= 0 ? 'bal-pos' : 'bal-neg' ?>">
                            Bal: ₹<?= number_format($entry['balance_after'], 2) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Summary Footer -->
            <div class="ledger-summary">
                <div class="summary-row">
                    <span class="summary-label">Advances Received:</span>
                    <span class="summary-value text-success">+₹<?= number_format($advancesGiven, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Expenses Incurred:</span>
                    <span class="summary-value text-danger">-₹<?= number_format($expensesIncurred, 2) ?></span>
                </div>
                <div class="summary-row summary-row--total">
                    <span class="summary-label"><strong>Outstanding:</strong></span>
                    <span class="summary-value <?= $outstanding == 0 ? 'text-muted' : ($outstanding > 0 ? 'text-danger' : 'text-success') ?>">
                        <strong>
                            <?php if ($outstanding == 0): ?>
                                ₹0.00 ✅ Settled
                            <?php elseif ($outstanding > 0): ?>
                                ₹<?= number_format($outstanding, 2) ?> 🔴 Employee owes company
                            <?php else: ?>
                                ₹<?= number_format(abs($outstanding), 2) ?> 🟢 Company owes employee
                            <?php endif; ?>
                        </strong>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.btn--info { background:#0ea5e9;color:#fff;border:none;cursor:pointer; }
.btn--info:hover { background:#0284c7; }
.btn--info:disabled { opacity:.6;cursor:not-allowed; }
.btn--sm { padding:.25rem .6rem;font-size:.8rem; }

/* Filter */
.filter-section { margin-bottom:1.5rem; }
.filter-form { margin:0; }
.filter-row { display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end; }
.filter-group { display:flex;flex-direction:column;min-width:140px;flex:1; }
.filter-group label { font-size:.85rem;font-weight:500;margin-bottom:.25rem;color:#495057; }
.filter-group input,.filter-group select { padding:.45rem .5rem;border:1px solid #ced4da;border-radius:4px;font-size:.85rem; }
.filter-actions { display:flex;gap:.5rem;align-items:flex-end; }

/* Ledger cards */
.ledger-entries { display:flex;flex-direction:column;gap:.5rem; }

.ledger-card {
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    padding:.75rem 1rem;
    border-radius:8px;
    border:1px solid #e5e7eb;
    border-left:4px solid #e5e7eb;
    background:#fff;
    gap:.75rem;
}
.ledger-card--credit { border-left-color:#10b981;background:#f0fdf4; }
.ledger-card--debit  { border-left-color:#ef4444;background:#fef2f2; }

.ledger-card__left { flex:1;min-width:0; }
.ledger-card__date { font-size:.75rem;color:#6b7280;margin-bottom:2px; }
.ledger-card__ref  { font-size:.7rem;font-family:monospace;color:#9ca3af;margin-bottom:3px; }
.ledger-card__desc { font-size:.88rem;font-weight:600;color:#1f2937;margin-bottom:5px;word-break:break-word; }
.ledger-card__meta { display:flex;flex-wrap:wrap;gap:.35rem;align-items:center; }

.ledger-card__right { text-align:right;flex-shrink:0; }
.ledger-card__amount { font-size:1rem;font-weight:700; }
.ledger-card__amount--credit { color:#059669; }
.ledger-card__amount--debit  { color:#dc2626; }
.ledger-card__balance { font-size:.75rem;margin-top:3px;font-weight:500; }
.bal-pos { color:#059669; }
.bal-neg { color:#dc2626; }

/* Badges */
.lc-badge { display:inline-block;padding:2px 7px;border-radius:10px;font-size:.7rem;font-weight:600; }
.lc-badge--advance   { background:#d1fae5;color:#065f46; }
.lc-badge--expense   { background:#fef3c7;color:#92400e; }
.lc-badge--reimburse { background:#dbeafe;color:#1e40af; }
.lc-badge--manual    { background:#e0e7ff;color:#3730a3; }
.lc-cat    { font-size:.7rem;color:#6b7280;background:#f3f4f6;padding:2px 6px;border-radius:8px; }
.lc-status { font-size:.7rem;padding:2px 6px;border-radius:8px;font-weight:600; }
.lc-status--paid     { background:#d1fae5;color:#065f46; }
.lc-status--approved { background:#fef3c7;color:#92400e; }
.lc-status--pending  { background:#fef3c7;color:#92400e; }
.lc-status--rejected { background:#fee2e2;color:#991b1b; }
.lc-status--manual   { background:#e0e7ff;color:#3730a3; }

/* Summary footer */
.ledger-summary { margin-top:1rem;padding:1rem;background:#f8f9fa;border-radius:6px;border-top:2px solid #dee2e6; }
.summary-row { display:flex;justify-content:space-between;margin-bottom:.4rem;font-size:.9rem; }
.summary-row--total { border-top:1px solid #dee2e6;padding-top:.5rem;margin-top:.5rem; }

.text-success { color:#28a745!important; }
.text-danger  { color:#dc3545!important; }
.text-muted   { color:#6c757d!important; }

.empty-state { text-align:center;padding:3rem 1rem;color:#6c757d; }
.empty-state__icon { font-size:3rem;margin-bottom:1rem;opacity:.5; }

/* Mobile tweaks */
@media(max-width:480px) {
    .filter-group { min-width:100%;flex:none; }
    .filter-actions { width:100%;justify-content:stretch; }
    .filter-actions .btn { flex:1; }
    .ledger-card { padding:.6rem .75rem; }
    .ledger-card__amount { font-size:.9rem; }
    .page-actions { flex-wrap:wrap;gap:.4rem; }
    .page-actions .btn { font-size:.78rem;padding:.35rem .6rem; }
}

@media print {
    body * { visibility:hidden; }
    #ledger-section, #ledger-section * { visibility:visible; }
    #ledger-section { position:absolute;top:0;left:0;width:100%; }
    .filter-section,.card__actions,.btn { display:none!important; }
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
    window.location.href = '<?= htmlspecialchars($csvUrl) ?>';
}

// Initialize quick-filter dropdown to match current filter state
document.addEventListener('DOMContentLoaded', function() {
    const fromDate       = document.getElementById('fromDate').value;
    const toDate         = document.getElementById('toDate').value;
    const typeSelect     = document.getElementById('transactionType');

    // Inputs are already pre-filled by PHP; just set the quick-filter label
    if (fromDate || toDate) {
        document.getElementById('dateRange').value = 'custom';
    }
});
</script>

</div><!-- /#ledger-section -->

<?php if (!empty($pendingEntries)): ?>
<div class="card" style="margin-top:1.5rem;">
    <div class="card__header">
        <h2 class="card__title">⏳ Pending Approvals</h2>
        <span class="badge badge--warning"><?= count($pendingEntries) ?> Pending</span>
    </div>
    <div class="card__body">
        <div class="ledger-entries">
            <?php foreach ($pendingEntries as $p): ?>
            <div class="ledger-card" style="border-left-color:#f59e0b;background:#fffbeb;">
                <div class="ledger-card__left">
                    <div class="ledger-card__date"><?= $p['date'] ? date('d M Y', strtotime($p['date'])) : '—' ?></div>
                    <div class="ledger-card__ref"><?= strtoupper($p['reference_type']) ?> #<?= $p['reference_id'] ?></div>
                    <div class="ledger-card__desc"><?= htmlspecialchars($p['description']) ?></div>
                    <div class="ledger-card__meta">
                        <?php if ($p['reference_type'] === 'advance'): ?>
                            <span class="lc-badge lc-badge--advance">💸 Advance</span>
                        <?php else: ?>
                            <span class="lc-badge lc-badge--expense">🧾 Expense</span>
                        <?php endif; ?>
                        <span class="lc-cat"><?= htmlspecialchars($p['category']) ?></span>
                        <span class="lc-status lc-status--pending">⏳ Pending</span>
                    </div>
                </div>
                <div class="ledger-card__right">
                    <div class="ledger-card__amount" style="color:#d97706;">₹<?= number_format($p['amount'], 2) ?></div>
                    <?php if (in_array($_SESSION['role'] ?? '', ['owner', 'company_owner'])): ?>
                    <div style="display:flex;gap:.4rem;margin-top:.5rem;justify-content:flex-end;">
                        <button onclick="ledgerApprove('<?= $p['reference_type'] ?>', <?= $p['reference_id'] ?>, <?= $p['amount'] ?>)"
                                class="btn btn--sm" style="background:#059669;color:#fff;border:none;cursor:pointer;">✅ Approve</button>
                        <button onclick="ledgerReject('<?= $p['reference_type'] ?>', <?= $p['reference_id'] ?>)"
                                class="btn btn--sm" style="background:#dc2626;color:#fff;border:none;cursor:pointer;">❌ Reject</button>
                    </div>
                    <?php else: ?>
                    <div style="font-size:.7rem;color:#92400e;margin-top:3px;">Awaiting approval</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if (in_array($_SESSION['role'] ?? '', ['owner', 'company_owner'])): ?>
<!-- Approve Modal -->
<div id="ledgerApproveModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:8px;padding:1.5rem;width:90%;max-width:400px;">
        <h3 style="margin:0 0 1rem;">✅ Approve</h3>
        <div style="margin-bottom:1rem;">
            <label style="font-size:.85rem;font-weight:500;">Approved Amount (₹)</label>
            <input type="number" id="lapproveAmount" step="0.01" style="width:100%;padding:.45rem;border:1px solid #ced4da;border-radius:4px;margin-top:.25rem;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:1rem;">
            <label style="font-size:.85rem;font-weight:500;">Remarks (optional)</label>
            <input type="text" id="lapproveRemarks" style="width:100%;padding:.45rem;border:1px solid #ced4da;border-radius:4px;margin-top:.25rem;box-sizing:border-box;">
        </div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;">
            <button onclick="closeLedgerModals()" class="btn btn--secondary">Cancel</button>
            <button onclick="submitLedgerApprove()" class="btn btn--primary">Approve</button>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="ledgerRejectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:8px;padding:1.5rem;width:90%;max-width:400px;">
        <h3 style="margin:0 0 1rem;">❌ Reject</h3>
        <div style="margin-bottom:1rem;">
            <label style="font-size:.85rem;font-weight:500;">Reason <span style="color:red;">*</span></label>
            <input type="text" id="lrejectReason" style="width:100%;padding:.45rem;border:1px solid #ced4da;border-radius:4px;margin-top:.25rem;box-sizing:border-box;" placeholder="Enter rejection reason">
        </div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;">
            <button onclick="closeLedgerModals()" class="btn btn--secondary">Cancel</button>
            <button onclick="submitLedgerReject()" style="background:#dc2626;color:#fff;border:none;padding:.45rem 1rem;border-radius:4px;cursor:pointer;">Reject</button>
        </div>
    </div>
</div>

<script>
let _lType, _lId;

function ledgerApprove(type, id, amount) {
    _lType = type; _lId = id;
    document.getElementById('lapproveAmount').value = amount;
    document.getElementById('lapproveRemarks').value = '';
    const m = document.getElementById('ledgerApproveModal');
    m.style.display = 'flex';
}
function ledgerReject(type, id) {
    _lType = type; _lId = id;
    document.getElementById('lrejectReason').value = '';
    const m = document.getElementById('ledgerRejectModal');
    m.style.display = 'flex';
}
function closeLedgerModals() {
    document.getElementById('ledgerApproveModal').style.display = 'none';
    document.getElementById('ledgerRejectModal').style.display = 'none';
}
function submitLedgerApprove() {
    const amount  = document.getElementById('lapproveAmount').value;
    const remarks = document.getElementById('lapproveRemarks').value;
    const url = '/ergon/' + _lType + 's/approve/' + _lId;
    const body = new URLSearchParams({approved_amount: amount, approval_remarks: remarks});
    fetch(url, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body})
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); else alert(d.error || 'Approval failed'); })
        .catch(() => alert('Request failed'));
}
function submitLedgerReject() {
    const reason = document.getElementById('lrejectReason').value.trim();
    if (!reason) { alert('Rejection reason is required'); return; }
    const url = '/ergon/' + _lType + 's/reject/' + _lId;
    const body = new URLSearchParams({rejection_reason: reason});
    fetch(url, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body})
        .then(() => location.reload())
        .catch(() => alert('Request failed'));
}
</script>
<?php endif; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
