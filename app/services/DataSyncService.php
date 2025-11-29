<?php

class DataSyncService {
    private $pgConnection;
    private $mysqlConnection;
    
    public function __construct() {
        $this->pgConnection = $this->getPostgreSQLConnection();
        $this->mysqlConnection = $this->getMySQLConnection();
    }
    
    private function getPostgreSQLConnection() {
        $config = require_once __DIR__ . '/../config/database.php';
        $pg = $config['postgresql'];
        
        try {
            $pdo = new PDO(
                "pgsql:host={$pg['host']};port={$pg['port']};dbname={$pg['database']}",
                $pg['username'],
                $pg['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("PostgreSQL connection failed: " . $e->getMessage());
        }
    }
    
    private function getMySQLConnection() {
        $config = require_once __DIR__ . '/../config/database.php';
        $mysql = $config['mysql'];
        
        try {
            $pdo = new PDO(
                "mysql:host={$mysql['host']};port={$mysql['port']};dbname={$mysql['database']};charset=utf8mb4",
                $mysql['username'],
                $mysql['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("MySQL connection failed: " . $e->getMessage());
        }
    }
    
    public function syncAllTables() {
        $results = [];
        
        $results['customers'] = $this->syncCustomers();
        $results['quotations'] = $this->syncQuotations();
        $results['purchase_orders'] = $this->syncPurchaseOrders();
        $results['invoices'] = $this->syncInvoices();
        $results['payments'] = $this->syncPayments();
        
        return $results;
    }
    
    public function syncCustomers() {
        return $this->syncTable(
            'finance_customers',
            'SELECT customer_id, customer_name, customer_gstin FROM finance_customers',
            'INSERT INTO finance_customers (customer_id, customer_name, customer_gstin) VALUES (?, ?, ?) 
             ON DUPLICATE KEY UPDATE customer_name = VALUES(customer_name), customer_gstin = VALUES(customer_gstin), updated_at = NOW()',
            ['customer_id', 'customer_name', 'customer_gstin']
        );
    }
    
    public function syncQuotations() {
        return $this->syncTable(
            'finance_quotations',
            'SELECT quotation_number, customer_id, quotation_amount, quotation_date, status FROM finance_quotations',
            'INSERT INTO finance_quotations (quotation_number, customer_id, quotation_amount, quotation_date, status) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id), quotation_amount = VALUES(quotation_amount), 
             quotation_date = VALUES(quotation_date), status = VALUES(status), updated_at = NOW()',
            ['quotation_number', 'customer_id', 'quotation_amount', 'quotation_date', 'status']
        );
    }
    
    public function syncPurchaseOrders() {
        return $this->syncTable(
            'finance_purchase_orders',
            'SELECT po_number, customer_id, po_total_value, po_date, po_status FROM finance_purchase_orders',
            'INSERT INTO finance_purchase_orders (po_number, customer_id, po_total_value, po_date, po_status) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id), po_total_value = VALUES(po_total_value),
             po_date = VALUES(po_date), po_status = VALUES(po_status), updated_at = NOW()',
            ['po_number', 'customer_id', 'po_total_value', 'po_date', 'po_status']
        );
    }
    
    public function syncInvoices() {
        return $this->syncTable(
            'finance_invoices',
            'SELECT invoice_number, customer_id, total_amount, taxable_amount, amount_paid, 
                    igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, status 
             FROM finance_invoices',
            'INSERT INTO finance_invoices (invoice_number, customer_id, total_amount, taxable_amount, amount_paid,
                    igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id), total_amount = VALUES(total_amount),
             taxable_amount = VALUES(taxable_amount), amount_paid = VALUES(amount_paid),
             igst_amount = VALUES(igst_amount), cgst_amount = VALUES(cgst_amount), sgst_amount = VALUES(sgst_amount),
             due_date = VALUES(due_date), invoice_date = VALUES(invoice_date), status = VALUES(status), updated_at = NOW()',
            ['invoice_number', 'customer_id', 'total_amount', 'taxable_amount', 'amount_paid', 
             'igst_amount', 'cgst_amount', 'sgst_amount', 'due_date', 'invoice_date', 'status']
        );
    }
    
    public function syncPayments() {
        return $this->syncTable(
            'finance_payments',
            'SELECT payment_id, customer_id, amount, payment_date, receipt_number, status FROM finance_payments',
            'INSERT INTO finance_payments (payment_id, customer_id, amount, payment_date, receipt_number, status) VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id), amount = VALUES(amount),
             payment_date = VALUES(payment_date), receipt_number = VALUES(receipt_number), status = VALUES(status), updated_at = NOW()',
            ['payment_id', 'customer_id', 'amount', 'payment_date', 'receipt_number', 'status']
        );
    }
    
    private function syncTable($tableName, $selectQuery, $insertQuery, $fields) {
        $syncStarted = date('Y-m-d H:i:s');
        $recordsSynced = 0;
        $errorMessage = null;
        
        try {
            // Fetch data from PostgreSQL
            $stmt = $this->pgConnection->prepare($selectQuery);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            
            if (empty($rows)) {
                $this->logSync($tableName, 0, 'completed', null, $syncStarted);
                return ['table' => $tableName, 'records' => 0, 'status' => 'no_data'];
            }
            
            // Insert/Update data in MySQL
            $insertStmt = $this->mysqlConnection->prepare($insertQuery);
            
            $this->mysqlConnection->beginTransaction();
            
            foreach ($rows as $row) {
                $values = [];
                foreach ($fields as $field) {
                    $values[] = $row[$field] ?? null;
                }
                $insertStmt->execute($values);
                $recordsSynced++;
            }
            
            $this->mysqlConnection->commit();
            
            $this->logSync($tableName, $recordsSynced, 'completed', null, $syncStarted);
            
            return [
                'table' => $tableName,
                'records' => $recordsSynced,
                'status' => 'success'
            ];
            
        } catch (Exception $e) {
            if ($this->mysqlConnection->inTransaction()) {
                $this->mysqlConnection->rollback();
            }
            
            $errorMessage = $e->getMessage();
            $this->logSync($tableName, $recordsSynced, 'failed', $errorMessage, $syncStarted);
            
            return [
                'table' => $tableName,
                'records' => $recordsSynced,
                'status' => 'error',
                'error' => $errorMessage
            ];
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