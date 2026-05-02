<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>RA Bill <?= htmlspecialchars($ra['ra_bill_number'] ?? '') ?> — <?= htmlspecialchars($ra['po_number'] ?? '') ?></title>
<style>
/* Phase 1: Base Layout & Customizable Structure */
:root {
    --primary-color: #000080;
    --secondary-color: #1a1a8c;
    --success-color: #0d5c2e;
    --text-color: #000;
    --border-color: #999;
    --light-bg: #f5f5f5;
    --font-size-base: 11px;
    --font-size-small: 9px;
    --font-size-large: 12px;
    --border-width: 1px;
    --border-style: solid;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: Arial, sans-serif;
    font-size: var(--font-size-base);
    color: var(--text-color);
    background: #e5e7eb;
    line-height: 1.4;
}

/* Print Controls */
.print-controls {
    position: fixed;
    top: 12px;
    right: 12px;
    z-index: 999;
    display: flex;
    gap: 8px;
}

.print-controls button {
    padding: 8px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
}

.btn-print { background: var(--primary-color); color: #fff; }
.btn-back { background: #6b7280; color: #fff; }
.btn-customize { background: #059669; color: #fff; }

@media print {
    .print-controls { display: none; }
    body { background: #fff; }
}

/* Page Layout */
.page {
    width: 210mm;
    min-height: 297mm;
    background: #fff;
    margin: 12px auto;
    padding: 10mm 12mm;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    position: relative;
}

@media print {
    .page {
        margin: 0;
        box-shadow: none;
        padding: 8mm 10mm;
    }
    .page-break { page-break-before: always; }
    @page { size: A4 portrait; margin: 6mm; }
}

/* Customizable Header Layouts */
.header-layout-standard .hdr-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
}

.header-layout-compact .hdr-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 4px;
}

.header-layout-detailed .hdr-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
    border: var(--border-width) var(--border-style) var(--border-color);
}

/* Logo Positioning */
.logo-position-left .logo-cell { width: 80px; text-align: left; }
.logo-position-center .logo-cell { width: 80px; text-align: center; }
.logo-position-right .logo-cell { width: 80px; text-align: right; }

.logo-cell, .seal-cell {
    width: 72px;
    text-align: center;
    vertical-align: middle;
    padding: 4px;
}

.placeholder-box {
    width: 64px;
    height: 64px;
    border: 2px dashed #aaa;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    color: #999;
    text-align: center;
    line-height: 1.3;
}

.seal-circle { border-radius: 50%; }

.co-cell {
    text-align: center;
    vertical-align: middle;
    padding: 0 8px;
}

.co-name {
    font-size: 15px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 2px;
}

.co-sub {
    font-size: 9px;
    color: #555;
    margin-top: 2px;
}

/* Document Title */
.doc-title {
    text-align: center;
    font-size: var(--font-size-large);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    border: 2px solid var(--text-color);
    padding: 6px;
    margin: 8px 0;
    background: #e8eaf6;
}

/* Info Grid */
.info-grid {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
}

.info-grid td {
    padding: 4px 8px;
    font-size: 10px;
    border: var(--border-width) var(--border-style) var(--border-color);
}

.info-grid .lbl {
    font-weight: 700;
    background: var(--light-bg);
    width: 120px;
}

/* Border Styles */
.border-style-standard { --border-width: 1px; }
.border-style-thick { --border-width: 2px; }
.border-style-minimal { --border-width: 0.5px; }

/* Font Size Classes */
.font-size-small { --font-size-base: 10px; --font-size-small: 8px; --font-size-large: 11px; }
.font-size-medium { --font-size-base: 11px; --font-size-small: 9px; --font-size-large: 12px; }
.font-size-large { --font-size-base: 12px; --font-size-small: 10px; --font-size-large: 13px; }

/* Measurement Table Base */
.ms-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
    font-size: var(--font-size-small);
}

.ms-table th {
    background: var(--primary-color);
    color: #fff;
    padding: 6px 4px;
    text-align: center;
    border: var(--border-width) var(--border-style) var(--text-color);
    font-size: var(--font-size-small);
    font-weight: 700;
}

