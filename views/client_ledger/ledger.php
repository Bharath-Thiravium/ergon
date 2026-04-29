<?php
$title = 'Ledger: ' . htmlspecialchars($client['name']);
$active_page = 'client_ledger';
ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <a href="/ergon/client-ledger" style="font-size:13px;color:#6b7280;text-decoration:none;">
            ← All Clients
        </a>
        <h1 style="margin:4px 0 0;font-size:22px;font-weight:700;"><?= htmlspecialchars($client['name']) ?></h1>
        <?php if ($client['company_name']): ?>
        <p style="margin:2px 0 0;color:#6b7280;font-size:14px;"><?= htmlspecialchars($client['company_name']) ?></p>
        <?php endif; ?>
    </div>
    <button onclick="document.getElementById('addEntryModal').style.display='flex'" class="btn btn--primary" style="display:flex;align-items:center;gap:6px;">
        <i class="bi bi-plus-lg"></i> Add Entry
    </button>
</div>

<?php if (isset($_GET['success'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;border-radius:8px;background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;">✅ Entry saved successfully.</div>
<?php elseif (isset($_GET['error'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;border-radius:8px;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;">❌ Failed to save entry. Please try again.</div>
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
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($entries)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#9ca3af;">No entries yet. Add the first entry.</td></tr>
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
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:460px;max-height:90vh;overflow-y:auto;margin:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;font-size:18px;font-weight:700;">Add Entry — <?= htmlspecialchars($client['name']) ?></h3>
            <button onclick="document.getElementById('addEntryModal').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#6b7280;line-height:1;">&times;</button>
        </div>
        <form method="POST" action="/ergon/client-ledger/store">
            <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Type *</label>
                <select name="entry_type" id="entryTypeSelect" required onchange="toggleAdjDir()" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;">
                    <option value="payment_received">Payment Received (Credit — money IN)</option>
                    <option value="payment_sent">Payment Sent (Debit — money OUT)</option>
                    <option value="adjustment">Adjustment</option>
                </select>
            </div>
            <div id="adjDirRow" style="display:none;margin-bottom:14px;">
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
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('addEntryModal').style.display='none'" class="btn btn--secondary">Cancel</button>
                <button type="submit" class="btn btn--primary">Save Entry</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAdjDir() {
    const v = document.getElementById('entryTypeSelect').value;
    document.getElementById('adjDirRow').style.display = v === 'adjustment' ? 'block' : 'none';
}
document.getElementById('addEntryModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
