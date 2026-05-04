<?php
$title = 'Ledger: ' . htmlspecialchars($client['name']);
$active_page = 'client_ledger';
ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <a href="/ergon/client-ledger" style="font-size:13px;color:#6b7280;text-decoration:none;">← All Clients</a>
        <h1 style="margin:4px 0 0;font-size:22px;font-weight:700;"><?= htmlspecialchars($client['name']) ?></h1>
        <?php if ($client['company_name']): ?>
        <p style="margin:2px 0 0;color:#6b7280;font-size:14px;"><?= htmlspecialchars($client['company_name']) ?></p>
        <?php endif; ?>
    </div>
    <button onclick="openAddModal()" class="btn btn--primary" style="display:flex;align-items:center;gap:6px;">
        <i class="bi bi-plus-lg"></i> Add Entry
    </button>
</div>

<?php if (isset($_GET['success'])): ?>
<div id="flashMsg" style="margin-bottom:16px;padding:12px 16px;border-radius:8px;background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;">
Success: <?= $_GET['success'] === 'updated' ? 'Entry updated successfully.' : ($_GET['success'] === 'deleted' ? 'Entry deleted successfully.' : 'Entry saved successfully.') ?>
</div>
<?php elseif (isset($_GET['error'])): ?>
<div id="flashMsg" style="margin-bottom:16px;padding:12px 16px;border-radius:8px;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;">
Error: <?= $_GET['error'] === 'duplicate_reference' ? 'That reference number already exists for this client.' : 'Failed to save entry. Please try again.' ?>
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px;">
    <div class="card" style="padding:16px 20px;">
        <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Current Balance</div>
        <div style="font-size:24px;font-weight:800;margin-top:4px;color:<?= $currentBalance >= 0 ? '#059669' : '#dc2626' ?>;">
            <?= $currentBalance < 0 ? '-' : '' ?>₹<?= number_format(abs($currentBalance), 2) ?>
        </div>
    </div>
    <div class="card" style="padding:16px 20px;">
        <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Total Credits</div>
        <div style="font-size:22px;font-weight:700;margin-top:4px;color:#059669;">₹<?= number_format($totalCredits, 2) ?></div>
    </div>
    <div class="card" style="padding:16px 20px;">
        <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Total Debits</div>
        <div style="font-size:22px;font-weight:700;margin-top:4px;color:#dc2626;">₹<?= number_format($totalDebits, 2) ?></div>
    </div>
    <div class="card" style="padding:16px 20px;">
        <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Entries</div>
        <div style="font-size:22px;font-weight:700;margin-top:4px;"><?= count($entries) ?></div>
    </div>
</div>

<!-- Ledger Table -->
<div class="card">
    <div class="card__body" style="padding:0;">
        <!-- Desktop Table View -->
        <div class="table-responsive desktop-view">
            <table class="table table--striped" style="margin:0;">
                <thead>
                    <tr>
                        <th class="th-sort" data-col="0" data-type="str">Date <span class="sort-icon">&#8597;</span></th>
                        <th class="th-sort" data-col="1" data-type="str">Type <span class="sort-icon">&#8597;</span></th>
                        <th class="th-sort" data-col="2" data-type="str">Description <span class="sort-icon">&#8597;</span></th>
                        <th class="th-sort" data-col="3" data-type="str">Reference <span class="sort-icon">&#8597;</span></th>
                        <th class="th-sort" data-col="4" data-type="num" style="text-align:right;">Debit <span class="sort-icon">&#8597;</span></th>
                        <th class="th-sort" data-col="5" data-type="num" style="text-align:right;">Credit <span class="sort-icon">&#8597;</span></th>
                        <th class="th-sort" data-col="6" data-type="num" style="text-align:right;">Balance <span class="sort-icon">&#8597;</span></th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
