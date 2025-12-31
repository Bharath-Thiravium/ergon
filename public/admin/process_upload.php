<?php
// public/admin/process_upload.php
declare(strict_types=1);
require_once __DIR__ . '/../../inc/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

$companyPrefix = trim($_POST['company_prefix'] ?? '');
$recordType = trim($_POST['record_type'] ?? '');

if ($companyPrefix === '' || $recordType === '') {
    echo "Missing company prefix or record type";
    exit;
}

if (!isset($_FILES['csvfile']) || $_FILES['csvfile']['error'] !== UPLOAD_ERR_OK) {
    echo "File upload error";
    exit;
}

$config = require __DIR__ . '/../../inc/config.php';
$maxSize = $config['max_file_size'] ?? 5*1024*1024;

if ($_FILES['csvfile']['size'] > $maxSize) {
    echo "File too large (max " . number_format($maxSize/1024/1024, 1) . "MB)";
    exit;
}

$fname = $_FILES['csvfile']['tmp_name'];
$pdo = getPdo();

$handle = fopen($fname, 'r');
if ($handle === false) {
    echo "Unable to open file";
    exit;
}

// Read header row
$header = fgetcsv($handle);
if ($header === false) {
    echo "Empty CSV file";
    exit;
}
$header = array_map('trim', $header);

$rowsProcessed = 0;
$rowsSuccess = 0;
$errors = [];

$pdo->beginTransaction();

try {
    while (($data = fgetcsv($handle)) !== false) {
        $rowsProcessed++;
        
        // Map header to associative row
        $row = [];
        foreach ($header as $i => $col) {
            $row[$col] = isset($data[$i]) ? trim($data[$i]) : null;
        }

        try {
            if ($recordType === 'invoice') {
                // Map common variations of column names
                $mapped = [
                    'invoice_number' => $row['invoice_number'] ?? $row['InvoiceNumber'] ?? $row['document_number'] ?? '',
                    'total_amount' => $row['total_amount'] ?? $row['TotalAmount'] ?? $row['amount'] ?? 0,
                    'amount_paid' => $row['amount_paid'] ?? $row['AmountPaid'] ?? 0,
                    'taxable_amount' => $row['taxable_amount'] ?? $row['TaxableAmount'] ?? $row['total_amount'] ?? 0,
                    'igst' => $row['igst'] ?? $row['IGST'] ?? 0,
                    'cgst' => $row['cgst'] ?? $row['CGST'] ?? 0,
                    'sgst' => $row['sgst'] ?? $row['SGST'] ?? 0,
                    'due_date' => $row['due_date'] ?? $row['DueDate'] ?? null,
                    'invoice_date' => $row['invoice_date'] ?? $row['InvoiceDate'] ?? null,
                    'customer_id' => $row['customer_id'] ?? $row['CustomerID'] ?? null,
                    'customer_name' => $row['customer_name'] ?? $row['CustomerName'] ?? null,
                    'customer_gstin' => $row['customer_gstin'] ?? $row['CustomerGSTIN'] ?? null,
                    'status' => $row['status'] ?? $row['Status'] ?? null,
                ];
                $conRow = transformInvoiceCsvRow($mapped, $companyPrefix);
            } else {
                // For other activity types
                $mapped = [
                    'document_number' => $row['document_number'] ?? $row['quotation_number'] ?? $row['po_number'] ?? $row['payment_id'] ?? '',
                    'amount' => $row['amount'] ?? $row['Amount'] ?? $row['po_total_value'] ?? 0,
                    'customer_id' => $row['customer_id'] ?? $row['CustomerID'] ?? null,
                    'customer_name' => $row['customer_name'] ?? $row['CustomerName'] ?? null,
                    'customer_gstin' => $row['customer_gstin'] ?? $row['CustomerGSTIN'] ?? null,
                    'status' => $row['status'] ?? $row['Status'] ?? null,
                ];
                $conRow = transformActivityCsvRow($mapped, $companyPrefix, $recordType);
            }

            $ok = upsertConsolidatedRow($pdo, $conRow);
            if ($ok) {
                $rowsSuccess++;
            } else {
                $errors[] = "Row $rowsProcessed: Upsert failed";
                logSyncError($pdo, $companyPrefix, 'db_upsert_failed', 'Upsert returned false', $conRow['raw_data']);
            }
        } catch (Throwable $e) {
            $errors[] = "Row $rowsProcessed: " . $e->getMessage();
            logSyncError($pdo, $companyPrefix, 'row_process_error', $e->getMessage(), $row);
        }
        
        // Avoid timeouts
        if ($rowsProcessed >= ($config['max_rows_per_upload'] ?? 5000)) {
            break;
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    echo "Fatal error: " . htmlentities($e->getMessage());
    exit;
} finally {
    fclose($handle);
}

// Recompute dashboard stats after processing
try {
    recomputeDashboardStats($pdo, $companyPrefix);
    $statsUpdated = true;
} catch (Throwable $e) {
    logSyncError($pdo, $companyPrefix, 'dashboard_recompute_failed', $e->getMessage());
    $statsUpdated = false;
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Upload Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeaa7; border-radius: 4px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; }
        .stats { margin: 20px 0; }
        a { color: #007cba; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h2>Upload Results</h2>
    
    <div class="<?= $rowsSuccess > 0 ? 'success' : 'warning' ?>">
        <strong>Processing Complete:</strong><br>
        Rows Processed: <?= $rowsProcessed ?><br>
        Rows Successfully Imported: <?= $rowsSuccess ?><br>
        Company Prefix: <?= htmlentities($companyPrefix) ?><br>
        Record Type: <?= htmlentities($recordType) ?>
    </div>

    <?php if ($statsUpdated): ?>
    <div class="success">
        <strong>Dashboard stats updated successfully.</strong>
    </div>
    <?php else: ?>
    <div class="warning">
        <strong>Warning:</strong> Dashboard stats update failed. Check error logs.
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="error">
        <strong>Errors encountered:</strong>
        <ul>
            <?php foreach (array_slice($errors, 0, 10) as $error): ?>
            <li><?= htmlentities($error) ?></li>
            <?php endforeach; ?>
            <?php if (count($errors) > 10): ?>
            <li>... and <?= count($errors) - 10 ?> more errors</li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="stats">
        <a href="../index.php?prefix=<?= urlencode($companyPrefix) ?>">View Dashboard</a> |
        <a href="upload.php">Upload Another File</a> |
        <a href="../recent_activities.php?prefix=<?= urlencode($companyPrefix) ?>">View Recent Activities (JSON)</a>
    </div>
</body>
</html>
