<?php
/*
===========================================================================
ERGON – FINANCE ANALYTICS BACKEND (ONE FILE)
- Purpose: ETL SAP -> Postgres (finance_consolidated) and serve analytics
- Important: This file DOES NOT alter frontend components/styles.
  It exposes JSON endpoints that the existing STAT Cards, Funnel, Donut/Bar/Gauge
  components can call exactly as before.
- Usage:
    * Run manual sync:     analytics_backend.php?action=sync
    * Stats (stat cards):  analytics_backend.php?action=stats&prefix=AS
    * Funnel:               analytics_backend.php?action=funnel&prefix=AS&customer=CUST01
    * Quotation Donut:      analytics_backend.php?action=quotations_donut&prefix=AS
    * PO Claim Ranges:      analytics_backend.php?action=po_claims&prefix=AS
    * Invoices Summary:     analytics_backend.php?action=invoices_summary&prefix=AS
    * Exposure:             analytics_backend.php?action=exposure&prefix=AS
===========================================================================
*/

/* ---------- CONFIG: update these ---------- */
define('SAP_BASE', 'https://sapserver.com/api/'); // change to real SAP base
define('SAP_TOKEN', 'YOUR_SAP_TOKEN_HERE');       // change to real token

// Postgres DSN - change to your credentials
$pg_dsn = "pgsql:host=127.0.0.1;port=5432;dbname=ergon_db;";
$pg_user = "ergon_user";
$pg_pass = "ergon_pass";
/* ------------------------------------------ */

/* ---------- SETUP PDO (Postgres) ---------- */
try {
    $pdo = new PDO($pg_dsn, $pg_user, $pg_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => false, "error" => "DB connection failed: ".$e->getMessage()]);
    exit;
}

/* ---------- HELPER: fetchSAP ---------- */
function fetchSAP($endpoint) {
    $url = rtrim(SAP_BASE, '/') . '/' . ltrim($endpoint, '/');
    $headers = [
        "Authorization: Bearer " . SAP_TOKEN,
        "Content-Type: application/json"
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 40
    ]);

    $response = curl_exec($curl);
    if ($response === false) {
        $err = curl_error($curl);
        curl_close($curl);
        return ["__error" => "CURL_ERROR: ".$err];
    }

    curl_close($curl);
    $data = json_decode($response, true);
    if ($data === null) return ["__error" => "INVALID_JSON", "raw" => $response];
    return $data;
}

/* ---------- Ensure consolidated table exists ---------- */
function ensureTable($pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS finance_consolidated (
        id BIGSERIAL PRIMARY KEY,
        company_prefix VARCHAR(10),
        customer_id VARCHAR(100),
        customer_name VARCHAR(255),
        customer_email VARCHAR(255),
        customer_phone VARCHAR(50),
        customer_country VARCHAR(100),

        record_type VARCHAR(30),    -- QUOTATION / PURCHASE_ORDER / INVOICE
        record_id VARCHAR(100),
        status VARCHAR(50),

        record_date DATE,
        due_date DATE,
        currency VARCHAR(10),

        sub_total NUMERIC(14,2),
        tax_amount NUMERIC(14,2),
        discount_amount NUMERIC(14,2),
        shipping_amount NUMERIC(14,2),

        total_amount NUMERIC(18,2),
        paid_amount NUMERIC(18,2),
        balance_amount NUMERIC(18,2),
        claim_percentage NUMERIC(5,2),

        created_at TIMESTAMP DEFAULT NOW(),
        updated_at TIMESTAMP DEFAULT NOW()
    )";
    $pdo->exec($sql);
}
ensureTable($pdo);