<tr id="filterRow">
                        <td><input class="col-filter" data-col="0" placeholder="Date..." /></td>
                        <td><input class="col-filter" data-col="1" placeholder="Type..." /></td>
                        <td><input class="col-filter" data-col="2" placeholder="Description..." /></td>
                        <td><input class="col-filter" data-col="3" placeholder="Reference..." /></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>

                </thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:#9ca3af;">No entries yet. Add the first entry.</td></tr>
                    <?php else: ?>
                        <?php foreach ($entries as $e): ?>
                        <?php
                        $typeMap = [
                            'payment_received' => ['Payment Received', '#d1fae5', '#065f46'],
                            'payment_sent'     => ['Payment Sent',     '#fee2e2', '#991b1b'],
                            'adjustment'       => ['Adjustment',        '#fef3c7', '#92400e'],
                            'invoice_raised'   => ['Invoice Raised',    '#dbeafe', '#1e40af'],
                            'invoice_received' => ['Invoice Received',  '#e0e7ff', '#3730a3'],
                            'purchase'         => ['Purchase',          '#fce7f3', '#be185d'],
                            'sale'            => ['Sale',              '#dcfce7', '#166534'],
                            'expense'         => ['Expense',           '#fed7d7', '#b91c1c'],
                            'income'          => ['Income',            '#d1fae5', '#047857'],
                            'opening_balance' => ['Opening Balance',   '#f3e8ff', '#7c3aed'],
                            'closing_balance' => ['Closing Balance',   '#fef3c7', '#a16207'],
                            'fees_paid'       => ['Fees Paid',         '#fef2f2', '#dc2626'],
                            'penalties_paid'  => ['Penalties Paid',    '#fef2f2', '#b91c1c'],
                        ];
                        [$typeLabel, $typeBg, $typeColor] = $typeMap[$e['entry_type']] ?? [$e['entry_type'], '#f3f4f6', '#374151'];
                        ?>
                        <tr>
                            <td style="white-space:nowrap;"><?= date('d M Y', strtotime($e['transaction_date'])) ?></td>
                            <td>
                                <span style="padding:2px 8px;border-radius:10px;font-size:12px;font-weight:600;background:<?= $typeBg ?>;color:<?= $typeColor ?>;">
                                    <?= $typeLabel ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($e['description'] ?? '—') ?></td>
                            <td style="color:#6b7280;font-size:13px;"><?= htmlspecialchars($e['reference_no'] ?? '—') ?></td>
                            <td style="text-align:right;color:#dc2626;font-weight:600;">
                                <?= $e['direction'] === 'debit' ? '₹' . number_format($e['amount'], 2) : '—' ?>
                            </td>
                            <td style="text-align:right;color:#059669;font-weight:600;">
                                <?= $e['direction'] === 'credit' ? '₹' . number_format($e['amount'], 2) : '—' ?>
                            </td>
                            <td style="text-align:right;font-weight:700;color:<?= $e['balance_after'] >= 0 ? '#059669' : '#dc2626' ?>;">
                                <?= $e['balance_after'] < 0 ? '-' : '' ?>₹<?= number_format(abs($e['balance_after']), 2) ?>
                            </td>
                            <td style="text-align:center;">
                                <div class="ab-container">
                                    <button class="ab-btn ab-btn--view" onclick="openViewModal(<?= $e['id'] ?>)" data-tooltip="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    <button class="ab-btn ab-btn--edit" onclick="openEditModal(<?= $e['id'] ?>)" data-tooltip="Edit Entry">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="M15 5l4 4"/></svg>
                                    </button>
                                    <button class="ab-btn ab-btn--delete" onclick="confirmDelete(<?= $e['id'] ?>, '<?= date('d M Y', strtotime($e['transaction_date'])) ?>', '<?= number_format($e['amount'], 2) ?>')" data-tooltip="Delete Entry">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Mobile Card View -->
        <div class="mobile-card-view" style="display:none;padding:16px;">
            <?php if (empty($entries)): ?>
                <div style="text-align:center;padding:40px;color:#9ca3af;">No entries yet. Add the first entry.</div>
            <?php else: ?>
                <?php foreach ($entries as $e): ?>
                <?php
                $typeMap = [
                    'payment_received' => ['Payment Received', '#d1fae5', '#065f46'],
                    'payment_sent'     => ['Payment Sent',     '#fee2e2', '#991b1b'],
                    'adjustment'       => ['Adjustment',        '#fef3c7', '#92400e'],
                    'invoice_raised'   => ['Invoice Raised',    '#dbeafe', '#1e40af'],
                    'invoice_received' => ['Invoice Received',  '#e0e7ff', '#3730a3'],
                    'purchase'         => ['Purchase',          '#fce7f3', '#be185d'],
                    'sale'            => ['Sale',              '#dcfce7', '#166534'],
                    'expense'         => ['Expense',           '#fed7d7', '#b91c1c'],
                    'income'          => ['Income',            '#d1fae5', '#047857'],
                    'opening_balance' => ['Opening Balance',   '#f3e8ff', '#7c3aed'],
                    'closing_balance' => ['Closing Balance',   '#fef3c7', '#a16207'],
                    'fees_paid'       => ['Fees Paid',         '#fef2f2', '#dc2626'],
                    'penalties_paid'  => ['Penalties Paid',    '#fef2f2', '#b91c1c'],
                ];
                [$typeLabel, $typeBg, $typeColor] = $typeMap[$e['entry_type']] ?? [$e['entry_type'], '#f3f4f6', '#374151'];
                ?>
                <div class="task-card">
                    <div class="task-card__header">
                        <div>
                            <span class="task-card__priority badge badge--<?= in_array($e['entry_type'], ['payment_received', 'income']) ? 'success' : (in_array($e['entry_type'], ['payment_sent', 'purchase', 'sale', 'expense', 'fees_paid', 'penalties_paid']) ? 'danger' : 'warning') ?>" style="background:<?= $typeBg ?>;color:<?= $typeColor ?>;">
                                <?= $typeLabel ?>
                            </span>
                        </div>
                        <span style="font-size:14px;color:#6b7280;font-weight:500;"><?= date('d M Y', strtotime($e['transaction_date'])) ?></span>
                    </div>
                    
                    <div style="font-size:18px;font-weight:700;margin:8px 0;color:<?= $e['direction'] === 'credit' ? '#059669' : '#dc2626' ?>;">
                        <?= $e['direction'] === 'credit' ? '+' : '-' ?>₹<?= number_format($e['amount'], 2) ?>
                    </div>
                    
                    <div class="task-card__meta">
                        <div class="task-card__field">
                            <span class="task-card__label">Description</span>
                            <span class="task-card__value"><?= htmlspecialchars($e['description'] ?? '—') ?></span>
                        </div>
                        <div class="task-card__field">
                            <span class="task-card__label">Reference</span>
                            <span class="task-card__value"><?= htmlspecialchars($e['reference_no'] ?? '—') ?></span>
                        </div>
                        <div class="task-card__field">
                            <span class="task-card__label">Balance After</span>
                            <span class="task-card__value" style="font-weight:700;color:<?= $e['balance_after'] >= 0 ? '#059669' : '#dc2626' ?>;">
                                <?= $e['balance_after'] < 0 ? '-' : '' ?>₹<?= number_format(abs($e['balance_after']), 2) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="task-card__actions">
                        <button class="ab-btn ab-btn--view" onclick="openViewModal(<?= $e['id'] ?>)" data-tooltip="View Details">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                        <button class="ab-btn ab-btn--edit" onclick="openEditModal(<?= $e['id'] ?>)" data-tooltip="Edit Entry">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="M15 5l4 4"/></svg>
                        </button>
                        <button class="ab-btn ab-btn--delete" onclick="confirmDelete(<?= $e['id'] ?>, '<?= date('d M Y', strtotime($e['transaction_date'])) ?>', '<?= number_format($e['amount'], 2) ?>')" data-tooltip="Delete Entry">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── ADD ENTRY MODAL ─────────────────────────────────────────── -->
