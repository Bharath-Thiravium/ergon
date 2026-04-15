<?php
$title = 'Import Opening Balance';
$active_page = 'measurement_sheet';
ob_start();
?>
<?php if ($error || !$po): ?>
<div style="padding:40px;text-align:center;color:#dc2626;"><?= htmlspecialchars($error ?? 'PO not found') ?></div>
<?php else:
    $poNum = $po['po_number'] ?? $po['internal_po_number'] ?? '—';
    $pgClaimedTotal = floatval($po['invoice_claimed_amount'] ?? 0);
    $pgPoTotal      = floatval($po['total_amount'] ?? 0);
?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
    <a href="/ergon/finance/measurement-sheet" style="color:#6b7280;text-decoration:none;font-size:13px;">← Back</a>
    <h2 style="margin:0;font-size:20px;font-weight:700;">Import Opening Balance</h2>
    <span style="background:#fffbeb;color:#d97706;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:700;">RA-00</span>
    <span style="font-size:13px;color:#6b7280;"><?= htmlspecialchars($poNum) ?></span>
</div>

<?php if ($alreadyImported): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:14px 18px;border-radius:8px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
        <strong>Opening balance already imported for this PO.</strong><br>
        <span style="font-size:13px;">To correct it, delete the existing RA-00 record first.</span>
    </div>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
    <?= $_GET['error'] === 'duplicate' ? 'Opening balance already exists for this PO.' : 'An error occurred. Please try again.' ?>
</div>
<?php endif; ?>

<!-- Info banner: what this does -->
<div style="background:#eff6ff;border:1px solid #bfdbfe;padding:14px 18px;border-radius:8px;margin-bottom:20px;font-size:13px;color:#1e40af;">
    <strong>ℹ️ What is this?</strong><br>
    Enter the amounts already claimed in your <strong>previous system / application</strong> for each line item of this PO.
    This will be saved as <strong>RA-00 (Opening Balance)</strong> and will appear as "Previous Claimed" in all future RA bills.
    The PO header shows <strong>₹<?= number_format($pgClaimedTotal, 2) ?></strong> already claimed.
</div>

<!-- PO Summary -->
<div style="background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:16px 20px;margin-bottom:20px;display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
    <div>
        <div style="font-size:11px;font-weight:600;color:#6b7280;margin-bottom:3px;">PO NUMBER</div>
        <div style="font-weight:700;color:#111827;"><?= htmlspecialchars($poNum) ?></div>
    </div>
    <div>
        <div style="font-size:11px;font-weight:600;color:#6b7280;margin-bottom:3px;">COMPANY</div>
        <div style="color:#374151;"><?= htmlspecialchars($po['company_name'] ?? '—') ?></div>
    </div>
    <div>
        <div style="font-size:11px;font-weight:600;color:#6b7280;margin-bottom:3px;">PO VALUE</div>
        <div style="font-weight:700;">₹<?= number_format($pgPoTotal, 2) ?></div>
    </div>
    <div>
        <div style="font-size:11px;font-weight:600;color:#6b7280;margin-bottom:3px;">ALREADY CLAIMED (PG)</div>
        <div style="font-weight:700;color:#d97706;">₹<?= number_format($pgClaimedTotal, 2) ?></div>
    </div>
</div>

