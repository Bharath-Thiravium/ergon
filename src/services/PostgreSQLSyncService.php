<?php

class PostgreSQLSyncService {
    private $pgPdo;
    private $mysqlPdo;
    
    public function __construct($mysqlPdo) {
        $this->mysqlPdo = $mysqlPdo;
        
        // PostgreSQL connection - update with your actual credentials
        $pgDsn = "pgsql:host=localhost;port=5432;dbname=sap_db";
        $this->pgPdo = new PDO($pgDsn, 'postgres', 'postgres');
    }
    
    public function syncAll() {
        try {
            $invoiceCount = $this->syncInvoices();
            $poCount = $this->syncPurchaseOrders();
            $customerCount = $this->syncCustomers();
            
            return [
                'success' => true, 
                'message' => "Synced {$invoiceCount} invoices, {$poCount} POs, {$customerCount} customers from PostgreSQL"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function syncInvoices() {
        $pgSql = "SELECT invoice_number, customer_id, total_amount, taxable_amount, amount_paid, igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, status FROM invoices";
        $pgStmt = $this->pgPdo->query($pgSql);
        
        $mysqlSql = "INSERT INTO finance_invoices (invoice_number, customer_id, total_amount, taxable_amount, amount_paid, igst_amount, cgst_amount, sgst_amount, due_date, invoice_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE total_amount=VALUES(total_amount), amount_paid=VALUES(amount_paid)";
        $mysqlStmt = $this->mysqlPdo->prepare($mysqlSql);
        
        $count = 0;
        while ($row = $pgStmt->fetch(PDO::FETCH_ASSOC)) {
            $mysqlStmt->execute(array_values($row));
            $count++;
        }
        return $count;
    }
    
    private function syncPurchaseOrders() {
        $pgSql = "SELECT po_number, customer_id, po_total_value, po_date, po_status FROM purchase_orders";
        $pgStmt = $this->pgPdo->query($pgSql);
        
        $mysqlSql = "INSERT INTO finance_purchase_orders (po_number, customer_id, po_total_value, po_date, po_status) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE po_total_value=VALUES(po_total_value), po_status=VALUES(po_status)";
        $mysqlStmt = $this->mysqlPdo->prepare($mysqlSql);
        
        $count = 0;
        while ($row = $pgStmt->fetch(PDO::FETCH_ASSOC)) {
            $mysqlStmt->execute(array_values($row));
            $count++;
        }
        return $count;
    }
    
    private function syncCustomers() {
        $pgSql = "SELECT customer_id, customer_name, customer_gstin FROM customers";
        $pgStmt = $this->pgPdo->query($pgSql);
        
        $mysqlSql = "INSERT INTO finance_customers (customer_id, customer_name, customer_gstin) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE customer_name=VALUES(customer_name)";
        $mysqlStmt = $this->mysqlPdo->prepare($mysqlSql);
        
        $count = 0;
        while ($row = $pgStmt->fetch(PDO::FETCH_ASSOC)) {
            $mysqlStmt->execute(array_values($row));
            $count++;
        }
        return $count;
    }
}