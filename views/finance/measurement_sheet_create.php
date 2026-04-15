<?php
$title = 'New RA Bill';
$active_page = 'measurement_sheet';
ob_start();
?>
<?php if ($error || !$po): ?>
<div style="padding:40px;text-align:center;color:#dc2626;"><?= htmlspecialchars($error ?? 'PO not found') ?></div>
<?php else:
    $poNum    = $po['po_number'] ?? $po['internal_po_number'] ?? '—';
    $raLabel  = 'RA-' . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <a href="/ergon/finance/measurement-sheet" style="color:#6b7280;text-decoration:none;font-size:13px;">← Back</a>
    <h2 style="margin:0;font-size:20px;font-weight:700;">New RA Bill — <?= htmlspecialchars($poNum) ?></h2>
    <span style="background:#eff6ff;color:#2563eb;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:700;"><?= $raLabel ?></span>
</div>

<form method="POST" action="/ergon/finance/measurement-sheet/<?= (int)$po['id'] ?>/store" id="raForm">
    <input type="hidden" name="po_number"   value="<?= htmlspecialchars($poNum) ?>">
    <input type="hidden" name="company_id"  value="<?= (int)($po['company_id'] ?? 0) ?>">
    <input type="hidden" name="customer_id" value="<?= (int)($po['customer_id'] ?? 0) ?>">

    <!-- Header Info -->
    <div style="background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px;margin-bottom:20px;">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
            <div>
                <label style="font-size:12px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">PROJECT / SITE</label>
                <input type="text" name="project" value="<?= htmlspecialchars($po['reference'] ?? '') ?>"
                       style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:14px;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">CONTRACTOR / VENDOR</label>
                <input type="text" name="contractor" value="<?= htmlspecialchars($po['company_name'] ?? '') ?>"
                       style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:14px;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">BILL DATE</label>
                <input type="date" name="bill_date" value="<?= date('Y-m-d') ?>" required
                       style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:14px;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">PO / WO REF</label>
                <input type="text" readonly value="<?= htmlspecialchars($poNum) ?>"
                       style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:14px;background:#f9fafb;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">RA BILL NO</label>
                <input type="text" readonly value="<?= $raLabel ?>"
                       style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:14px;background:#f9fafb;font-weight:700;color:#000080;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">CUSTOMER</label>
                <input type="text" readonly value="<?= htmlspecialchars($po['customer_name'] ?? '') ?>"
                       style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:14px;background:#f9fafb;">
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div style="background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,0.08);overflow:hidden;margin-bottom:20px;">
        <div style="padding:14px 20px;border-bottom:1px solid #f3f4f6;font-weight:700;font-size:15px;">
            Measurement Sheet — Line Items
        </div>
        <div class="table-responsive">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#000080;color:#fff;">
                    <th style="padding:10px 12px;text-align:center;width:36px;">S.No</th>
                    <th style="padding:10px 12px;text-align:left;min-width:180px;">Description of Work</th>
                    <th style="padding:10px 12px;text-align:center;">Unit</th>
                    <th style="padding:10px 12px;text-align:right;">PO Qty</th>
                    <th style="padding:10px 12px;text-align:right;">PO Rate (₹)</th>
                    <th style="padding:10px 12px;text-align:right;">PO Amount (₹)</th>
                    <th style="padding:10px 12px;text-align:right;background:#1a1a8c;">Prev Claimed Qty</th>
                    <th style="padding:10px 12px;text-align:right;background:#1a1a8c;">Prev Claimed %</th>
                    <th style="padding:10px 12px;text-align:right;background:#1a1a8c;">Prev Amount (₹)</th>
                    <th style="padding:10px 12px;text-align:center;background:#0d5c2e;min-width:90px;">Claim Type</th>
                    <th style="padding:10px 12px;text-align:right;background:#0d5c2e;min-width:90px;">This Qty</th>
                    <th style="padding:10px 12px;text-align:right;background:#0d5c2e;min-width:80px;">This %</th>
                    <th style="padding:10px 12px;text-align:right;background:#0d5c2e;min-width:110px;">This Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="13" style="padding:30px;text-align:center;color:#9ca3af;">No line items found for this PO.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $idx => $item):
                    $poQty   = floatval($item['quantity']);
                    $poRate  = floatval($item['unit_price']);
                    $poTotal = floatval($item['line_total']);
                    $prevQty = floatval($item['prev_claimed_qty']);
                    $prevPct = floatval($item['prev_claimed_pct']);
                    $prevAmt = floatval($item['prev_claimed_amount']);
                    $claimType = $item['item_claim_type'] ?? 'quantity';
                    $n = $idx;
                ?>
                <tr style="border-bottom:1px solid #f3f4f6;" class="item-row" data-po-rate="<?= $poRate ?>" data-po-qty="<?= $poQty ?>" data-po-total="<?= $poTotal ?>">
                    <td style="padding:10px 12px;text-align:center;color:#9ca3af;"><?= $item['line_number'] ?></td>
                    <td style="padding:10px 12px;">
                        <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($item['product_name']) ?></div>
                        <?php if (!empty($item['description'])): ?>
                        <div style="font-size:11px;color:#6b7280;margin-top:2px;white-space:pre-line;"><?= htmlspecialchars(substr($item['description'],0,120)) ?></div>
                        <?php endif; ?>
                        <input type="hidden" name="items[<?=$n?>][po_item_id]"    value="<?= $item['id'] ?>">
                        <input type="hidden" name="items[<?=$n?>][line_number]"   value="<?= $item['line_number'] ?>">
                        <input type="hidden" name="items[<?=$n?>][product_name]"  value="<?= htmlspecialchars($item['product_name']) ?>">
                        <input type="hidden" name="items[<?=$n?>][description]"   value="<?= htmlspecialchars($item['description'] ?? '') ?>">
                        <input type="hidden" name="items[<?=$n?>][unit]"          value="<?= htmlspecialchars($item['unit'] ?? '') ?>">
                        <input type="hidden" name="items[<?=$n?>][po_quantity]"   value="<?= $poQty ?>">
                        <input type="hidden" name="items[<?=$n?>][po_unit_price]" value="<?= $poRate ?>">
                        <input type="hidden" name="items[<?=$n?>][po_line_total]" value="<?= $poTotal ?>">
                        <input type="hidden" name="items[<?=$n?>][prev_claimed_qty]"    value="<?= $prevQty ?>">
                        <input type="hidden" name="items[<?=$n?>][prev_claimed_pct]"    value="<?= $prevPct ?>">
                        <input type="hidden" name="items[<?=$n?>][prev_claimed_amount]" value="<?= $prevAmt ?>">
                    </td>
                    <td style="padding:10px 12px;text-align:center;color:#6b7280;"><?= htmlspecialchars($item['unit'] ?? '—') ?></td>
                    <td style="padding:10px 12px;text-align:right;"><?= number_format($poQty, 3) ?></td>
                    <td style="padding:10px 12px;text-align:right;">₹<?= number_format($poRate, 2) ?></td>
                    <td style="padding:10px 12px;text-align:right;font-weight:600;">₹<?= number_format($poTotal, 2) ?></td>
                    <td style="padding:10px 12px;text-align:right;background:#f8f9ff;color:#374191;"><?= number_format($prevQty, 3) ?></td>
                    <td style="padding:10px 12px;text-align:right;background:#f8f9ff;color:#374191;"><?= number_format($prevPct, 2) ?>%</td>
                    <td style="padding:10px 12px;text-align:right;background:#f8f9ff;color:#374191;font-weight:600;">₹<?= number_format($prevAmt, 2) ?></td>
                    <!-- Claim type selector -->
                    <td style="padding:8px 10px;text-align:center;background:#f0fdf4;">
                        <select name="items[<?=$n?>][claim_type]" class="claim-type-sel"
                                onchange="onClaimTypeChange(this)"
                                style="padding:5px 6px;border:1px solid #d1fae5;border-radius:5px;font-size:12px;background:#fff;width:100%;">
                            <option value="quantity" <?= $claimType==='quantity'?'selected':'' ?>>Quantity</option>
                            <option value="percentage" <?= $claimType==='percentage'?'selected':'' ?>>Percentage</option>
                        </select>
                    </td>
                    <!-- This Qty -->
                    <td style="padding:8px 10px;background:#f0fdf4;">
                        <input type="number" name="items[<?=$n?>][this_qty]" class="this-qty"
                               min="0" step="0.001" value="0"
                               style="width:80px;padding:5px 6px;border:1px solid #d1fae5;border-radius:5px;font-size:13px;text-align:right;"
                               oninput="calcRow(this.closest('tr'))">
                    </td>
                    <!-- This % -->
                    <td style="padding:8px 10px;background:#f0fdf4;">
                        <input type="number" name="items[<?=$n?>][this_pct]" class="this-pct"
                               min="0" max="100" step="0.01" value="0"
                               style="width:70px;padding:5px 6px;border:1px solid #d1fae5;border-radius:5px;font-size:13px;text-align:right;"
                               oninput="calcRow(this.closest('tr'))">
                    </td>
                    <!-- This Amount (computed) -->
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
                    <td colspan="12" style="padding:12px 16px;text-align:right;font-weight:700;font-size:14px;">Total This Bill:</td>
                    <td style="padding:12px 16px;text-align:right;font-weight:700;font-size:15px;color:#059669;" id="grandTotal">₹0.00</td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>

    <!-- Notes -->
    <div style="background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:16px 20px;margin-bottom:20px;">
        <label style="font-size:12px;font-weight:600;color:#6b7280;display:block;margin-bottom:6px;">NOTES / REMARKS</label>
        <textarea name="notes" rows="2" style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:14px;resize:vertical;"></textarea>
    </div>

    <div style="display:flex;gap:12px;justify-content:flex-end;">
        <a href="/ergon/finance/measurement-sheet" style="padding:10px 24px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;font-weight:600;color:#374151;text-decoration:none;">Cancel</a>
        <button type="submit" style="padding:10px 28px;background:#000080;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;">
            Save &amp; Print RA Bill
        </button>
    </div>
