<?php

class DataSyncService {
    private $pgConnection;
    private $mysqlConnection;
    
    public function __construct() {
        $this->mysqlConnection = Database::connect();
        if (extension_loaded('pdo_pgsql')) {
            $this->pgConnection = $this->getPostgreSQLConnection();
        }
    }
    
    public function isPostgreSQLAvailable(): bool {
        return $this->pgConnection !== null;
    }

    private function getPostgreSQLConnection() {
        $config = Database::getPostgreSQLConfig();
        $pg = $config['postgresql'];
        try {
            $pdo = new PDO(
                "pgsql:host={$pg['host']};port={$pg['port']};dbname={$pg['database']};connect_timeout=10",
                $pg['username'],
                $pg['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 30
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            error_log("PostgreSQL connection failed: " . $e->getMessage());
            return null;
        }
    }

    public function syncAllTables() {
        if (!$this->isPostgreSQLAvailable()) {
            return array_fill_keys(
                ['companies', 'customers', 'quotations', 'purchase_orders', 'invoices', 'payments'],
                ['records' => 0, 'status' => 'unavailable', 'error' => 'pdo_pgsql driver not available on this server']
            );
        }

        // Phase 1: fetch everything from PG in one session
        $allData = [];
        $queries = [
            'companies'       => ["SELECT id, company_prefix, name FROM authentication_company WHERE approval_status = 'approved'", ['id','company_prefix','name']],
            'customers'       => ['SELECT id, name, gstin FROM finance_customer WHERE is_active = true', ['id','name','gstin']],
            'quotations'      => ['SELECT id, quotation_number, customer_id, company_id, total_amount, quotation_date, status FROM finance_quotations', ['id','quotation_number','customer_id','company_id','total_amount','quotation_date','status']],
            'purchase_orders' => ['SELECT id, po_number, customer_id, company_id, total_amount, po_date, status FROM finance_purchase_orders', ['id','po_number','customer_id','company_id','total_amount','po_date','status']],
            'invoices'        => ['SELECT id, invoice_number, customer_id, company_id, total_amount, subtotal, paid_amount, igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, payment_status, outstanding_amount FROM finance_invoices', ['id','invoice_number','customer_id','company_id','total_amount','subtotal','paid_amount','igst_amount','cgst_amount','sgst_amount','due_date','invoice_date','payment_status','outstanding_amount']],
            'payments'        => ['SELECT id, payment_number, customer_id, company_id, amount, payment_date, COALESCE(reference_number, payment_number) as reference_number, status FROM finance_payments', ['id','payment_number','customer_id','company_id','amount','payment_date','reference_number','status']],
        ];
        // Set PG statement timeout to 20s to avoid silent hangs
        $this->pgConnection->exec("SET statement_timeout = 20000");
        foreach ($queries as $key => [$sql, $fields]) {
            error_log("DataSync Phase1: fetching $key");
            $stmt = $this->pgConnection->prepare($sql);
            $stmt->execute();
            $allData[$key] = ['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'fields' => $fields];
            $stmt->closeCursor();
            error_log("DataSync Phase1: $key done (" . count($allData[$key]['rows']) . " rows)");
        }
        // Close PG connection before MySQL work
        $this->pgConnection = null;

        // Phase 2: insert into MySQL
        $results = [];
        $results['companies']       = $this->insertRows($allData['companies']['rows'], $allData['companies']['fields'],
            'INSERT INTO finance_companies (company_id, company_prefix, company_name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE company_prefix=VALUES(company_prefix), company_name=VALUES(company_name)');
        $results['customers']       = $this->insertRows($allData['customers']['rows'], $allData['customers']['fields'],
            'INSERT INTO finance_customers (customer_id, customer_name, customer_gstin) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE customer_name=VALUES(customer_name), customer_gstin=VALUES(customer_gstin), updated_at=NOW()');
        $results['quotations']      = $this->insertRows($allData['quotations']['rows'], $allData['quotations']['fields'],
            'INSERT INTO finance_quotations (id, quotation_number, customer_id, company_id, quotation_amount, quotation_date, status) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE quotation_number=VALUES(quotation_number), customer_id=VALUES(customer_id), company_id=VALUES(company_id), quotation_amount=VALUES(quotation_amount), quotation_date=VALUES(quotation_date), status=VALUES(status)');
        $results['purchase_orders'] = $this->insertRows($allData['purchase_orders']['rows'], $allData['purchase_orders']['fields'],
            'INSERT INTO finance_purchase_orders (id, po_number, customer_id, company_id, po_total_value, po_date, po_status) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE po_number=VALUES(po_number), customer_id=VALUES(customer_id), company_id=VALUES(company_id), po_total_value=VALUES(po_total_value), po_date=VALUES(po_date), po_status=VALUES(po_status)');
        $results['invoices']        = $this->insertRows($allData['invoices']['rows'], $allData['invoices']['fields'],
            'INSERT INTO finance_invoices (id, invoice_number, customer_id, company_id, total_amount, taxable_amount, amount_paid, igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, status, outstanding_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE invoice_number=VALUES(invoice_number), customer_id=VALUES(customer_id), company_id=VALUES(company_id), total_amount=VALUES(total_amount), taxable_amount=VALUES(taxable_amount), amount_paid=VALUES(amount_paid), igst_amount=VALUES(igst_amount), cgst_amount=VALUES(cgst_amount), sgst_amount=VALUES(sgst_amount), due_date=VALUES(due_date), invoice_date=VALUES(invoice_date), status=VALUES(status), outstanding_amount=VALUES(outstanding_amount)');
        $results['payments']        = $this->insertRows($allData['payments']['rows'], $allData['payments']['fields'],
            'INSERT INTO finance_payments (id, payment_number, customer_id, company_id, amount, payment_date, receipt_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE payment_number=VALUES(payment_number), customer_id=VALUES(customer_id), company_id=VALUES(company_id), amount=VALUES(amount), payment_date=VALUES(payment_date), receipt_number=VALUES(receipt_number), status=VALUES(status)');
        return $results;
    }

    private function insertRows($rows, $fields, $insertQuery) {
        $syncStarted = date('Y-m-d H:i:s');
        $recordsSynced = 0;
        try {
            if (empty($rows)) {
                $this->logSync('', 0, 'completed', null, $syncStarted);
                return ['records' => 0, 'status' => 'no_data'];
            }
            $stmt = $this->mysqlConnection->prepare($insertQuery);
            foreach ($rows as $row) {
                $values = array_map(fn($f) => $row[$f] ?? null, $fields);
                $stmt->execute($values);
                $recordsSynced++;
            }
            return ['records' => $recordsSynced, 'status' => 'success'];
        } catch (Exception $e) {
            return ['records' => $recordsSynced, 'status' => 'error', 'error' => $e->getMessage()];
        }
    }

    public function syncCompanies() {
        return $this->syncTable(
            'authentication_company',
            "SELECT id, company_prefix, name FROM authentication_company WHERE approval_status = 'approved'",
            'INSERT INTO finance_companies (company_id, company_prefix, company_name) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE company_prefix = VALUES(company_prefix), company_name = VALUES(company_name)',
            ['id', 'company_prefix', 'name']
        );
    }

    public function syncCustomers() {
        return $this->syncTable(
            'finance_customer',
            'SELECT id, name, gstin FROM finance_customer WHERE is_active = true',
            'INSERT INTO finance_customers (customer_id, customer_name, customer_gstin) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE customer_name = VALUES(customer_name), customer_gstin = VALUES(customer_gstin), updated_at = NOW()',
            ['id', 'name', 'gstin']
        );
    }

    public function syncQuotations() {
        return $this->syncTable(
            'finance_quotations',
            'SELECT id, quotation_number, customer_id, company_id, total_amount, quotation_date, status FROM finance_quotations',
            'INSERT INTO finance_quotations (id, quotation_number, customer_id, company_id, quotation_amount, quotation_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE quotation_number = VALUES(quotation_number), customer_id = VALUES(customer_id),
             company_id = VALUES(company_id), quotation_amount = VALUES(quotation_amount),
             quotation_date = VALUES(quotation_date), status = VALUES(status)',
            ['id', 'quotation_number', 'customer_id', 'company_id', 'total_amount', 'quotation_date', 'status']
        );
    }

    public function syncPurchaseOrders() {
        return $this->syncTable(
            'finance_purchase_orders',
            'SELECT id, po_number, customer_id, company_id, total_amount, po_date, status FROM finance_purchase_orders',
            'INSERT INTO finance_purchase_orders (id, po_number, customer_id, company_id, po_total_value, po_date, po_status) VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE po_number = VALUES(po_number), customer_id = VALUES(customer_id),
             company_id = VALUES(company_id), po_total_value = VALUES(po_total_value),
             po_date = VALUES(po_date), po_status = VALUES(po_status)',
            ['id', 'po_number', 'customer_id', 'company_id', 'total_amount', 'po_date', 'status']
        );
    }

    public function syncInvoices() {
        return $this->syncTable(
            'finance_invoices',
            'SELECT id, invoice_number, customer_id, company_id, total_amount, subtotal, paid_amount,
                    igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, payment_status,
                    outstanding_amount FROM finance_invoices',
            'INSERT INTO finance_invoices (id, invoice_number, customer_id, company_id, total_amount, taxable_amount,
                    amount_paid, igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, status, outstanding_amount)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE invoice_number = VALUES(invoice_number), customer_id = VALUES(customer_id),
             company_id = VALUES(company_id), total_amount = VALUES(total_amount),
             taxable_amount = VALUES(taxable_amount), amount_paid = VALUES(amount_paid),
             igst_amount = VALUES(igst_amount), cgst_amount = VALUES(cgst_amount), sgst_amount = VALUES(sgst_amount),
             due_date = VALUES(due_date), invoice_date = VALUES(invoice_date),
             status = VALUES(status), outstanding_amount = VALUES(outstanding_amount)',
            ['id', 'invoice_number', 'customer_id', 'company_id', 'total_amount', 'subtotal', 'paid_amount',
             'igst_amount', 'cgst_amount', 'sgst_amount', 'due_date', 'invoice_date', 'payment_status',
             'outstanding_amount']
        );
    }

    public function syncPayments() {
        return $this->syncTable(
            'finance_payments',
            'SELECT id, payment_number, customer_id, company_id, amount, payment_date, COALESCE(reference_number, payment_number) as reference_number, status FROM finance_payments',
            'INSERT INTO finance_payments (id, payment_number, customer_id, company_id, amount, payment_date, receipt_number, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE payment_number = VALUES(payment_number), customer_id = VALUES(customer_id),
             company_id = VALUES(company_id), amount = VALUES(amount),
             payment_date = VALUES(payment_date), receipt_number = VALUES(receipt_number), status = VALUES(status)',
            ['id', 'payment_number', 'customer_id', 'company_id', 'amount', 'payment_date', 'reference_number', 'status']
        );
    }

    private function syncTable($tableName, $selectQuery, $insertQuery, $fields) {
        $syncStarted = date('Y-m-d H:i:s');
        $recordsSynced = 0;
        $errorMessage = null;

        try {
            $stmt = $this->pgConnection->prepare($selectQuery);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            unset($stmt);

            if (empty($rows)) {
                $this->logSync($tableName, 0, 'completed', null, $syncStarted);
                return ['table' => $tableName, 'records' => 0, 'status' => 'no_data'];
            }

            $insertStmt = $this->mysqlConnection->prepare($insertQuery);
            foreach ($rows as $row) {
                $values = [];
                foreach ($fields as $field) {
                    $values[] = $row[$field] ?? null;
                }
                $insertStmt->execute($values);
                $recordsSynced++;
            }

            $this->logSync($tableName, $recordsSynced, 'completed', null, $syncStarted);
            return ['table' => $tableName, 'records' => $recordsSynced, 'status' => 'success'];

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $this->logSync($tableName, $recordsSynced, 'failed', $errorMessage, $syncStarted);
            return ['table' => $tableName, 'records' => $recordsSynced, 'status' => 'error', 'error' => $errorMessage];
        }
    }

    private function logSync($tableName, $recordsSynced, $status, $errorMessage, $syncStarted) {
        try {
            $stmt = $this->mysqlConnection->prepare(
                'INSERT INTO sync_log (table_name, records_synced, sync_status, error_message, sync_started_at, sync_completed_at)
                 VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$tableName, $recordsSynced, $status, $errorMessage, $syncStarted]);
        } catch (Exception $e) {
            error_log("Failed to log sync: " . $e->getMessage());
        }
    }

    public function getSyncHistory($limit = 10) {
        try {
            $stmt = $this->mysqlConnection->prepare(
                'SELECT * FROM sync_log ORDER BY sync_started_at DESC LIMIT ?'
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
