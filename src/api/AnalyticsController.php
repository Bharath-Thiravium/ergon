<?php

namespace Ergon\FinanceSync\Api;

use PDO;

class AnalyticsController
{
    private PDO $pdo;
    private $logger;
    
    public function __construct(PDO $pdo, $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }
    
    public function getAnalytics(array $params = []): array
    {
        $prefix = $params['prefix'] ?? null;
        $customerId = $params['customer_id'] ?? null;
        $widget = $params['widget'] ?? null;
        
        if (!$prefix) {
            return $this->errorResponse('Company prefix is required', 400);
        }
        
        try {
            if ($widget) {
                return $this->getWidgetData($widget, $prefix, $customerId);
            }
            
            return $this->successResponse([
                'quotations' => $this->getQuotationsAnalytics($customerId),
                'purchaseOrders' => $this->getPurchaseOrdersAnalytics($customerId),
                'invoices' => $this->getInvoicesAnalytics($customerId),
                'outstanding' => $this->getOutstandingAnalytics($customerId),
                'aging' => $this->getAgingAnalytics($customerId),
                'payments' => $this->getPaymentsAnalytics($customerId),
                'conversionFunnel' => $this->getConversionFunnel($customerId),
                'quotationDonut' => $this->getQuotationDonut($prefix, $customerId),
                'poClaimDistribution' => $this->getPOClaimDistribution($prefix, $customerId),
                'invoiceCollections' => $this->getInvoiceCollections($prefix, $customerId),
                'customerOutstanding' => $this->getCustomerOutstanding($prefix, $customerId)
            ]);
        } catch (\PDOException $e) {
            $this->logger->error("Analytics failed: " . $e->getMessage());
            return $this->errorResponse('Database error: ' . $e->getMessage(), 500);
        }
    }
    
    private function getWidgetData(string $widget, string $prefix, ?string $customerId): array
    {
        return match($widget) {
            'quotation_donut' => $this->successResponse($this->getQuotationDonut($prefix, $customerId)),
            'po_claims' => $this->successResponse($this->getPOClaimDistribution($prefix, $customerId)),
            'invoice_collections' => $this->successResponse($this->getInvoiceCollections($prefix, $customerId)),
            'customer_outstanding' => $this->successResponse($this->getCustomerOutstanding($prefix, $customerId)),
            default => $this->errorResponse('Invalid widget type', 400)
        };
    }
    
