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
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <div>
        <h2 style="margin:0;font-size:24px;font-weight:700;">Import Opening Balance</h2>
        <p style="margin:4px 0 0;color:#6b7280;font-size:14px;">Enter previously claimed amounts for PO <?= htmlspecialchars($poNum) ?></p>
    </div>
    <a href="/ergon/finance/measurement-sheet" style="padding:10px 20px;background:#6b7280;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;">
        ← Back
    </a>
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

<!-- Info banner -->
<div style="background:#eff6ff;border:1px solid #bfdbfe;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:13px;color:#1e40af;">
    <strong>ℹ️ Opening Balance Import:</strong> Enter quantities already claimed in your previous system. This creates RA-00 and shows as "Previous Claimed" in future RA bills.
</div>

<!-- PO Summary -->
<div style="background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:16px 20px;margin-bottom:20px;display:grid;grid-template-columns:repeat(2,1fr);gap:20px;">
    <div>
        <div style="font-size:11px;font-weight:600;color:#6b7280;margin-bottom:3px;">PO NUMBER & COMPANY</div>
        <div style="font-weight:700;color:#111827;"><?= htmlspecialchars($poNum) ?></div>
        <div style="color:#374151;font-size:13px;"><?= htmlspecialchars($po['company_name'] ?? '—') ?></div>
    </div>
    <div>
        <div style="font-size:11px;font-weight:600;color:#6b7280;margin-bottom:3px;">PO VALUE & PROGRESS</div>
        <div style="font-weight:700;">₹<?= number_format($pgPoTotal, 2) ?></div>
        <div style="font-weight:700;color:#d97706;font-size:13px;">Progress: <?= $pgPoTotal > 0 ? number_format(($pgClaimedTotal / $pgPoTotal) * 100, 1) : 0 ?>% completed</div>
    </div>
</div>