<div id="addEntryModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:460px;max-height:90vh;overflow-y:auto;margin:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;font-size:18px;font-weight:700;">Add Entry — <?= htmlspecialchars($client['name']) ?></h3>
            <button onclick="closeModal('addEntryModal')" style="background:none;border:none;font-size:22px;cursor:pointer;color:#6b7280;line-height:1;">&times;</button>
        </div>
<form id="addEntryForm" method="POST" action="/ergon/client-ledger/store" enctype="multipart/form-data" onsubmit="return validateRefBeforeSubmit('addRefErr')">
            <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Type *</label>
                <select name="entry_type" id="addEntryType" required onchange="toggleAdjDir('add')"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;">
                    <option value="payment_received">Payment Received (Credit — money IN)</option>
                    <option value="payment_sent">Payment Sent (Debit — money OUT)</option>
                    <option value="invoice_raised">Invoice Raised (Debit — bill sent to client)</option>
                    <option value="invoice_received">Invoice Received (Credit — bill from supplier)</option>
                    <option value="purchase">Purchase (Debit — goods/services bought)</option>
                    <option value="sale">Sale (Debit — goods/services sold)</option>
                    <option value="expense">Expense (Debit — business expense)</option>
                    <option value="income">Income (Credit — business income)</option>
                    <option value="opening_balance">Opening Balance</option>
                    <option value="closing_balance">Closing Balance</option>
                    <option value="fees_paid">Fees Paid (Debit — fees/charges paid)</option>
                    <option value="penalties_paid">Penalties Paid (Debit — penalties/fines paid)</option>
                    <option value="adjustment">Adjustment</option>
                </select>
            </div>
            <div id="addAdjRow" style="display:none;margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Direction *</label>
                <select name="adjustment_direction"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;">
                    <option value="credit">Credit (increase balance)</option>
                    <option value="debit">Debit (decrease balance)</option>
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Amount *</label>
                <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Transaction Date *</label>
                <input type="date" name="transaction_date" required value="<?= date('Y-m-d') ?>"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Description</label>
                <textarea name="description" rows="2" placeholder="Optional note..."
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;resize:vertical;"></textarea>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Reference No.</label>
                <input type="text" name="reference_no" id="addRefNo"
                    placeholder="Invoice / Cheque / UTR..."
                    oninput="checkRefDuplicate('add', 0)"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
    <div id="addRefErr" style="display:none;margin-top:4px;font-size:12px;color:#dc2626;font-weight:600;">
                    Warning: This reference number already exists for this client.
                </div>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Attachment</label>
                <input type="file" name="attachment" accept="image/*,.pdf"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;background:#fff;">
                <div style="font-size:11px;color:#6b7280;margin-top:4px;">Allowed: JPG, PNG, GIF, WEBP, PDF (optional)</div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="closeModal('addEntryModal')" class="btn btn--secondary">Cancel</button>
                <button type="submit" class="btn btn--primary">Save Entry</button>
            </div>
        </form>
    </div>
