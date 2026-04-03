<?php
$title = 'Cash Ledger';
$active_page = 'ledgers';
ob_start();
?>

<div class="page-header" id="ledger-header">
    <div class="page-title">
        <h1>🏦 Cash Ledger</h1>
        <p>Company-wide paid expenses and advances</p>
    </div>
    <div class="page-actions">
        <button onclick="printLedger()" class="btn btn--outline">🖨️ Print / Save PDF</button>
        <a href="<?= htmlspecialchars($csvUrl) ?>" class="btn btn--primary">📥 Download CSV</a>
    </div>
</div>

<!-- Filter Panel (outside print zone intentionally) -->
<div class="filter-section no-print">
    <div class="card">
        <div class="card__header">
            <h3 class="card__title">📅 Filter Transactions</h3>
        </div>
        <div class="card__body">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Quick Filter:</label>
                    <select id="quickRange" onchange="applyQuickFilter()">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="this_week">This Week</option>
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="this_quarter">This Quarter</option>
                        <option value="this_year">This Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>From Date:</label>
                    <input type="date" id="fromDate" value="<?= htmlspecialchars($fromDate ?? '') ?>">
                </div>

                <div class="filter-group">
                    <label>To Date:</label>
                    <input type="date" id="toDate" value="<?= htmlspecialchars($toDate ?? '') ?>">
                </div>

                <div class="filter-group">
                    <label>Type:</label>
                    <select id="transactionType">
                        <option value="">All Types</option>
                        <option value="expense"  <?= ($transactionType ?? '') === 'expense'  ? 'selected' : '' ?>>Expenses Only</option>
                        <option value="advance"  <?= ($transactionType ?? '') === 'advance'  ? 'selected' : '' ?>>Advances Only</option>
                    </select>
                </div>

                <?php if (!empty($projects)): ?>
                <div class="filter-group">
                    <label>Project:</label>
                    <select id="projectFilter">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= ($projectId ?? 0) == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="filter-actions">
                    <button type="button" onclick="applyFilter()" class="btn btn--primary">🔍 Filter</button>
                    <button type="button" onclick="clearFilter()" class="btn btn--secondary">🗑️ Clear</button>
                </div>
            </div>
        </div>
    </div>
</div><!-- /.filter-section -->

<div id="ledger-section">

<!-- Print-only header: first child so it flows directly above cards -->
<div id="print-header" class="print-only">
    <div style="text-align:center;margin-bottom:16px;border-bottom:2px solid #333;padding-bottom:12px;">
        <h2 style="margin:0;font-size:20px;">Cash Ledger Report</h2>
        <p id="print-filter-summary" style="margin:4px 0 0;font-size:13px;color:#555;"></p>
        <p style="margin:2px 0 0;font-size:12px;color:#888;">Generated: <?= date('d M Y, h:i A') ?></p>
    </div>
</div>

<!-- Summary Cards -->
<div class="dashboard-grid" style="margin-bottom:1.5rem;">
    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon">💸</div></div>
        <div class="kpi-card__value text-danger">₹<?= number_format($totalDebits, 2) ?></div>
        <div class="kpi-card__label">Total Outflow</div>
        <div class="kpi-card__status"><?= count($entries) ?> transactions</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon">💳</div></div>
        <div class="kpi-card__value" style="color:#dc2626;">₹<?= number_format(array_sum(array_column(array_filter($entries, fn($e) => $e['reference_type'] === 'expense'), 'amount')), 2) ?></div>
        <div class="kpi-card__label">Expenses Paid</div>
        <div class="kpi-card__status"><?= $expenseCount ?> entries</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon">📤</div></div>
        <div class="kpi-card__value" style="color:#d97706;">₹<?= number_format(array_sum(array_column(array_filter($entries, fn($e) => $e['reference_type'] === 'advance'), 'amount')), 2) ?></div>
        <div class="kpi-card__label">Advances Paid</div>
        <div class="kpi-card__status"><?= $advanceCount ?> entries</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon">📊</div></div>
        <div class="kpi-card__value"><?= count($entries) ?></div>
        <div class="kpi-card__label">Total Entries</div>
        <div class="kpi-card__status"><?= ($isFiltered ?? false) ? 'Filtered view' : 'All records' ?></div>
    </div>
</div>