<form method="POST" action="/ergon/finance/measurement-sheet/<?= (int)$po['id'] ?>/opening-balance" id="obForm">
    <input type="hidden" name="po_number"   value="<?= htmlspecialchars($poNum) ?>">
    <input type="hidden" name="company_id"  value="<?= (int)($po['company_id'] ?? 0) ?>">
    <input type="hidden" name="customer_id" value="<?= (int)($po['customer_id'] ?? 0) ?>">
    <input type="hidden" name="project"     value="<?= htmlspecialchars($po['reference'] ?? '') ?>">
    <input type="hidden" name="contractor"  value="<?= htmlspecialchars($po['company_name'] ?? '') ?>">
    <input type="hidden" name="bill_date"   value="<?= htmlspecialchars($po['po_date'] ?? date('Y-m-d')) ?>">

    <div style="background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,0.08);overflow:hidden;margin-bottom:20px;">
        <div style="padding:14px 20px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-weight:700;font-size:15px;">Line Items — Enter Previously Claimed Amounts</span>
            <button type="button" onclick="autoFillFromPO()"
                    style="padding:6px 14px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">
                ⚡ Auto-fill from PO header (proportional)
            </button>
        </div>
        <div class="table-responsive">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#000080;color:#fff;">
                    <th style="padding:10px 12px;text-align:center;width:36px;">S.No</th>
                    <th style="padding:10px 12px;text-align:left;min-width:180px;">Description</th>
                    <th style="padding:10px 12px;text-align:center;">Unit</th>
                    <th style="padding:10px 12px;text-align:right;">PO Qty</th>
                    <th style="padding:10px 12px;text-align:right;">PO Rate (₹)</th>
                    <th style="padding:10px 12px;text-align:right;">PO Amount (₹)</th>
                    <th style="padding:10px 12px;text-align:center;background:#0d5c2e;min-width:100px;">Claim Type</th>
                    <th style="padding:10px 12px;text-align:right;background:#0d5c2e;min-width:90px;">Claimed Qty</th>
                    <th style="padding:10px 12px;text-align:right;background:#0d5c2e;min-width:80px;">Claimed %</th>
                    <th style="padding:10px 12px;text-align:right;background:#0d5c2e;min-width:110px;">Claimed Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="10" style="padding:30px;text-align:center;color:#9ca3af;">No line items found.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $idx => $item):
                    $poQty   = floatval($item['quantity']);
                    $poRate  = floatval($item['unit_price']);
                    $poTotal = floatval($item['line_total']);
                    $claimType = $item['item_claim_type'] ?? 'quantity';
                    $n = $idx;
                ?>
                <tr style="border-bottom:1px solid #f3f4f6;" class="item-row"
                    data-po-rate="<?= $poRate ?>" data-po-qty="<?= $poQty ?>" data-po-total="<?= $poTotal ?>">
                    <td style="padding:10px 12px;text-align:center;color:#9ca3af;"><?= $item['line_number'] ?></td>
                    <td style="padding:10px 12px;">
                        <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($item['product_name']) ?></div>
                        <?php if (!empty($item['description'])): ?>
                        <div style="font-size:11px;color:#6b7280;margin-top:2px;"><?= htmlspecialchars(substr($item['description'],0,100)) ?></div>
                        <?php endif; ?>
                        <input type="hidden" name="items[<?=$n?>][po_item_id]"    value="<?= $item['id'] ?>">
                        <input type="hidden" name="items[<?=$n?>][line_number]"   value="<?= $item['line_number'] ?>">
                        <input type="hidden" name="items[<?=$n?>][product_name]"  value="<?= htmlspecialchars($item['product_name']) ?>">
                        <input type="hidden" name="items[<?=$n?>][description]"   value="<?= htmlspecialchars($item['description'] ?? '') ?>">
                        <input type="hidden" name="items[<?=$n?>][unit]"          value="<?= htmlspecialchars($item['unit'] ?? '') ?>">
                        <input type="hidden" name="items[<?=$n?>][po_quantity]"   value="<?= $poQty ?>">
                        <input type="hidden" name="items[<?=$n?>][po_unit_price]" value="<?= $poRate ?>">
                        <input type="hidden" name="items[<?=$n?>][po_line_total]" value="<?= $poTotal ?>">
                    </td>
                    <td style="padding:10px 12px;text-align:center;color:#6b7280;"><?= htmlspecialchars($item['unit'] ?? '—') ?></td>
                    <td style="padding:10px 12px;text-align:right;"><?= number_format($poQty, 3) ?></td>
                    <td style="padding:10px 12px;text-align:right;">₹<?= number_format($poRate, 2) ?></td>
                    <td style="padding:10px 12px;text-align:right;font-weight:600;">₹<?= number_format($poTotal, 2) ?></td>
                    <td style="padding:8px 10px;text-align:center;background:#f0fdf4;">
                        <select name="items[<?=$n?>][claim_type]" class="claim-type-sel"
                                onchange="onClaimTypeChange(this)"
                                style="padding:5px 6px;border:1px solid #d1fae5;border-radius:5px;font-size:12px;background:#fff;width:100%;">
                            <option value="quantity"   <?= $claimType==='quantity'   ?'selected':'' ?>>Quantity</option>
                            <option value="percentage" <?= $claimType==='percentage' ?'selected':'' ?>>Percentage</option>
                        </select>
                    </td>
                    <td style="padding:8px 10px;background:#f0fdf4;">
                        <input type="number" name="items[<?=$n?>][this_qty]" class="this-qty"
                               min="0" step="0.001" value="0"
                               style="width:80px;padding:5px 6px;border:1px solid #d1fae5;border-radius:5px;font-size:13px;text-align:right;"
                               oninput="calcRow(this.closest('tr'))">
                    </td>
                    <td style="padding:8px 10px;background:#f0fdf4;">
                        <input type="number" name="items[<?=$n?>][this_pct]" class="this-pct"
                               min="0" max="100" step="0.01" value="0"
                               style="width:70px;padding:5px 6px;border:1px solid #d1fae5;border-radius:5px;font-size:13px;text-align:right;"
                               oninput="calcRow(this.closest('tr'))">
                    </td>
                    <td style="padding:8px 10px;background:#f0fdf4;text-align:right;">
                        <input type="hidden" name="items[<?=$n?>][this_amount]" class="this-amount" value="0">
                        <span class="this-amount-display" style="font-weight:700;color:#059669;">₹0.00</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f9fafb;border-top:2px solid #e5e7eb;">
                    <td colspan="9" style="padding:12px 16px;text-align:right;font-weight:700;font-size:14px;">Total Claimed (Opening Balance):</td>
                    <td style="padding:12px 16px;text-align:right;font-weight:700;font-size:15px;color:#059669;" id="grandTotal">₹0.00</td>
                </tr>
                <tr style="background:#fffbeb;">
                    <td colspan="9" style="padding:8px 16px;text-align:right;font-size:13px;color:#92400e;">PG Header shows claimed:</td>
                    <td style="padding:8px 16px;text-align:right;font-size:13px;font-weight:700;color:#d97706;">₹<?= number_format($pgClaimedTotal, 2) ?></td>
                </tr>
                <tr id="diffRow" style="display:none;background:#fef2f2;">
                    <td colspan="9" style="padding:8px 16px;text-align:right;font-size:13px;color:#dc2626;">Difference:</td>
                    <td style="padding:8px 16px;text-align:right;font-size:13px;font-weight:700;color:#dc2626;" id="diffAmt">₹0.00</td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:flex-end;">
        <a href="/ergon/finance/measurement-sheet" style="padding:10px 24px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;font-weight:600;color:#374151;text-decoration:none;">Cancel</a>
        <button type="submit" <?= $alreadyImported ? 'disabled' : '' ?>
                style="padding:10px 28px;background:<?= $alreadyImported ? '#9ca3af' : '#059669' ?>;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:<?= $alreadyImported ? 'not-allowed' : 'pointer' ?>;">
            <?= $alreadyImported ? 'Already Imported' : 'Save Opening Balance (RA-00)' ?>
        </button>
    </div>
