<?php
// inc/functions.php
declare(strict_types=1);

function getPdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $c = require __DIR__ . '/config.php';
        $db = $c['db'];
        $dsn = "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}";
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function upsertConsolidatedRow(PDO $pdo, array $row): bool {
    $sql = "INSERT INTO finance_consolidated (
        record_type, document_number, customer_id, customer_name, customer_gstin,
        amount, taxable_amount, amount_paid, outstanding_amount,
        igst, cgst, sgst,
        due_date, invoice_date, status, company_prefix, raw_data, created_at
    ) VALUES (
        :record_type, :document_number, :customer_id, :customer_name, :customer_gstin,
        :amount, :taxable_amount, :amount_paid, :outstanding_amount,
        :igst, :cgst, :sgst,
        :due_date, :invoice_date, :status, :company_prefix, :raw_data, NOW()
    ) ON DUPLICATE KEY UPDATE
        customer_name = VALUES(customer_name),
        customer_gstin = VALUES(customer_gstin),
        amount = VALUES(amount),
        taxable_amount = VALUES(taxable_amount),
        amount_paid = VALUES(amount_paid),
        outstanding_amount = VALUES(outstanding_amount),
        igst = VALUES(igst),
        cgst = VALUES(cgst),
        sgst = VALUES(sgst),
        due_date = VALUES(due_date),
        invoice_date = VALUES(invoice_date),
        status = VALUES(status),
        updated_at = NOW(),
        raw_data = VALUES(raw_data)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':record_type', $row['record_type']);
    $stmt->bindValue(':document_number', $row['document_number']);
    $stmt->bindValue(':customer_id', $row['customer_id']);
    $stmt->bindValue(':customer_name', $row['customer_name']);
    $stmt->bindValue(':customer_gstin', $row['customer_gstin']);
    $stmt->bindValue(':amount', $row['amount']);
    $stmt->bindValue(':taxable_amount', $row['taxable_amount']);
    $stmt->bindValue(':amount_paid', $row['amount_paid']);
    $stmt->bindValue(':outstanding_amount', $row['outstanding_amount']);
    $stmt->bindValue(':igst', $row['igst']);
    $stmt->bindValue(':cgst', $row['cgst']);
    $stmt->bindValue(':sgst', $row['sgst']);
    $stmt->bindValue(':due_date', $row['due_date']);
    $stmt->bindValue(':invoice_date', $row['invoice_date']);
    $stmt->bindValue(':status', $row['status']);
    $stmt->bindValue(':company_prefix', $row['company_prefix']);
    $stmt->bindValue(':raw_data', json_encode($row['raw_data'], JSON_UNESCAPED_UNICODE));
    return $stmt->execute();
}

function logSyncError(PDO $pdo, string $companyPrefix, string $errorType, string $message, $rawData = null) {
    $sql = "INSERT INTO sync_errors (company_prefix, error_type, message, raw_data) VALUES (:company_prefix, :error_type, :message, :raw_data)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':company_prefix' => $companyPrefix,
        ':error_type' => $errorType,
        ':message' => $message,
        ':raw_data' => $rawData ? json_encode($rawData, JSON_UNESCAPED_UNICODE) : null
    ]);
}