<!-- Ledger Table -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">📋 Transaction History</h2>
        <div class="card__actions">
            <span class="badge badge--info"><?= count($entries) ?> Entries</span>
            <?php if ($isFiltered ?? false): ?>
                <span class="badge badge--warning">Filtered</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card__body">
        <?php if (empty($entries)): ?>
            <div class="empty-state">
                <div class="empty-state__icon">📝</div>
                <h3>No Transactions Found</h3>
                <p>No paid expenses or advances match the selected filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table--striped">
                    <thead>
                        <tr>
                            <th>📅 Date</th>
                            <th>👤 Employee</th>
                            <th>🏷️ Type</th>
                            <th>📁 Project</th>
                            <th>📝 Description</th>
                            <th>📊 Category</th>
                            <th>💸 Debit</th>
                            <th>⚖️ Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                        <tr class="ledger-entry ledger-entry--debit">
                            <td style="white-space:nowrap;">
                                <strong><?= date('M d, Y', strtotime($entry['created_at'])) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($entry['employee_name'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge badge--<?= $entry['reference_type'] === 'expense' ? 'warning' : 'info' ?>">
                                    <?= $entry['reference_type'] === 'expense' ? '💳 Expense' : '📤 Advance' ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($entry['project_name'])): ?>
                                    <span class="category-tag"><?= htmlspecialchars($entry['project_name']) ?></span>
                                <?php else: ?>
                                    <span style="color:#9ca3af;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width:200px;">
                                <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?= htmlspecialchars($entry['description'] ?? 'N/A') ?>
                                </div>
                            </td>
                            <td>
                                <span class="category-tag"><?= htmlspecialchars($entry['category'] ?? 'N/A') ?></span>
                            </td>
                            <td>
                                <span style="color:#dc2626;font-weight:bold;">
                                    -₹<?= number_format($entry['amount'], 2) ?>
                                </span>
                            </td>
                            <td>
                                <strong style="font-family:monospace;color:<?= $entry['balance_after'] >= 0 ? '#28a745' : '#dc3545' ?>;">
                                    ₹<?= number_format($entry['balance_after'], 2) ?>
                                </strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="ledger-summary">
                <div class="summary-row">
                    <span class="summary-label">Total Expenses Paid:</span>
                    <span class="summary-value text-danger">-₹<?= number_format(array_sum(array_column(array_filter($entries, fn($e) => $e['reference_type'] === 'expense'), 'amount')), 2) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Advances Paid:</span>
                    <span class="summary-value text-danger">-₹<?= number_format(array_sum(array_column(array_filter($entries, fn($e) => $e['reference_type'] === 'advance'), 'amount')), 2) ?></span>
                </div>
                <div class="summary-row summary-row--total">
                    <span class="summary-label"><strong>Total Outflow:</strong></span>
                    <span class="summary-value text-danger"><strong>-₹<?= number_format($totalDebits, 2) ?></strong></span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

</div><!-- /#ledger-section -->

<style>
.filter-section { margin-bottom: 2rem; }
.filter-row { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; }
.filter-group { display: flex; flex-direction: column; min-width: 150px; }
.filter-group label { font-size: .9rem; font-weight: 500; margin-bottom: .25rem; color: #495057; }
.filter-group input, .filter-group select { padding: .5rem; border: 1px solid #ced4da; border-radius: 4px; font-size: .9rem; }
.filter-group input:focus, .filter-group select:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 2px rgba(0,123,255,.25); }
.filter-actions { display: flex; gap: .5rem; align-items: flex-end; }
.ledger-entry--debit { background-color: rgba(220,53,69,.03); }
.category-tag { background: #f8f9fa; padding: .25rem .5rem; border-radius: 4px; font-size: .8rem; border: 1px solid #dee2e6; }
.ledger-summary { margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 4px; border-top: 2px solid #dee2e6; }
.summary-row { display: flex; justify-content: space-between; margin-bottom: .5rem; }
.summary-row--total { border-top: 1px solid #dee2e6; padding-top: .5rem; margin-top: .5rem; }
.text-danger { color: #dc3545 !important; }
.empty-state { text-align: center; padding: 3rem 1rem; color: #6c757d; }
.empty-state__icon { font-size: 4rem; margin-bottom: 1rem; opacity: .5; }
/* Screen-only / print-only helpers */
.print-only { display: none; }
@media (max-width: 768px) {
    .filter-row { flex-direction: column; align-items: stretch; }
    .filter-group { min-width: auto; }
    .filter-actions { justify-content: center; margin-top: 1rem; }
}
@media print {
    /* ── Hide UI chrome ───────────────────────────────────────────── */
    .main-header, .sidebar, .mobile-overlay,
    .global-back-btn, .global-forward-btn,
    .no-print, .page-actions, .card__actions,
    #ledger-header { display: none !important; }

    /* ── Reset body layout to plain flow ─────────────────────────── */
    body, .main-content {
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        font-size: 11px;
    }
    #ledger-section {
        display: block !important;
        width: 100% !important;
    }

    /* ── Print header ─────────────────────────────────────────────── */
    #print-header, .print-only {
        display: block !important;
        page-break-after: avoid;
        margin-bottom: 12px;
    }

    /* ── Summary cards: flat row, never forced to new page ────────── */
    .dashboard-grid {
        display: flex !important;          /* override CSS Grid */
        flex-wrap: nowrap !important;
        gap: 8px !important;
        margin-bottom: 12px !important;
        page-break-inside: avoid;          /* keep 4 cards together */
        page-break-after: avoid;           /* no forced break after cards */
    }
    .kpi-card {
        flex: 1 1 0 !important;
        min-width: 0 !important;
        padding: 8px !important;
        box-shadow: none !important;
        border: 1px solid #d1d5db !important;
        border-radius: 4px !important;
        page-break-inside: avoid;
    }
    .kpi-card__icon { display: none !important; }  /* save space */
    .kpi-card__value { font-size: 13px !important; font-weight: 700; }
    .kpi-card__label { font-size: 10px !important; }
    .kpi-card__status { font-size: 9px !important; color: #6b7280; }

    /* ── Table card: allow natural page breaks ────────────────────── */
    .card {
        box-shadow: none !important;
        border: 1px solid #e5e7eb !important;
        page-break-inside: auto;           /* let it break across pages */
    }
    .card__header { page-break-after: avoid; }
    .table-responsive { overflow: visible !important; }

    /* ── Table: continuous flow ───────────────────────────────────── */
    table {
        font-size: 10px;
        width: 100%;
        border-collapse: collapse;
        page-break-inside: auto;
    }
    thead {
        display: table-header-group;       /* repeat header on every page */
        background: #f3f4f6 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    tfoot  { display: table-footer-group; }
    tbody  { display: table-row-group; }
    tr {
        page-break-inside: avoid;          /* don't split a single row */
        page-break-after: auto;            /* allow break between rows */
    }
    th, td {
        padding: 3px 5px !important;
        border: 1px solid #d1d5db !important;
        vertical-align: top;
    }
    th { font-weight: 700; }

    /* ── Summary footer ───────────────────────────────────────────── */
    .ledger-summary {
        page-break-inside: avoid;
        background: #f9fafb !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* ── Misc ─────────────────────────────────────────────────────── */
    .badge {
        border: 1px solid #9ca3af !important;
        background: transparent !important;
        font-size: 9px !important;
    }
    .ledger-entry--debit { background: transparent !important; }
    .category-tag {
        background: transparent !important;
        border: 1px solid #d1d5db !important;
        font-size: 9px !important;
    }
}
</style>

<script>
function printLedger() {
    const from = document.getElementById('fromDate').value;
    const to   = document.getElementById('toDate').value;
    const type = document.getElementById('transactionType').value;
    const projEl = document.getElementById('projectFilter');
    const proj = projEl ? projEl.options[projEl.selectedIndex].text : '';

    const parts = [];
    if (from) parts.push('From: ' + from);
    if (to)   parts.push('To: ' + to);
    if (type) parts.push('Type: ' + type.charAt(0).toUpperCase() + type.slice(1) + 's');
    if (proj && proj !== 'All Projects') parts.push('Project: ' + proj);

    document.getElementById('print-filter-summary').textContent =
        parts.length ? parts.join('  |  ') : 'All records — no filters applied';

    window.print();
}

function applyQuickFilter() {
    const range = document.getElementById('quickRange').value;
    const from  = document.getElementById('fromDate');
    const to    = document.getElementById('toDate');
    const today = new Date();
    let f, t;

    switch (range) {
        case 'today':
            f = t = today.toISOString().split('T')[0]; break;
        case 'this_week':
            const sow = new Date(today); sow.setDate(today.getDate() - today.getDay());
            f = sow.toISOString().split('T')[0]; t = today.toISOString().split('T')[0]; break;
        case 'this_month':
            f = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            t = today.toISOString().split('T')[0]; break;
        case 'last_month':
            const lms = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const lme = new Date(today.getFullYear(), today.getMonth(), 0);
            f = lms.toISOString().split('T')[0]; t = lme.toISOString().split('T')[0]; break;
        case 'this_quarter':
            const q = Math.floor(today.getMonth() / 3);
            f = new Date(today.getFullYear(), q * 3, 1).toISOString().split('T')[0];
            t = today.toISOString().split('T')[0]; break;
        case 'this_year':
            f = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
            t = today.toISOString().split('T')[0]; break;
        case 'all':   clearFilter(); return;
        case 'custom': return;
        default: return;
    }
    from.value = f; to.value = t;
    applyFilter();
}

function applyFilter() {
    const params = new URLSearchParams();
    const from = document.getElementById('fromDate').value;
    const to   = document.getElementById('toDate').value;
    const type = document.getElementById('transactionType').value;
    const proj = document.getElementById('projectFilter') ? document.getElementById('projectFilter').value : '';

    if (from) params.set('from_date', from);
    if (to)   params.set('to_date', to);
    if (type) params.set('transaction_type', type);
    if (proj) params.set('project_id', proj);

    window.location.href = '/ergon/owner/cash-ledger' + (params.toString() ? '?' + params.toString() : '');
}

function clearFilter() {
    window.location.href = '/ergon/owner/cash-ledger';
}

// Restore quick-filter dropdown label on load
document.addEventListener('DOMContentLoaded', function () {
    const from = document.getElementById('fromDate').value;
    const to   = document.getElementById('toDate').value;
    if (from || to) document.getElementById('quickRange').value = 'custom';
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