</div>

<!-- ── EDIT ENTRY MODAL ────────────────────────────────────────── -->
<div id="editEntryModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:460px;max-height:90vh;overflow-y:auto;margin:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;font-size:18px;font-weight:700;">Edit Entry</h3>
            <button onclick="closeModal('editEntryModal')" style="background:none;border:none;font-size:22px;cursor:pointer;color:#6b7280;line-height:1;">&times;</button>
        </div>
        <div id="editModalBody" style="text-align:center;padding:24px;color:#6b7280;">Loading…</div>
    </div>
</div>

<!-- ── VIEW ENTRY MODAL ────────────────────────────────────────── -->
<div id="viewEntryModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;margin:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;font-size:18px;font-weight:700;">Entry Details</h3>
            <button onclick="closeModal('viewEntryModal')" style="background:none;border:none;font-size:22px;cursor:pointer;color:#6b7280;line-height:1;">&times;</button>
        </div>
        <div id="viewModalBody" style="text-align:center;padding:24px;color:#6b7280;">Loading…</div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
            <button type="button" onclick="deleteFromView()" id="viewDeleteBtn" class="btn btn--danger" style="background:#fee2e2;color:#991b1b;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;margin-right:auto;">Delete</button>
            <button type="button" onclick="closeModal('viewEntryModal')" class="btn btn--secondary" style="background:none;border:1px solid #d1d5db;padding:8px 16px;border-radius:6px;cursor:pointer;">Close</button>
            <button type="button" onclick="editFromView()" id="viewEditBtn" class="btn btn--primary" style="background:#1d4ed8;color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;">Edit</button>
        </div>
    </div>
</div>



<script>
const CLIENT_ID = <?= (int)$client['id'] ?>;

/* ── modal helpers ─────────────────────────────────────────────── */
function openAddModal() {
    document.getElementById('addEntryModal').style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
['addEntryModal','editEntryModal','viewEntryModal'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});

