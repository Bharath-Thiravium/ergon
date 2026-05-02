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
        <div style="overflow-x:auto;">
        <table style="width:100%;min-width:1200px;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#000080;color:#fff;">
                    <th style="padding:12px 8px;text-align:center;width:50px;font-weight:600;">S.NO</th>
                    <th style="padding:12px 16px;text-align:left;width:220px;font-weight:600;">Description</th>
                    <th style="padding:12px 8px;text-align:center;width:60px;font-weight:600;">UOM</th>
                    <th style="padding:12px 12px;text-align:right;width:90px;font-weight:600;">AS PER WO<br>Qty</th>
                    <th style="padding:12px 12px;text-align:right;width:110px;background:#1a1a8c;font-weight:600;">Previous Bills<br>Qty (%)</th>
                    <th style="padding:12px 12px;text-align:center;width:100px;background:#0d5c2e;font-weight:600;">Claim Type</th>
                    <th style="padding:12px 12px;text-align:right;width:110px;background:#0d5c2e;font-weight:600;">Present Bill<br>Qty (%)</th>
                    <th style="padding:12px 12px;text-align:right;width:110px;background:#4b5563;font-weight:600;">Cumulative<br>Qty (%)</th>
                    <th style="padding:12px 16px;text-align:left;width:150px;font-weight:600;">Remarks</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="9" style="padding:40px;text-align:center;color:#9ca3af;font-style:italic;">No line items found for this PO.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $idx => $item):
                    $poQty   = floatval($item['quantity']);
                    $prevQty = floatval($item['prev_claimed_qty']);
                    $prevPct = $poQty > 0 ? ($prevQty / $poQty) * 100 : 0;
                    $n = $idx;
                ?>
                <tr style="border-bottom:1px solid #f3f4f6;" class="item-row" data-po-qty="<?= $poQty ?>" data-prev-qty="<?= $prevQty ?>">
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
                        <input type="hidden" name="items[<?=$n?>][po_item_id]"    value="<?= $item['id'] ?>">
                        <input type="hidden" name="items[<?=$n?>][line_number]"   value="<?= $item['line_number'] ?>">
                        <input type="hidden" name="items[<?=$n?>][product_name]"  value="<?= htmlspecialchars($item['product_name']) ?>">
                        <input type="hidden" name="items[<?=$n?>][description]"   value="<?= htmlspecialchars($item['description'] ?? '') ?>">
                        <input type="hidden" name="items[<?=$n?>][unit]"          value="<?= htmlspecialchars($item['unit'] ?? '') ?>">
                        <input type="hidden" name="items[<?=$n?>][po_quantity]"   value="<?= $poQty ?>">
                        <input type="hidden" name="items[<?=$n?>][po_unit_price]" value="<?= floatval($item['unit_price']) ?>">
                        <input type="hidden" name="items[<?=$n?>][po_line_total]" value="<?= floatval($item['line_total']) ?>">
                        <input type="hidden" name="items[<?=$n?>][prev_claimed_qty]"    value="<?= $prevQty ?>">
                        <input type="hidden" name="items[<?=$n?>][prev_claimed_pct]"    value="<?= $prevPct ?>">
                        <input type="hidden" name="items[<?=$n?>][prev_claimed_amount]" value="<?= floatval($item['prev_claimed_amount']) ?>">
                        <input type="hidden" name="items[<?=$n?>][claim_type]" value="quantity">
                    </td>
                    
                    <!-- UOM -->
                    <td style="padding:12px 8px;text-align:center;color:#6b7280;font-weight:500;vertical-align:top;">
                        <?= htmlspecialchars($item['unit'] ?? '—') ?>
                    </td>
                    
                    <!-- AS PER WO Qty -->
                    <td style="padding:12px 12px;text-align:right;font-weight:600;color:#374151;vertical-align:top;">
                        <?= number_format($poQty, 2) ?>
                    </td>
                    
                    <!-- Previous Bills Qty (%) -->
                    <td style="padding:12px 12px;text-align:right;background:#f8f9ff;vertical-align:top;">
                        <div style="color:#374191;font-weight:600;"><?= number_format($prevQty, 2) ?></div>
                        <div style="color:#6366f1;font-size:11px;margin-top:2px;">(<?= number_format($prevPct, 1) ?>%)</div>
                    </td>
                    <!-- Claim Type -->
                    <td style="padding:8px 12px;background:#f0fdf4;text-align:center;vertical-align:top;">
                        <select name="items[<?=$n?>][claim_type]" class="claim-type-sel"
                                onchange="onClaimTypeChange(this)"
                                style="width:100%;padding:6px 4px;border:1px solid #d1fae5;border-radius:6px;font-size:11px;background:#fff;font-weight:600;">
                            <option value="quantity">Actual Qty</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </td>
                    
                    <!-- Present Bill Qty (%) -->
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
                        <input type="hidden" name="items[<?=$n?>][this_amount]" class="this-amount" value="0">
                    </td>
                    
                    <!-- Cumulative Qty (%) -->
                    <td style="padding:12px 12px;text-align:right;background:#f3f4f6;vertical-align:top;">
                        <div class="cumulative-display" style="color:#4b5563;font-weight:600;">
                            <div><?= number_format($prevQty, 2) ?></div>
                            <div style="font-size:11px;color:#6b7280;margin-top:2px;">(<?= number_format($prevPct, 1) ?>%)</div>
                        </div>
                    </td>
                    
                    <!-- Remarks -->
                    <td style="padding:8px 16px;vertical-align:top;">
                        <input type="text" name="items[<?=$n?>][remarks]" placeholder="Optional remarks..."
                               style="width:100%;padding:8px 6px;border:1px solid #e5e7eb;border-radius:6px;font-size:12px;background:#fff;">
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <tfoot style="display:none;">
                <!-- Total removed as per requirement -->
            </tfoot>
        </table>
        </div>
    </div>

    <!-- Notes Section -->
    <div style="background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px;margin-bottom:24px;">
        <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:8px;">NOTES / REMARKS</label>
        <textarea name="notes" rows="3" placeholder="Enter any additional notes or remarks for this RA bill..."
                  style="width:100%;padding:12px 16px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;resize:vertical;font-family:inherit;line-height:1.5;"></textarea>
    </div>

    <!-- Action Buttons -->
    <div style="display:flex;gap:16px;justify-content:flex-end;align-items:center;">
        <a href="/ergon/finance/measurement-sheet" 
           style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border:2px solid #e5e7eb;border-radius:8px;font-size:14px;font-weight:600;color:#374151;text-decoration:none;background:#fff;transition:all 0.2s;"
           onmouseover="this.style.borderColor='#d1d5db';this.style.backgroundColor='#f9fafb'"
           onmouseout="this.style.borderColor='#e5e7eb';this.style.backgroundColor='#fff'">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
            </svg>
            Cancel
        </a>
        
        <button type="submit" id="submitBtn"
                style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:#000080;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;transition:all 0.2s;box-shadow:0 2px 4px rgba(0,0,128,0.2);"
                onmouseover="this.style.backgroundColor='#000066';this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 8px rgba(0,0,128,0.3)'"
                onmouseout="this.style.backgroundColor='#000080';this.style.transform='translateY(0)';this.style.boxShadow='0 2px 4px rgba(0,0,128,0.2)'">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1zm3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4h-3.5zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V5z"/>
            </svg>
            Save & Print RA Bill
        </button>
    </div>