function transformInvoiceCsvRow(array $csvRow, string $companyPrefix): array {
    $customerName = trim($csvRow['customer_name'] ?? '');
    if ($customerName === '') $customerName = (string)($csvRow['customer_id'] ?? '');
    
    $taxable = (float)($csvRow['taxable_amount'] ?? 0);
    $paid = (float)($csvRow['amount_paid'] ?? 0);
    $outstanding = max(0.0, $taxable - $paid);
    
    return [
        'record_type' => 'invoice',
        'document_number' => $csvRow['invoice_number'] ?? '',
        'customer_id' => $csvRow['customer_id'] ?? null,
        'customer_name' => $customerName,
        'customer_gstin' => $csvRow['customer_gstin'] ?? null,
        'amount' => (float)($csvRow['total_amount'] ?? 0.0),
        'taxable_amount' => $taxable,
        'amount_paid' => $paid,
        'outstanding_amount' => $outstanding,
        'igst' => (float)($csvRow['igst'] ?? 0.0),
        'cgst' => (float)($csvRow['cgst'] ?? 0.0),
        'sgst' => (float)($csvRow['sgst'] ?? 0.0),
        'due_date' => !empty($csvRow['due_date']) ? date('Y-m-d', strtotime($csvRow['due_date'])) : null,
        'invoice_date' => !empty($csvRow['invoice_date']) ? date('Y-m-d', strtotime($csvRow['invoice_date'])) : null,
        'status' => $csvRow['status'] ?? null,
        'company_prefix' => $companyPrefix,
        'raw_data' => $csvRow,
    ];
}

function transformActivityCsvRow(array $csvRow, string $companyPrefix, string $type): array {
    $customerName = trim($csvRow['customer_name'] ?? '');
    if ($customerName === '') $customerName = (string)($csvRow['customer_id'] ?? '');
    
    $documentNumber = $csvRow['document_number'] ?? 
                     $csvRow['quotation_number'] ?? 
                     $csvRow['po_number'] ?? 
                     $csvRow['payment_id'] ?? '';
    
    return [
        'record_type' => $type,
        'document_number' => $documentNumber,
        'customer_id' => $csvRow['customer_id'] ?? null,
        'customer_name' => $customerName,
        'customer_gstin' => $csvRow['customer_gstin'] ?? null,
        'amount' => (float)($csvRow['amount'] ?? $csvRow['po_total_value'] ?? 0.0),
        'taxable_amount' => 0.0,
        'amount_paid' => 0.0,
        'outstanding_amount' => 0.0,
        'igst' => 0.0,
        'cgst' => 0.0,
        'sgst' => 0.0,
        'due_date' => null,
        'invoice_date' => null,
        'status' => $csvRow['status'] ?? null,
        'company_prefix' => $companyPrefix,
        'raw_data' => $csvRow,
    ];
}

function recomputeDashboardStats(PDO $pdo, string $companyPrefix) {
    // Expected inflow from invoices
    $stmt = $pdo->prepare("SELECT amount, amount_paid FROM finance_consolidated WHERE company_prefix = :prefix AND record_type = 'invoice'");
    $stmt->execute([':prefix' => $companyPrefix]);
    $expectedInflow = 0.0;
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $total = (float)$r['amount'];
        $paid = (float)$r['amount_paid'];
        $inflow = $total - $paid;
        if ($inflow > 0) $expectedInflow += $inflow;
    }
    
    // PO commitments
    $stmt2 = $pdo->prepare("SELECT amount FROM finance_consolidated WHERE company_prefix = :prefix AND record_type = 'purchase_order' AND status IN ('Active','Released')");
    $stmt2->execute([':prefix' => $companyPrefix]);
    $poCommitments = 0.0;
    while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $poCommitments += (float)$r['amount'];
    }
    
    $netCashFlow = $expectedInflow - $poCommitments;
    
    // Upsert dashboard stats
    $up = $pdo->prepare("INSERT INTO dashboard_stats (company_prefix, expected_inflow, po_commitments, net_cash_flow, last_computed_at) VALUES (:prefix,:expected,:po,:net, NOW()) ON DUPLICATE KEY UPDATE expected_inflow = VALUES(expected_inflow), po_commitments = VALUES(po_commitments), net_cash_flow = VALUES(net_cash_flow), last_computed_at = VALUES(last_computed_at)");
    $up->execute([
        ':prefix' => $companyPrefix,
        ':expected' => number_format($expectedInflow, 2, '.', ''),
        ':po' => number_format($poCommitments, 2, '.', ''),
        ':net' => number_format($netCashFlow, 2, '.', '')
    ]);
}