/* ── adjustment direction toggle ───────────────────────────────── */
function toggleAdjDir(prefix) {
    const v = document.getElementById(prefix + 'EntryType').value;
    const needsDirection = ['adjustment', 'opening_balance', 'closing_balance'];
    document.getElementById(prefix + 'AdjRow').style.display = needsDirection.includes(v) ? 'block' : 'none';
}

// ── Sort & Filter ───────────────────────────────────────────────
(function() {
    const tbody = document.querySelector('table.table tbody');
    if (!tbody) return;
    let sortCol = -1, sortAsc = true;

    document.querySelectorAll('.th-sort').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            const col = +this.dataset.col;
            const type = this.dataset.type;
            sortAsc = (sortCol === col) ? !sortAsc : true;
            sortCol = col;
            document.querySelectorAll('.th-sort .sort-icon').forEach(i => i.textContent = '\u21C5');
            this.querySelector('.sort-icon').textContent = sortAsc ? '\u2191' : '\u2193';
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort((a, b) => {
                const av = a.cells[col]?.textContent.trim() || '';
                const bv = b.cells[col]?.textContent.trim() || '';
                const cmp = type === 'num'
                    ? (parseFloat(av.replace(/[^0-9.-]/g,'')) || 0) - (parseFloat(bv.replace(/[^0-9.-]/g,'')) || 0)
                    : av.localeCompare(bv);
                return sortAsc ? cmp : -cmp;
            });
            rows.forEach(r => tbody.appendChild(r));
        });
    });

    document.querySelectorAll('.col-filter').forEach(input => {
        input.addEventListener('input', applyFilters);
        input.addEventListener('click', e => e.stopPropagation());
    });

    function applyFilters() {
        const filters = Array.from(document.querySelectorAll('.col-filter')).map(f => ({
            col: +f.dataset.col, val: f.value.toLowerCase()
        }));
        Array.from(tbody.querySelectorAll('tr')).forEach(row => {
            const show = filters.every(f => !f.val || (row.cells[f.col]?.textContent.toLowerCase().includes(f.val)));
            row.style.display = show ? '' : 'none';
        });
    }
})();

/* ── reference duplicate check (debounced) ─────────────────────── */
const _refTimers = {};
function checkRefDuplicate(prefix, excludeId) {
    clearTimeout(_refTimers[prefix]);
    _refTimers[prefix] = setTimeout(function() {
        const val = (document.getElementById(prefix + 'RefNo') || {}).value || '';
        const err = document.getElementById(prefix + 'RefErr');
        if (!val.trim()) { if (err) err.style.display = 'none'; return; }

        const params = new URLSearchParams({ client_id: CLIENT_ID, reference_no: val.trim() });
        if (excludeId) params.set('exclude_id', excludeId);

        fetch('/ergon/client-ledger/check-reference?' + params.toString(), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (err) err.style.display = data.duplicate ? 'block' : 'none';
            const refInput = document.getElementById(prefix + 'RefNo');
            if (refInput) refInput.style.borderColor = data.duplicate ? '#dc2626' : '#d1d5db';
        })
        .catch(() => {});
    }, 400);
}

/* ── block submit if duplicate ref is showing ──────────────────── */
function validateRefBeforeSubmit(errDivId) {
    const err = document.getElementById(errDivId);
    if (err && err.style.display === 'block') {
        err.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }
    return true;
}