</form>

<script>
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
    const poQty   = parseFloat(row.dataset.poQty) || 0;
    const prevQty = parseFloat(row.dataset.prevQty) || 0;
    const qtyInp  = row.querySelector('.this-qty');
    const pctHid  = row.querySelector('.this-pct');
    const pctDisp = row.querySelector('.this-pct-display');
    const amtHid  = row.querySelector('.this-amount');
    const cumDisp = row.querySelector('.cumulative-display');
    const claimType = row.querySelector('.claim-type-sel').value;
    const inputMode = qtyInp.getAttribute('data-mode') || 'quantity';
    
    let thisQty = 0;
    let thisPct = 0;
    const inputValue = parseFloat(qtyInp.value) || 0;
    
    if (inputMode === 'quantity') {
        // User entered actual quantity
        thisQty = inputValue;
        thisPct = poQty > 0 ? (thisQty / poQty) * 100 : 0;
    } else {
        // User entered percentage
        thisPct = inputValue;
        thisQty = poQty > 0 ? (thisPct / 100) * poQty : 0;
    }
    
    const cumulativeQty = prevQty + thisQty;
    const cumulativePct = poQty > 0 ? (cumulativeQty / poQty) * 100 : 0;
    
    // Calculate amount
    const poRate = parseFloat(row.querySelector('input[name*="[po_unit_price]"]').value) || 0;
    const thisAmount = thisQty * poRate;
    
    // Update hidden fields
    pctHid.value = thisPct.toFixed(2);
    amtHid.value = thisAmount.toFixed(2);
    
    // Update displays based on input mode
    if (inputMode === 'quantity') {
        pctDisp.textContent = `(${thisPct.toFixed(1)}%)`;
        pctDisp.style.color = '#059669';
    } else {
        pctDisp.textContent = `(${thisQty.toFixed(2)} qty)`;
        pctDisp.style.color = '#2563eb';
    }
    
    cumDisp.innerHTML = `
        <div>${cumulativeQty.toFixed(2)}</div>
        <div style="font-size:11px;color:#6b7280;margin-top:2px;">(${cumulativePct.toFixed(1)}%)</div>
    `;
    
    // Validation styling
    let hasWarning = false;
    
    // Check for quantity overflow
    if (cumulativeQty > poQty) {
        qtyInp.style.borderColor = '#dc2626';
        qtyInp.style.backgroundColor = '#fef2f2';
        qtyInp.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.1)';
        pctDisp.style.color = '#dc2626';
        pctDisp.style.fontWeight = '700';
        cumDisp.style.color = '#dc2626';
        cumDisp.style.backgroundColor = '#fef2f2';
        hasWarning = true;
        qtyInp.title = `Warning: Total quantity (${cumulativeQty.toFixed(2)}) exceeds PO quantity (${poQty.toFixed(2)})`;
    } else {
        qtyInp.style.borderColor = '#d1fae5';
        qtyInp.style.backgroundColor = '#fff';
        qtyInp.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
        pctDisp.style.fontWeight = '500';
        cumDisp.style.color = '#4b5563';
        cumDisp.style.backgroundColor = '#f3f4f6';
        qtyInp.title = '';
    }
    
    // Check for percentage overflow (only in percentage mode)
    if (inputMode === 'percentage' && thisPct > 100) {
        hasWarning = true;
        qtyInp.style.borderColor = '#dc2626';
        qtyInp.style.backgroundColor = '#fef2f2';
        pctDisp.style.color = '#dc2626';
        pctDisp.style.fontWeight = '700';
        qtyInp.title = 'Warning: Percentage cannot exceed 100%';
    }
    
    updateSubmitButton();
}

function updateSubmitButton() {
    let hasWarnings = false;
    
    document.querySelectorAll('.this-qty').forEach(input => {
        if (input.style.borderColor === 'rgb(220, 38, 38)') {
            hasWarnings = true;
        }
    });
    
    const submitBtn = document.getElementById('submitBtn');
    
    if (hasWarnings) {
        submitBtn.style.backgroundColor = '#dc2626';
        submitBtn.innerHTML = `
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
            </svg>
            Save & Print (Warnings)
        `;
        submitBtn.title = 'There are validation warnings. Please review quantities before proceeding.';
    } else {
        submitBtn.style.backgroundColor = '#000080';
        submitBtn.innerHTML = `
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1zm3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4h-3.5zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V5z"/>
            </svg>
            Save & Print RA Bill
        `;
        submitBtn.title = '';
    }
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