/* ---------- ETL Insert (upsert safe via simple delete+insert by record_id & record_type) ---------- */
function upsertRecord($pdo, $row) {
    // we will delete existing same record_type+record_id to keep simple idempotent logic
    $del = "DELETE FROM finance_consolidated WHERE record_type = :record_type AND record_id = :record_id";
    $stmt = $pdo->prepare($del);
    $stmt->execute([
        ":record_type" => $row['record_type'],
        ":record_id" => $row['record_id']
    ]);

    $ins = "INSERT INTO finance_consolidated (
        company_prefix, customer_id, customer_name, customer_email, customer_phone, customer_country,
        record_type, record_id, status, record_date, due_date, currency,
        sub_total, tax_amount, discount_amount, shipping_amount,
        total_amount, paid_amount, balance_amount, claim_percentage, created_at, updated_at
    ) VALUES (
        :company_prefix, :customer_id, :customer_name, :customer_email, :customer_phone, :customer_country,
        :record_type, :record_id, :status, :record_date, :due_date, :currency,
        :sub_total, :tax_amount, :discount_amount, :shipping_amount,
        :total_amount, :paid_amount, :balance_amount, :claim_percentage, NOW(), NOW()
    )";
    $stmt = $pdo->prepare($ins);
    $stmt->execute([
        ":company_prefix" => $row['company_prefix'] ?? null,
        ":customer_id" => $row['customer_id'] ?? null,
        ":customer_name" => $row['customer_name'] ?? null,
        ":customer_email" => $row['customer_email'] ?? null,
        ":customer_phone" => $row['customer_phone'] ?? null,
        ":customer_country" => $row['customer_country'] ?? null,
        ":record_type" => $row['record_type'],
        ":record_id" => $row['record_id'],
        ":status" => $row['status'] ?? null,
        ":record_date" => $row['record_date'] ?? null,
        ":due_date" => $row['due_date'] ?? null,
        ":currency" => $row['currency'] ?? null,
        ":sub_total" => $row['sub_total'] ?? 0,
        ":tax_amount" => $row['tax_amount'] ?? 0,
        ":discount_amount" => $row['discount_amount'] ?? 0,
        ":shipping_amount" => $row['shipping_amount'] ?? 0,
        ":total_amount" => $row['total_amount'] ?? 0,
        ":paid_amount" => $row['paid_amount'] ?? 0,
        ":balance_amount" => $row['balance_amount'] ?? 0,
        ":claim_percentage" => $row['claim_percentage'] ?? 0
    ]);
}