</form>

<script>
const PG_CLAIMED = <?= $pgClaimedTotal ?>;
const PO_TOTAL   = <?= $pgPoTotal ?>;

function onClaimTypeChange(sel) {
    const row = sel.closest('tr');
    const qtyInput = row.querySelector('.this-qty');
    const pctInput = row.querySelector('.this-pct');
    if (sel.value === 'quantity') {
        qtyInput.disabled = false; qtyInput.style.opacity = '1';
        pctInput.disabled = true;  pctInput.style.opacity = '0.4'; pctInput.value = 0;
    } else {
        pctInput.disabled = false; pctInput.style.opacity = '1';
        qtyInput.disabled = true;  qtyInput.style.opacity = '0.4'; qtyInput.value = 0;
    }
    calcRow(row);
}

function calcRow(row) {
    const poRate  = parseFloat(row.dataset.poRate)  || 0;
    const poTotal = parseFloat(row.dataset.poTotal) || 0;
    const sel     = row.querySelector('.claim-type-sel');
    const qtyInp  = row.querySelector('.this-qty');
    const pctInp  = row.querySelector('.this-pct');
    const amtHid  = row.querySelector('.this-amount');
    const amtDisp = row.querySelector('.this-amount-display');
    let amt = 0;
    if (sel.value === 'quantity') {
        amt = (parseFloat(qtyInp.value) || 0) * poRate;
        if (poTotal > 0) pctInp.value = ((amt / poTotal) * 100).toFixed(2);
    } else {
        amt = ((parseFloat(pctInp.value) || 0) / 100) * poTotal;
        if (poRate > 0) qtyInp.value = (amt / poRate).toFixed(3);
    }
    amtHid.value = amt.toFixed(2);
    amtDisp.textContent = '₹' + amt.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    updateGrandTotal();
}

function updateGrandTotal() {
    let total = 0;
    document.querySelectorAll('.this-amount').forEach(h => { total += parseFloat(h.value) || 0; });
    document.getElementById('grandTotal').textContent =
        '₹' + total.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    // Show diff row if there's a mismatch
    const diff = Math.abs(total - PG_CLAIMED);
    const diffRow = document.getElementById('diffRow');
    if (diff > 0.5) {
        diffRow.style.display = '';
        document.getElementById('diffAmt').textContent =
            (total > PG_CLAIMED ? '+' : '-') + '₹' + diff.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    } else {
        diffRow.style.display = 'none';
    }
}

// Auto-fill: distribute PG claimed amount proportionally across line items
function autoFillFromPO() {
    if (PG_CLAIMED <= 0 || PO_TOTAL <= 0) {
        alert('No claimed amount found in PG for this PO.');
        return;
    }
    const ratio = PG_CLAIMED / PO_TOTAL;
    document.querySelectorAll('.item-row').forEach(row => {
        const poTotal = parseFloat(row.dataset.poTotal) || 0;
        const poRate  = parseFloat(row.dataset.poRate)  || 0;
        const sel     = row.querySelector('.claim-type-sel');
        const pctInp  = row.querySelector('.this-pct');
        const qtyInp  = row.querySelector('.this-qty');
        const amtHid  = row.querySelector('.this-amount');
        const amtDisp = row.querySelector('.this-amount-display');

        const amt = poTotal * ratio;
        const pct = ratio * 100;

        // Force percentage mode for auto-fill
        sel.value = 'percentage';
        onClaimTypeChange(sel);
        pctInp.value = pct.toFixed(2);
        qtyInp.value = poRate > 0 ? (amt / poRate).toFixed(3) : 0;
        amtHid.value = amt.toFixed(2);
        amtDisp.textContent = '₹' + amt.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    });
    updateGrandTotal();
}

// Init claim type state
document.querySelectorAll('.claim-type-sel').forEach(sel => onClaimTypeChange(sel));
</script>
<?php endif; ?>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