/* ── open edit modal: fetch entry data then render form ─────────── */
function openEditModal(entryId) {
    const modal = document.getElementById('editEntryModal');
    const body  = document.getElementById('editModalBody');
    body.innerHTML = '<div style="text-align:center;padding:24px;color:#6b7280;">Loading…</div>';
    modal.style.display = 'flex';

    fetch('/ergon/client-ledger/entry/' + entryId, {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { body.innerHTML = '<p style="color:#dc2626;">Failed to load entry.</p>'; return; }
        const e = data.entry;

        const typeOpts = [
            ['payment_received', 'Payment Received (Credit — money IN)'],
            ['payment_sent',     'Payment Sent (Debit — money OUT)'],
            ['invoice_raised',   'Invoice Raised (Debit — bill sent to client)'],
            ['invoice_received', 'Invoice Received (Credit — bill from supplier)'],
            ['purchase',         'Purchase (Debit — goods/services bought)'],
            ['sale',            'Sale (Debit — goods/services sold)'],
            ['expense',         'Expense (Debit — business expense)'],
            ['income',          'Income (Credit — business income)'],
            ['fees_paid',       'Fees Paid (Debit — fees/charges paid)'],
            ['penalties_paid',  'Penalties Paid (Debit — penalties/fines paid)'],
            ['opening_balance', 'Opening Balance'],
            ['closing_balance', 'Closing Balance'],
            ['adjustment',       'Adjustment'],
        ].map(([v, l]) => `<option value="${v}"${v === e.entry_type ? ' selected' : ''}>${l}</option>`).join('');

        const adjDisplay = ['adjustment', 'opening_balance', 'closing_balance'].includes(e.entry_type) ? 'block' : 'none';
        const adjCredit  = e.direction !== 'debit' ? 'selected' : '';
        const adjDebit   = e.direction === 'debit'  ? 'selected' : '';

const existingAttachment = e.attachment ? `<a href="/ergon/client-ledger/view/${encodeURIComponent(e.attachment)}" target="_blank" style="display:inline-flex;align-items:center;gap:4px;padding:6px 10px;background:#d1fae5;color:#065f46;border-radius:6px;font-size:12px;text-decoration:none;"><i class="bi bi-paperclip"></i> View Current</a>` : '<span style="color:#9ca3af;font-size:12px;">No attachment</span>';
        body.innerHTML = `
        <form id="editEntryForm"
              onsubmit="submitEdit(event, ${entryId})"
              enctype="multipart/form-data"
              style="text-align:left;">
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Type *</label>
                <select name="entry_type" id="editEntryType" required onchange="toggleAdjDir('edit')"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;">${typeOpts}</select>
            </div>
            <div id="editAdjRow" style="display:${adjDisplay};margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Direction *</label>
                <select name="adjustment_direction"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;">
                    <option value="credit" ${adjCredit}>Credit (increase balance)</option>
                    <option value="debit"  ${adjDebit}>Debit (decrease balance)</option>
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Amount *</label>
                <input type="number" name="amount" step="0.01" min="0.01" required value="${e.amount}"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Transaction Date *</label>
                <input type="date" name="transaction_date" required value="${e.transaction_date}"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Description</label>
                <textarea name="description" rows="2" placeholder="Optional note..."
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;resize:vertical;">${escHtml(e.description || '')}</textarea>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Reference No.</label>
                <input type="text" name="reference_no" id="editRefNo" value="${escHtml(e.reference_no || '')}"
                    placeholder="Invoice / Cheque / UTR..."
                    oninput="checkRefDuplicate('edit', ${entryId})"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
                <div id="editRefErr" style="display:none;margin-top:4px;font-size:12px;color:#dc2626;font-weight:600;">
                    Warning: This reference number already exists for this client.
                </div>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Attachment <span style="font-weight:400;color:#6b7280;">(optional)</span></label>
                <div style="margin-bottom:8px;">${existingAttachment}</div>
                <input type="file" name="attachment" accept="image/*,.pdf"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;background:#fff;">
                <div style="font-size:11px;color:#6b7280;margin-top:4px;">Allowed: JPG, PNG, GIF, WEBP, PDF — Leave empty to keep existing</div>
            </div>
            <div id="editFormErr" style="display:none;margin-bottom:12px;padding:10px 14px;border-radius:6px;background:#fee2e2;color:#991b1b;font-size:13px;"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="closeModal('editEntryModal')" class="btn btn--secondary">Cancel</button>
                <button type="submit" id="editSubmitBtn" class="btn btn--primary">Update Entry</button>
            </div>
        </form>`;
    })
    .catch(() => { body.innerHTML = '<p style="color:#dc2626;">Network error. Please try again.</p>'; });
}