/* ---------- ETL: transform SAP payload to consolidated rows ---------- */
function transformAndLoad($pdo, $data, $type) {
    // $type: 'customers' | 'quotations' | 'purchaseOrders' | 'invoices'
    if (!is_array($data)) return ["status"=>false, "error"=>"invalid_data_for_$type"];
    foreach ($data as $r) {
        // Defensive access with many possible keys from SAP exports.
        // Adapt field names below to match your SAP payload exactly.
        if ($type === 'customers') {
            // Insert a customer row for reference (record_type = 'CUSTOMER') - optional
            $row = [
                "company_prefix" => $r['prefix'] ?? (isset($r['companyPrefix']) ? $r['companyPrefix'] : null),
                "customer_id" => $r['id'] ?? $r['customerId'] ?? $r['customer_id'] ?? null,
                "customer_name" => $r['name'] ?? $r['customerName'] ?? null,
                "customer_email" => $r['email'] ?? null,
                "customer_phone" => $r['phone'] ?? null,
                "customer_country" => $r['country'] ?? null,
                "record_type" => "CUSTOMER",
                "record_id" => $r['id'] ?? $r['customerId'] ?? uniqid('cust_'),
                "status" => null,
                "record_date" => null,
                "due_date" => null,
                "currency" => null,
                "sub_total" => 0,
                "tax_amount" => 0,
                "discount_amount" => 0,
                "shipping_amount" => 0,
                "total_amount" => 0,
                "paid_amount" => 0,
                "balance_amount" => 0,
                "claim_percentage" => 0
            ];
            upsertRecord($pdo, $row);
            continue;
        }

        if ($type === 'quotations') {
            $row = [
                "company_prefix" => $r['prefix'] ?? ($r['companyPrefix'] ?? null),
                "customer_id" => $r['customerId'] ?? ($r['customer_id'] ?? null),
                "customer_name" => $r['customerName'] ?? ($r['customer_name'] ?? null),
                "customer_email" => $r['customerEmail'] ?? null,
                "customer_phone" => $r['customerPhone'] ?? null,
                "customer_country" => $r['customerCountry'] ?? null,
                "record_type" => "QUOTATION",
                "record_id" => $r['quotationNo'] ?? ($r['id'] ?? uniqid('q_')),
                "status" => $r['status'] ?? null,
                "record_date" => $r['date'] ?? ($r['quotationDate'] ?? null),
                "due_date" => $r['validUntil'] ?? null,
                "currency" => $r['currency'] ?? null,
                "sub_total" => $r['subTotal'] ?? ($r['amountWithoutTax'] ?? 0),
                "tax_amount" => $r['taxAmount'] ?? 0,
                "discount_amount" => $r['discountAmount'] ?? 0,
                "shipping_amount" => $r['shipping'] ?? 0,
                "total_amount" => $r['totalAmount'] ?? ($r['amount'] ?? 0),
                "paid_amount" => 0,
                "balance_amount" => $r['totalAmount'] ?? ($r['amount'] ?? 0),
                "claim_percentage" => 0
            ];
            upsertRecord($pdo, $row);
            continue;
        }

        if ($type === 'purchaseOrders') {
            $total = $r['totalAmount'] ?? ($r['amount'] ?? 0);
            $paid = $r['paidAmount'] ?? ($r['claimed'] ?? 0);
            $claimPct = $total != 0 ? round(($paid / $total) * 100, 2) : 0;
            $row = [
                "company_prefix" => $r['prefix'] ?? ($r['companyPrefix'] ?? null),
                "customer_id" => $r['customerId'] ?? ($r['customer_id'] ?? null),
                "customer_name" => $r['customerName'] ?? ($r['customer_name'] ?? null),
                "customer_email" => $r['customerEmail'] ?? null,
                "customer_phone" => $r['customerPhone'] ?? null,
                "customer_country" => $r['customerCountry'] ?? null,
                "record_type" => "PURCHASE_ORDER",
                "record_id" => $r['poNumber'] ?? ($r['poNo'] ?? $r['id'] ?? uniqid('po_')),
                "status" => $r['status'] ?? null,
                "record_date" => $r['date'] ?? ($r['poDate'] ?? null),
                "due_date" => null,
                "currency" => $r['currency'] ?? null,
                "sub_total" => $r['subTotal'] ?? 0,
                "tax_amount" => $r['taxAmount'] ?? 0,
                "discount_amount" => $r['discountAmount'] ?? 0,
                "shipping_amount" => $r['shipping'] ?? 0,
                "total_amount" => $total,
                "paid_amount" => $paid,
                "balance_amount" => $total - $paid,
                "claim_percentage" => $claimPct
            ];
            upsertRecord($pdo, $row);
            continue;
        }

        if ($type === 'invoices') {
            $total = $r['totalAmount'] ?? ($r['amount'] ?? 0);
            $paid = $r['paidAmount'] ?? ($r['paid'] ?? 0);
            $balance = $total - $paid;
            $tax = $r['taxAmount'] ?? (($r['cgst'] ?? 0) + ($r['sgst'] ?? 0) + ($r['igst'] ?? 0));
            $row = [
                "company_prefix" => $r['prefix'] ?? ($r['companyPrefix'] ?? null),
                "customer_id" => $r['customerId'] ?? ($r['customer_id'] ?? null),
                "customer_name" => $r['customerName'] ?? ($r['customer_name'] ?? null),
                "customer_email" => $r['customerEmail'] ?? null,
                "customer_phone" => $r['customerPhone'] ?? null,
                "customer_country" => $r['customerCountry'] ?? null,
                "record_type" => "INVOICE",
                "record_id" => $r['invoiceNo'] ?? ($r['id'] ?? uniqid('inv_')),
                "status" => $r['status'] ?? null,
                "record_date" => $r['date'] ?? ($r['invoiceDate'] ?? null),
                "due_date" => $r['dueDate'] ?? null,
                "currency" => $r['currency'] ?? null,
                "sub_total" => $r['amountWithoutTax'] ?? ($r['subTotal'] ?? 0),
                "tax_amount" => $tax,
                "discount_amount" => $r['discountAmount'] ?? 0,
                "shipping_amount" => $r['shipping'] ?? 0,
                "total_amount" => $total,
                "paid_amount" => $paid,
                "balance_amount" => $balance,
                "claim_percentage" => $total != 0 ? round((($paid/$total)*100),2) : 0
            ];
            upsertRecord($pdo, $row);
            continue;
        }
    } // foreach
    return ["status"=>true];
}

