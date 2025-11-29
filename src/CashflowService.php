<?php

namespace Ergon\FinanceSync;

use PDO;
use Psr\Log\LoggerInterface;

class CashflowService
{
    private SourceRepo $sourceRepo;
    private TargetRepo $targetRepo;
    private LoggerInterface $logger;
    private array $config;
    
    public function __construct(
        SourceRepo $sourceRepo,
        TargetRepo $targetRepo,
        LoggerInterface $logger,
        array $config
    ) {
        $this->sourceRepo = $sourceRepo;
        $this->targetRepo = $targetRepo;
        $this->logger = $logger;
        $this->config = $config;
    }
    
    /**
     * Compute cash flow projections for a company
     */
    public function computeCashflow(string $companyPrefix, bool $fullLoad = false): int
    {
        $startedAt = date('Y-m-d H:i:s');
        
        try {
            $this->logger->info("Starting cashflow computation", [
                'company_prefix' => $companyPrefix,
                'full_load' => $fullLoad
            ]);
            
            // Get last sync timestamp for incremental computation
            $lastSyncAt = null;
            if (!$fullLoad) {
                $lastSyncAt = $this->targetRepo->getLastSyncTimestamp(
                    $companyPrefix,
                    $this->config['sync_table'],
                    'cashflow'
                );
            }
            
            // Compute expected inflow from invoices
            $expectedInflow = $this->computeExpectedInflow($companyPrefix, $fullLoad, $lastSyncAt);
            
            // Compute PO commitments
            $poCommitments = $this->computePoCommitments($companyPrefix, $fullLoad, $lastSyncAt);
            
            // Compute net cash flow
            $netCashFlow = bcsub($expectedInflow, $poCommitments, 2);
            
            // Store dashboard stats
            $this->storeDashboardStats($companyPrefix, $expectedInflow, $poCommitments, $netCashFlow);
            
            // Update last sync timestamp
            if (!$fullLoad) {
                $this->targetRepo->updateLastSyncTimestamp(
                    $companyPrefix,
                    date('Y-m-d H:i:s'),
                    $this->config['sync_table'],
                    'cashflow'
                );
            }
            
            $endedAt = date('Y-m-d H:i:s');
            
            $this->logger->info("Cashflow computation completed", [
                'company_prefix' => $companyPrefix,
                'expected_inflow' => $expectedInflow,
                'po_commitments' => $poCommitments,
                'net_cash_flow' => $netCashFlow,
                'duration' => strtotime($endedAt) - strtotime($startedAt) . 's'
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->logger->error("Cashflow computation failed", [
                'company_prefix' => $companyPrefix,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 2;
        }
    }
    
    /**
     * Compute expected inflow from outstanding invoices
     */
    private function computeExpectedInflow(string $companyPrefix, bool $fullLoad, ?string $lastSyncAt): string
    {
        $invoiceRows = $this->sourceRepo->fetchInvoicesForCashflow($companyPrefix, $fullLoad, $lastSyncAt);
        
        $totalInflow = '0.00';
        
        foreach ($invoiceRows as $row) {
            $totalAmount = $this->coerceToDecimal($row['total_amount'] ?? 0);
            $amountPaid = $this->coerceToDecimal($row['amount_paid'] ?? 0);
            
            $outstanding = bcsub($totalAmount, $amountPaid, 2);
            
            if (bccomp($outstanding, '0.00', 2) > 0) {
                $totalInflow = bcadd($totalInflow, $outstanding, 2);
            }
        }
        
        $this->logger->info("Computed expected inflow", [
            'company_prefix' => $companyPrefix,
            'invoice_count' => count($invoiceRows),
            'expected_inflow' => $totalInflow
        ]);
        
        return $totalInflow;
    }
    
    /**
     * Compute PO commitments from active purchase orders
     */
    private function computePoCommitments(string $companyPrefix, bool $fullLoad, ?string $lastSyncAt): string
    {
        $poRows = $this->sourceRepo->fetchPurchaseOrdersForCashflow($companyPrefix, $fullLoad, $lastSyncAt);
        
        $activeStatuses = explode(',', $_ENV['CASHFLOW_ACTIVE_PO_STATUSES'] ?? 'Active,Released,Approved');
        $activeStatuses = array_map('trim', $activeStatuses);
        
        $totalCommitments = '0.00';
        
        foreach ($poRows as $row) {
            $poStatus = $row['po_status'] ?? '';
            
            if (in_array($poStatus, $activeStatuses)) {
                $poValue = $this->coerceToDecimal($row['po_total_value'] ?? $row['po_amount'] ?? 0);
                $totalCommitments = bcadd($totalCommitments, $poValue, 2);
            }
        }
        
        $this->logger->info("Computed PO commitments", [
            'company_prefix' => $companyPrefix,
            'po_count' => count($poRows),
            'active_statuses' => $activeStatuses,
            'po_commitments' => $totalCommitments
        ]);
        
        return $totalCommitments;
    }
    
    /**
     * Store computed dashboard stats
     */
    private function storeDashboardStats(
        string $companyPrefix,
        string $expectedInflow,
        string $poCommitments,
        string $netCashFlow
    ): void {
        $this->targetRepo->upsertDashboardStats($companyPrefix, [
            'expected_inflow' => $expectedInflow,
            'po_commitments' => $poCommitments,
            'net_cash_flow' => $netCashFlow,
            'last_computed_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Coerce value to decimal string
     */
    private function coerceToDecimal($value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }
        
        return number_format((float)$value, 2, '.', '');
    }
}