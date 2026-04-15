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
    <button class="btn-back" onclick="window.close()">✕ Close</button>
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
            <td class="logo-cell"><div class="placeholder-box">Insert<br>Logo</div></td>
            <td class="co-cell">
                <div class="co-name"><?= htmlspecialchars($coName) ?></div>
                <div class="co-sub">Civil &amp; Construction Works</div>
                <div class="co-sub" style="margin-top:2px;">GSTIN: <?= htmlspecialchars($po['company_gstin'] ?? '') ?></div>
            </td>
            <td class="seal-cell"><div class="placeholder-box seal-circle">Insert<br>Seal</div></td>
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
                <th rowspan="2" style="width:28px;">S.No</th>
                <th rowspan="2" style="min-width:150px;">Description of Work</th>
                <th rowspan="2" style="width:36px;">Unit</th>
                <th rowspan="2" style="width:52px;">PO Qty</th>
                <th rowspan="2" style="width:60px;">PO Rate (₹)</th>
                <th rowspan="2" style="width:68px;">PO Amount (₹)</th>
                <th colspan="3" class="prev-hdr">Previous Claimed</th>
                <th colspan="3" class="this-hdr">This Bill</th>
                <th colspan="2" style="background:#374151;">Cumulative</th>
            </tr>
            <tr>
                <th class="prev-hdr">Qty</th>
                <th class="prev-hdr">%</th>
                <th class="prev-hdr">Amount (₹)</th>
                <th class="this-hdr">Qty</th>
                <th class="this-hdr">%</th>
                <th class="this-hdr">Amount (₹)</th>
                <th style="background:#374151;">Qty</th>
                <th style="background:#374151;">Amount (₹)</th>
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
            <td style="background:#f0f0ff;"><?= number_format(floatval($item['prev_claimed_qty']),3) ?></td>
            <td style="background:#f0f0ff;"><?= number_format(floatval($item['prev_claimed_pct']),2) ?>%</td>
            <td style="background:#f0f0ff;"><?= number_format(floatval($item['prev_claimed_amount']),2) ?></td>
            <td style="background:#f0fff4;font-weight:700;"><?= number_format(floatval($item['this_qty']),3) ?></td>
            <td style="background:#f0fff4;font-weight:700;"><?= number_format(floatval($item['this_pct']),2) ?>%</td>
            <td style="background:#f0fff4;font-weight:700;"><?= number_format(floatval($item['this_amount']),2) ?></td>
            <td style="background:#f5f5f5;"><?= number_format(floatval($item['cumulative_qty']),3) ?></td>
            <td style="background:#f5f5f5;font-weight:700;"><?= number_format(floatval($item['cumulative_amount']),2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" style="text-align:right;padding-right:8px;">TOTAL</td>
                <td>₹<?= number_format($poValue,2) ?></td>
                <td></td><td></td>
                <td>₹<?= number_format($prevTotal,2) ?></td>
                <td></td><td></td>
                <td>₹<?= number_format($totalClaimed,2) ?></td>
                <td></td>
                <td>₹<?= number_format($cumTotal,2) ?></td>
            </tr>
        </tfoot>
    </table>

    <table class="sum-table" style="margin-top:10px;">
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

<!-- ═══════════════════════════════════════════════════════════
     PAGE 2 — CLEARANCE SHEET
     ═══════════════════════════════════════════════════════════ -->
<div class="page page-break">

    <table class="hdr-table">
        <tr>
            <td class="logo-cell"><div class="placeholder-box">Insert<br>Logo</div></td>
            <td class="co-cell">
                <div class="co-name"><?= htmlspecialchars($coName) ?></div>
                <div class="co-sub">Civil &amp; Construction Works</div>
                <div class="co-sub" style="margin-top:2px;">GSTIN: <?= htmlspecialchars($po['company_gstin'] ?? '') ?></div>
            </td>
            <td class="seal-cell"><div class="placeholder-box seal-circle">Insert<br>Seal</div></td>
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
