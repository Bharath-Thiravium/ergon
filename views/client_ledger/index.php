<?php
$title = 'Customer Ledger';
$active_page = 'client_ledger';
ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-size:22px;font-weight:700;">🧾 Customer Ledger</h1>
        <p style="margin:4px 0 0;color:#6b7280;font-size:14px;">Track payments sent to and received from clients</p>
    </div>
    <div style="display:flex;gap:10px;">
        <button onclick="document.getElementById('addEntryModal').style.display='flex'" class="btn btn--primary" style="display:flex;align-items:center;gap:6px;">
            <i class="bi bi-plus-lg"></i> Add Entry
        </button>
        <button onclick="document.getElementById('addClientModal').style.display='flex'" class="btn btn--secondary" style="display:flex;align-items:center;gap:6px;">
            <i class="bi bi-person-plus"></i> New Client
        </button>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success" style="margin-bottom:16px;padding:12px 16px;border-radius:8px;background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;">
    <?= $_GET['success'] === 'client_created' ? '✅ Client created successfully.' : ($_GET['success'] === 'client_updated' ? '✅ Client updated successfully.' : '✅ Entry saved successfully.') ?>
</div>
<?php elseif (isset($_GET['error'])): ?>
<div class="alert alert--error" style="margin-bottom:16px;padding:12px 16px;border-radius:8px;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;">
    ❌ <?= htmlspecialchars($_GET['error'] === 'invalid_input' ? 'Invalid input. Please check all fields.' : $_GET['error']) ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card__body" style="padding:0;">
        <div class="table-responsive">
            <table class="table table--striped" style="margin:0;">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Company</th>
                        <th style="text-align:right;">Total Credits</th>
                        <th style="text-align:right;">Total Debits</th>
                        <th style="text-align:right;">Balance</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($clients)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#9ca3af;">No clients yet. Add your first client.</td></tr>
                <?php else: ?>
                    <?php foreach ($clients as $c): ?>
                    <tr>
                        <td>
                            <a href="/ergon/client-ledger/<?= $c['id'] ?>" style="font-weight:600;color:#1d4ed8;text-decoration:none;">
                                <?= htmlspecialchars($c['name']) ?>
                            </a>
                            <?php if ($c['email']): ?>
                            <div style="font-size:12px;color:#6b7280;"><?= htmlspecialchars($c['email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($c['company_name'] ?? '—') ?></td>
                        <td style="text-align:right;color:#059669;font-weight:600;">₹<?= number_format($c['total_credits'], 2) ?></td>
                        <td style="text-align:right;color:#dc2626;font-weight:600;">₹<?= number_format($c['total_debits'], 2) ?></td>
                        <td style="text-align:right;font-weight:700;color:<?= $c['current_balance'] >= 0 ? '#059669' : '#dc2626' ?>;">
                            <?= $c['current_balance'] < 0 ? '-' : '' ?>₹<?= number_format(abs($c['current_balance']), 2) ?>
                        </td>
                        <td style="text-align:center;">
                            <span style="padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600;
                                background:<?= $c['status'] === 'active' ? '#d1fae5' : '#f3f4f6' ?>;
                                color:<?= $c['status'] === 'active' ? '#065f46' : '#6b7280' ?>;">
                                <?= ucfirst($c['status']) ?>
                            </span>
                        </td>
<td style="text-align:center;">
                            <a href="/ergon/client-ledger/<?= $c['id'] ?>" class="btn btn--sm btn--primary">View Ledger</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Entry Modal -->
<div id="addEntryModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;margin:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;font-size:18px;font-weight:700;">Add Ledger Entry</h3>
            <button onclick="document.getElementById('addEntryModal').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#6b7280;line-height:1;">&times;</button>
        </div>
<form method="POST" action="/ergon/client-ledger/store" enctype="multipart/form-data">
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Client *</label>
                <select name="client_id" required style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;">
                    <option value="">Select client...</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?><?= $c['company_name'] ? ' — ' . htmlspecialchars($c['company_name']) : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Type *</label>
                <select name="entry_type" id="entryTypeSelectMain" required onchange="toggleAdjDirMain()" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;">
                    <option value="payment_received">Payment Received (Credit — money IN)</option>
                    <option value="payment_sent">Payment Sent (Debit — money OUT)</option>
                    <option value="adjustment">Adjustment</option>
                </select>
            </div>
            <div id="adjDirRowMain" style="display:none;margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Direction *</label>
                <select name="adjustment_direction" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;">
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
                <input type="text" name="reference_no" placeholder="Invoice / Cheque / UTR..."
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Attachment</label>
                <input type="file" name="attachment" accept="image/*,.pdf"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;background:#fff;">
                <div style="font-size:11px;color:#6b7280;margin-top:4px;">Allowed: JPG, PNG, GIF, WEBP, PDF (optional)</div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('addEntryModal').style.display='none'" class="btn btn--secondary">Cancel</button>
                <button type="submit" class="btn btn--primary">Save Entry</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Client Modal -->
<div id="addClientModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:420px;margin:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;font-size:18px;font-weight:700;">New Client</h3>
            <button onclick="document.getElementById('addClientModal').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#6b7280;line-height:1;">&times;</button>
        </div>
        <form method="POST" action="/ergon/client-ledger/create-client">
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Name *</label>
                <input type="text" name="name" required placeholder="Client name"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Company</label>
                <input type="text" name="company_name" placeholder="Company name"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Email</label>
                <input type="email" name="email" placeholder="email@example.com"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Phone</label>
                <input type="text" name="phone" placeholder="+91 XXXXX XXXXX"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('addClientModal').style.display='none'" class="btn btn--secondary">Cancel</button>
                <button type="submit" class="btn btn--primary">Create Client</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Client Modal -->
<div id="editClientModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:420px;margin:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;font-size:18px;font-weight:700;">Edit Client</h3>
            <button onclick="closeEditClientModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#6b7280;line-height:1;">&times;</button>
        </div>
        <form id="editClientForm" onsubmit="submitEditClient(event)">
            <input type="hidden" id="editClientId" name="client_id">
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Name *</label>
                <input type="text" id="editClientName" name="name" required placeholder="Client name"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Company</label>
                <input type="text" id="editClientCompany" name="company_name" placeholder="Company name"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Email</label>
                <input type="email" id="editClientEmail" name="email" placeholder="email@example.com"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Phone</label>
                <input type="text" id="editClientPhone" name="phone" placeholder="+91 XXXXX XXXXX"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;box-sizing:border-box;">
            </div>
            <div id="editClientErr" style="display:none;margin-bottom:12px;padding:10px 14px;border-radius:6px;background:#fee2e2;color:#991b1b;font-size:13px;"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="closeEditClientModal()" class="btn btn--secondary">Cancel</button>
                <button type="submit" id="editClientSubmitBtn" class="btn btn--primary">Update Client</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAdjDirMain() {
    const v = document.getElementById('entryTypeSelectMain').value;
    document.getElementById('adjDirRowMain').style.display = v === 'adjustment' ? 'block' : 'none';
}
['addEntryModal','addClientModal'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});

/* ── Edit Client Modal ────────────────────────────────────────── */
function openEditClientModal(clientId, name, companyName, email, phone) {
    document.getElementById('editClientId').value = clientId;
    document.getElementById('editClientName').value = name;
    document.getElementById('editClientCompany').value = companyName;
    document.getElementById('editClientEmail').value = email;
    document.getElementById('editClientPhone').value = phone;
    document.getElementById('editClientModal').style.display = 'flex';
}

function closeEditClientModal() {
    document.getElementById('editClientModal').style.display = 'none';
}

document.getElementById('editClientModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeEditClientModal();
});

function submitEditClient(e) {
    e.preventDefault();
    const form = document.getElementById('editClientForm');
    const formData = new FormData(form);
    const btn = document.getElementById('editClientSubmitBtn');
    const errDiv = document.getElementById('editClientErr');
    
    btn.disabled = true;
    btn.textContent = 'Saving...';
    if (errDiv) errDiv.style.display = 'none';
    
    fetch('/ergon/client-ledger/update-client', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/ergon/client-ledger?success=client_updated';
        } else {
            if (errDiv) { errDiv.textContent = data.error || 'Failed to update client'; errDiv.style.display = 'block'; }
            btn.disabled = false;
            btn.textContent = 'Update Client';
        }
    })
    .catch(() => {
        if (errDiv) { errDiv.textContent = 'Network error'; errDiv.style.display = 'block'; }
        btn.disabled = false;
        btn.textContent = 'Update Client';
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
