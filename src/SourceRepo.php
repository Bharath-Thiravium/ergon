<?php

namespace Ergon\FinanceSync;

use PDO;
use Psr\Log\LoggerInterface;

class SourceRepo
{
    private PDO $pdo;
    private LoggerInterface $logger;
    
    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }
    
    /**
     * Fetch invoice rows from PostgreSQL source
     */
    public function fetchInvoiceRows(
        string $prefix,
        bool $fullLoad = false,
        ?string $lastSyncAt = null,
        ?int $limit = null
    ): array {
        return $this->fetchRows('invoices', $prefix, $fullLoad, $lastSyncAt, $limit);
    }
    
    /**
     * Fetch quotation rows from PostgreSQL source
     */
    public function fetchQuotationRows(
        string $prefix,
        bool $fullLoad = false,
        ?string $lastSyncAt = null,
        ?int $limit = null
    ): array {
        return $this->fetchRows('quotations', $prefix, $fullLoad, $lastSyncAt, $limit);
    }
    
    /**
     * Fetch purchase order rows from PostgreSQL source
     */
    public function fetchPurchaseOrderRows(
        string $prefix,
        bool $fullLoad = false,
        ?string $lastSyncAt = null,
        ?int $limit = null
    ): array {
        return $this->fetchRows('purchase_orders', $prefix, $fullLoad, $lastSyncAt, $limit);
    }
    
    /**
     * Fetch payment rows from PostgreSQL source
     */
    public function fetchPaymentRows(
        string $prefix,
        bool $fullLoad = false,
        ?string $lastSyncAt = null,
        ?int $limit = null
    ): array {
        return $this->fetchRows('payments', $prefix, $fullLoad, $lastSyncAt, $limit);
    }
    
    /**
     * Generic method to fetch rows by type
     */
    private function fetchRows(
        string $type,
        string $prefix,
        bool $fullLoad = false,
        ?string $lastSyncAt = null,
        ?int $limit = null
    ): array {
        $sql = $this->buildQuery($type, $fullLoad, $limit !== null);
        
        $params = [
            'prefix' => $prefix,
            'full_load' => $fullLoad
        ];
        
        if (!$fullLoad && $lastSyncAt) {
            $params['last_sync_at'] = $lastSyncAt;
        }
        
        if ($limit !== null) {
            $params['limit'] = $limit;
        }
        
        $this->logger->info("Executing source query", [
            'type' => $type,
            'prefix' => $prefix,
            'full_load' => $fullLoad,
            'last_sync_at' => $lastSyncAt,
            'limit' => $limit
        ]);
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            
            $this->logger->info("Fetched {count} {type} rows from source", ['count' => count($rows), 'type' => $type]);
            
            return $rows;
        } catch (\PDOException $e) {
            $this->logger->error("Failed to fetch {type} data: " . $e->getMessage(), ['type' => $type]);
            throw $e;
        }
    }
    
    /**
     * Build the parameterized SQL query
     */
    private function buildQuery(string $type, bool $fullLoad, bool $hasLimit): string
    {
        switch ($type) {
            case 'invoices':
                return $this->buildInvoiceQuery($fullLoad, $hasLimit);
            case 'quotations':
                return $this->buildQuotationQuery($fullLoad, $hasLimit);
            case 'purchase_orders':
                return $this->buildPurchaseOrderQuery($fullLoad, $hasLimit);
            case 'payments':
                return $this->buildPaymentQuery($fullLoad, $hasLimit);
            default:
                throw new \InvalidArgumentException("Unknown type: {$type}");
        }
    }
    
    private function buildInvoiceQuery(bool $fullLoad, bool $hasLimit): string
    {
        $sql = "
            SELECT
                fi.*,
                fc.customer_name,
                fc.customer_gstin
            FROM finance_invoices fi
            LEFT JOIN finance_customers fc ON fi.customer_id = fc.customer_id
            WHERE fi.invoice_number LIKE :prefix || '%'
        ";
        
        if (!$fullLoad) {
            $sql .= " AND (
                (fi.updated_at IS NOT NULL AND fi.updated_at > :last_sync_at) OR
                (fi.updated_at IS NULL AND fi.invoice_date > :last_sync_at)
            )";
        }
        
        $sql .= " ORDER BY COALESCE(fi.updated_at, fi.invoice_date) ASC";
        
        if ($hasLimit) {
            $sql .= " LIMIT :limit";
        }
        
        return $sql;
    }
    
    private function buildQuotationQuery(bool $fullLoad, bool $hasLimit): string
    {
        $sql = "
            SELECT
                fq.*,
                fc.customer_name,
                fc.customer_gstin
            FROM finance_quotations fq
            LEFT JOIN finance_customers fc ON fq.customer_id = fc.customer_id
            WHERE fq.quotation_number LIKE :prefix || '%'
        ";
        
        if (!$fullLoad) {
            $sql .= " AND (
                (fq.updated_at IS NOT NULL AND fq.updated_at > :last_sync_at) OR
                (fq.updated_at IS NULL AND fq.quotation_date > :last_sync_at)
            )";
        }
        
        $sql .= " ORDER BY COALESCE(fq.updated_at, fq.quotation_date) ASC";
        
        if ($hasLimit) {
            $sql .= " LIMIT :limit";
        }
        
        return $sql;
    }
    
    private function buildPurchaseOrderQuery(bool $fullLoad, bool $hasLimit): string
    {
        $sql = "
            SELECT
                fpo.*,
                fc.customer_name,
                fc.customer_gstin
            FROM finance_purchase_orders fpo
            LEFT JOIN finance_customers fc ON fpo.customer_id = fc.customer_id
            WHERE fpo.po_number LIKE :prefix || '%'
        ";
        
        if (!$fullLoad) {
            $sql .= " AND (
                (fpo.updated_at IS NOT NULL AND fpo.updated_at > :last_sync_at) OR
                (fpo.updated_at IS NULL AND fpo.po_date > :last_sync_at)
            )";
        }
        
        $sql .= " ORDER BY COALESCE(fpo.updated_at, fpo.po_date) ASC";
        
        if ($hasLimit) {
            $sql .= " LIMIT :limit";
        }
        
        return $sql;
    }
    
    private function buildPaymentQuery(bool $fullLoad, bool $hasLimit): string
    {
        $sql = "
            SELECT
                fp.*,
                fc.customer_name,
                fc.customer_gstin
            FROM finance_payments fp
            LEFT JOIN finance_customers fc ON fp.customer_id = fc.customer_id
            WHERE fp.customer_id IS NOT NULL
        ";
        
        if (!$fullLoad) {
            $sql .= " AND (
                (fp.updated_at IS NOT NULL AND fp.updated_at > :last_sync_at) OR
                (fp.updated_at IS NULL AND fp.payment_date > :last_sync_at)
            )";
        }
        
        $sql .= " ORDER BY COALESCE(fp.updated_at, fp.payment_date) ASC";
        
        if ($hasLimit) {
            $sql .= " LIMIT :limit";
        }
        
        return $sql;
    }
    
    /**
     * Fetch invoices for cashflow computation
     */
    public function fetchInvoicesForCashflow(
        string $prefix,
        bool $fullLoad = false,
        ?string $lastSyncAt = null
    ): array {
        $sql = "
            SELECT
                invoice_number,
                total_amount,
                amount_paid,
                updated_at
            FROM finance_invoices
            WHERE invoice_number LIKE :prefix || '%'
        ";
        
        $params = ['prefix' => $prefix];
        
        if (!$fullLoad && $lastSyncAt) {
            $sql .= " AND updated_at > :last_sync_at";
            $params['last_sync_at'] = $lastSyncAt;
        }
        
        $sql .= " ORDER BY updated_at ASC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            $this->logger->error("Failed to fetch invoices for cashflow: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Fetch purchase orders for cashflow computation
     */
    public function fetchPurchaseOrdersForCashflow(
        string $prefix,
        bool $fullLoad = false,
        ?string $lastSyncAt = null
    ): array {
        $sql = "
            SELECT
                po_number,
                po_total_value,
                po_amount,
                po_status,
                updated_at
            FROM finance_purchase_orders
            WHERE po_number LIKE :prefix || '%'
        ";
        
        $params = ['prefix' => $prefix];
        
        if (!$fullLoad && $lastSyncAt) {
            $sql .= " AND updated_at > :last_sync_at";
            $params['last_sync_at'] = $lastSyncAt;
        }
        
        $sql .= " ORDER BY updated_at ASC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            $this->logger->error("Failed to fetch POs for cashflow: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get the maximum updated_at timestamp from fetched rows
     */
    public function getMaxTimestamp(array $rows): ?string
    {
        if (empty($rows)) {
            return null;
        }
        
        $maxTimestamp = null;
        foreach ($rows as $row) {
            $timestamp = $row['updated_at'] ?? $row['invoice_date'] ?? null;
            if ($timestamp && ($maxTimestamp === null || $timestamp > $maxTimestamp)) {
                $maxTimestamp = $timestamp;
            }
        }
        
        return $maxTimestamp;
    }
}