/* ---------- ROUTER / ACTIONS ---------- */
$action = $_GET['action'] ?? null;
$prefix = $_GET['prefix'] ?? null;
$customer = $_GET['customer'] ?? null;

header('Content-Type: application/json; charset=utf-8');

/* ---------- ACTION: sync (manual ETL run) ---------- */
if ($action === 'sync') {
    // fetch data from SAP
    $cust = fetchSAP("finance/customers");
    $quotes = fetchSAP("finance/quotations");
    $pos = fetchSAP("finance/purchase-orders");
    $invs = fetchSAP("finance/invoices");

    // basic error reporting
    $errors = [];
    if (isset($cust['__error'])) $errors['customers'] = $cust;
    if (isset($quotes['__error'])) $errors['quotations'] = $quotes;
    if (isset($pos['__error'])) $errors['purchaseOrders'] = $pos;
    if (isset($invs['__error'])) $errors['invoices'] = $invs;

    if (!empty($errors)) {
        echo json_encode(["status"=>false, "errors"=>$errors]);
        exit;
    }

    // load into consolidated table
    $r1 = transformAndLoad($pdo, $cust, 'customers');
    $r2 = transformAndLoad($pdo, $quotes, 'quotations');
    $r3 = transformAndLoad($pdo, $pos, 'purchaseOrders');
    $r4 = transformAndLoad($pdo, $invs, 'invoices');

    echo json_encode(["status"=>true, "message"=>"Sync completed", "details"=>[$r1,$r2,$r3,$r4]]);
    exit;
}

