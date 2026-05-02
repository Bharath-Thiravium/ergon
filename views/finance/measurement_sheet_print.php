<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>RA Bill <?= htmlspecialchars($ra['ra_bill_number'] ?? '') ?> — <?= htmlspecialchars($ra['po_number'] ?? '') ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:Arial,sans-serif;font-size:11px;color:#000;background:#e5e7eb;}
.print-controls{position:fixed;top:12px;right:12px;z-index:999;display:flex;gap:8px;}
.print-controls button{padding:8px 18px;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;}
.btn-print{background:#000080;color:#fff;}
.btn-back{background:#6b7280;color:#fff;}
@media print{.print-controls{display:none;}body{background:#fff;}}

.page{
    width:210mm;min-height:297mm;background:#fff;
    margin:12px auto;padding:10mm 12mm;
    box-shadow:0 2px 8px rgba(0,0,0,0.15);
}
@media print{
    .page{margin:0;box-shadow:none;padding:8mm 10mm;}
    .page-break{page-break-before:always;}
    @page{size:A4 portrait;margin:6mm;}
}

/* ── Header ── */
.hdr-table{width:100%;border-collapse:collapse;margin-bottom:3px;}
.logo-cell,.seal-cell{width:72px;text-align:center;vertical-align:middle;}
.placeholder-box{width:64px;height:64px;border:2px dashed #aaa;display:inline-flex;align-items:center;justify-content:center;font-size:8px;color:#999;text-align:center;line-height:1.3;}
.seal-circle{border-radius:50%;}
.co-cell{text-align:center;vertical-align:middle;padding:0 8px;}
.co-name{font-size:15px;font-weight:700;text-transform:uppercase;letter-spacing:1px;}
.co-sub{font-size:9px;color:#555;margin-top:2px;}

.doc-title{text-align:center;font-size:12px;font-weight:700;text-transform:uppercase;
    letter-spacing:2px;border:2px solid #000;padding:5px;margin:6px 0 5px;background:#e8eaf6;}

/* ── Info grid ── */
.info-grid{width:100%;border-collapse:collapse;margin-bottom:5px;}
.info-grid td{padding:3px 7px;font-size:10px;border:1px solid #999;}
.info-grid .lbl{font-weight:700;background:#f5f5f5;width:120px;}

/* ── Measurement table ── */
.ms-table{width:100%;border-collapse:collapse;margin-top:6px;}
.ms-table th{background:#000080;color:#fff;padding:5px 5px;font-size:9px;text-align:center;border:1px solid #000;}
.ms-table th.prev-hdr{background:#1a1a8c;}
.ms-table th.this-hdr{background:#0d5c2e;}
.ms-table td{padding:4px 5px;border:1px solid #bbb;font-size:9px;text-align:center;vertical-align:middle;}
.ms-table td.desc{text-align:left;}
.ms-table tr:nth-child(even) td{background:#fafafa;}
.ms-table .total-row td{font-weight:700;background:#f0f0f0;border-top:2px solid #000;}

/* ── Clearance table ── */
.cl-table{width:100%;border-collapse:collapse;margin-top:8px;}
.cl-table th{background:#000080;color:#fff;padding:6px 8px;font-size:10px;text-align:center;border:1px solid #000;}
.cl-table td{padding:18px 8px 6px;border:1px solid #bbb;font-size:10px;text-align:center;vertical-align:bottom;}

/* ── Summary ── */
.sum-table{width:100%;border-collapse:collapse;margin-top:8px;}
.sum-table td{padding:4px 8px;border:1px solid #bbb;font-size:10px;}
.sum-table .lbl{font-weight:700;background:#f5f5f5;width:200px;}
.sum-table .val{text-align:right;font-weight:700;}

/* ── Sig ── */
.sig-table{width:100%;border-collapse:collapse;margin-top:16px;}
.sig-table td{padding:5px 10px;text-align:center;vertical-align:bottom;width:33%;}
.sig-line{border-top:1px solid #000;margin-top:28px;padding-top:4px;font-size:9px;font-weight:700;}

.ref-box{font-size:9px;color:#666;margin-top:6px;text-align:right;}
</style>
</head>
<body>

<div class="print-controls">
    <button class="btn-back" onclick="history.back()">✕ Close</button>
    <button class="btn-print" onclick="window.print()">🖨 Print</button>
</div>

<?php if ($error || !$ra): ?>
<div style="padding:40px;text-align:center;color:#dc2626;font-size:14px;">
    <?= htmlspecialchars($error ?? 'RA Bill not found') ?>
</div>
<?php else:
    $poNum      = $ra['po_number'];
    $raNum      = $ra['ra_bill_number'];
    $billDate   = $ra['bill_date'];
    $project    = $ra['project']    ?? '—';
    $contractor = $ra['contractor'] ?? '—';
    $coName     = $po['company_name']   ?? '—';
    $custName   = $po['customer_name']  ?? '—';
    $totalClaimed = floatval($ra['total_claimed']);

    // totals
    $prevTotal = array_sum(array_column($items, 'prev_claimed_amount'));
    $cumTotal  = array_sum(array_column($items, 'cumulative_amount'));
    $poValue   = floatval($po['total_amount'] ?? 0);
    $balance   = $poValue - $cumTotal;
?>

<!-- ═══════════════════════════════════════════════════════════
     PAGE 1 — MEASUREMENT SHEET
     ═══════════════════════════════════════════════════════════ -->
<div class="page">

    <table class="hdr-table">
        <tr>
            <td class="logo-cell"><?php 
                // Use selected logo or fallback logic
                $selectedLogo = $ra['selected_logo'] ?? null;
                
                if ($selectedLogo) {
                    // Try PNG first, then JPG
                    $logoPath = "/ergon/storage/company/logos/{$selectedLogo}.png";
                    $logoFile = __DIR__ . "/../../storage/company/logos/{$selectedLogo}.png";
                    
                    if (!file_exists($logoFile)) {
                        $logoPath = "/ergon/storage/company/logos/{$selectedLogo}.jpg";
                        $logoFile = __DIR__ . "/../../storage/company/logos/{$selectedLogo}.jpg";
                    }
                } else {
                    // Fallback to company-specific or default
                    $companyId = $po['company_id'] ?? 'default';
                    $logoPath = "/ergon/storage/company/logos/{$companyId}.png";
                    $logoFile = __DIR__ . "/../../storage/company/logos/{$companyId}.png";
                    
                    if (!file_exists($logoFile)) {
                        $logoPath = "/ergon/storage/company/logos/{$companyId}.jpg";
                        $logoFile = __DIR__ . "/../../storage/company/logos/{$companyId}.jpg";
                    }
                    
                    if (!file_exists($logoFile)) {
                        $logoPath = "/ergon/storage/company/logos/default.png";
                        $logoFile = __DIR__ . "/../../storage/company/logos/default.png";
                        
                        if (!file_exists($logoFile)) {
                            $logoPath = "/ergon/storage/company/logos/default.jpg";
                            $logoFile = __DIR__ . "/../../storage/company/logos/default.jpg";
                        }
                    }
                }
                
                if (file_exists($logoFile)) {
                    echo '<img src="' . $logoPath . '" alt="Company Logo" style="max-width:64px;max-height:64px;object-fit:contain;">';
                } else {
                    echo '<div class="placeholder-box">Insert<br>Logo</div>';
                }
            ?></td>
            <td class="co-cell">
                <div class="co-name"><?= htmlspecialchars($coName) ?></div>
                <div class="co-sub">Civil &amp; Construction Works</div>
                <div class="co-sub" style="margin-top:2px;">GSTIN: <?= htmlspecialchars($po['company_gstin'] ?? '') ?></div>
            </td>
            <td class="seal-cell">
                <!-- Seal removed from header -->
                <div class="placeholder-box seal-circle" style="visibility:hidden;">Space</div>
            </td>
        </tr>
    </table>

    <div class="doc-title">Measurement Sheet &nbsp;|&nbsp; RA-07</div>

    <table class="info-grid">
        <tr>
            <td class="lbl">PROJECT</td>
            <td><?= htmlspecialchars($project) ?></td>
            <td class="lbl">PO / WO REF</td>
            <td><?= htmlspecialchars($poNum) ?></td>
        </tr>
        <tr>
            <td class="lbl">CONTRACTOR / VENDOR</td>
            <td><?= htmlspecialchars($contractor) ?></td>
            <td class="lbl">RA BILL NO</td>
            <td style="font-weight:700;color:#000080;"><?= htmlspecialchars($raNum) ?></td>
        </tr>
        <tr>
            <td class="lbl">CLIENT</td>
            <td><?= htmlspecialchars($custName) ?></td>
            <td class="lbl">DATE</td>
            <td><?= date('d-M-Y', strtotime($billDate)) ?></td>
        </tr>
    </table>

    <table class="ms-table">
        <thead>
            <tr>
                <th style="width:40px;">S.NO</th>
                <th style="min-width:200px;">Description</th>
                <th style="width:50px;">UOM</th>
                <th style="width:80px;">AS PER WO<br><small>Qty</small></th>
                <th style="width:100px;">Previous Bills<br><small>Qty (%)</small></th>
                <th style="width:100px;">Present Bill<br><small>Qty (%)</small></th>
                <th style="width:100px;">Cumulative Bill<br><small>Qty (%)</small></th>
                <th style="width:120px;">Remarks</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><?= $item['line_number'] ?></td>
            <td class="desc">
                <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                <?php if (!empty($item['description'])): ?>
                <div style="font-size:8px;color:#555;margin-top:1px;"><?= htmlspecialchars(substr($item['description'],0,100)) ?></div>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($item['unit'] ?? '') ?></td>
            <td><?= number_format(floatval($item['po_quantity']),2) ?></td>
            <td style="background:#f0f0ff;"><?php 
                $prevQty = floatval($item['prev_claimed_qty']);
                $prevPct = floatval($item['prev_claimed_pct']);
                $poQty = floatval($item['po_quantity']);
                
                // Calculate percentage from quantity if percentage is 0 but quantity exists
                if ($prevPct == 0 && $prevQty > 0 && $poQty > 0) {
                    $prevPct = ($prevQty / $poQty) * 100;
                }
                // Calculate quantity from percentage if quantity is 0 but percentage exists
                else if ($prevQty == 0 && $prevPct > 0 && $poQty > 0) {
                    $prevQty = ($prevPct / 100) * $poQty;
                }
                
                echo number_format($prevQty, 2) . ' (' . number_format($prevPct, 1) . '%)';
            ?></td>
            <td style="background:#f0fff4;font-weight:700;"><?php 
                $thisQty = floatval($item['this_qty']);
                $thisPct = floatval($item['this_pct']);
                $poQty = floatval($item['po_quantity']);
                
                // Calculate percentage from quantity if percentage is 0 but quantity exists
                if ($thisPct == 0 && $thisQty > 0 && $poQty > 0) {
                    $thisPct = ($thisQty / $poQty) * 100;
                }
                // Calculate quantity from percentage if quantity is 0 but percentage exists
                else if ($thisQty == 0 && $thisPct > 0 && $poQty > 0) {
                    $thisQty = ($thisPct / 100) * $poQty;
                }
                
                echo number_format($thisQty, 2) . ' (' . number_format($thisPct, 1) . '%)';
            ?></td>
            <td style="background:#f5f5f5;font-weight:700;"><?php 
                $cumQty = floatval($item['cumulative_qty']);
                $poQty = floatval($item['po_quantity']);
                
                // Calculate cumulative percentage from quantity
                $cumPct = $poQty > 0 ? ($cumQty / $poQty * 100) : 0;
                
                // If cumulative quantity is 0, calculate from previous + this
                if ($cumQty == 0) {
                    $prevQty = floatval($item['prev_claimed_qty']);
                    $thisQty = floatval($item['this_qty']);
                    $prevPct = floatval($item['prev_claimed_pct']);
                    $thisPct = floatval($item['this_pct']);
                    
                    // Calculate quantities from percentages if needed
                    if ($prevQty == 0 && $prevPct > 0 && $poQty > 0) {
                        $prevQty = ($prevPct / 100) * $poQty;
                    }
                    if ($thisQty == 0 && $thisPct > 0 && $poQty > 0) {
                        $thisQty = ($thisPct / 100) * $poQty;
                    }
                    
                    $cumQty = $prevQty + $thisQty;
                    $cumPct = $poQty > 0 ? ($cumQty / $poQty * 100) : 0;
                }
                
                echo number_format($cumQty, 2) . ' (' . number_format($cumPct, 1) . '%)';
            ?></td>
            <td style="font-size:8px;"></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>



    <table class="sig-table">
        <tr>
            <td>
                <?php 
                // Add company seal above "Prepared By" section with larger size
                $selectedSeal = $ra['selected_seal'] ?? null;
                
                if ($selectedSeal) {
                    // Try PNG first, then JPG
                    $sealPath = "/ergon/storage/company/seals/{$selectedSeal}.png";
                    $sealFile = __DIR__ . "/../../storage/company/seals/{$selectedSeal}.png";
                    
                    if (!file_exists($sealFile)) {
                        $sealPath = "/ergon/storage/company/seals/{$selectedSeal}.jpg";
                        $sealFile = __DIR__ . "/../../storage/company/seals/{$selectedSeal}.jpg";
                    }
                } else {
                    // Fallback to company-specific or default
                    $companyId = $po['company_id'] ?? 'default';
                    $sealPath = "/ergon/storage/company/seals/{$companyId}.png";
                    $sealFile = __DIR__ . "/../../storage/company/seals/{$companyId}.png";
                    
                    if (!file_exists($sealFile)) {
                        $sealPath = "/ergon/storage/company/seals/{$companyId}.jpg";
                        $sealFile = __DIR__ . "/../../storage/company/seals/{$companyId}.jpg";
                    }
                    
                    if (!file_exists($sealFile)) {
                        $sealPath = "/ergon/storage/company/seals/default.png";
                        $sealFile = __DIR__ . "/../../storage/company/seals/default.png";
                        
                        if (!file_exists($sealFile)) {
                            $sealPath = "/ergon/storage/company/seals/default.jpg";
                            $sealFile = __DIR__ . "/../../storage/company/seals/default.jpg";
                        }
                    }
                }
                
                if (file_exists($sealFile)) {
                    echo '<div style="text-align:center;margin-bottom:8px;">';
                    echo '<img src="' . $sealPath . '" alt="Company Seal" style="width:100px;height:100px;object-fit:contain;border-radius:50%;">';
                    echo '</div>';
                }
                ?>
                <div class="sig-line">Prepared By<br><span style="font-weight:400;">Name &amp; Designation</span></div>
            </td>
            <td><div class="sig-line">Checked By<br><span style="font-weight:400;">Site Engineer</span></div></td>
            <td><div class="sig-line">Approved By<br><span style="font-weight:400;">Project Manager</span></div></td>
        </tr>
    </table>

    <div class="ref-box">Generated: <?= date('d-M-Y H:i') ?> &nbsp;|&nbsp; <?= htmlspecialchars($raNum) ?> &nbsp;|&nbsp; <?= htmlspecialchars($poNum) ?></div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     PAGE 2 — CLEARANCE SHEET
     ═══════════════════════════════════════════════════════════ -->
<div class="page page-break">

    <table class="hdr-table">
        <tr>
            <td class="logo-cell"><?php 
                // Use selected logo (same logic as page 1)
                $selectedLogo = $ra['selected_logo'] ?? null;
                
                if ($selectedLogo) {
                    $logoPath = "/ergon/storage/company/logos/{$selectedLogo}.png";
                    $logoFile = __DIR__ . "/../../storage/company/logos/{$selectedLogo}.png";
                } else {
                    $logoPath = "/ergon/storage/company/logos/" . ($po['company_id'] ?? 'default') . ".png";
                    $logoFile = __DIR__ . "/../../storage/company/logos/" . ($po['company_id'] ?? 'default') . ".png";
                    
                    if (!file_exists($logoFile)) {
                        $logoPath = "/ergon/storage/company/logos/default.png";
                        $logoFile = __DIR__ . "/../../storage/company/logos/default.png";
                    }
                }
                
                if (file_exists($logoFile)) {
                    echo '<img src="' . $logoPath . '" alt="Company Logo" style="max-width:64px;max-height:64px;object-fit:contain;">';
                } else {
                    echo '<div class="placeholder-box">Insert<br>Logo</div>';
                }
            ?></td>
            <td class="co-cell">
                <div class="co-name"><?= htmlspecialchars($coName) ?></div>
                <div class="co-sub">Civil &amp; Construction Works</div>
                <div class="co-sub" style="margin-top:2px;">GSTIN: <?= htmlspecialchars($po['company_gstin'] ?? '') ?></div>
            </td>
            <td class="seal-cell">
                <!-- Seal removed from header -->
                <div class="placeholder-box seal-circle" style="visibility:hidden;">Space</div>
            </td>
        </tr>
    </table>

    <div class="doc-title">Clearance Sheet &nbsp;|&nbsp; RA-07</div>

    <table class="info-grid">
        <tr>
            <td class="lbl">PROJECT</td>
            <td><?= htmlspecialchars($project) ?></td>
            <td class="lbl">PO / WO REF</td>
            <td><?= htmlspecialchars($poNum) ?></td>
        </tr>
        <tr>
            <td class="lbl">CONTRACTOR / VENDOR</td>
            <td><?= htmlspecialchars($contractor) ?></td>
            <td class="lbl">RA BILL NO</td>
            <td style="font-weight:700;color:#000080;"><?= htmlspecialchars($raNum) ?></td>
        </tr>
        <tr>
            <td class="lbl">CLIENT</td>
            <td><?= htmlspecialchars($custName) ?></td>
            <td class="lbl">DATE</td>
            <td><?= date('d-M-Y', strtotime($billDate)) ?></td>
        </tr>
    </table>

    <!-- Clearance table: S.No | Department | Comments | Incharge | Signature -->
    <table class="cl-table" style="margin-top:10px;">
        <thead>
            <tr>
                <th style="width:40px;">S.No</th>
                <th>DEPARTMENT</th>
                <th style="min-width:200px;">COMMENTS</th>
                <th style="width:130px;">INCHARGE</th>
                <th style="width:110px;">SIGNATURE</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $depts = ['Civil / Site','Electrical','Mechanical','Quality / QC','Safety / HSE','Accounts / Finance','Project Manager','Client Representative'];
        foreach ($depts as $idx => $dept):
        ?>
        <tr>
            <td><?= $idx+1 ?></td>
            <td style="text-align:left;padding-left:8px;font-weight:600;"><?= $dept ?></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- RA Bill Summary for clearance -->
    <table class="sum-table" style="margin-top:12px;">
        <tr>
            <td class="lbl">PO Contract Value</td>
            <td class="val">₹<?= number_format($poValue,2) ?></td>
            <td class="lbl">This RA Bill (<?= htmlspecialchars($raNum) ?>)</td>
            <td class="val" style="color:#059669;">₹<?= number_format($totalClaimed,2) ?></td>
        </tr>
        <tr>
            <td class="lbl">Total Claimed to Date</td>
            <td class="val">₹<?= number_format($cumTotal,2) ?></td>
            <td class="lbl">Balance Remaining</td>
            <td class="val">₹<?= number_format($balance,2) ?></td>
        </tr>
    </table>

    <div style="margin-top:10px;padding:6px 10px;border:1px solid #e5e7eb;background:#fffbeb;font-size:9px;color:#92400e;">
        <strong>Note:</strong> This Clearance Sheet must be signed by all department incharges before payment processing.
        RA Bill <?= htmlspecialchars($raNum) ?> is valid only upon full clearance.
    </div>

    <table class="sig-table">
        <tr>
            <td><div class="sig-line">Prepared By<br><span style="font-weight:400;">Name &amp; Designation</span></div></td>
            <td><div class="sig-line">Verified By<br><span style="font-weight:400;">Site Engineer</span></div></td>
            <td><div class="sig-line">Authorized By<br><span style="font-weight:400;">Project Manager / Owner</span></div></td>
        </tr>
    </table>

    <div class="ref-box">Generated: <?= date('d-M-Y H:i') ?> &nbsp;|&nbsp; <?= htmlspecialchars($raNum) ?> &nbsp;|&nbsp; <?= htmlspecialchars($poNum) ?></div>
</div>

<?php endif; ?>
</body>
</html>
