<?php
$title = 'Measurement Sheet';
$active_page = 'measurement_sheet';
ob_start();
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div>
        <h2 style="margin:0;font-size:22px;font-weight:700;">Measurement Sheet</h2>
        <p style="margin:4px 0 0;color:#6b7280;font-size:14px;">Select a PO to raise an RA Bill</p>
    </div>
    <div style="display:flex;gap:12px;">
        <a href="/ergon/finance/measurement-sheet/manage" style="padding:10px 20px;background:#6b7280;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;">
            📋 Manage RA Bills
        </a>
    </div>
</div>

<?php if (!empty($error)): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['imported'])): ?>
<div style="background:#ecfdf5;border:1px solid #6ee7b7;color:#065f46;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
    ✅ Opening balance imported successfully. Future RA bills will now show correct previous claimed amounts.
</div>
<?php endif; ?>

<div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);overflow:hidden;">
    <div style="padding:14px 20px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;gap:12px;">
        <input type="text" id="poSearch" placeholder="Search PO, customer, company..."
               style="padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;width:300px;"
               oninput="filterTable()">
        <span style="color:#6b7280;font-size:13px;" id="poCount"><?= count($purchase_orders) ?> POs</span>
    </div>
    <div class="table-responsive">
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <thead>
                <tr style="background:#f9fafb;border-bottom:2px solid #e5e7eb;">
                    <th style="padding:11px 16px;text-align:left;font-weight:600;color:#374151;">#</th>
                    <th style="padding:11px 16px;text-align:left;font-weight:600;color:#374151;">PO Number</th>
                    <th style="padding:11px 16px;text-align:left;font-weight:600;color:#374151;">Company</th>
                    <th style="padding:11px 16px;text-align:left;font-weight:600;color:#374151;">Customer</th>
                    <th style="padding:11px 16px;text-align:left;font-weight:600;color:#374151;">PO Date</th>
                    <th style="padding:11px 16px;text-align:right;font-weight:600;color:#374151;">PO Value</th>
                    <th style="padding:11px 16px;text-align:center;font-weight:600;color:#374151;">Status</th>
                    <th style="padding:11px 16px;text-align:center;font-weight:600;color:#374151;">RA Bills</th>
                    <th style="padding:11px 16px;text-align:center;font-weight:600;color:#374151;">Action</th>
                </tr>
            </thead>
            <tbody id="poTableBody">
            <?php if (empty($purchase_orders)): ?>
                <tr><td colspan="9" style="padding:40px;text-align:center;color:#9ca3af;">No purchase orders found.</td></tr>
            <?php else: ?>
                <?php foreach ($purchase_orders as $i => $po):
                    $status = strtolower($po['status'] ?? 'active');
                    $sc = ['active'=>['#ecfdf5','#059669'],'partially_completed'=>['#fffbeb','#d97706'],
                           'completed'=>['#eff6ff','#2563eb'],'cancelled'=>['#fef2f2','#dc2626']][$status]
                          ?? ['#f3f4f6','#6b7280'];
                ?>
                <tr style="border-bottom:1px solid #f3f4f6;" class="po-row">
                    <td style="padding:11px 16px;color:#9ca3af;"><?= $i+1 ?></td>
                    <td style="padding:11px 16px;font-weight:600;color:#111827;"><?= htmlspecialchars($po['po_number'] ?? $po['internal_po_number'] ?? '—') ?></td>
                    <td style="padding:11px 16px;color:#374151;"><?= htmlspecialchars($po['company_name'] ?? '—') ?></td>
                    <td style="padding:11px 16px;color:#374151;"><?= htmlspecialchars($po['customer_name'] ?? '—') ?></td>
                    <td style="padding:11px 16px;color:#6b7280;"><?= htmlspecialchars($po['po_date'] ?? '—') ?></td>
                    <td style="padding:11px 16px;text-align:right;font-weight:600;">₹<?= number_format(floatval($po['total_amount'] ?? 0), 2) ?></td>
                    <td style="padding:11px 16px;text-align:center;">
                        <span style="background:<?= $sc[0] ?>;color:<?= $sc[1] ?>;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;text-transform:capitalize;">
                            <?= str_replace('_',' ', $status) ?>
                        </span>
                    </td>
                    <td style="padding:11px 16px;text-align:center;">
                        <?php if (($po['ra_count'] ?? 0) > 0): ?>
                            <span style="background:#eff6ff;color:#2563eb;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;">
                                <?= $po['ra_count'] ?> bill<?= $po['ra_count'] > 1 ? 's' : '' ?>
                            </span>
                        <?php else: ?>
                            <span style="color:#9ca3af;font-size:12px;">None</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:11px 16px;text-align:center;">
                        <?php
                            $hasClaimed  = floatval($po['invoice_claimed_amount'] ?? 0) > 0;
                            $hasOpening  = ($po['has_opening'] ?? false);
                        ?>
                        <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                            <?php if ($hasClaimed && !$hasOpening): ?>
                            <a href="/ergon/finance/measurement-sheet/<?= (int)$po['id'] ?>/opening-balance"
                               style="display:inline-flex;align-items:center;gap:4px;background:#d97706;color:#fff;padding:5px 11px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;">
                                <i class="bi bi-upload"></i> Import History
                            </a>
                            <?php endif; ?>
                            <a href="/ergon/finance/measurement-sheet/<?= (int)$po['id'] ?>/create"
                               style="display:inline-flex;align-items:center;gap:4px;background:#000080;color:#fff;padding:5px 11px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;">
                                <i class="bi bi-plus-lg"></i> New RA Bill
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterTable() {
    const q = document.getElementById('poSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.po-row');
    let v = 0;
    rows.forEach(r => { const m = r.textContent.toLowerCase().includes(q); r.style.display = m ? '' : 'none'; if(m) v++; });
    document.getElementById('poCount').textContent = v + ' POs';
}
</script>

<script>
function toggleDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    const button = dropdown ? dropdown.previousElementSibling : null;
    
    if (!dropdown) return;
    
    // Close all other dropdowns first
    document.querySelectorAll('.nav-dropdown-menu').forEach(function(menu) {
        if (menu !== dropdown) {
            menu.classList.remove('show');
        }
    });
    document.querySelectorAll('.nav-dropdown-btn').forEach(function(btn) {
        if (btn !== button) {
            btn.classList.remove('active');
        }
    });
    
    // Toggle current dropdown
    const isOpen = dropdown.classList.contains('show');
    if (isOpen) {
        dropdown.classList.remove('show');
        if (button) button.classList.remove('active');
    } else {
        dropdown.classList.add('show');
        if (button) button.classList.add('active');
    }
}
window.toggleDropdown = toggleDropdown;
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
