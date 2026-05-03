<?php
$title = 'RA Bill Clearance Sheet';
$active_page = 'measurement_sheet';

// Don't include dashboard layout for print
if ($error || !$ra || !$po) {
    echo "<div style='padding:40px;text-align:center;color:#dc2626;'>" . htmlspecialchars($error ?? 'RA Bill or PO not found') . "</div>";
    exit;
}

$poNum = $po['po_number'] ?? $po['internal_po_number'] ?? '—';
$companyLogo = '';
$clientLogo = '';
$companySeal = '';

// Load company logo
if (!empty($ra['selected_logo'])) {
    $logoPath = __DIR__ . "/../../storage/company/logos/{$ra['selected_logo']}.png";
    $logoPathJpg = __DIR__ . "/../../storage/company/logos/{$ra['selected_logo']}.jpg";
    if (file_exists($logoPath)) {
        $companyLogo = "/ergon/storage/company/logos/{$ra['selected_logo']}.png";
    } elseif (file_exists($logoPathJpg)) {
        $companyLogo = "/ergon/storage/company/logos/{$ra['selected_logo']}.jpg";
    }
}

// Load client logo
if (!empty($ra['selected_client_logo'])) {
    $clientLogoPath = __DIR__ . "/../../storage/client/logos/{$ra['selected_client_logo']}.png";
    $clientLogoPathJpg = __DIR__ . "/../../storage/client/logos/{$ra['selected_client_logo']}.jpg";
    if (file_exists($clientLogoPath)) {
        $clientLogo = "/ergon/storage/client/logos/{$ra['selected_client_logo']}.png";
    } elseif (file_exists($clientLogoPathJpg)) {
        $clientLogo = "/ergon/storage/client/logos/{$ra['selected_client_logo']}.jpg";
    }
}