    private function getQuotationsAnalytics(?string $customerId): array
    {
        if ($customerId) {
            $sql = "SELECT SUM(CASE WHEN status='PLACED' THEN 1 ELSE 0 END) AS placed_count,
                           SUM(CASE WHEN status='REJECTED' THEN 1 ELSE 0 END) AS rejected_count,
                           SUM(CASE WHEN status IN ('PENDING','DRAFT','REVISED') THEN 1 ELSE 0 END) AS pending_count,
                           COUNT(*) AS total
                    FROM finance_quotations WHERE customer_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$customerId]);
        } else {
            $sql = "SELECT SUM(CASE WHEN status='PLACED' THEN 1 ELSE 0 END) AS placed_count,
                           SUM(CASE WHEN status='REJECTED' THEN 1 ELSE 0 END) AS rejected_count,
                           SUM(CASE WHEN status IN ('PENDING','DRAFT','REVISED') THEN 1 ELSE 0 END) AS pending_count,
                           COUNT(*) AS total
                    FROM finance_quotations";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }
        $statusCounts = $stmt->fetch();
        
        if ($customerId) {
            $thisMonthSql = "SELECT COUNT(*) AS cnt FROM finance_quotations 
                             WHERE DATE_FORMAT(quotation_date,'%Y-%m') = DATE_FORMAT(CURRENT_DATE,'%Y-%m')
                             AND customer_id = ?";
            $stmt = $this->pdo->prepare($thisMonthSql);
            $stmt->execute([$customerId]);
        } else {
            $thisMonthSql = "SELECT COUNT(*) AS cnt FROM finance_quotations 
                             WHERE DATE_FORMAT(quotation_date,'%Y-%m') = DATE_FORMAT(CURRENT_DATE,'%Y-%m')";
            $stmt = $this->pdo->prepare($thisMonthSql);
            $stmt->execute();
        }
        $thisMonth = $stmt->fetch()['cnt'] ?? 0;
        
        if ($customerId) {
            $lastMonthSql = "SELECT COUNT(*) AS cnt FROM finance_quotations 
                             WHERE DATE_FORMAT(quotation_date,'%Y-%m') = DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH,'%Y-%m')
                             AND customer_id = ?";
            $stmt = $this->pdo->prepare($lastMonthSql);
            $stmt->execute([$customerId]);
        } else {
            $lastMonthSql = "SELECT COUNT(*) AS cnt FROM finance_quotations 
                             WHERE DATE_FORMAT(quotation_date,'%Y-%m') = DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH,'%Y-%m')";
            $stmt = $this->pdo->prepare($lastMonthSql);
            $stmt->execute();
        }
        $lastMonth = $stmt->fetch()['cnt'] ?? 0;
        
        $growth = ($lastMonth == 0) ? 0 : round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
        
        return ['statusCounts' => $statusCounts, 'growth' => $growth, 'thisMonth' => $thisMonth, 'lastMonth' => $lastMonth];
    }
    
    private function getPurchaseOrdersAnalytics(?string $customerId): array
    {
        if ($customerId) {
            $sql = "SELECT COUNT(*) AS po_count, COALESCE(SUM(total_amount),0) AS total_po_value,
                           SUM(CASE WHEN status IN ('Active','Released','Open') THEN 1 ELSE 0 END) AS open_pos
                    FROM finance_purchase_orders WHERE customer_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$customerId]);
        } else {
            $sql = "SELECT COUNT(*) AS po_count, COALESCE(SUM(total_amount),0) AS total_po_value,
                           SUM(CASE WHEN status IN ('Active','Released','Open') THEN 1 ELSE 0 END) AS open_pos
                    FROM finance_purchase_orders";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }
        $poData = $stmt->fetch();
        
        return [
            'poCount' => (int)$poData['po_count'],
            'totalValue' => (float)$poData['total_po_value'],
            'openPOs' => (int)$poData['open_pos'],
            'fulfillmentRate' => 0,
            'avgLeadDays' => 0
        ];
    }
    
    private function getInvoicesAnalytics(?string $customerId): array
    {
        if ($customerId) {
            $sql = "SELECT COALESCE(SUM(total_amount),0) AS total_invoice_value,
                           COALESCE(SUM(paid_amount),0) AS collected_amount,
                           COALESCE(SUM(outstanding_amount),0) AS pending_amount
                    FROM finance_invoices WHERE customer_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$customerId]);
        } else {
            $sql = "SELECT COALESCE(SUM(total_amount),0) AS total_invoice_value,
                           COALESCE(SUM(paid_amount),0) AS collected_amount,
                           COALESCE(SUM(outstanding_amount),0) AS pending_amount
                    FROM finance_invoices";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }
        $amounts = $stmt->fetch();
        
        if ($customerId) {
            $statusSql = "SELECT SUM(CASE WHEN paid_amount >= total_amount THEN 1 ELSE 0 END) AS paid_count,
                                 SUM(CASE WHEN outstanding_amount > 0 AND (payment_due_date >= CURRENT_DATE OR payment_due_date IS NULL) THEN 1 ELSE 0 END) AS unpaid_count,
                                 SUM(CASE WHEN outstanding_amount > 0 AND payment_due_date < CURRENT_DATE THEN 1 ELSE 0 END) AS overdue_count
                          FROM finance_invoices WHERE customer_id = ?";
            $stmt = $this->pdo->prepare($statusSql);
            $stmt->execute([$customerId]);
        } else {
            $statusSql = "SELECT SUM(CASE WHEN paid_amount >= total_amount THEN 1 ELSE 0 END) AS paid_count,
                                 SUM(CASE WHEN outstanding_amount > 0 AND (payment_due_date >= CURRENT_DATE OR payment_due_date IS NULL) THEN 1 ELSE 0 END) AS unpaid_count,
                                 SUM(CASE WHEN outstanding_amount > 0 AND payment_due_date < CURRENT_DATE THEN 1 ELSE 0 END) AS overdue_count
                          FROM finance_invoices";
            $stmt = $this->pdo->prepare($statusSql);
            $stmt->execute();
        }
        $statusCounts = $stmt->fetch();
        
        $avgDailySales = ($amounts['total_invoice_value'] == 0) ? 0 : ($amounts['total_invoice_value'] / 30);
        $dso = ($avgDailySales == 0) ? 0 : round($amounts['pending_amount'] / $avgDailySales, 0);
        
        if ($customerId) {
            $badDebtSql = "SELECT COALESCE(SUM(outstanding_amount),0) AS bad_debt_risk
                           FROM finance_invoices
                           WHERE DATEDIFF(CURRENT_DATE, COALESCE(payment_due_date, invoice_date)) > 180
                           AND outstanding_amount > 0 AND customer_id = ?";
            $stmt = $this->pdo->prepare($badDebtSql);
            $stmt->execute([$customerId]);
        } else {
            $badDebtSql = "SELECT COALESCE(SUM(outstanding_amount),0) AS bad_debt_risk
                           FROM finance_invoices
                           WHERE DATEDIFF(CURRENT_DATE, COALESCE(payment_due_date, invoice_date)) > 180
                           AND outstanding_amount > 0";
            $stmt = $this->pdo->prepare($badDebtSql);
            $stmt->execute();
        }
        $badDebtRisk = $stmt->fetch()['bad_debt_risk'];
        
        $collectionEfficiency = ($amounts['total_invoice_value'] == 0) ? 0 : 
            round(($amounts['collected_amount'] / $amounts['total_invoice_value']) * 100, 2);
        
        return [
            'totalValue' => (float)$amounts['total_invoice_value'],
            'collectedAmount' => (float)$amounts['collected_amount'],
            'pendingAmount' => (float)$amounts['pending_amount'],
            'statusCounts' => $statusCounts,
            'dso' => $dso,
            'badDebtRisk' => (float)$badDebtRisk,
            'collectionEfficiency' => $collectionEfficiency
        ];
    }
    
    private function getOutstandingAnalytics(?string $customerId): array
    {
        if ($customerId) {
            $totalSql = "SELECT COALESCE(SUM(outstanding_amount),0) AS total_outstanding
                         FROM finance_invoices WHERE customer_id = ?";
            $stmt = $this->pdo->prepare($totalSql);
            $stmt->execute([$customerId]);
        } else {
            $totalSql = "SELECT COALESCE(SUM(outstanding_amount),0) AS total_outstanding
                         FROM finance_invoices";
            $stmt = $this->pdo->prepare($totalSql);
            $stmt->execute();
        }
        $totalOutstanding = $stmt->fetch()['total_outstanding'];
        
        if ($customerId) {
            $customerSql = "SELECT fi.customer_id, fc.display_name as customer_name,
                                   SUM(fi.outstanding_amount) AS outstanding
                            FROM finance_invoices fi
                            LEFT JOIN finance_customer fc ON fi.customer_id = fc.id
                            WHERE fi.customer_id = ?
                            GROUP BY fi.customer_id, fc.display_name
                            HAVING outstanding > 0
                            ORDER BY outstanding DESC
                            LIMIT 10";
            $stmt = $this->pdo->prepare($customerSql);
            $stmt->execute([$customerId]);
        } else {
            $customerSql = "SELECT fi.customer_id, fc.display_name as customer_name,
                                   SUM(fi.outstanding_amount) AS outstanding
                            FROM finance_invoices fi
                            LEFT JOIN finance_customer fc ON fi.customer_id = fc.id
                            GROUP BY fi.customer_id, fc.display_name
                            HAVING outstanding > 0
                            ORDER BY outstanding DESC
                            LIMIT 10";
            $stmt = $this->pdo->prepare($customerSql);
            $stmt->execute();
        }
        $topCustomers = $stmt->fetchAll();
        
        $topSum = array_sum(array_slice(array_column($topCustomers, 'outstanding'), 0, 3));
        $concentrationPercent = ($totalOutstanding == 0) ? 0 : round(($topSum / $totalOutstanding) * 100, 2);
        
        if ($customerId) {
            $diversitySql = "SELECT COUNT(DISTINCT customer_id) AS customers_with_outstanding
                             FROM finance_invoices WHERE outstanding_amount > 0 AND customer_id = ?";
            $stmt = $this->pdo->prepare($diversitySql);
            $stmt->execute([$customerId]);
        } else {
            $diversitySql = "SELECT COUNT(DISTINCT customer_id) AS customers_with_outstanding
                             FROM finance_invoices WHERE outstanding_amount > 0";
            $stmt = $this->pdo->prepare($diversitySql);
            $stmt->execute();
        }
        $customerDiversity = $stmt->fetch()['customers_with_outstanding'];
        
        return [
            'totalOutstanding' => (float)$totalOutstanding,
            'topCustomers' => $topCustomers,
            'concentrationRisk' => $concentrationPercent,
            'customerDiversity' => (int)$customerDiversity
        ];
    }
    
    private function getAgingAnalytics(?string $customerId): array
    {
        if ($customerId) {
            $sql = "SELECT SUM(CASE WHEN age <= 30 AND outstanding > 0 THEN outstanding ELSE 0 END) AS bucket_0_30,
                           SUM(CASE WHEN age BETWEEN 31 AND 60 AND outstanding > 0 THEN outstanding ELSE 0 END) AS bucket_31_60,
                           SUM(CASE WHEN age BETWEEN 61 AND 90 AND outstanding > 0 THEN outstanding ELSE 0 END) AS bucket_61_90,
                           SUM(CASE WHEN age > 90 AND outstanding > 0 THEN outstanding ELSE 0 END) AS bucket_90_plus
                    FROM (
                        SELECT outstanding_amount AS outstanding,
                               DATEDIFF(CURRENT_DATE, COALESCE(payment_due_date, invoice_date)) AS age
                        FROM finance_invoices WHERE customer_id = ?
                    ) t";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$customerId]);
        } else {
            $sql = "SELECT SUM(CASE WHEN age <= 30 AND outstanding > 0 THEN outstanding ELSE 0 END) AS bucket_0_30,
                           SUM(CASE WHEN age BETWEEN 31 AND 60 AND outstanding > 0 THEN outstanding ELSE 0 END) AS bucket_31_60,
                           SUM(CASE WHEN age BETWEEN 61 AND 90 AND outstanding > 0 THEN outstanding ELSE 0 END) AS bucket_61_90,
                           SUM(CASE WHEN age > 90 AND outstanding > 0 THEN outstanding ELSE 0 END) AS bucket_90_plus
                    FROM (
                        SELECT outstanding_amount AS outstanding,
                               DATEDIFF(CURRENT_DATE, COALESCE(payment_due_date, invoice_date)) AS age
                        FROM finance_invoices
                    ) t";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }
        $buckets = $stmt->fetch();
        
        $provisionRequired = 
            ($buckets['bucket_31_60'] * 0.10) + 
            ($buckets['bucket_61_90'] * 0.30) + 
            ($buckets['bucket_90_plus'] * 0.70);
        
        $totalCollected = $buckets['bucket_0_30'] + $buckets['bucket_31_60'] + $buckets['bucket_61_90'];
        $totalAtRisk = $buckets['bucket_90_plus'];
        $recoveryRate = ($totalAtRisk == 0) ? 100 : 
            round((1 - ($totalAtRisk / ($totalCollected + $totalAtRisk))) * 100, 2);
        
        $totalOutstanding = array_sum($buckets);
        $overduePercent = ($totalOutstanding == 0) ? 0 : 
            (($buckets['bucket_61_90'] + $buckets['bucket_90_plus']) / $totalOutstanding) * 100;
        
        $creditQuality = 'Good';
        if ($overduePercent > 30) {
            $creditQuality = 'Poor';
        } elseif ($overduePercent > 15) {
            $creditQuality = 'Concern';
        }
        
        return [
            'buckets' => $buckets,
            'provisionRequired' => round($provisionRequired, 2),
            'recoveryRate' => $recoveryRate,
            'creditQuality' => $creditQuality
        ];
    }
    
    private function getPaymentsAnalytics(?string $customerId): array
    {
        if ($customerId) {
            $sql = "SELECT COALESCE(SUM(paid_amount),0) AS total_payments, COUNT(*) AS payment_count
                    FROM finance_invoices
                    WHERE paid_amount > 0 AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                    AND customer_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$customerId]);
        } else {
            $sql = "SELECT COALESCE(SUM(paid_amount),0) AS total_payments, COUNT(*) AS payment_count
                    FROM finance_invoices
                    WHERE paid_amount > 0 AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }
        $paymentData = $stmt->fetch();
        
        $velocity = round($paymentData['total_payments'] / 30, 2);
        
        return [
            'totalPayments' => (float)$paymentData['total_payments'],
            'paymentCount' => (int)$paymentData['payment_count'],
            'velocity' => $velocity,
            'cashConversion' => 0,
            'forecastAccuracy' => 85
        ];
    }
    
    private function getConversionFunnel(?string $customerId): array
    {
        if ($customerId) {
            $q = $this->pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as val FROM finance_quotations WHERE customer_id = ?");
            $q->execute([$customerId]);
            $quotations = $q->fetch();
            
            $p = $this->pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as val FROM finance_purchase_orders WHERE customer_id = ?");
            $p->execute([$customerId]);
            $pos = $p->fetch();
            
            $i = $this->pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as val FROM finance_invoices WHERE customer_id = ?");
            $i->execute([$customerId]);
            $invoices = $i->fetch();
            
            $pm = $this->pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(paid_amount),0) as val FROM finance_invoices WHERE paid_amount > 0 AND customer_id = ?");
            $pm->execute([$customerId]);
            $payments = $pm->fetch();
        } else {
            $q = $this->pdo->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as val FROM finance_quotations");
            $quotations = $q->fetch();
            
            $p = $this->pdo->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as val FROM finance_purchase_orders");
            $pos = $p->fetch();
            
            $i = $this->pdo->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as val FROM finance_invoices");
            $invoices = $i->fetch();
            
            $pm = $this->pdo->query("SELECT COUNT(*) as cnt, COALESCE(SUM(paid_amount),0) as val FROM finance_invoices WHERE paid_amount > 0");
            $payments = $pm->fetch();
        }
        
        $funnel = [
            'quotations' => ['count' => $quotations['cnt'] ?? 0, 'value' => $quotations['val'] ?? 0],
            'purchase_orders' => ['count' => $pos['cnt'] ?? 0, 'value' => $pos['val'] ?? 0],
            'invoices' => ['count' => $invoices['cnt'] ?? 0, 'value' => $invoices['val'] ?? 0],
            'payments' => ['count' => $payments['cnt'] ?? 0, 'value' => $payments['val'] ?? 0]
        ];
        
        return [
            'funnel' => $funnel,
            'conversions' => [
                'quotationToPO' => $this->pct($funnel['quotations']['count'], $funnel['purchase_orders']['count']),
                'poToInvoice' => $this->pct($funnel['purchase_orders']['count'], $funnel['invoices']['count']),
                'invoiceToPayment' => $this->pct($funnel['invoices']['count'], $funnel['payments']['count'])
            ]
        ];
    }
    
    private function getQuotationDonut(string $prefix, ?string $customerId): array
    {
        $sql = "SELECT 
                    SUM(CASE WHEN status='PLACED' THEN 1 ELSE 0 END) AS placed_count,
                    SUM(CASE WHEN status='REJECTED' THEN 1 ELSE 0 END) AS rejected_count,
                    SUM(CASE WHEN status IN ('PENDING','DRAFT','REVISED') THEN 1 ELSE 0 END) AS pending_count
                FROM finance_quotations
                WHERE company_prefix = ?";
        $params = [$prefix];
        
        if ($customerId) {
            $sql .= " AND customer_id = ?";
            $params[] = $customerId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'placed' => (int)($result['placed_count'] ?? 0),
            'rejected' => (int)($result['rejected_count'] ?? 0),
            'pending' => (int)($result['pending_count'] ?? 0)
        ];
    }
    
    private function getPOClaimDistribution(string $prefix, ?string $customerId): array
    {
        $sql = "SELECT
                  CASE
                    WHEN (amount_paid / NULLIF(po_amount, 0)) * 100 < 40 THEN '<40%'
                    WHEN (amount_paid / NULLIF(po_amount, 0)) * 100 BETWEEN 40 AND 60 THEN '40-60%'
                    WHEN (amount_paid / NULLIF(po_amount, 0)) * 100 BETWEEN 60 AND 80 THEN '60-80%'
                    WHEN (amount_paid / NULLIF(po_amount, 0)) * 100 BETWEEN 80 AND 99 THEN '80-99%'
                    WHEN (amount_paid / NULLIF(po_amount, 0)) * 100 >= 100 THEN '100%'
                  END AS bucket,
                  COUNT(*) AS count
                FROM finance_purchase_orders
                WHERE company_prefix = ?";
        $params = [$prefix];
        
        if ($customerId) {
            $sql .= " AND customer_id = ?";
            $params[] = $customerId;
        }
        
        $sql .= " GROUP BY bucket ORDER BY FIELD(bucket, '<40%', '40-60%', '60-80%', '80-99%', '100%')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $distribution = [];
        foreach ($results as $row) {
            $distribution[$row['bucket']] = (int)$row['count'];
        }
        
        return $distribution;
    }
    
    private function getInvoiceCollections(string $prefix, ?string $customerId): array
    {
        $sql = "SELECT
                  SUM(total_amount) AS total_invoice_value,
                  SUM(taxable_amount - amount_paid) AS pending_invoice_value,
                  CASE 
                    WHEN SUM(total_amount) > 0 
                    THEN (SUM(taxable_amount - amount_paid) / (SUM(total_amount) / 30))
                    ELSE 0
                  END AS dso
                FROM finance_invoices
                WHERE company_prefix = ?";
        $params = [$prefix];
        
        if ($customerId) {
            $sql .= " AND customer_id = ?";
            $params[] = $customerId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_invoice_value' => (float)($result['total_invoice_value'] ?? 0),
            'pending_invoice_value' => (float)($result['pending_invoice_value'] ?? 0),
            'dso' => round((float)($result['dso'] ?? 0), 1)
        ];
    }
    
    private function getCustomerOutstanding(string $prefix, ?string $customerId): array
    {
        $sql = "SELECT 
                  customer_id,
                  SUM(taxable_amount - amount_paid) AS outstanding
                FROM finance_invoices
                WHERE company_prefix = ?";
        $params = [$prefix];
        
        if ($customerId) {
            $sql .= " AND customer_id = ?";
            $params[] = $customerId;
        }
        
        $sql .= " GROUP BY customer_id ORDER BY outstanding DESC LIMIT 10";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(fn($row) => [
            'customer_id' => $row['customer_id'],
            'outstanding' => (float)$row['outstanding']
        ], $results);
    }
    
    private function pct(int $numerator, int $denominator): float
    {
        return ($denominator <= 0) ? 0.0 : round(($numerator / $denominator) * 100.0, 2);
    }
    
    private function successResponse(array $data): array
    {
        return ['success' => true, 'data' => $data, 'timestamp' => date('Y-m-d H:i:s')];
    }
    
    private function errorResponse(string $message, int $code = 400): array
    {
        return ['success' => false, 'error' => $message, 'code' => $code, 'timestamp' => date('Y-m-d H:i:s')];
    }
}