.ms-table th.prev-hdr { background: var(--secondary-color); }
.ms-table th.this-hdr { background: var(--success-color); }
.ms-table th.cumulative-hdr { background: #374151; }

.ms-table td {
    padding: 4px 5px;
    border: var(--border-width) var(--border-style) var(--border-color);
    font-size: var(--font-size-small);
    text-align: center;
    vertical-align: middle;
}

.ms-table td.desc { text-align: left; }
.ms-table tr:nth-child(even) td { background: #fafafa; }
.ms-table .total-row td {
    font-weight: 700;
    background: #f0f0f0;
    border-top: 2px solid var(--text-color);
}

/* Column Visibility Classes */
.hide-prev-claimed .prev-claimed-col { display: none; }
.hide-cumulative .cumulative-col { display: none; }

/* Summary Table */
.sum-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.sum-table td {
    padding: 5px 8px;
    border: var(--border-width) var(--border-style) var(--border-color);
    font-size: 10px;
}

.sum-table .lbl {
    font-weight: 700;
    background: var(--light-bg);
    width: 200px;
}

.sum-table .val {
    text-align: right;
    font-weight: 700;
}

/* Signature Table */
.sig-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.sig-table td {
    padding: 8px 12px;
    text-align: center;
    vertical-align: bottom;
    width: 33%;
}

.sig-line {
    border-top: 1px solid var(--text-color);
    margin-top: 35px;
    padding-top: 6px;
    font-size: var(--font-size-small);
    font-weight: 700;
}

.ref-box {
    font-size: var(--font-size-small);
    color: #666;
    margin-top: 8px;
    text-align: right;
}

/* Clearance Sheet Styles */
.cl-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.cl-table th {
    background: var(--primary-color);
    color: #fff;
    padding: 8px;
    font-size: 10px;
    text-align: center;
    border: var(--border-width) var(--border-style) var(--text-color);
}

.cl-table td {
    padding: 20px 8px 8px;
    border: var(--border-width) var(--border-style) var(--border-color);
    font-size: 10px;
    text-align: center;
    vertical-align: bottom;
    min-height: 60px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .page {
        width: 100%;
        margin: 0;
        padding: 8px;
    }
    
    .ms-table {
        font-size: 8px;
    }
    
    .ms-table th,
    .ms-table td {
        padding: 2px 3px;
    }
}
</style>
</head>
<body class="font-size-medium border-style-standard">

<div class="print-controls">
    <button class="btn-back" onclick="window.close()">✕ Close</button>
    <button class="btn-customize" onclick="showCustomizer()">🎨 Customize</button>
    <button class="btn-print" onclick="window.print()">🖨 Print</button>
</div>

<!-- Format Customizer -->
<div id="formatCustomizer" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:1000;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:12px;padding:24px;width:90%;max-width:500px;">
        <h3 style="margin-bottom:20px;">Customize Print Format</h3>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Logo Position</label>
                <select id="logoPos" onchange="updateFormat()">
                    <option value="left">Left</option>
                    <option value="center">Center</option>
                    <option value="right">Right</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Header Style</label>
                <select id="headerStyle" onchange="updateFormat()">
                    <option value="standard">Standard</option>
                    <option value="compact">Compact</option>
                    <option value="detailed">Detailed</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Border Style</label>
                <select id="borderStyle" onchange="updateFormat()">
                    <option value="standard">Standard</option>
                    <option value="thick">Thick</option>
                    <option value="minimal">Minimal</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px;">Font Size</label>
                <select id="fontSize" onchange="updateFormat()">
                    <option value="small">Small</option>
                    <option value="medium" selected>Medium</option>
                    <option value="large">Large</option>
                </select>
            </div>
        </div>
        
        <div style="margin-bottom:20px;">
            <label><input type="checkbox" id="showPrev" onchange="updateFormat()" checked> Show Previous Claimed</label><br>
            <label><input type="checkbox" id="showCum" onchange="updateFormat()" checked> Show Cumulative</label><br>
            <label><input type="checkbox" id="showClearance" onchange="updateFormat()" checked> Include Clearance Sheet</label>
        </div>
        
        <div style="display:flex;gap:12px;justify-content:flex-end;">
            <button onclick="hideCustomizer()" style="padding:8px 16px;border:1px solid #ccc;background:#fff;">Cancel</button>
            <button onclick="saveFormat()" style="padding:8px 16px;background:#000080;color:#fff;border:none;">Apply</button>
        </div>
    </div>
</div>

<?php if ($error || !$ra): ?>
<div style="padding:40px;text-align:center;color:#dc2626;font-size:14px;">
    <?= htmlspecialchars($error ?? 'RA Bill not found') ?>
</div>
<?php else:
    $poNum = $ra['po_number'];
    $raNum = $ra['ra_bill_number'];
    $billDate = $ra['bill_date'];
    $project = $ra['project'] ?? '—';
    $contractor = $ra['contractor'] ?? '—';
    $coName = $po['company_name'] ?? '—';
    $custName = $po['customer_name'] ?? '—';
    $totalClaimed = floatval($ra['total_claimed']);
    
    $prevTotal = array_sum(array_column($items, 'prev_claimed_amount'));
    $cumTotal = array_sum(array_column($items, 'cumulative_amount'));
    $poValue = floatval($po['total_amount'] ?? 0);
    $balance = $poValue - $cumTotal;
?>

<!-- Page 1: Measurement Sheet -->
<div class="page header-layout-standard logo-position-left" id="measurementPage">
    
    <table class="hdr-table">
        <tr>
            <td class="logo-cell">
                <div class="placeholder-box">Insert<br>Logo</div>
            </td>
            <td class="co-cell">
                <div class="co-name"><?= htmlspecialchars($coName) ?></div>
                <div class="co-sub">Civil &amp; Construction Works</div>
                <div class="co-sub">GSTIN: <?= htmlspecialchars($po['company_gstin'] ?? '') ?></div>
            </td>
            <td class="seal-cell">
                <div class="placeholder-box seal-circle">Insert<br>Seal</div>
            </td>
        </tr>
    </table>

    <div class="doc-title">Measurement Sheet &nbsp;|&nbsp; <?= htmlspecialchars($raNum) ?></div>

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
            <td style="font-weight:700;color:var(--primary-color);"><?= htmlspecialchars($raNum) ?></td>
        </tr>
        <tr>
            <td class="lbl">CLIENT</td>
            <td><?= htmlspecialchars($custName) ?></td>
            <td class="lbl">DATE</td>
            <td><?= date('d-M-Y', strtotime($billDate)) ?></td>
        </tr>
    </table>

    <table class="ms-table" id="measurementTable">
        <thead>
            <tr>
                <th rowspan="2" style="width:28px;">S.No</th>
                <th rowspan="2" style="min-width:150px;">Description of Work</th>
                <th rowspan="2" style="width:36px;">Unit</th>
                <th rowspan="2" style="width:52px;">PO Qty</th>
                <th rowspan="2" style="width:60px;">PO Rate (₹)</th>
                <th rowspan="2" style="width:68px;">PO Amount (₹)</th>
                <th colspan="3" class="prev-hdr prev-claimed-col">Previous Claimed</th>
                <th colspan="3" class="this-hdr">This Bill</th>
                <th colspan="2" style="background:#374151;">Cumulative</th>
            </tr>
            <tr>
                <th class="prev-hdr prev-claimed-col">Qty</th>
                <th class="prev-hdr prev-claimed-col">%</th>
                <th class="prev-hdr prev-claimed-col">Amount (₹)</th>
                <th class="this-hdr">Qty</th>
                <th class="this-hdr">%</th>
                <th class="this-hdr">Amount (₹)</th>
                <th style="background:#374151;" class="cumulative-col">Qty</th>
                <th style="background:#374151;" class="cumulative-col">Amount (₹)</th>
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
            <td><?= number_format(floatval($item['po_quantity']),3) ?></td>
            <td><?= number_format(floatval($item['po_unit_price']),2) ?></td>
            <td><?= number_format(floatval($item['po_line_total']),2) ?></td>
            <td class="prev-claimed-col" style="background:#f0f0ff;"><?= number_format(floatval($item['prev_claimed_qty']),3) ?></td>
            <td class="prev-claimed-col" style="background:#f0f0ff;"><?= number_format(floatval($item['prev_claimed_pct']),2) ?>%</td>
            <td class="prev-claimed-col" style="background:#f0f0ff;"><?= number_format(floatval($item['prev_claimed_amount']),2) ?></td>
            <td style="background:#f0fff4;font-weight:700;"><?= number_format(floatval($item['this_qty']),3) ?></td>
            <td style="background:#f0fff4;font-weight:700;"><?= number_format(floatval($item['this_pct']),2) ?>%</td>
            <td style="background:#f0fff4;font-weight:700;"><?= number_format(floatval($item['this_amount']),2) ?></td>
            <td class="cumulative-col" style="background:#f5f5f5;"><?= number_format(floatval($item['cumulative_qty']),3) ?></td>
            <td class="cumulative-col" style="background:#f5f5f5;font-weight:700;"><?= number_format(floatval($item['cumulative_amount']),2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" style="text-align:right;padding-right:8px;">TOTAL</td>
                <td>₹<?= number_format($poValue,2) ?></td>
                <td class="prev-claimed-col"></td><td class="prev-claimed-col"></td>
                <td class="prev-claimed-col">₹<?= number_format($prevTotal,2) ?></td>
                <td></td><td></td>
                <td>₹<?= number_format($totalClaimed,2) ?></td>
                <td class="cumulative-col"></td>
                <td class="cumulative-col">₹<?= number_format($cumTotal,2) ?></td>
            </tr>
        </tfoot>
    </table>

    <table class="sum-table">
        <tr>
            <td class="lbl">PO Contract Value</td>
            <td class="val">₹<?= number_format($poValue,2) ?></td>
            <td class="lbl">Total Claimed to Date</td>
            <td class="val">₹<?= number_format($cumTotal,2) ?></td>
        </tr>
        <tr>
            <td class="lbl">This RA Bill Amount</td>
            <td class="val" style="color:#059669;">₹<?= number_format($totalClaimed,2) ?></td>
            <td class="lbl">Balance Remaining</td>
            <td class="val" style="color:<?= $balance < 0 ? '#dc2626' : '#374151' ?>;">₹<?= number_format($balance,2) ?></td>
        </tr>
    </table>

    <table class="sig-table">
        <tr>
            <td><div class="sig-line">Prepared By<br><span style="font-weight:400;">Name &amp; Designation</span></div></td>
            <td><div class="sig-line">Checked By<br><span style="font-weight:400;">Site Engineer</span></div></td>
            <td><div class="sig-line">Approved By<br><span style="font-weight:400;">Project Manager</span></div></td>
        </tr>
    </table>

    <div class="ref-box">Generated: <?= date('d-M-Y H:i') ?> &nbsp;|&nbsp; <?= htmlspecialchars($raNum) ?> &nbsp;|&nbsp; <?= htmlspecialchars($poNum) ?></div>
</div>

<!-- Page 2: Clearance Sheet -->
<div class="page page-break header-layout-standard logo-position-left" id="clearancePage">
    
    <table class="hdr-table">
        <tr>
            <td class="logo-cell">
                <div class="placeholder-box">Insert<br>Logo</div>
            </td>
            <td class="co-cell">
                <div class="co-name"><?= htmlspecialchars($coName) ?></div>
                <div class="co-sub">Civil &amp; Construction Works</div>
                <div class="co-sub">GSTIN: <?= htmlspecialchars($po['company_gstin'] ?? '') ?></div>
            </td>
            <td class="seal-cell">
                <div class="placeholder-box seal-circle">Insert<br>Seal</div>
            </td>
        </tr>
    </table>

    <div class="doc-title">Clearance Sheet &nbsp;|&nbsp; <?= htmlspecialchars($raNum) ?></div>

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
            <td style="font-weight:700;color:var(--primary-color);"><?= htmlspecialchars($raNum) ?></td>
        </tr>
        <tr>
            <td class="lbl">CLIENT</td>
            <td><?= htmlspecialchars($custName) ?></td>
            <td class="lbl">DATE</td>
            <td><?= date('d-M-Y', strtotime($billDate)) ?></td>
        </tr>
    </table>

    <!-- Clearance table: S.No | Department | Comments | Incharge | Signature -->
    <table class="cl-table">
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

<script>
function showCustomizer() {
    document.getElementById('formatCustomizer').style.display = 'block';
}

function hideCustomizer() {
    document.getElementById('formatCustomizer').style.display = 'none';
}

function updateFormat() {
    const body = document.body;
    const page = document.getElementById('measurementPage');
    
    // Update font size
    const fontSize = document.getElementById('fontSize').value;
    body.className = body.className.replace(/font-size-\w+/, `font-size-${fontSize}`);
    
    // Update border style
    const borderStyle = document.getElementById('borderStyle').value;
    body.className = body.className.replace(/border-style-\w+/, `border-style-${borderStyle}`);
    
    // Update header layout
    const headerStyle = document.getElementById('headerStyle').value;
    page.className = page.className.replace(/header-layout-\w+/, `header-layout-${headerStyle}`);
    
    // Update logo position
    const logoPos = document.getElementById('logoPos').value;
    page.className = page.className.replace(/logo-position-\w+/, `logo-position-${logoPos}`);
    
    // Toggle column visibility
    const showPrev = document.getElementById('showPrev').checked;
    const showCum = document.getElementById('showCum').checked;
    
    if (!showPrev) {
        body.classList.add('hide-prev-claimed');
    } else {
        body.classList.remove('hide-prev-claimed');
    }
    
    if (!showCum) {
        body.classList.add('hide-cumulative');
    } else {
        body.classList.remove('hide-cumulative');
    }
}

function saveFormat() {
    updateFormat();
    
    // Apply same format to clearance page
    const clearancePage = document.getElementById('clearancePage');
    if (clearancePage) {
        const measurementPage = document.getElementById('measurementPage');
        clearancePage.className = measurementPage.className;
    }
    
    // Toggle clearance sheet visibility
    const showClearance = document.getElementById('showClearance').checked;
    if (clearancePage) {
        clearancePage.style.display = showClearance ? 'block' : 'none';
    }
    
    hideCustomizer();
    alert('Format applied! You can now print with the customized layout.');
}
</script>

<?php endif; ?>
</body>
</html>