/* ---------- ACTION: stats (6 stat cards) ---------- */
if ($action === 'stats') {
    if (!$prefix) { echo json_encode(["status"=>false,"error"=>"prefix required"]); exit; }

    // 1. Total Invoice Amount & count
    $sql = "SELECT COALESCE(SUM(total_amount),0) AS total_invoice_amount, COUNT(*) FILTER (WHERE record_type='INVOICE') AS invoice_count
            FROM finance_consolidated WHERE company_prefix = :prefix AND record_type='INVOICE'";
    $stmt = $pdo->prepare($sql); $stmt->execute([":prefix"=>$prefix]); $s = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Amount Received
    $sql = "SELECT COALESCE(SUM(paid_amount),0) AS amount_received FROM finance_consolidated WHERE company_prefix=:prefix AND record_type='INVOICE'";
    $stmt = $pdo->prepare($sql); $stmt->execute([":prefix"=>$prefix]); $r2 = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Outstanding
    $sql = "SELECT COALESCE(SUM(balance_amount),0) AS outstanding, COUNT(*) FILTER (WHERE record_type='INVOICE' AND balance_amount>0) AS pending_invoices,
                   COUNT(DISTINCT customer_id) FILTER (WHERE record_type='INVOICE' AND balance_amount>0) AS pending_customers
            FROM finance_consolidated WHERE company_prefix = :prefix AND record_type='INVOICE'";
    $stmt = $pdo->prepare($sql); $stmt->execute([":prefix"=>$prefix]); $r3 = $stmt->fetch(PDO::FETCH_ASSOC);

    // 4. GST Liability (sum tax_amount on outstanding)
    $sql = "SELECT COALESCE(SUM(tax_amount),0) AS gst_liability FROM finance_consolidated WHERE company_prefix=:prefix AND record_type='INVOICE' AND balance_amount>0";
    $stmt = $pdo->prepare($sql); $stmt->execute([":prefix"=>$prefix]); $r4 = $stmt->fetch(PDO::FETCH_ASSOC);

    // 5. PO Commitments
    $sql = "SELECT COALESCE(SUM(total_amount),0) AS po_commitments, COUNT(*) FILTER (WHERE record_type='PURCHASE_ORDER') AS total_pos,
                   COUNT(*) FILTER (WHERE record_type='PURCHASE_ORDER' AND balance_amount>0) AS open_pos
            FROM finance_consolidated WHERE company_prefix=:prefix AND record_type='PURCHASE_ORDER'";
    $stmt = $pdo->prepare($sql); $stmt->execute([":prefix"=>$prefix]); $r5 = $stmt->fetch(PDO::FETCH_ASSOC);

    // 6. Claimable Amount (Invoice total - payments)
    $sql = "SELECT COALESCE(SUM(total_amount - paid_amount),0) AS claimable_amount, COUNT(*) FILTER (WHERE record_type='INVOICE' AND (total_amount - paid_amount) > 0) AS claimable_invoices
            FROM finance_consolidated WHERE company_prefix=:prefix AND record_type='INVOICE'";
    $stmt = $pdo->prepare($sql); $stmt->execute([":prefix"=>$prefix]); $r6 = $stmt->fetch(PDO::FETCH_ASSOC);

    // Build response (fields match what frontend stat cards expect)
    $out = [
        "status"=>true,
        "data"=>[
            "total_invoice_amount"=> (float)$s['total_invoice_amount'],
            "invoice_count"=> (int)$s['invoice_count'],

            "amount_received"=> (float)$r2['amount_received'],

            "outstanding"=> (float)$r3['outstanding'],
            "pending_invoices"=> (int)$r3['pending_invoices'],
            "pending_customers"=> (int)$r3['pending_customers'],

            "gst_liability"=> (float)$r4['gst_liability'],

            "po_commitments"=> (float)$r5['po_commitments'],
            "total_pos"=> (int)$r5['total_pos'],
            "open_pos"=> (int)$r5['open_pos'],

            "claimable_amount"=> (float)$r6['claimable_amount'],
            "claimable_invoices"=> (int)$r6['claimable_invoices']
        ]
    ];
    echo json_encode($out);
    exit;
}