</form>

<script>
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
    const poQty   = parseFloat(row.dataset.poQty)   || 0;
    const poTotal = parseFloat(row.dataset.poTotal) || 0;
    const sel     = row.querySelector('.claim-type-sel');
    const qtyInp  = row.querySelector('.this-qty');
    const pctInp  = row.querySelector('.this-pct');
    const amtHid  = row.querySelector('.this-amount');
    const amtDisp = row.querySelector('.this-amount-display');

    let amt = 0;
    if (sel.value === 'quantity') {
        const qty = parseFloat(qtyInp.value) || 0;
        amt = qty * poRate;
        // auto-fill pct
        if (poTotal > 0) { pctInp.value = ((amt / poTotal) * 100).toFixed(2); }
    } else {
        const pct = parseFloat(pctInp.value) || 0;
        amt = (pct / 100) * poTotal;
        // auto-fill qty
        if (poRate > 0) { qtyInp.value = (amt / poRate).toFixed(3); }
    }
    amtHid.value  = amt.toFixed(2);
    amtDisp.textContent = '₹' + amt.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    updateGrandTotal();
}

function updateGrandTotal() {
    let total = 0;
    document.querySelectorAll('.this-amount').forEach(h => { total += parseFloat(h.value) || 0; });
    document.getElementById('grandTotal').textContent =
        '₹' + total.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
}

// Init: apply claim type state on load
document.querySelectorAll('.claim-type-sel').forEach(sel => onClaimTypeChange(sel));
</script>
<?php endif; ?>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
