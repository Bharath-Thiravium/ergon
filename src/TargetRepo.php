<?php

namespace Ergon\FinanceSync;

use PDO;
use Psr\Log\LoggerInterface;

class TargetRepo
{
    private PDO $pdo;
    private LoggerInterface $logger;
    
    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }
    
    /**
     * Create tables if they don't exist
     */
    public function createTablesIfNotExist(): void
    {
        $this->createFinanceConsolidatedTable();
        $this->createDashboardStatsTable();
        $this->createSyncMetadataTable();
        $this->createSyncRunsTable();
        $this->createSyncErrorsTable();
    }
    
    /**
     * Upsert rows in batches with transaction
     */
    public function upsertBatch(array $rows, string $companyPrefix): array
    {
        $upserted = 0;
        $errors = [];
        
        $this->pdo->beginTransaction();
        
        try {
            $stmt = $this->prepareUpsertStatement();
            
            foreach ($rows as $row) {
                try {
                    $stmt->execute($row);
                    $upserted++;
                } catch (\PDOException $e) {
                    $error = [
                        'document_number' => $row['document_number'],
                        'company_prefix' => $companyPrefix,
                        'error_type' => 'upsert_failed',
                        'message' => $e->getMessage(),
                        'raw_data' => json_encode($row)
                    ];
                    $errors[] = $error;
                    $this->logError($error);
                }
            }
            
            $this->pdo->commit();
            
            $this->logger->info("Batch upserted", [
                'upserted' => $upserted,
                'errors' => count($errors)
            ]);
            
            return ['upserted' => $upserted, 'errors' => $errors];
            
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error("Batch transaction failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Log sync run summary
     */
    public function logSyncRun(
        string $companyPrefix,
        string $startedAt,
        string $endedAt,
        int $rowsFetched,
        int $rowsUpserted,
        int $errorsCount,
        string $status
    ): void {
        $sql = "
            INSERT INTO sync_runs (
                company_prefix, started_at, ended_at, rows_fetched, 
                rows_upserted, errors_count, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $companyPrefix, $startedAt, $endedAt, $rowsFetched,
            $rowsUpserted, $errorsCount, $status
        ]);
    }
    
    /**
     * Get last sync timestamp for incremental sync
     */
    public function getLastSyncTimestamp(string $companyPrefix, string $syncTable, string $syncType = 'invoices'): ?string
    {
        $column = match ($syncType) {
            'invoices' => 'last_sync_invoices',
            'activities' => 'last_sync_activities',
            'cashflow' => 'last_sync_cashflow',
            default => 'last_sync_invoices'
        };
        
        $sql = "SELECT {$column} FROM {$syncTable} WHERE company_prefix = ?";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$companyPrefix]);
            $result = $stmt->fetch();
            
            return $result ? $result[$column] : null;
        } catch (\PDOException $e) {
            $this->logger->warning("Could not fetch last sync timestamp for {$syncType}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update last sync timestamp
     */
    public function updateLastSyncTimestamp(string $companyPrefix, string $timestamp, string $syncTable, string $syncType = 'invoices'): void
    {
        $column = match ($syncType) {
            'invoices' => 'last_sync_invoices',
            'activities' => 'last_sync_activities',
            'cashflow' => 'last_sync_cashflow',
            default => 'last_sync_invoices'
        };
        
        $sql = "
            INSERT INTO {$syncTable} (company_prefix, {$column}, updated_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE {$column} = ?, updated_at = NOW()
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$companyPrefix, $timestamp, $timestamp]);
    }
    
    /**
     * Log individual row error
     */
    private function logError(array $error): void
    {
        $sql = "
            INSERT INTO sync_errors (
                document_number, company_prefix, error_type, message, raw_data, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $error['document_number'],
            $error['company_prefix'],
            $error['error_type'],
            $error['message'],
            $error['raw_data']
        ]);
    }
    
    /**
     * Prepare upsert statement for finance_consolidated
     */
    private function prepareUpsertStatement(): \PDOStatement
    {
        $sql = "
            INSERT INTO finance_consolidated (
                record_type, document_number, customer_id, customer_name, customer_gstin,
                amount, taxable_amount, amount_paid, outstanding_amount,
                igst, cgst, sgst, due_date, invoice_date, status,
                company_prefix, raw_data, created_at
            ) VALUES (
                :record_type, :document_number, :customer_id, :customer_name, :customer_gstin,
                :amount, :taxable_amount, :amount_paid, :outstanding_amount,
                :igst, :cgst, :sgst, :due_date, :invoice_date, :status,
                :company_prefix, :raw_data, NOW()
            )
            ON DUPLICATE KEY UPDATE
                record_type = VALUES(record_type),
                customer_id = VALUES(customer_id),
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
                raw_data = VALUES(raw_data),
                updated_at = NOW()
        ";
        
        return $this->pdo->prepare($sql);
    }
    
    private function createFinanceConsolidatedTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS finance_consolidated (
                id INT AUTO_INCREMENT PRIMARY KEY,
                record_type VARCHAR(20) NOT NULL DEFAULT 'invoice',
                document_number VARCHAR(100) NOT NULL,
                customer_id VARCHAR(50) NOT NULL,
                customer_name VARCHAR(255) NOT NULL,
                customer_gstin VARCHAR(15),
                amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                taxable_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                amount_paid DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                outstanding_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                igst DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                cgst DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                sgst DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                due_date DATE,
                invoice_date DATE,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                company_prefix VARCHAR(10) NOT NULL,
                raw_data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_company_document (company_prefix, document_number),
                INDEX idx_company_prefix (company_prefix),
                INDEX idx_customer_id (customer_id),
                INDEX idx_status (status),
                INDEX idx_outstanding (outstanding_amount)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $this->pdo->exec($sql);
    }
    
    /**
     * Upsert dashboard stats
     */
    public function upsertDashboardStats(string $companyPrefix, array $stats): void
    {
        $sql = "
            INSERT INTO dashboard_stats (
                company_prefix, expected_inflow, po_commitments, net_cash_flow, 
                last_computed_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                expected_inflow = VALUES(expected_inflow),
                po_commitments = VALUES(po_commitments),
                net_cash_flow = VALUES(net_cash_flow),
                last_computed_at = VALUES(last_computed_at),
                updated_at = NOW()
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $companyPrefix,
            $stats['expected_inflow'],
            $stats['po_commitments'],
            $stats['net_cash_flow'],
            $stats['last_computed_at']
        ]);
    }
    
    /**
     * Get dashboard stats
     */
    public function getDashboardStats(string $companyPrefix): ?array
    {
        $sql = "
            SELECT expected_inflow, po_commitments, net_cash_flow, last_computed_at
            FROM dashboard_stats
            WHERE company_prefix = ?
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$companyPrefix]);
        
        return $stmt->fetch() ?: null;
    }
    
    private function createSyncMetadataTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS sync_metadata (
                company_prefix VARCHAR(10) PRIMARY KEY,
                last_sync_invoices TIMESTAMP NULL,
                last_sync_activities TIMESTAMP NULL,
                last_sync_cashflow TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        $this->pdo->exec($sql);
    }
    
    private function createDashboardStatsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS dashboard_stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_prefix VARCHAR(10) NOT NULL,
                expected_inflow DECIMAL(18,2) DEFAULT 0.00,
                po_commitments DECIMAL(18,2) DEFAULT 0.00,
                net_cash_flow DECIMAL(18,2) DEFAULT 0.00,
                last_computed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_company_prefix (company_prefix)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $this->pdo->exec($sql);
    }
    
    private function createSyncRunsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS sync_runs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_prefix VARCHAR(10) NOT NULL,
                started_at TIMESTAMP NOT NULL,
                ended_at TIMESTAMP NOT NULL,
                rows_fetched INT NOT NULL DEFAULT 0,
                rows_upserted INT NOT NULL DEFAULT 0,
                errors_count INT NOT NULL DEFAULT 0,
                status ENUM('success', 'partial_failure', 'failure') NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_company_prefix (company_prefix),
                INDEX idx_created_at (created_at)
            )
        ";
        $this->pdo->exec($sql);
    }
    
    private function createSyncErrorsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS sync_errors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                document_number VARCHAR(100) NOT NULL,
                company_prefix VARCHAR(10) NOT NULL,
                error_type VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                raw_data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_company_prefix (company_prefix),
                INDEX idx_document_number (document_number),
                INDEX idx_created_at (created_at)
            )
        ";
        $this->pdo->exec($sql);
    }
}