/* ---------- ACTION: funnel ---------- */
if ($action === 'funnel') {
    if (!$prefix || !$customer) { echo json_encode(["status"=>false,"error"=>"prefix & customer required"]); exit; }
    // Quotations
    $sql = "SELECT COUNT(*) AS q_count, COALESCE(SUM(total_amount),0) AS q_value
            FROM finance_consolidated WHERE company_prefix=:prefix AND customer_id=:customer AND record_type='QUOTATION'";
    $stmt = $pdo->prepare($sql); $stmt->execute([":prefix"=>$prefix, ":customer"=>$customer]); $q = $stmt->fetch(PDO::FETCH_ASSOC);

    // POs
    $sql = "SELECT COUNT(*) AS po_count, COALESCE(SUM(total_amount),0) AS po_value FROM finance_consolidated
            WHERE company_prefix=:prefix AND customer_id=:customer AND record_type='PURCHASE_ORDER'";
    $stmt = $pdo->prepare($sql); $stmt->execute([":prefix"=>$prefix, ":customer"=>$customer]); $po = $stmt->fetch(PDO::FETCH_ASSOC);

    // Invoices
    $sql = "SELECT COUNT(*) AS inv_count, COALESCE(SUM(total_amount),0) AS inv_value FROM finance_consolidated
            WHERE company_prefix=:prefix AND customer_id=:customer AND record_type='INVOICE'";
    $stmt = $pdo->prepare([":prefix"=>$prefix, ":customer"=>$customer]); $inv = $stmt->fetch(PDO::FETCH_ASSOC);

    // Payments (invoices with paid_amount>0)
    $sql = "SELECT COUNT(*) AS pay_count, COALESCE(SUM(paid_amount),0) AS pay_value FROM finance_consolidated
            WHERE company_prefix=:prefix AND customer_id=:customer AND record_type='INVOICE' AND paid_amount>0";
    $stmt = $pdo->prepare($sql); $stmt->execute([":prefix"=>$prefix, ":customer"=>$customer]); $pay = $stmt->fetch(PDO::FETCH_ASSOC);

    // conversion rates (safe)
    $conv_po = (int)$q['q_count'] ? round(((int)$po['po_count'] / (int)$q['q_count']) * 100,2) : 0;
    $conv_inv = (int)$po['po_count'] ? round(((int)$inv['inv_count'] / (int)$po['po_count']) * 100,2) : 0;
    $conv_pay = (float)$inv['inv_value'] ? round(((float)$pay['pay_value'] / (float)$inv['inv_value']) * 100,2) : 0;

    $out = [
        "status"=>true,
        "data"=>[
            "quotations"=>["count"=>(int)$q['q_count'], "value"=>(float)$q['q_value']],
            "purchase_orders"=>["count"=>(int)$po['po_count'], "value"=>(float)$po['po_value'], "conversion_percent"=>$conv_po],
            "invoices"=>["count"=>(int)$inv['inv_count'], "value"=>(float)$inv['inv_value'], "conversion_percent"=>$conv_inv],
            "payments"=>["count"=>(int)$pay['pay_count'], "value"=>(float)$pay['pay_value'], "conversion_percent"=>$conv_pay]
        ]
    ];
    echo json_encode($out);
    exit;
}

/* ---------- ACTION: quotations_donut ---------- */
if ($action === 'quotations_donut') {
    if (!$prefix) { echo json_encode(["status"=>false,"error"=>"prefix required"]); exit; }
    $sql = "SELECT
              COALESCE(SUM(CASE WHEN status ILIKE 'PLACED' THEN 1 ELSE 0 END),0) AS placed,
              COALESCE(SUM(CASE WHEN status ILIKE 'REJECTED' THEN 1 ELSE 0 END),0) AS rejected,
              COALESCE(SUM(CASE WHEN status ILIKE 'PENDING' THEN 1 ELSE 0 END),0) AS pending
            FROM finance_consolidated
            WHERE company_prefix=:prefix AND record_type='QUOTATION'";
    $stmt = $pdo->prepare($sql); $stmt->execute([":prefix"=>$prefix]); $d = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(["status"=>true, "data"=>$d]); exit;
}

/* ---------- ACTION: po_claims ---------- */
if ($action === 'po_claims') {
    if (!$prefix) { echo json_encode(["status"=>false,"error"=>"prefix required"]); exit; }
    // We'll compute buckets using claim_percentage stored in table
    $sql = "SELECT
        COALESCE(SUM(CASE WHEN claim_percentage < 40 THEN total_amount ELSE 0 END),0) AS below_40,
        COALESCE(SUM(CASE WHEN claim_percentage BETWEEN 40 AND 60 THEN total_amount ELSE 0 END),0) AS between_40_60,
        COALESCE(SUM(CASE WHEN claim_percentage BETWEEN 60 AND 80 THEN total_amount ELSE 0 END),0) AS between_60_80,
        COALESCE(SUM(CASE WHEN claim_percentage >= 80 AND claim_percentage < 100 THEN total_amount ELSE 0 END),0) AS above_80,
        COALESCE(SUM(CASE WHEN claim_percentage = 100 THEN total_amount ELSE 0 END),0) AS full_claim,
        COALESCE(SUM(paid_amount),0) AS total_claimed,
        COALESCE(SUM(total_amount),0) AS total_po_value
      FROM finance_consolidated
      WHERE company_prefix=:prefix AND record_type='PURCHASE_ORDER'";
    $stmt = $pdo->prepare($sql); $stmt->execute([":prefix"=>$prefix]); $p = $stmt->fetch(PDO::FETCH_ASSOC);
    // fulfillment rate
    $fulfillment = (float)$p['total_po_value'] ? round(((float)$p['total_claimed'] / (float)$p['total_po_value']) * 100,2) : 0;
    $p['fulfillment_rate_percent'] = $fulfillment;
    echo json_encode(["status"=>true,"data"=>$p]); exit;
}