// Load company seal
if (!empty($ra['selected_seal'])) {
    $sealPath = __DIR__ . "/../../storage/company/seals/{$ra['selected_seal']}.png";
    $sealPathJpg = __DIR__ . "/../../storage/company/seals/{$ra['selected_seal']}.jpg";
    if (file_exists($sealPath)) {
        $companySeal = "/ergon/storage/company/seals/{$ra['selected_seal']}.png";
    } elseif (file_exists($sealPathJpg)) {
        $companySeal = "/ergon/storage/company/seals/{$ra['selected_seal']}.jpg";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RA Bill Clearance Sheet - <?= htmlspecialchars($ra['ra_bill_number']) ?></title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            margin: 0;
            padding: 15mm;
            background: white;
            color: black;
        }
        
        .clearance-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
        }
        
        /* Header Section */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .company-logo {
            height: 50px;
            max-width: 120px;
            object-fit: contain;
        }
        
        .main-title {
            text-align: center;
            flex: 1;
            margin: 0 20px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .client-logo {
            height: 50px;
            max-width: 120px;
            object-fit: contain;
        }
        
        /* Project Information Table */
        .project-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        
        .project-info td {
            padding: 4px 8px;
            border: 1px solid #000;
            vertical-align: top;
        }
        
        .project-info .label {
            font-weight: bold;
            background-color: #f5f5f5;
            width: 15%;
        }
        
        .project-info .value {
            width: 35%;
        }
        
        /* Clearance Table */
        .clearance-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 9px;
        }
        
        .clearance-table th,
        .clearance-table td {
            border: 1px solid #000;
            padding: 8px 4px;
            text-align: left;
            vertical-align: top;
            page-break-inside: avoid;
        }
        
        .clearance-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }
        
        .clearance-table .sno-col { width: 5%; text-align: center; }
        .clearance-table .dept-col { width: 25%; }
        .clearance-table .comments-col { width: 40%; }
        .clearance-table .incharge-col { width: 15%; text-align: center; }
        .clearance-table .signature-col { width: 15%; text-align: center; }
        
        .clearance-table .row-height {
            height: 40px;
        }
        
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .print-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .print-btn:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print</button>
        <a href="/ergon/finance/measurement-sheet/manage" style="margin-left: 10px; padding: 10px 20px; background: #6b7280; color: white; text-decoration: none; border-radius: 5px;">← Back</a>
    </div>

    <div class="clearance-container">
        <!-- Header Section -->
        <div class="header-section">
            <?php if ($companyLogo): ?>
                <img src="<?= $companyLogo ?>" alt="Company Logo" class="company-logo">
            <?php else: ?>
                <div style="width: 120px;"></div>
            <?php endif; ?>
            
            <h1 class="main-title">
                <?= htmlspecialchars($po['company_name'] ?? 'PROZEAL GREEN ENERGY PVT LTD') ?> - RUNNING / FINAL BILL CLEARANCE CHECKLIST
            </h1>
            
            <?php if ($clientLogo): ?>
                <img src="<?= $clientLogo ?>" alt="Client Logo" class="client-logo">
            <?php else: ?>
                <div style="width: 120px;"></div>
            <?php endif; ?>
        </div>

        <!-- Project Information Table -->
        <table class="project-info">
            <tr>
                <td class="label">Project</td>
                <td class="value"><?= htmlspecialchars($ra['project'] ?? '—') ?></td>
                <td class="label">Contractor / Vendor</td>
                <td class="value"><?= htmlspecialchars($ra['contractor'] ?? '—') ?></td>
            </tr>
            <tr>
                <td class="label">PO / WO Ref</td>
                <td class="value"><?= htmlspecialchars($poNum) ?></td>
                <td class="label">RA Bill No</td>
                <td class="value"><?= htmlspecialchars($ra['ra_bill_number']) ?></td>
            </tr>
            <tr>
                <td class="label">Date</td>
                <td class="value"><?= date('d-M-Y', strtotime($ra['bill_date'])) ?></td>
                <td class="label">Customer</td>
                <td class="value"><?= htmlspecialchars($po['customer_name'] ?? '—') ?></td>
            </tr>
        </table>
        
        .measurement-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 11px;
        }
        
        .measurement-table th,
        .measurement-table td {
            border: 1px solid #000;
            padding: 8px 4px;
            text-align: center;
            vertical-align: middle;
        }
        
        .measurement-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 10px;
        }
        
        .measurement-table .desc-col {
            text-align: left;
            max-width: 200px;
        }
        
        .measurement-table .amount-col {
            text-align: right;
        }
        
        .clearance-section {
            margin-top: 30px;
            padding: 20px;
            border: 2px solid #000;
        }
        
        .clearance-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
            background: #f0f0f0;
            padding: 10px;
        }
        
        .clearance-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 20px 0;
        }
        
        .clearance-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dotted #ccc;
        }
        
        .clearance-label {
            font-weight: bold;
        }
        
        .clearance-status {
            width: 60px;
            text-align: center;
            border: 1px solid #000;
            padding: 5px;
        }
        
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
            margin-top: 40px;
            padding: 20px 0;
        }
        
        .signature-box {
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
        
        .signature-title {
            font-weight: bold;
            margin-bottom: 40px;
        }
        
        .seal-area {
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 10px 0;
        }
        
        .seal-image {
            max-width: 100px;
            max-height: 80px;
            object-fit: contain;
        }
        
        .totals-section {
            background: #f9f9f9;
            padding: 15px;
            margin: 20px 0;
            border: 1px solid #ccc;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        
        .total-label {
            font-weight: bold;
        }
        
        .total-amount {
            font-weight: bold;
            text-align: right;
        }
        
        .remarks-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ccc;
        }
        
        .remarks-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .print-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .print-btn:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print</button>
        <a href="/ergon/finance/measurement-sheet/manage" style="margin-left: 10px; padding: 10px 20px; background: #6b7280; color: white; text-decoration: none; border-radius: 5px;">← Back</a>
    </div>

    <div class="clearance-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="logo-section">
                <?php if ($companyLogo): ?>
                    <img src="<?= $companyLogo ?>" alt="Company Logo" class="logo">
                <?php endif; ?>
            </div>
            
            <div class="title-section">
                <h1 class="main-title">Running Account Bill & Clearance Sheet</h1>
                <p class="sub-title">Measurement Sheet with Clearance Certificate</p>
                <p style="margin: 5px 0; font-weight: bold;">RA Bill No: <?= htmlspecialchars($ra['ra_bill_number']) ?></p>
            </div>
            
            <div class="logo-section">
                <?php if ($clientLogo): ?>
                    <img src="<?= $clientLogo ?>" alt="Client Logo" class="logo">
                <?php endif; ?>
            </div>
        </div>

        <!-- Project Information Grid -->
        <div class="info-grid">
            <div class="info-box">
                <div class="info-label">Work Order / PO Number</div>
                <div class="info-value"><?= htmlspecialchars($poNum) ?></div>
            </div>
            <div class="info-box">
                <div class="info-label">RA Bill Date</div>
                <div class="info-value"><?= htmlspecialchars($ra['bill_date']) ?></div>
            </div>
            <div class="info-box">
                <div class="info-label">Project / Site</div>
                <div class="info-value"><?= htmlspecialchars($ra['project'] ?? '—') ?></div>
            </div>
            <div class="info-box">
                <div class="info-label">Contractor / Vendor</div>
                <div class="info-value"><?= htmlspecialchars($ra['contractor'] ?? '—') ?></div>
            </div>
            <div class="info-box">
                <div class="info-label">Client / Customer</div>
                <div class="info-value"><?= htmlspecialchars($po['customer_name'] ?? '—') ?></div>
            </div>
            <div class="info-box">
                <div class="info-label">Company</div>
                <div class="info-value"><?= htmlspecialchars($po['company_name'] ?? '—') ?></div>
            </div>
        </div>

        <table class="clearance-table">
            <thead>
                <tr>
                    <th class="sno-col">S.No</th>
                    <th class="dept-col">Department</th>
                    <th class="comments-col">Comments</th>
                    <th class="incharge-col">Incharge</th>
                    <th class="signature-col">Signature</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clearanceItems)): ?>
                    <tr class="row-height">
                        <td colspan="5" style="text-align: center; padding: 20px; color: #666;">No clearance items found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clearanceItems as $index => $clearance): ?>
                    <tr class="row-height">
                        <td class="sno-col"><?= $index + 1 ?></td>
                        <td class="dept-col">
                            <strong><?= htmlspecialchars($clearance['department']) ?></strong><br>
                            <?= nl2br(htmlspecialchars($clearance['checklist_items'] ?? '')) ?>
                        </td>
                        <td class="comments-col"><?= htmlspecialchars($clearance['comments'] ?? '') ?></td>
                        <td class="incharge-col"><?= htmlspecialchars($clearance['incharge'] ?? '') ?></td>
                        <td class="signature-col">
                            <?php if ($clearance['status']): ?>
                                <div style="text-align: center; color: green; font-weight: bold;">✓ CLEARED</div>
                                <?php if ($clearance['cleared_at']): ?>
                                    <div style="font-size: 8px; margin-top: 5px;"><?= date('d-M-Y', strtotime($clearance['cleared_at'])) ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <table class="measurement-table">
            <thead>
                <tr>
                    <th style="width: 40px;">S.No</th>
                    <th style="width: 250px;">Description of Work</th>
                    <th style="width: 50px;">UOM</th>
                    <th style="width: 80px;">As per WO<br>Qty</th>
                    <th style="width: 80px;">Previous Bills<br>Qty (%)</th>
                    <th style="width: 80px;">Present Bill<br>Qty (%)</th>
                    <th style="width: 80px;">Cumulative<br>Qty (%)</th>
                    <th style="width: 100px;">Rate</th>
                    <th style="width: 100px;">Amount</th>
                    <th style="width: 120px;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 20px; color: #666;">No items found</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $totalAmount = 0;
                    foreach ($items as $index => $item): 
                        $totalAmount += floatval($item['this_amount']);
                    ?>
                    <tr>
                        <td><?= $item['line_number'] ?></td>
                        <td class="desc-col">
                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                            <?php if (!empty($item['description'])): ?>
                                <br><small style="color: #666;"><?= htmlspecialchars($item['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($item['unit'] ?? '—') ?></td>
                        <td><?= number_format($item['po_quantity'], 2) ?></td>
                        <td>
                            <?= number_format($item['prev_claimed_qty'], 2) ?>
                            <br><small>(<?= number_format($item['prev_claimed_pct'], 1) ?>%)</small>
                        </td>
                        <td>
                            <?= number_format($item['this_qty'], 2) ?>
                            <br><small>(<?= number_format($item['this_pct'], 1) ?>%)</small>
                        </td>
                        <td>
                            <?= number_format($item['cumulative_qty'], 2) ?>
                            <br><small>(<?= number_format($item['cumulative_pct'], 1) ?>%)</small>
                        </td>
                        <td class="amount-col">₹<?= number_format($item['po_unit_price'], 2) ?></td>
                        <td class="amount-col">₹<?= number_format($item['this_amount'], 2) ?></td>
                        <td><?= htmlspecialchars($item['remarks'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Footer Section -->
        <div style="margin-top: 30px; page-break-inside: avoid;">
            <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                <tr>
                    <td style="width: 33%; text-align: center; padding: 20px 10px; border: 1px solid #000; vertical-align: bottom;">
                        <div style="height: 60px; border-bottom: 1px solid #000; margin-bottom: 10px;"></div>
                        <strong>Site Engineer</strong><br>
                        Date: ___________
                    </td>
                    <td style="width: 33%; text-align: center; padding: 20px 10px; border: 1px solid #000; vertical-align: bottom;">
                        <div style="height: 60px; border-bottom: 1px solid #000; margin-bottom: 10px;"></div>
                        <strong>DGM / PM Projects</strong><br>
                        Date: ___________
                    </td>
                    <td style="width: 34%; text-align: center; padding: 20px 10px; border: 1px solid #000; vertical-align: bottom;">
                        <?php if ($companySeal): ?>
                            <div style="height: 60px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                                <img src="<?= $companySeal ?>" alt="Company Seal" style="max-width: 80px; max-height: 60px; object-fit: contain;">
                            </div>
                        <?php else: ?>
                            <div style="height: 60px; border-bottom: 1px solid #000; margin-bottom: 10px;"></div>
                        <?php endif; ?>
                        <strong>Authorized Signatory</strong><br>
                        Date: ___________
                    </td>
                </tr>
            </table>
        </div>

        <!-- Document Footer -->
        <div style="text-align: center; margin-top: 20px; padding: 10px; border: 1px solid #000; background-color: #f5f5f5; font-size: 9px; font-weight: bold;">
            This is a system generated clearance checklist and requires proper departmental authorization before final approval.
        </div>
    </div>

    <script>
        // Auto-print functionality
        function autoPrint() {
            if (window.location.search.includes('auto_print=1')) {
                setTimeout(() => {
                    window.print();
                }, 1000);
            }
        }
        
        document.addEventListener('DOMContentLoaded', autoPrint);
    </script>
</body>
</html>