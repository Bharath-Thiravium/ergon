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
    ✅ <?= $_GET['success'] === 'updated' ? 'Entry updated successfully.' : ($_GET['success'] === 'deleted' ? 'Entry deleted successfully.' : 'Entry saved successfully.') ?>
</div>
<?php elseif (isset($_GET['error'])): ?>
<div id="flashMsg" style="margin-bottom:16px;padding:12px 16px;border-radius:8px;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;">
    ❌ <?= $_GET['error'] === 'duplicate_reference' ? 'That reference number already exists for this client.' : 'Failed to save entry. Please try again.' ?>
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
        <div class="table-responsive">
            <table class="table table--striped" style="margin:0;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Reference</th>
                        <th style="text-align:right;">Debit</th>
                        <th style="text-align:right;">Credit</th>
                        <th style="text-align:right;">Balance</th>
                        <th style="text-align:center;">Actions</th>
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
                        <td style="text-align:center;white-space:nowrap;">
                            <button onclick="openEditModal(<?= $e['id'] ?>)"
                                style="background:none;border:1px solid #d1d5db;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:12px;color:#374151;display:inline-flex;align-items:center;gap:4px;margin-right:4px;">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button onclick="confirmDelete(<?= $e['id'] ?>, '<?= date('d M Y', strtotime($e['transaction_date'])) ?>', '<?= addslashes(number_format($e['amount'], 2)) ?>')"
                                style="background:none;border:1px solid #fca5a5;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:12px;color:#dc2626;display:inline-flex;align-items:center;gap:4px;">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
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
        <form id="addEntryForm" method="POST" action="/ergon/client-ledger/store" onsubmit="return validateRefBeforeSubmit('addRefErr')">
            <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Type *</label>
                <select name="entry_type" id="addEntryType" required onchange="toggleAdjDir('add')"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;">
                    <option value="payment_received">Payment Received (Credit — money IN)</option>
                    <option value="payment_sent">Payment Sent (Debit — money OUT)</option>
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
                    ⚠️ This reference number already exists for this client.
                </div>
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



<script>
const CLIENT_ID = <?= (int)$client['id'] ?>;

/* ── modal helpers ─────────────────────────────────────────────── */
function openAddModal() {
    document.getElementById('addEntryModal').style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
['addEntryModal','editEntryModal'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});

/* ── adjustment direction toggle ───────────────────────────────── */
function toggleAdjDir(prefix) {
    const v = document.getElementById(prefix + 'EntryType').value;
    document.getElementById(prefix + 'AdjRow').style.display = v === 'adjustment' ? 'block' : 'none';
}

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
            ['adjustment',       'Adjustment'],
        ].map(([v, l]) => `<option value="${v}"${v === e.entry_type ? ' selected' : ''}>${l}</option>`).join('');

        const adjDisplay = e.entry_type === 'adjustment' ? 'block' : 'none';
        const adjCredit  = e.direction !== 'debit' ? 'selected' : '';
        const adjDebit   = e.direction === 'debit'  ? 'selected' : '';

        body.innerHTML = `
        <form id="editEntryForm"
              onsubmit="submitEdit(event, ${entryId})"
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
                    ⚠️ This reference number already exists for this client.
                </div>
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
    const body = new URLSearchParams(new FormData(form));

    fetch('/ergon/client-ledger/entry/' + entryId + '/update', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: body.toString()
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = window.location.pathname + '?success=updated';
        } else {
            const msg = data.error === 'duplicate_reference'
                ? '⚠️ That reference number already exists for this client.'
                : '❌ ' + (data.error || 'Failed to update. Please try again.');
            if (formErr) { formErr.textContent = msg; formErr.style.display = 'block'; }
            btn.disabled = false;
            btn.textContent = 'Update Entry';
        }
    })
    .catch(() => {
        if (formErr) { formErr.textContent = '❌ Network error. Please try again.'; formErr.style.display = 'block'; }
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

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
