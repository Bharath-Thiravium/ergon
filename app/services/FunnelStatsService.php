<?php
require_once __DIR__ . '/../config/database.php';

class FunnelStatsService {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
        $this->createFunnelStatsTable();
    }
    
    private function createFunnelStatsTable() {
        $this->db->exec("CREATE TABLE IF NOT EXISTS funnel_stats (
            id INT PRIMARY KEY AUTO_INCREMENT,
            company_prefix VARCHAR(50) NOT NULL,
            
            quotation_count INT DEFAULT 0,
            quotation_value DECIMAL(15,2) DEFAULT 0,
            
            po_count INT DEFAULT 0,
            po_value DECIMAL(15,2) DEFAULT 0,
            po_conversion_rate DECIMAL(5,2) DEFAULT 0,
            
            invoice_count INT DEFAULT 0,
            invoice_value DECIMAL(15,2) DEFAULT 0,
            invoice_conversion_rate DECIMAL(5,2) DEFAULT 0,
            
            payment_count INT DEFAULT 0,
            payment_value DECIMAL(15,2) DEFAULT 0,
            payment_conversion_rate DECIMAL(5,2) DEFAULT 0,
            
            generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_prefix (company_prefix)
        )");
    }
    
    /**
     * Calculate and store funnel stats for a company prefix
     * Following exact specification: raw data → backend calculations → stored → UI reads
     */
    public function calculateFunnelStats($prefix) {
        // Step 1: Fetch raw quotation records (NO AGGREGATE SQL)
        $quotations = $this->fetchRawQuotations($prefix);
        
        // Step 2: Fetch raw purchase order records (NO AGGREGATE SQL)
        $purchaseOrders = $this->fetchRawPurchaseOrders($prefix);
        
        // Step 3: Fetch raw invoice records (NO AGGREGATE SQL)
        $invoices = $this->fetchRawInvoices($prefix);
        
        // Step 4: Backend calculations
        $quotation_count = count($quotations);
        $quotation_value = $this->sumTotalAmount($quotations);
        
        $po_count = count($purchaseOrders);
        $po_value = $this->sumTotalAmount($purchaseOrders);
        $po_conversion_rate = $quotation_count > 0 ? ($po_count / $quotation_count) * 100 : 0;
        
        $invoice_count = count($invoices);
        $invoice_value = $this->sumTotalAmount($invoices);
        $invoice_conversion_rate = $po_count > 0 ? ($invoice_count / $po_count) * 100 : 0;
        
        // Payment calculations from invoice amount_paid
        $payment_value = $this->sumAmountPaid($invoices);
        $payment_count = $this->countPaidInvoices($invoices);
        $payment_conversion_rate = $invoice_count > 0 ? ($payment_count / $invoice_count) * 100 : 0;
        
        // Step 5: Save calculated results to funnel_stats
        $this->saveFunnelStats($prefix, [
            'quotation_count' => $quotation_count,
            'quotation_value' => $quotation_value,
            'po_count' => $po_count,
            'po_value' => $po_value,
            'po_conversion_rate' => round($po_conversion_rate, 2),
            'invoice_count' => $invoice_count,
            'invoice_value' => $invoice_value,
            'invoice_conversion_rate' => round($invoice_conversion_rate, 2),
            'payment_count' => $payment_count,
            'payment_value' => $payment_value,
            'payment_conversion_rate' => round($payment_conversion_rate, 2)
        ]);
        
        return [
            'quotation_count' => $quotation_count,
            'quotation_value' => $quotation_value,
            'po_count' => $po_count,
            'po_value' => $po_value,
            'po_conversion_rate' => round($po_conversion_rate, 2),
            'invoice_count' => $invoice_count,
            'invoice_value' => $invoice_value,
            'invoice_conversion_rate' => round($invoice_conversion_rate, 2),
            'payment_count' => $payment_count,
            'payment_value' => $payment_value,
            'payment_conversion_rate' => round($payment_conversion_rate, 2)
        ];
    }
    
    /**
     * Fetch raw quotation records with prefix filtering (NO AGGREGATE SQL)
     */
    private function fetchRawQuotations($prefix) {
        $stmt = $this->db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $quotations = [];
        foreach ($results as $row) {
            $data = json_decode($row['data'], true);
            $quotationNumber = $data['quotation_number'] ?? '';
            
            // Apply prefix filtering
            if (!$prefix || strpos($quotationNumber, $prefix) === 0) {
                $quotations[] = [
                    'id' => $data['id'] ?? '',
                    'quotation_number' => $quotationNumber,
                    'total_amount' => floatval($data['total_amount'] ?? $data['amount'] ?? 0)
                ];
            }
        }
        
        return $quotations;
    }
    
    /**
     * Fetch raw purchase order records with prefix filtering (NO AGGREGATE SQL)
     */
    private function fetchRawPurchaseOrders($prefix) {
        $stmt = $this->db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $purchaseOrders = [];
        foreach ($results as $row) {
            $data = json_decode($row['data'], true);
            $poNumber = $data['po_number'] ?? '';
            
            // Apply prefix filtering
            if (!$prefix || strpos($poNumber, $prefix) === 0) {
                $purchaseOrders[] = [
                    'id' => $data['id'] ?? '',
                    'po_number' => $poNumber,
                    'total_amount' => floatval($data['total_amount'] ?? $data['amount'] ?? 0),
                    'amount_paid' => floatval($data['amount_paid'] ?? 0)
                ];
            }
        }
        
        return $purchaseOrders;
    }
    
    /**
     * Fetch raw invoice records with prefix filtering (NO AGGREGATE SQL)
     */
    private function fetchRawInvoices($prefix) {
        $stmt = $this->db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $invoices = [];
        foreach ($results as $row) {
            $data = json_decode($row['data'], true);
            $invoiceNumber = $data['invoice_number'] ?? '';
            
            // Apply prefix filtering
            if (!$prefix || strpos($invoiceNumber, $prefix) === 0) {
                $invoices[] = [
                    'id' => $data['id'] ?? '',
                    'invoice_number' => $invoiceNumber,
                    'total_amount' => floatval($data['total_amount'] ?? $data['amount'] ?? 0),
                    'amount_paid' => floatval($data['amount_paid'] ?? 0)
                ];
            }
        }
        
        return $invoices;
    }
    
    /**
     * Sum total_amount from array of records
     */
    private function sumTotalAmount($records) {
        $total = 0;
        foreach ($records as $record) {
            $total += $record['total_amount'];
        }
        return $total;
    }
    
    /**
     * Sum amount_paid from array of records
     */
    private function sumAmountPaid($records) {
        $total = 0;
        foreach ($records as $record) {
            $total += $record['amount_paid'];
        }
        return $total;
    }
    
    /**
     * Count records with amount_paid > 0
     */
    private function countPaidInvoices($invoices) {
        $count = 0;
        foreach ($invoices as $invoice) {
            if ($invoice['amount_paid'] > 0) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Save calculated funnel stats to database
     */
    private function saveFunnelStats($prefix, $stats) {
        $stmt = $this->db->prepare("
            INSERT INTO funnel_stats (
                company_prefix, quotation_count, quotation_value,
                po_count, po_value, po_conversion_rate,
                invoice_count, invoice_value, invoice_conversion_rate,
                payment_count, payment_value, payment_conversion_rate,
                generated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                quotation_count = VALUES(quotation_count),
                quotation_value = VALUES(quotation_value),
                po_count = VALUES(po_count),
                po_value = VALUES(po_value),
                po_conversion_rate = VALUES(po_conversion_rate),
                invoice_count = VALUES(invoice_count),
                invoice_value = VALUES(invoice_value),
                invoice_conversion_rate = VALUES(invoice_conversion_rate),
                payment_count = VALUES(payment_count),
                payment_value = VALUES(payment_value),
                payment_conversion_rate = VALUES(payment_conversion_rate),
                generated_at = NOW()
        ");
        
        $stmt->execute([
            $prefix,
            $stats['quotation_count'],
            $stats['quotation_value'],
            $stats['po_count'],
            $stats['po_value'],
            $stats['po_conversion_rate'],
            $stats['invoice_count'],
            $stats['invoice_value'],
            $stats['invoice_conversion_rate'],
            $stats['payment_count'],
            $stats['payment_value'],
            $stats['payment_conversion_rate']
        ]);
    }
    
    /**
     * Get funnel stats from database (UI reads ONLY from funnel_stats)
     */
    public function getFunnelStats($prefix) {
        $stmt = $this->db->prepare("
            SELECT * FROM funnel_stats 
            WHERE company_prefix = ? 
            ORDER BY generated_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$prefix]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            // Calculate if no stats exist
            return $this->calculateFunnelStats($prefix);
        }
        
        return [
            'quotation_count' => intval($result['quotation_count']),
            'quotation_value' => floatval($result['quotation_value']),
            'po_count' => intval($result['po_count']),
            'po_value' => floatval($result['po_value']),
            'po_conversion_rate' => floatval($result['po_conversion_rate']),
            'invoice_count' => intval($result['invoice_count']),
            'invoice_value' => floatval($result['invoice_value']),
            'invoice_conversion_rate' => floatval($result['invoice_conversion_rate']),
            'payment_count' => intval($result['payment_count']),
            'payment_value' => floatval($result['payment_value']),
            'payment_conversion_rate' => floatval($result['payment_conversion_rate']),
            'generated_at' => $result['generated_at']
        ];
    }
    
    /**
     * Get funnel container data mapped to UI format
     */
    public function getFunnelContainers($prefix) {
        $stats = $this->getFunnelStats($prefix);
        
        return [
            'container1' => [
                'title' => 'Quotations',
                'quotations_count' => $stats['quotation_count'],
                'quotations_total_value' => $stats['quotation_value']
            ],
            'container2' => [
                'title' => 'Purchase Orders',
                'po_count' => $stats['po_count'],
                'po_total_value' => $stats['po_value'],
                'po_conversion_rate' => $stats['po_conversion_rate']
            ],
            'container3' => [
                'title' => 'Invoices',
                'invoice_count' => $stats['invoice_count'],
                'invoice_total_value' => $stats['invoice_value'],
                'invoice_conversion_rate' => $stats['invoice_conversion_rate']
            ],
            'container4' => [
                'title' => 'Payments',
                'payment_count' => $stats['payment_count'],
                'total_payment_received' => $stats['payment_value'],
                'payment_conversion_rate' => $stats['payment_conversion_rate']
            ]
        ];
    }
}
?>