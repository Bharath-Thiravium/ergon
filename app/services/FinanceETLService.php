<?php

require_once __DIR__ . '/../config/database.php';

class FinanceETLService {
    
    private $db;
    private $pgConn;
    
    public function __construct() {
        $this->db = Database::connect();
        $this->createConsolidatedTable();
    }
    
    /**
     * Main ETL process - Extract from SAP API, Transform, Load to SQL
     */
    public function runETL($prefix = null) {
        try {
            // Step 1: Extract from SAP (PostgreSQL)
            $rawData = $this->extractFromSAP();
            
            // Step 2: Transform data for analytics
            $transformedData = $this->transformData($rawData, $prefix);
            
            // Step 3: Load into consolidated table
            $this->loadToSQL($transformedData, $prefix);
            
            // Step 4: Calculate analytics metrics
            $this->calculateAnalytics($prefix);
            
            return [
                'success' => true,
                'records_processed' => count($transformedData),
                'prefix' => $prefix
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract data from SAP API (PostgreSQL source)
     */
    private function extractFromSAP() {
        $this->connectToSAP();
        
        $tables = [
            'finance_invoices',
            'finance_quotations', 
            'finance_customers',
            'finance_customer',
            'finance_payments',
            'finance_purchase_orders'
        ];
        
        $extractedData = [];
        
        foreach ($tables as $tableName) {
            $result = @pg_query($this->pgConn, "SELECT * FROM $tableName");
            if ($result && pg_num_rows($result) > 0) {
                $data = pg_fetch_all($result);
                $extractedData[$tableName] = $data;
            }
        }
        
        @pg_close($this->pgConn);
        return $extractedData;
    }
    
    /**
     * Transform raw SAP data into analytics-ready format
     */
    private function transformData($rawData, $prefix) {
        $consolidated = [];
        
        // Transform Invoices
        if (isset($rawData['finance_invoices'])) {
            foreach ($rawData['finance_invoices'] as $invoice) {
                if ($this->matchesPrefix($invoice['invoice_number'] ?? '', $prefix)) {
                    $consolidated[] = [
                        'record_type' => 'invoice',
                        'document_number' => $invoice['invoice_number'] ?? '',
                        'customer_id' => $invoice['customer_id'] ?? '',
                        'customer_name' => $invoice['customer_name'] ?? '',
                        'amount' => floatval($invoice['total_amount'] ?? 0),
                        'taxable_amount' => floatval($invoice['taxable_amount'] ?? $invoice['total_amount'] ?? 0),
                        'amount_paid' => floatval($invoice['amount_paid'] ?? 0),
                        'outstanding_amount' => floatval($invoice['outstanding_amount'] ?? 0),
                        'igst' => floatval($invoice['igst'] ?? 0),
                        'cgst' => floatval($invoice['cgst'] ?? 0),
                        'sgst' => floatval($invoice['sgst'] ?? 0),
                        'due_date' => $invoice['due_date'] ?? null,
                        'invoice_date' => $invoice['invoice_date'] ?? $invoice['created_date'] ?? null,
                        'status' => $invoice['status'] ?? 'pending',
                        'company_prefix' => $prefix,
                        'raw_data' => json_encode($invoice)
                    ];
                }
            }
        }
        
        // Transform Quotations
        if (isset($rawData['finance_quotations'])) {
            foreach ($rawData['finance_quotations'] as $quotation) {
                if ($this->matchesPrefix($quotation['quotation_number'] ?? '', $prefix)) {
                    $consolidated[] = [
                        'record_type' => 'quotation',
                        'document_number' => $quotation['quotation_number'] ?? '',
                        'customer_id' => $quotation['customer_id'] ?? '',
                        'customer_name' => $quotation['customer_name'] ?? $quotation['name'] ?? '',
                        'amount' => floatval($quotation['total_amount'] ?? $quotation['amount'] ?? 0),
                        'taxable_amount' => floatval($quotation['total_amount'] ?? $quotation['amount'] ?? 0),
                        'amount_paid' => 0,
                        'outstanding_amount' => 0,
                        'igst' => 0,
                        'cgst' => 0,
                        'sgst' => 0,
                        'due_date' => null,
                        'invoice_date' => $quotation['created_date'] ?? $quotation['date'] ?? null,
                        'status' => $quotation['status'] ?? 'draft',
                        'company_prefix' => $prefix,
                        'raw_data' => json_encode($quotation)
                    ];
                }
            }
        }
        
        // Transform Purchase Orders
        if (isset($rawData['finance_purchase_orders'])) {
            foreach ($rawData['finance_purchase_orders'] as $po) {
                $poNumber = $po['po_number'] ?? $po['internal_po_number'] ?? '';
                if ($this->matchesPrefix($poNumber, $prefix)) {
                    $consolidated[] = [
                        'record_type' => 'purchase_order',
                        'document_number' => $poNumber,
                        'customer_id' => $po['customer_id'] ?? '',
                        'customer_name' => $po['customer_name'] ?? '',
                        'amount' => floatval($po['total_amount'] ?? $po['amount'] ?? $po['subtotal'] ?? 0),
                        'taxable_amount' => floatval($po['total_amount'] ?? $po['amount'] ?? $po['subtotal'] ?? 0),
                        'amount_paid' => floatval($po['amount_paid'] ?? 0),
                        'outstanding_amount' => floatval($po['total_amount'] ?? 0) - floatval($po['amount_paid'] ?? 0),
                        'igst' => 0,
                        'cgst' => 0,
                        'sgst' => 0,
                        'due_date' => null,
                        'invoice_date' => $po['po_date'] ?? $po['created_date'] ?? null,
                        'status' => $po['status'] ?? 'open',
                        'company_prefix' => $prefix,
                        'raw_data' => json_encode($po)
                    ];
                }
            }
        }
        
        // Transform Payments
        if (isset($rawData['finance_payments'])) {
            foreach ($rawData['finance_payments'] as $payment) {
                $consolidated[] = [
                    'record_type' => 'payment',
                    'document_number' => $payment['payment_reference'] ?? $payment['reference'] ?? '',
                    'customer_id' => $payment['customer_id'] ?? '',
                    'customer_name' => $payment['customer_name'] ?? '',
                    'amount' => floatval($payment['amount'] ?? $payment['payment_amount'] ?? 0),
                    'taxable_amount' => floatval($payment['amount'] ?? $payment['payment_amount'] ?? 0),
                    'amount_paid' => floatval($payment['amount'] ?? $payment['payment_amount'] ?? 0),
                    'outstanding_amount' => 0,
                    'igst' => 0,
                    'cgst' => 0,
                    'sgst' => 0,
                    'due_date' => null,
                    'invoice_date' => $payment['payment_date'] ?? $payment['date'] ?? null,
                    'status' => 'completed',
                    'company_prefix' => $prefix,
                    'raw_data' => json_encode($payment)
                ];
            }
        }
        
        return $consolidated;
    }
    
    /**
     * Load transformed data into consolidated SQL table
     */
    private function loadToSQL($data, $prefix) {
        // Clear existing data for this prefix
        if ($prefix) {
            $stmt = $this->db->prepare("DELETE FROM finance_consolidated WHERE company_prefix = ?");
            $stmt->execute([$prefix]);
        } else {
            $this->db->exec("TRUNCATE TABLE finance_consolidated");
        }
        
        // Insert new data
        $stmt = $this->db->prepare("
            INSERT INTO finance_consolidated (
                record_type, document_number, customer_id, customer_name,
                amount, taxable_amount, amount_paid, outstanding_amount,
                igst, cgst, sgst, due_date, invoice_date, status,
                company_prefix, raw_data, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        foreach ($data as $record) {
            $stmt->execute([
                $record['record_type'],
                $record['document_number'],
                $record['customer_id'],
                $record['customer_name'],
                $record['amount'],
                $record['taxable_amount'],
                $record['amount_paid'],
                $record['outstanding_amount'],
                $record['igst'],
                $record['cgst'],
                $record['sgst'],
                $record['due_date'],
                $record['invoice_date'],
                $record['status'],
                $record['company_prefix'],
                $record['raw_data']
            ]);
        }
    }
    
    /**
     * Calculate analytics metrics from consolidated data
     */
    private function calculateAnalytics($prefix) {
        // Calculate Stat Card 3 using backend-only processing
        $statCard3 = $this->calculateStatCard3($prefix);
        
        // Calculate Stat Card 6 using backend-only processing
        $statCard6 = $this->calculateStatCard6($prefix);
        
        // Revenue Analytics (other stats)
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as invoice_count,
                SUM(amount) as total_revenue,
                SUM(amount_paid) as amount_received,
                COUNT(DISTINCT customer_id) as customer_count,
                SUM(igst) as igst_liability,
                SUM(cgst + sgst) as cgst_sgst_total
            FROM finance_consolidated 
            WHERE record_type = 'invoice' AND company_prefix = ?
        ");
        $stmt->execute([$prefix]);
        $invoiceStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Merge Stat Card 3 and 6 results
        $invoiceStats = array_merge($invoiceStats, $statCard3, $statCard6);
        
        // PO Analytics
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as po_count,
                SUM(amount) as po_commitments,
                SUM(CASE WHEN status IN ('open', 'pending') THEN 1 ELSE 0 END) as open_pos,
                SUM(CASE WHEN status IN ('closed', 'completed') THEN 1 ELSE 0 END) as closed_pos,
                SUM(outstanding_amount) as claimable_amount
            FROM finance_consolidated 
            WHERE record_type = 'purchase_order' AND company_prefix = ?
        ");
        $stmt->execute([$prefix]);
        $poStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Quotation Analytics
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_quotations,
                SUM(CASE WHEN status = 'placed' THEN 1 ELSE 0 END) as placed_quotations,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_quotations,
                SUM(CASE WHEN status IN ('pending', 'draft') THEN 1 ELSE 0 END) as pending_quotations
            FROM finance_consolidated 
            WHERE record_type = 'quotation' AND company_prefix = ?
        ");
        $stmt->execute([$prefix]);
        $quotationStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Save to dashboard_stats
        $this->saveDashboardStats($prefix, $invoiceStats, $poStats, $quotationStats);
    }
    
    /**
     * Calculate Stat Card 3 using backend-only processing (no SQL aggregation)
     */
    private function calculateStatCard3($prefix) {
        $this->connectToSAP();
        
        // Step 1: Fetch raw invoice rows without SQL aggregation
        $query = "SELECT id, invoice_number, taxable_amount, amount_paid, cgst, sgst, total_amount, due_date, customer_gstin FROM finance_invoices WHERE invoice_number LIKE '$prefix%'";
        $result = @pg_query($this->pgConn, $query);
        
        if (!$result) {
            @pg_close($this->pgConn);
            return [
                'outstanding_amount' => 0,
                'pending_invoices' => 0,
                'customers_pending' => 0,
                'overdue_amount' => 0,
                'outstanding_percentage' => 0
            ];
        }
        
        $invoices = pg_fetch_all($result);
        @pg_close($this->pgConn);
        
        if (!$invoices) {
            return [
                'outstanding_amount' => 0,
                'pending_invoices' => 0,
                'customers_pending' => 0,
                'overdue_amount' => 0,
                'outstanding_percentage' => 0
            ];
        }
        
        // Step 2: Backend calculations only
        $totalTaxableAmount = 0;
        $outstandingAmount = 0;
        $pendingInvoices = 0;
        $overdueAmount = 0;
        $customersWithPending = [];
        $today = date('Y-m-d');
        
        foreach ($invoices as $invoice) {
            $taxableAmount = floatval($invoice['taxable_amount'] ?? 0);
            $amountPaid = floatval($invoice['amount_paid'] ?? 0);
            $dueDate = $invoice['due_date'] ?? null;
            $customerGstin = $invoice['customer_gstin'] ?? '';
            
            // Step 3: Calculate pending amount (taxable only, no GST)
            $pendingAmount = $taxableAmount - $amountPaid;
            $totalTaxableAmount += $taxableAmount;
            
            if ($pendingAmount > 0) {
                // Step 4: Calculate metrics
                $outstandingAmount += $pendingAmount;
                $pendingInvoices++;
                
                // Track unique customers with pending amounts
                if ($customerGstin && !in_array($customerGstin, $customersWithPending)) {
                    $customersWithPending[] = $customerGstin;
                }
                
                // Calculate overdue amount
                if ($dueDate && $dueDate < $today) {
                    $overdueAmount += $pendingAmount;
                }
            }
        }
        
        // Step 5: Return computed results
        return [
            'outstanding_amount' => $outstandingAmount,
            'pending_invoices' => $pendingInvoices,
            'customers_pending' => count($customersWithPending),
            'overdue_amount' => $overdueAmount,
            'outstanding_percentage' => $totalTaxableAmount > 0 ? ($outstandingAmount / $totalTaxableAmount) * 100 : 0
        ];
    }
    
    /**
     * Calculate Stat Card 6 using backend-only processing (no SQL aggregation)
     */
    private function calculateStatCard6($prefix) {
        $this->connectToSAP();
        
        // Step 1: Fetch raw invoice rows without SQL aggregation
        $query = "SELECT id, invoice_number, taxable_amount, total_amount, amount_paid, customer_gstin, invoice_date FROM finance_invoices WHERE invoice_number LIKE '$prefix%'";
        $result = @pg_query($this->pgConn, $query);
        
        if (!$result) {
            @pg_close($this->pgConn);
            return [
                'claimable_amount' => 0,
                'claimable_pos' => 0,
                'claim_rate' => 0
            ];
        }
        
        $invoices = pg_fetch_all($result);
        @pg_close($this->pgConn);
        
        if (!$invoices) {
            return [
                'claimable_amount' => 0,
                'claimable_pos' => 0,
                'claim_rate' => 0
            ];
        }
        
        // Step 2: Backend calculations only
        $totalInvoiceAmount = 0;
        $claimableAmount = 0;
        $claimablePos = 0;
        
        foreach ($invoices as $invoice) {
            $totalAmount = floatval($invoice['total_amount'] ?? 0);
            $amountPaid = floatval($invoice['amount_paid'] ?? 0);
            
            // Step 3: Calculate claimable amount (total_amount - amount_paid, GST included)
            $claimable = $totalAmount - $amountPaid;
            $totalInvoiceAmount += $totalAmount;
            
            if ($claimable > 0) {
                // Step 4: Calculate metrics
                $claimableAmount += $claimable;
                $claimablePos++;
            }
        }
        
        // Step 5: Return computed results
        return [
            'claimable_amount' => $claimableAmount,
            'claimable_pos' => $claimablePos,
            'claim_rate' => $totalInvoiceAmount > 0 ? ($claimableAmount / $totalInvoiceAmount) * 100 : 0
        ];
    }
    
    /**
     * Save calculated analytics to dashboard_stats table
     */
    private function saveDashboardStats($prefix, $invoiceStats, $poStats, $quotationStats) {
        $stmt = $this->db->prepare("
            INSERT INTO dashboard_stats (
                company_prefix, total_revenue, invoice_count, amount_received,
                outstanding_amount, pending_invoices, customers_pending, overdue_amount, outstanding_percentage,
                customer_count, po_commitments, open_pos, closed_pos, claimable_amount, claimable_pos, claim_rate,
                igst_liability, cgst_sgst_total, gst_liability,
                placed_quotations, rejected_quotations, pending_quotations, total_quotations,
                generated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                total_revenue = VALUES(total_revenue),
                invoice_count = VALUES(invoice_count),
                amount_received = VALUES(amount_received),
                outstanding_amount = VALUES(outstanding_amount),
                pending_invoices = VALUES(pending_invoices),
                customers_pending = VALUES(customers_pending),
                overdue_amount = VALUES(overdue_amount),
                outstanding_percentage = VALUES(outstanding_percentage),
                customer_count = VALUES(customer_count),
                po_commitments = VALUES(po_commitments),
                open_pos = VALUES(open_pos),
                closed_pos = VALUES(closed_pos),
                claimable_amount = VALUES(claimable_amount),
                claimable_pos = VALUES(claimable_pos),
                claim_rate = VALUES(claim_rate),
                igst_liability = VALUES(igst_liability),
                cgst_sgst_total = VALUES(cgst_sgst_total),
                gst_liability = VALUES(gst_liability),
                placed_quotations = VALUES(placed_quotations),
                rejected_quotations = VALUES(rejected_quotations),
                pending_quotations = VALUES(pending_quotations),
                total_quotations = VALUES(total_quotations),
                generated_at = NOW()
        ");
        
        $gstLiability = ($invoiceStats['igst_liability'] ?? 0) + ($invoiceStats['cgst_sgst_total'] ?? 0);
        
        $stmt->execute([
            $prefix,
            $invoiceStats['total_revenue'] ?? 0,
            $invoiceStats['invoice_count'] ?? 0,
            $invoiceStats['amount_received'] ?? 0,
            $invoiceStats['outstanding_amount'] ?? 0,
            $invoiceStats['pending_invoices'] ?? 0,
            $invoiceStats['customers_pending'] ?? 0,
            $invoiceStats['overdue_amount'] ?? 0,
            $invoiceStats['outstanding_percentage'] ?? 0,
            $invoiceStats['customer_count'] ?? 0,
            $poStats['po_commitments'] ?? 0,
            $poStats['open_pos'] ?? 0,
            $poStats['closed_pos'] ?? 0,
            $invoiceStats['claimable_amount'] ?? 0,
            $invoiceStats['claimable_pos'] ?? 0,
            $invoiceStats['claim_rate'] ?? 0,
            $invoiceStats['igst_liability'] ?? 0,
            $invoiceStats['cgst_sgst_total'] ?? 0,
            $gstLiability,
            $quotationStats['placed_quotations'] ?? 0,
            $quotationStats['rejected_quotations'] ?? 0,
            $quotationStats['pending_quotations'] ?? 0,
            $quotationStats['total_quotations'] ?? 0
        ]);
    }
    
    /**
     * Get analytics data from SQL (fast read)
     */
    public function getAnalytics($prefix, $filters = []) {
        $whereClause = "WHERE company_prefix = ?";
        $params = [$prefix];
        
        // Add customer filter if provided
        if (!empty($filters['customer'])) {
            $whereClause .= " AND customer_name = ?";
            $params[] = $filters['customer'];
        }
        
        // Revenue funnel from consolidated table
        $stmt = $this->db->prepare("
            SELECT 
                record_type,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                SUM(amount_paid) as amount_paid
            FROM finance_consolidated 
            $whereClause
            GROUP BY record_type
        ");
        $stmt->execute($params);
        $funnelData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Outstanding invoices
        $stmt = $this->db->prepare("
            SELECT 
                document_number, customer_name, outstanding_amount, due_date,
                DATEDIFF(NOW(), due_date) as days_overdue
            FROM finance_consolidated 
            $whereClause AND record_type = 'invoice' AND outstanding_amount > 0
            ORDER BY outstanding_amount DESC
            LIMIT 50
        ");
        $stmt->execute($params);
        $outstandingInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'funnel' => $funnelData,
            'outstanding_invoices' => $outstandingInvoices,
            'prefix' => $prefix
        ];
    }
    
    /**
     * Create consolidated table for analytics
     */
    private function createConsolidatedTable() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS finance_consolidated (
                id INT AUTO_INCREMENT PRIMARY KEY,
                record_type ENUM('invoice', 'quotation', 'purchase_order', 'payment') NOT NULL,
                document_number VARCHAR(100),
                customer_id VARCHAR(50),
                customer_name VARCHAR(255),
                amount DECIMAL(15,2) DEFAULT 0,
                taxable_amount DECIMAL(15,2) DEFAULT 0,
                amount_paid DECIMAL(15,2) DEFAULT 0,
                outstanding_amount DECIMAL(15,2) DEFAULT 0,
                igst DECIMAL(15,2) DEFAULT 0,
                cgst DECIMAL(15,2) DEFAULT 0,
                sgst DECIMAL(15,2) DEFAULT 0,
                due_date DATE NULL,
                invoice_date DATE NULL,
                status VARCHAR(50),
                company_prefix VARCHAR(10),
                raw_data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                INDEX idx_record_type (record_type),
                INDEX idx_company_prefix (company_prefix),
                INDEX idx_customer (customer_id),
                INDEX idx_status (status),
                INDEX idx_outstanding (outstanding_amount),
                INDEX idx_composite (company_prefix, record_type, status)
            )
        ");
        
        // Ensure dashboard_stats table exists
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS dashboard_stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_prefix VARCHAR(10),
                total_revenue DECIMAL(15,2) DEFAULT 0,
                invoice_count INT DEFAULT 0,
                amount_received DECIMAL(15,2) DEFAULT 0,
                outstanding_amount DECIMAL(15,2) DEFAULT 0,
                pending_invoices INT DEFAULT 0,
                customers_pending INT DEFAULT 0,
                overdue_amount DECIMAL(15,2) DEFAULT 0,
                outstanding_percentage DECIMAL(5,2) DEFAULT 0,
                customer_count INT DEFAULT 0,
                po_commitments DECIMAL(15,2) DEFAULT 0,
                open_pos INT DEFAULT 0,
                closed_pos INT DEFAULT 0,
                claimable_amount DECIMAL(15,2) DEFAULT 0,
                claimable_pos INT DEFAULT 0,
                claim_rate DECIMAL(5,2) DEFAULT 0,
                igst_liability DECIMAL(15,2) DEFAULT 0,
                cgst_sgst_total DECIMAL(15,2) DEFAULT 0,
                gst_liability DECIMAL(15,2) DEFAULT 0,
                placed_quotations INT DEFAULT 0,
                rejected_quotations INT DEFAULT 0,
                pending_quotations INT DEFAULT 0,
                total_quotations INT DEFAULT 0,
                generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_prefix (company_prefix)
            )
        ");
    }
    
    /**
     * Connect to SAP PostgreSQL
     */
    private function connectToSAP() {
        $pgHost = '72.60.218.167';
        $pgPort = '5432';
        $pgDb = 'modernsap';
        $pgUser = 'postgres';
        $pgPass = 'mango';
        
        $this->pgConn = @pg_connect("host=$pgHost port=$pgPort dbname=$pgDb user=$pgUser password=$pgPass");
        
        if (!$this->pgConn) {
            throw new Exception('SAP PostgreSQL connection failed');
        }
    }
    
    /**
     * Check if document number matches company prefix
     */
    private function matchesPrefix($documentNumber, $prefix) {
        if (!$prefix || empty($prefix)) {
            return true; // No prefix filter
        }
        
        return stripos($documentNumber, $prefix) === 0;
    }
}