/* ---------- ACTION: invoices_summary (invoices & DSO) ---------- */
if ($action === 'invoices_summary') {
    if (!$prefix) { echo json_encode(["status"=>false,"error"=>"prefix required"]); exit; }
    $sql = "SELECT COALESCE(SUM(total_amount),0) AS total_invoice_value,
                   COALESCE(SUM(balance_amount),0) AS pending_invoice_value,
                   COALESCE(SUM(paid_amount),0) AS collected_amount,
                   COUNT(*) FILTER (WHERE record_type='INVOICE') AS invoice_count
            FROM finance_consolidated
            WHERE company_prefix=:prefix AND record_type='INVOICE'";
    $stmt = $pdo->prepare($sql); $stmt->execute([":prefix"=>$prefix]); $inv = $stmt->fetch(PDO::FETCH_ASSOC);

    // DSO: pending / (avg daily sales). Approximating avg daily sales = total_invoice_value / 30
    $total_invoice = (float)$inv['total_invoice_value'];
    $pending = (float)$inv['pending_invoice_value'];
    $avg_daily = $total_invoice / 30.0;
    $dso = $avg_daily > 0 ? round($pending / $avg_daily, 2) : 0;

    echo json_encode(["status"=>true, "data"=>[
        "total_invoice_value"=>$total_invoice,
        "pending_invoice_value"=>$pending,
        "collected_amount"=>(float)$inv['collected_amount'],
        "invoice_count"=>(int)$inv['invoice_count'],
        "dso"=>$dso
    ]]);
    exit;
}

/* ---------- ACTION: exposure (customer outstanding & concentration) ---------- */
if ($action === 'exposure') {
    if (!$prefix) { echo json_encode(["status"=>false,"error"=>"prefix required"]); exit; }
    // Outstanding by customer (without GST)
    $sql = "SELECT customer_name, COALESCE(SUM(balance_amount),0) AS outstanding FROM finance_consolidated
            WHERE company_prefix=:prefix AND record_type='INVOICE'
            GROUP BY customer_name ORDER BY outstanding DESC";
    $stmt = $pdo->prepare($sql); $stmt->execute([":prefix"=>$prefix]); $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // total outstanding with GST (claimable_with_gst)
    $sql = "SELECT COALESCE(SUM(balance_amount + tax_amount),0) AS claimable_with_gst FROM finance_consolidated
            WHERE company_prefix=:prefix AND record_type='INVOICE'";
    $stmt = $pdo->prepare($sql); $stmt->execute([":prefix"=>$prefix]); $tot = $stmt->fetch(PDO::FETCH_ASSOC);

    // build concentration data (percent share)
    $total_out = array_sum(array_map(function($r){ return (float)$r['outstanding']; }, $rows));
    $concentration = [];
    foreach ($rows as $r) {
        $pct = $total_out ? round(((float)$r['outstanding'] / $total_out) * 100, 2) : 0;
        $concentration[] = ["customer"=>$r['customer_name'], "outstanding"=>(float)$r['outstanding'], "pct"=>$pct];
    }

    echo json_encode(["status"=>true,"data"=>[
        "outstanding_by_customer"=>$rows,
        "claimable_with_gst"=>(float)$tot['claimable_with_gst'],
        "concentration"=>$concentration
    ]]);
    exit;
}

/* ---------- Default: return instructions ---------- */
echo json_encode([
    "status"=>true,
    "message"=>"Analytics backend running. Use ?action=sync|stats|funnel|quotations_donut|po_claims|invoices_summary|exposure & prefix=XXX (& customer=YYY when required).",
    "example"=>"?action=stats&prefix=AS"
]);
exit;
?>