<form method="POST" action="/ergon/finance/measurement-sheet/<?= (int)$po['id'] ?>/opening-balance" id="obForm">
    <input type="hidden" name="po_number"   value="<?= htmlspecialchars($poNum) ?>">
    <input type="hidden" name="company_id"  value="<?= (int)($po['company_id'] ?? 0) ?>">
    <input type="hidden" name="customer_id" value="<?= (int)($po['customer_id'] ?? 0) ?>">
    <input type="hidden" name="project"     value="<?= htmlspecialchars($po['reference'] ?? '') ?>">
    <input type="hidden" name="contractor"  value="<?= htmlspecialchars($po['company_name'] ?? '') ?>">
    <input type="hidden" name="bill_date"   value="<?= htmlspecialchars($po['po_date'] ?? date('Y-m-d')) ?>">

    <!-- Header Info -->
    <div style="background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px;margin-bottom:20px;">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
            <div>
                <label style="font-size:12px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">PREVIOUS RA BILL NUMBER</label>
                <input type="text" name="previous_ra_number" placeholder="e.g., RA-03" 
                       style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:14px;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">CONTINUING FROM DATE</label>
                <input type="date" name="continue_from_date" value="<?= htmlspecialchars($po['po_date'] ?? date('Y-m-d')) ?>"
                       style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:14px;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">NOTES</label>
                <input type="text" name="opening_notes" placeholder="Opening balance import notes"
                       style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:14px;">
            </div>
        </div>
    </div>

    <div style="background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,0.08);overflow:hidden;margin-bottom:20px;">
        <div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;font-weight:700;font-size:16px;">
            Line Items
        </div>
        <div style="overflow-x:auto;">
        <table style="width:100%;min-width:1000px;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#000080;color:#fff;">
                    <th style="padding:12px 8px;text-align:center;width:50px;font-weight:600;">S.NO</th>
                    <th style="padding:12px 16px;text-align:left;width:220px;font-weight:600;">Description</th>
                    <th style="padding:12px 8px;text-align:center;width:60px;font-weight:600;">UOM</th>
                    <th style="padding:12px 12px;text-align:right;width:110px;font-weight:600;">AS PER WO<br>Amount</th>
                    <th style="padding:12px 12px;text-align:center;width:100px;background:#0d5c2e;font-weight:600;">Claim Type</th>
                    <th style="padding:12px 12px;text-align:right;width:110px;background:#0d5c2e;font-weight:600;">Claimed<br>Qty (%)</th>
                    <th style="padding:12px 16px;text-align:right;width:130px;background:#0d5c2e;font-weight:600;">Claimed Qty Value</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="7" style="padding:40px;text-align:center;color:#9ca3af;font-style:italic;">No line items found.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $idx => $item):
                    $poQty   = floatval($item['quantity']);
                    $poRate  = floatval($item['unit_price']);
                    $poTotal = floatval($item['line_total']);
                    $claimType = $item['item_claim_type'] ?? 'quantity';
                    $n = $idx;
                ?>
                <tr style="border-bottom:1px solid #f3f4f6;" class="item-row" data-po-rate="<?= $poRate ?>" data-po-qty="<?= $poQty ?>" data-po-total="<?= $poTotal ?>">
                    <!-- S.NO -->
                    <td style="padding:12px 8px;text-align:center;color:#6b7280;font-weight:600;vertical-align:top;">
                        <?= $item['line_number'] ?>
                    </td>
                    
                    <!-- Description -->
                    <td style="padding:12px 16px;vertical-align:top;">
                        <div style="font-weight:600;color:#111827;line-height:1.4;margin-bottom:4px;">
                            <?= htmlspecialchars($item['product_name']) ?>
                        </div>
                        <?php if (!empty($item['description'])): ?>
                        <div style="font-size:11px;color:#6b7280;line-height:1.3;">
                            <?= htmlspecialchars(substr($item['description'],0,120)) ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Hidden fields -->
                        <input type="hidden" name="items[<?=$n?>][po_item_id]" value="<?= $item['id'] ?>">
                        <input type="hidden" name="items[<?=$n?>][line_number]" value="<?= $item['line_number'] ?>">
                        <input type="hidden" name="items[<?=$n?>][product_name]" value="<?= htmlspecialchars($item['product_name']) ?>">
                        <input type="hidden" name="items[<?=$n?>][description]" value="<?= htmlspecialchars($item['description'] ?? '') ?>">
                        <input type="hidden" name="items[<?=$n?>][unit]" value="<?= htmlspecialchars($item['unit'] ?? '') ?>">
                        <input type="hidden" name="items[<?=$n?>][po_quantity]" value="<?= $poQty ?>">
                        <input type="hidden" name="items[<?=$n?>][po_unit_price]" value="<?= $poRate ?>">
                        <input type="hidden" name="items[<?=$n?>][po_line_total]" value="<?= $poTotal ?>">
                    </td>
                    
                    <!-- UOM -->
                    <td style="padding:12px 8px;text-align:center;color:#6b7280;font-weight:500;vertical-align:top;">
                        <?= htmlspecialchars($item['unit'] ?? '—') ?>
                    </td>
                    
                    <!-- AS PER WO Amount -->
                    <td style="padding:12px 12px;text-align:right;font-weight:600;color:#374151;vertical-align:top;">
                        ₹<?= number_format($poTotal, 2) ?>
                    </td>
                    
                    <!-- Claim Type -->
                    <td style="padding:8px 12px;background:#f0fdf4;text-align:center;vertical-align:top;">
                        <select name="items[<?=$n?>][claim_type]" class="claim-type-sel"
                                onchange="onClaimTypeChange(this)"
                                style="width:100%;padding:6px 4px;border:1px solid #d1fae5;border-radius:6px;font-size:11px;background:#fff;font-weight:600;">
                            <option value="quantity" <?= $claimType==='quantity' ?'selected':'' ?>>Actual Qty</option>
                            <option value="percentage" <?= $claimType==='percentage' ?'selected':'' ?>>Percentage</option>
                        </select>
                    </td>
                    
                    <!-- Claimed Qty (%) -->
                    <td style="padding:8px 12px;background:#f0fdf4;vertical-align:top;">
                        <div style="position:relative;">
                            <input type="number" name="items[<?=$n?>][this_qty]" class="this-qty"
                                   min="0" step="0.01" value="0" placeholder="0.00"
                                   style="width:100%;padding:8px 6px;border:1px solid #d1fae5;border-radius:6px;font-size:13px;text-align:right;background:#fff;font-weight:600;"
                                   oninput="calcRow(this.closest('tr'))">
                            <div class="this-pct-display" style="font-size:10px;color:#059669;margin-top:3px;text-align:right;font-weight:500;">
                                (0.0%)
                            </div>
                        </div>
                        <input type="hidden" name="items[<?=$n?>][this_pct]" class="this-pct" value="0">
                    </td>
                    
                    <!-- Claimed Qty Value -->
                    <td style="padding:12px 16px;text-align:right;background:#f0fdf4;vertical-align:top;">
                        <input type="hidden" name="items[<?=$n?>][this_amount]" class="this-amount" value="0">
                        <span class="this-amount-display" style="font-weight:700;color:#059669;">₹0.00</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <tfoot style="display:none;">
                <!-- Total will be shown in footer section -->
            </tfoot>
        </table>
        </div>
        
        <!-- Summary Section -->
        <div style="padding:16px 20px;border-top:1px solid #f3f4f6;background:#f9fafb;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <button type="button" onclick="autoFillFromPO()"
                        style="padding:8px 16px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;">
                    ⚡ Auto-fill from PO Header
                </button>
                <div style="font-size:15px;font-weight:700;color:#059669;">
                    Total Claimed Qty Value: <span id="grandTotal">₹0.00</span>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px;">
        <a href="/ergon/finance/measurement-sheet" style="padding:10px 24px;border:1px solid #e5e7eb;background:#fff;border-radius:8px;font-size:14px;font-weight:600;color:#374151;text-decoration:none;">
            Cancel
        </a>
        <button type="submit" <?= $alreadyImported ? 'disabled' : '' ?>
                style="padding:10px 28px;background:<?= $alreadyImported ? '#9ca3af' : '#000080' ?>;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:<?= $alreadyImported ? 'not-allowed' : 'pointer' ?>;">
            <?= $alreadyImported ? 'Already Imported' : 'Save Opening Balance' ?>
        </button>
    </div>