function number_format(num) {
    return Number(num).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/* ── view modal actions ─────────────────────────────────────────── */
let _currentViewEntryId = null;
function openViewModal(entryId) {
    _currentViewEntryId = entryId;
    const modal = document.getElementById('viewEntryModal');
    const body  = document.getElementById('viewModalBody');
    body.innerHTML = '<div style="text-align:center;padding:24px;color:#6b7280;">Loading…</div>';
    modal.style.display = 'flex';

    fetch('/ergon/client-ledger/entry/' + entryId, {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { body.innerHTML = '<p style="color:#dc2626;">Failed to load entry.</p>'; return; }
        const e = data.entry;

        const typeLabels = {
            'payment_received': 'Payment Received',
            'payment_sent': 'Payment Sent',
            'adjustment': 'Adjustment',
            'invoice_raised': 'Invoice Raised',
            'invoice_received': 'Invoice Received',
            'purchase': 'Purchase',
            'sale': 'Sale',
            'expense': 'Expense',
            'income': 'Income',
            'opening_balance': 'Opening Balance',
            'closing_balance': 'Closing Balance',
            'fees_paid': 'Fees Paid',
            'penalties_paid': 'Penalties Paid'
        };
        const typeLabel = typeLabels[e.entry_type] || e.entry_type;
        
        const typeColors = {
            'payment_received': { bg: '#d1fae5', color: '#065f46' },
            'payment_sent': { bg: '#fee2e2', color: '#991b1b' },
            'adjustment': { bg: '#fef3c7', color: '#92400e' },
            'invoice_raised': { bg: '#dbeafe', color: '#1e40af' },
            'invoice_received': { bg: '#e0e7ff', color: '#3730a3' },
            'purchase': { bg: '#fce7f3', color: '#be185d' },
            'sale': { bg: '#dcfce7', color: '#166534' },
            'expense': { bg: '#fed7d7', color: '#b91c1c' },
            'income': { bg: '#d1fae5', color: '#047857' },
            'opening_balance': { bg: '#f3e8ff', color: '#7c3aed' },
            'closing_balance': { bg: '#fef3c7', color: '#a16207' },
            'fees_paid': { bg: '#fef2f2', color: '#dc2626' },
            'penalties_paid': { bg: '#fef2f2', color: '#b91c1c' }
        };
        const typeStyle = typeColors[e.entry_type] || { bg: '#f3f4f6', color: '#374151' };
        
        const directionLabel = e.direction === 'credit' ? 'Credit' : (e.direction === 'debit' ? 'Debit' : '-');
        const amountDisplay = e.direction === 'debit' 
            ? '<span style="color:#dc2626;font-weight:600;">₹' + number_format(e.amount, 2) + '</span>'
            : '<span style="color:#059669;font-weight:600;">₹' + number_format(e.amount, 2) + '</span>';
        
        const attachmentLink = e.attachment 
            ? '<a href="/ergon/client-ledger/view/' + encodeURIComponent(e.attachment) + '" target="_blank" style="display:inline-flex;align-items:center;gap:4px;padding:6px 10px;background:#d1fae5;color:#065f46;border-radius:6px;font-size:12px;text-decoration:none;"><i class="bi bi-paperclip"></i> View Attachment</a>'
            : '<span style="color:#9ca3af;font-size:13px;">None</span>';

        _currentViewEntryId = entryId;
        body.innerHTML = `
            <div style="text-align:left;font-size:14px;line-height:1.6;">
                <div style="margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid #e5e7eb;">
                    <span style="padding:4px 12px;border-radius:12px;font-size:12px;font-weight:600;background:${typeStyle.bg};color:${typeStyle.color};">
                        ${typeLabel}
                    </span>
                    <span style="margin-left:8px;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;">(${directionLabel})</span>
                </div>
                <div style="display:grid;grid-template-columns:120px 1fr;gap:8px;margin-bottom:12px;">
                    <div style="font-weight:600;color:#6b7280;">Amount</div>
                    <div style="font-size:18px;font-weight:700;">${amountDisplay}</div>
                </div>
                <div style="display:grid;grid-template-columns:120px 1fr;gap:8px;margin-bottom:12px;">
                    <div style="font-weight:600;color:#6b7280;">Date</div>
                    <div>${formatDate(e.transaction_date)}</div>
                </div>
                <div style="display:grid;grid-template-columns:120px 1fr;gap:8px;margin-bottom:12px;">
                    <div style="font-weight:600;color:#6b7280;">Reference No.</div>
                    <div>${e.reference_no || '<span style="color:#9ca3af;">—</span>'}</div>
                </div>
                <div style="display:grid;grid-template-columns:120px 1fr;gap:8px;margin-bottom:12px;">
                    <div style="font-weight:600;color:#6b7280;">Description</div>
                    <div>${e.description || '<span style="color:#9ca3af;">—</span>'}</div>
                </div>
                <div style="display:grid;grid-template-columns:120px 1fr;gap:8px;margin-bottom:12px;">
                    <div style="font-weight:600;color:#6b7280;">Attachment</div>
                    <div>${attachmentLink}</div>
                </div>
                <div style="display:grid;grid-template-columns:120px 1fr;gap:8px;">
                    <div style="font-weight:600;color:#6b7280;">Balance After</div>
                    <div style="font-weight:700;color:${e.balance_after >= 0 ? '#059669' : '#dc2626'};">
                        ${e.balance_after < 0 ? '-' : ''}₹${number_format(Math.abs(e.balance_after), 2)}
                    </div>
                </div>
            </div>`;
    })
    .catch(() => { body.innerHTML = '<p style="color:#dc2626;">Network error. Please try again.</p>'; });
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
}

function editFromView() {
    if (!_currentViewEntryId) return;
    closeModal('viewEntryModal');
    openEditModal(_currentViewEntryId);
}

function deleteFromView() {
    if (!_currentViewEntryId) return;
    if (!confirm('Delete this entry?\n\nThis will recalculate all subsequent balances.')) return;
    closeModal('viewEntryModal');
    deleteEntry(_currentViewEntryId);
}

/* ── submit edit via fetch, reload on success ───────────────────── */
function submitEdit(e, entryId) {
    e.preventDefault();

    // Block if duplicate ref is visible
    const refErr = document.getElementById('editRefErr');
    if (refErr && refErr.style.display === 'block') {
        refErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    const btn     = document.getElementById('editSubmitBtn');
    const formErr = document.getElementById('editFormErr');
    btn.disabled  = true;
    btn.textContent = 'Saving…';
    if (formErr) formErr.style.display = 'none';

    const form = document.getElementById('editEntryForm');
    const formData = new FormData(form);

    fetch('/ergon/client-ledger/entry/' + entryId + '/update', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = window.location.pathname + '?success=updated';
        } else {
            const msg = data.error === 'duplicate_reference'
                ? '⚠️ That reference number already exists for this client.'
                : '❌ ' + (data.error || 'Failed to update. Please try again.');
            if (formErr) { formErr.textContent = 'Error: ' + (data.error || 'Failed to update. Please try again.'); formErr.style.display = 'block'; }
            btn.disabled = false;
            btn.textContent = 'Update Entry';
        }
    })
    .catch(() => {
        if (formErr) { formErr.textContent = 'Error: Network error. Please try again.'; formErr.style.display = 'block'; }
        btn.disabled = false;
        btn.textContent = 'Update Entry';
    });
}

