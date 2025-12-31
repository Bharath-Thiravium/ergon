<?php

class StatCard3Service {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function calculateStatCard3($companyPrefix) {
        // Step 1: Fetch raw invoice rows using simple SELECT
        $sql = "SELECT id, invoice_number, taxable_amount, amount_paid, cgst, sgst, total_amount, due_date, customer_gstin 
                FROM finance_invoices 
                WHERE invoice_number LIKE ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$companyPrefix . '%']);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Step 2: Perform calculations in backend
        $outstandingAmount = 0;
        $pendingInvoices = 0;
        $customersPending = [];
        $overdueAmount = 0;
        $totalTaxableAmount = 0;
        $today = date('Y-m-d');
        
        foreach ($invoices as $invoice) {
            $taxableAmount = (float)$invoice['taxable_amount'];
            $amountPaid = (float)$invoice['amount_paid'];
            $dueDate = $invoice['due_date'];
            $customerGstin = $invoice['customer_gstin'];
            
            // Step 3: Calculate pending amount using only taxable_amount
            $pendingAmount = $taxableAmount - $amountPaid;
            
            // Add to total taxable amount
            $totalTaxableAmount += $taxableAmount;
            
            if ($pendingAmount > 0) {
                // Step 4: Calculate metrics
                $outstandingAmount += $pendingAmount;
                $pendingInvoices++;
                
                // Track unique customers with pending amounts
                if (!in_array($customerGstin, $customersPending)) {
                    $customersPending[] = $customerGstin;
                }
                
                // Calculate overdue amount
                if ($dueDate < $today) {
                    $overdueAmount += $pendingAmount;
                }
            }
        }
        
        $customersPendingCount = count($customersPending);
        $outstandingPercentage = $totalTaxableAmount > 0 ? ($outstandingAmount / $totalTaxableAmount) * 100 : 0;
        
        // Step 5: Store results in dashboard_stats table
        $this->storeDashboardStats($companyPrefix, [
            'outstanding_amount' => $outstandingAmount,
            'pending_invoices' => $pendingInvoices,
            'customers_pending' => $customersPendingCount,
            'overdue_amount' => $overdueAmount,
            'outstanding_percentage' => $outstandingPercentage
        ]);
        
        return [
            'outstanding_amount' => $outstandingAmount,
            'pending_invoices' => $pendingInvoices,
            'customers_pending' => $customersPendingCount,
            'overdue_amount' => $overdueAmount,
            'outstanding_percentage' => $outstandingPercentage
        ];
    }
    
    private function storeDashboardStats($companyPrefix, $stats) {
        $sql = "INSERT INTO dashboard_stats (company_prefix, outstanding_amount, pending_invoices, customers_pending, overdue_amount, outstanding_percentage, computed_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                outstanding_amount = VALUES(outstanding_amount),
                pending_invoices = VALUES(pending_invoices),
                customers_pending = VALUES(customers_pending),
                overdue_amount = VALUES(overdue_amount),
                outstanding_percentage = VALUES(outstanding_percentage),
                computed_at = NOW()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $companyPrefix,
            $stats['outstanding_amount'],
            $stats['pending_invoices'],
            $stats['customers_pending'],
            $stats['overdue_amount'],
            $stats['outstanding_percentage']
        ]);
    }
    
    public function getStatCard3FromDashboard($companyPrefix) {
        $sql = "SELECT outstanding_amount, pending_invoices, customers_pending, overdue_amount 
                FROM dashboard_stats 
                WHERE company_prefix = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$companyPrefix]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