</form>

<script>
const PG_CLAIMED = <?= $pgClaimedTotal ?>;
const PO_TOTAL   = <?= $pgPoTotal ?>;

function onClaimTypeChange(sel) {
    const row = sel.closest('tr');
    const qtyInput = row.querySelector('.this-qty');
    
    if (sel.value === 'quantity') {
        // Quantity mode - input accepts actual quantities
        qtyInput.disabled = false;
        qtyInput.style.opacity = '1';
        qtyInput.placeholder = 'Enter quantity';
        qtyInput.setAttribute('data-mode', 'quantity');
    } else {
        // Percentage mode - input accepts percentages
        qtyInput.disabled = false;
        qtyInput.style.opacity = '1';
        qtyInput.placeholder = 'Enter percentage';
        qtyInput.setAttribute('data-mode', 'percentage');
        qtyInput.max = '100'; // Limit percentage to 100
    }
    calcRow(row);
}

function calcRow(row) {
    const poRate  = parseFloat(row.dataset.poRate)  || 0;
    const poTotal = parseFloat(row.dataset.poTotal) || 0;
    const poQty   = parseFloat(row.dataset.poQty) || 0;
    const qtyInp  = row.querySelector('.this-qty');
    const pctHid  = row.querySelector('.this-pct');
    const pctDisp = row.querySelector('.this-pct-display');
    const amtHid  = row.querySelector('.this-amount');
    const amtDisp = row.querySelector('.this-amount-display');
    const claimType = row.querySelector('.claim-type-sel').value;
    const inputMode = qtyInp.getAttribute('data-mode') || 'quantity';
    
    let thisQty = 0;
    let thisPct = 0;
    const inputValue = parseFloat(qtyInp.value) || 0;
    
    if (inputMode === 'percentage') {
        // User entered percentage - store percentage as entered, calculate quantity
        thisPct = inputValue;  // Store the actual percentage entered
        thisQty = poQty > 0 ? (thisPct / 100) * poQty : 0;
    } else {
        // User entered actual quantity - store quantity as entered, calculate percentage
        thisQty = inputValue;  // Store the actual quantity entered
        thisPct = poQty > 0 ? (thisQty / poQty) * 100 : 0;
    }
    
    // Calculate amount from quantity
    const thisAmount = thisQty * poRate;
    
    // Update hidden fields with the correct values
    pctHid.value = thisPct.toFixed(2);
    amtHid.value = thisAmount.toFixed(2);
    
    // Update displays based on input mode
    if (inputMode === 'percentage') {
        pctDisp.textContent = `(${thisQty.toFixed(2)} qty)`;
        pctDisp.style.color = '#2563eb';
    } else {
        pctDisp.textContent = `(${thisPct.toFixed(1)}%)`;
        pctDisp.style.color = '#059669';
    }
    
    amtDisp.textContent = '₹' + thisAmount.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    
    updateGrandTotal();
}

function updateGrandTotal() {
    let total = 0;
    document.querySelectorAll('.this-amount').forEach(h => { total += parseFloat(h.value) || 0; });
    document.getElementById('grandTotal').textContent =
        '₹' + total.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
}

// Auto-fill: distribute PG claimed amount proportionally across line items
function autoFillFromPO() {
    if (PG_CLAIMED <= 0 || PO_TOTAL <= 0) {
        alert('No claimed quantity data found in PG for this PO.');
        return;
    }
    
    if (!confirm('This will automatically distribute the PG claimed quantities proportionally across all line items. Continue?')) {
        return;
    }
    
    const ratio = PG_CLAIMED / PO_TOTAL;
    document.querySelectorAll('.item-row').forEach(row => {
        const poTotal = parseFloat(row.dataset.poTotal) || 0;
        const poQty   = parseFloat(row.dataset.poQty) || 0;
        const qtyInp  = row.querySelector('.this-qty');
        const sel     = row.querySelector('.claim-type-sel');

        const pct = ratio * 100;

        // Set to percentage mode for auto-fill
        sel.value = 'percentage';
        onClaimTypeChange(sel);
        
        // Enter percentage value
        qtyInp.value = pct.toFixed(2);
        
        // Trigger calculation
        calcRow(row);
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.item-row').forEach(row => {
        const claimTypeSel = row.querySelector('.claim-type-sel');
        onClaimTypeChange(claimTypeSel);
    });
    
    document.querySelectorAll('.this-qty').forEach(input => {
        input.addEventListener('focus', function() {
            this.style.transform = 'scale(1.02)';
            this.style.zIndex = '10';
        });
        
        input.addEventListener('blur', function() {
            this.style.transform = 'scale(1)';
            this.style.zIndex = '1';
        });
    });
});
</script>
<?php endif; ?>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