/* ── delete entry ───────────────────────────────────────────────── */
function confirmDelete(entryId, date, amount) {
    if (!confirm('Delete entry of ₹' + amount + ' on ' + date + '?\n\nThis will recalculate all subsequent balances.')) return;
    deleteEntry(entryId);
}

function deleteEntry(entryId) {
    fetch('/ergon/client-ledger/entry/' + entryId + '/delete', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = window.location.pathname + '?success=deleted';
        } else {
            alert('❌ Failed to delete entry. Please try again.');
        }
    })
    .catch(() => alert('❌ Network error. Please try again.'));
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Auto-dismiss flash message after 4 s
const flash = document.getElementById('flashMsg');
if (flash) setTimeout(() => flash.style.display = 'none', 4000);
</script>

<style>
.th-sort { cursor:pointer; user-select:none; white-space:nowrap; }
.th-sort:hover { background:#f1f5f9; }
.th-sort .sort-icon { font-size:11px; color:#9ca3af; margin-left:4px; }
#filterRow td { padding:4px 8px; background:#f8fafc; }
#filterRow input { width:100%; padding:4px 6px; font-size:12px; border:1px solid #d1d5db; border-radius:4px; box-sizing:border-box; }

@media (max-width: 768px) {
    .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .table { min-width: 700px; }
    .ab-container { gap: 2px; }
    .desktop-view { display: none !important; }
    .mobile-card-view { display: block !important; }
}
@media (min-width: 769px) {
    .desktop-view { display: block !important; }
    .mobile-card-view { display: none !important; }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
