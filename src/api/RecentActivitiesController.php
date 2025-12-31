<?php

namespace Ergon\FinanceSync\Api;

use PDO;
use Psr\Log\LoggerInterface;

class RecentActivitiesController
{
    private PDO $pdo;
    private LoggerInterface $logger;
    
    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }
    
    /**
     * Get recent activities from MySQL only
     */
    public function getRecentActivities(array $params = []): array
    {
        $companyPrefix = $params['prefix'] ?? null;
        $recordType = $params['record_type'] ?? null;
        $limit = (int)($params['limit'] ?? 20);
        
        if (!$companyPrefix) {
            return $this->errorResponse('Company prefix is required', 400);
        }
        
        try {
            $sql = "
                SELECT 
                    record_type, 
                    document_number, 
                    customer_name, 
                    status, 
                    amount, 
                    created_at,
                    customer_id,
                    outstanding_amount,
                    due_date
                FROM finance_consolidated
                WHERE company_prefix = ?
            ";
            
            $params = [$companyPrefix];
            
            // Optional record type filter
            if ($recordType && in_array($recordType, ['quotation', 'purchase_order', 'invoice', 'payment'])) {
                $sql .= " AND record_type = ?";
                $params[] = $recordType;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $activities = $stmt->fetchAll();
            
            // Add icons and format data
            $formattedActivities = array_map([$this, 'formatActivity'], $activities);
            
            $this->logger->info("Retrieved recent activities", [
                'company_prefix' => $companyPrefix,
                'record_type' => $recordType,
                'count' => count($activities)
            ]);
            
            return $this->successResponse($formattedActivities);
            
        } catch (\PDOException $e) {
            $this->logger->error("Failed to fetch recent activities: " . $e->getMessage());
            return $this->errorResponse('Database error occurred', 500);
        }
    }
    
    /**
     * Get activity statistics
     */
    public function getActivityStats(string $companyPrefix): array
    {
        try {
            $sql = "
                SELECT 
                    record_type,
                    COUNT(*) as count,
                    SUM(amount) as total_amount,
                    SUM(outstanding_amount) as total_outstanding
                FROM finance_consolidated
                WHERE company_prefix = ?
                GROUP BY record_type
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$companyPrefix]);
            $stats = $stmt->fetchAll();
            
            $formattedStats = [];
            foreach ($stats as $stat) {
                $formattedStats[$stat['record_type']] = [
                    'count' => (int)$stat['count'],
                    'total_amount' => (float)$stat['total_amount'],
                    'total_outstanding' => (float)$stat['total_outstanding'],
                    'icon' => $this->getActivityIcon($stat['record_type'])
                ];
            }
            
            return $this->successResponse($formattedStats);
            
        } catch (\PDOException $e) {
            $this->logger->error("Failed to fetch activity stats: " . $e->getMessage());
            return $this->errorResponse('Database error occurred', 500);
        }
    }
    
    /**
     * Get outstanding invoices
     */
    public function getOutstandingInvoices(array $params = []): array
    {
        $companyPrefix = $params['prefix'] ?? $_ENV['COMPANY_PREFIX'] ?? null;
        $limit = (int)($params['limit'] ?? 50);
        
        try {
            $sql = "
                SELECT 
                    document_number as invoice_number,
                    customer_name,
                    due_date,
                    outstanding_amount,
                    status,
                    CASE 
                        WHEN due_date IS NULL THEN 0
                        WHEN outstanding_amount <= 0 THEN 0
                        ELSE DATEDIFF(CURDATE(), due_date)
                    END as daysOverdue
                FROM finance_consolidated
                WHERE record_type = 'invoice'
                AND outstanding_amount > 0
            ";
            
            $params_array = [];
            
            if ($companyPrefix) {
                $sql .= " AND company_prefix = ?";
                $params_array[] = $companyPrefix;
            }
            
            $sql .= " ORDER BY outstanding_amount DESC, due_date ASC LIMIT ?";
            $params_array[] = $limit;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params_array);
            $invoices = $stmt->fetchAll();
            
            return $this->successResponse([
                'invoices' => $invoices,
                'count' => count($invoices),
                'message' => count($invoices) > 0 ? null : 'No outstanding invoices found'
            ]);
            
        } catch (\PDOException $e) {
            $this->logger->error("Failed to fetch outstanding invoices: " . $e->getMessage());
            return $this->errorResponse('Database error occurred', 500);
        }
    }
    
    /**
     * Get funnel containers data
     */
    public function getFunnelContainers(array $params = []): array
    {
        $companyPrefix = $params['prefix'] ?? $_ENV['COMPANY_PREFIX'] ?? null;
        
        try {
            // Container 1: Quotations
            $sql1 = "
                SELECT 
                    COUNT(*) as quotations_count,
                    COALESCE(SUM(amount), 0) as quotations_total_value
                FROM finance_consolidated
                WHERE record_type = 'quotation'
            ";
            
            $params_array = [];
            if ($companyPrefix) {
                $sql1 .= " AND company_prefix = ?";
                $params_array[] = $companyPrefix;
            }
            
            $stmt1 = $this->pdo->prepare($sql1);
            $stmt1->execute($params_array);
            $container1 = $stmt1->fetch() ?: ['quotations_count' => 0, 'quotations_total_value' => 0];
            
            // Container 2: Purchase Orders
            $sql2 = "
                SELECT 
                    COUNT(*) as po_count,
                    COALESCE(SUM(amount), 0) as po_total_value
                FROM finance_consolidated
                WHERE record_type = 'purchase_order'
            ";
            
            $params_array = [];
            if ($companyPrefix) {
                $sql2 .= " AND company_prefix = ?";
                $params_array[] = $companyPrefix;
            }
            
            $stmt2 = $this->pdo->prepare($sql2);
            $stmt2->execute($params_array);
            $container2 = $stmt2->fetch() ?: ['po_count' => 0, 'po_total_value' => 0];
            
            // Calculate conversion rate
            $quotations_count = (int)$container1['quotations_count'];
            $po_count = (int)$container2['po_count'];
            $container2['po_conversion_rate'] = $quotations_count > 0 ? round(($po_count / $quotations_count) * 100, 1) : 0;
            
            // Container 3: Invoices
            $sql3 = "
                SELECT 
                    COUNT(*) as invoice_count,
                    COALESCE(SUM(amount), 0) as invoice_total_value
                FROM finance_consolidated
                WHERE record_type = 'invoice'
            ";
            
            $params_array = [];
            if ($companyPrefix) {
                $sql3 .= " AND company_prefix = ?";
                $params_array[] = $companyPrefix;
            }
            
            $stmt3 = $this->pdo->prepare($sql3);
            $stmt3->execute($params_array);
            $container3 = $stmt3->fetch() ?: ['invoice_count' => 0, 'invoice_total_value' => 0];
            
            // Calculate conversion rate
            $invoice_count = (int)$container3['invoice_count'];
            $container3['invoice_conversion_rate'] = $po_count > 0 ? round(($invoice_count / $po_count) * 100, 1) : 0;
            
            // Container 4: Payments
            $sql4 = "
                SELECT 
                    COUNT(*) as payment_count,
                    COALESCE(SUM(amount_paid), 0) as total_payment_received
                FROM finance_consolidated
                WHERE record_type = 'invoice' AND amount_paid > 0
            ";
            
            $params_array = [];
            if ($companyPrefix) {
                $sql4 .= " AND company_prefix = ?";
                $params_array[] = $companyPrefix;
            }
            
            $stmt4 = $this->pdo->prepare($sql4);
            $stmt4->execute($params_array);
            $container4 = $stmt4->fetch() ?: ['payment_count' => 0, 'total_payment_received' => 0];
            
            // Calculate conversion rate
            $payment_count = (int)$container4['payment_count'];
            $container4['payment_conversion_rate'] = $invoice_count > 0 ? round(($payment_count / $invoice_count) * 100, 1) : 0;
            
            return $this->successResponse([
                'containers' => [
                    'container1' => $container1,
                    'container2' => $container2,
                    'container3' => $container3,
                    'container4' => $container4
                ]
            ]);
            
        } catch (\PDOException $e) {
            $this->logger->error("Failed to fetch funnel containers: " . $e->getMessage());
            return $this->errorResponse('Database error occurred', 500);
        }
    }
    
    /**
     * Sync data - simulate ETL process
     */
    public function syncData(array $params = []): array
    {
        $companyPrefix = $params['prefix'] ?? $_ENV['COMPANY_PREFIX'] ?? 'ERGN';
        
        try {
            // Simulate adding more sample data
            $newRecords = [
                [
                    'record_type' => 'quotation',
                    'document_number' => 'ERGN-Q002',
                    'customer_id' => 'CUST002',
                    'customer_name' => 'Sample Customer 2',
                    'amount' => 15000.00,
                    'taxable_amount' => 12711.86,
                    'status' => 'pending',
                    'company_prefix' => $companyPrefix
                ],
                [
                    'record_type' => 'invoice',
                    'document_number' => 'ERGN-INV002',
                    'customer_id' => 'CUST002',
                    'customer_name' => 'Sample Customer 2',
                    'amount' => 12000.00,
                    'taxable_amount' => 10169.49,
                    'amount_paid' => 6000.00,
                    'outstanding_amount' => 4169.49,
                    'igst' => 1830.51,
                    'due_date' => '2024-03-15',
                    'invoice_date' => '2024-02-15',
                    'status' => 'pending',
                    'company_prefix' => $companyPrefix
                ]
            ];
            
            $insertSql = "INSERT IGNORE INTO finance_consolidated 
                (record_type, document_number, customer_id, customer_name, amount, taxable_amount, amount_paid, outstanding_amount, igst, due_date, invoice_date, status, company_prefix, raw_data) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($insertSql);
            $recordsProcessed = 0;
            
            foreach ($newRecords as $data) {
                $result = $stmt->execute([
                    $data['record_type'],
                    $data['document_number'],
                    $data['customer_id'],
                    $data['customer_name'],
                    $data['amount'],
                    $data['taxable_amount'],
                    $data['amount_paid'] ?? 0,
                    $data['outstanding_amount'] ?? 0,
                    $data['igst'] ?? 0,
                    $data['due_date'] ?? null,
                    $data['invoice_date'] ?? null,
                    $data['status'],
                    $data['company_prefix'],
                    json_encode($data)
                ]);
                
                if ($result) {
                    $recordsProcessed++;
                }
            }
            
            // Update dashboard stats
            $this->updateDashboardStats($companyPrefix);
            
            return $this->successResponse([
                'message' => 'ETL sync completed successfully',
                'records_processed' => $recordsProcessed,
                'prefix' => $companyPrefix
            ]);
            
        } catch (\PDOException $e) {
            $this->logger->error("Sync failed: " . $e->getMessage());
            return $this->errorResponse('Sync failed: Database error', 500);
        }
    }
    
    /**
     * Update dashboard stats after sync
     */
    private function updateDashboardStats(string $companyPrefix): void
    {
        try {
            // Calculate expected inflow (outstanding invoices)
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(outstanding_amount), 0) as expected_inflow
                FROM finance_consolidated 
                WHERE record_type = 'invoice' 
                AND outstanding_amount > 0
                AND company_prefix = ?
            ");
            $stmt->execute([$companyPrefix]);
            $expectedInflow = $stmt->fetch()['expected_inflow'];
            
            // Calculate PO commitments
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as po_commitments
                FROM finance_consolidated 
                WHERE record_type = 'purchase_order'
                AND company_prefix = ?
            ");
            $stmt->execute([$companyPrefix]);
            $poCommitments = $stmt->fetch()['po_commitments'];
            
            // Calculate net cash flow
            $netCashFlow = $expectedInflow - $poCommitments;
            
            // Update dashboard stats
            $this->pdo->prepare("
                INSERT INTO dashboard_stats (company_prefix, expected_inflow, po_commitments, net_cash_flow, last_computed_at) 
                VALUES (?, ?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                expected_inflow = VALUES(expected_inflow),
                po_commitments = VALUES(po_commitments),
                net_cash_flow = VALUES(net_cash_flow),
                last_computed_at = VALUES(last_computed_at)
            ")->execute([$companyPrefix, $expectedInflow, $poCommitments, $netCashFlow]);
            
        } catch (\PDOException $e) {
            $this->logger->error("Failed to update dashboard stats: " . $e->getMessage());
        }
    }
    
    /**
     * Get visualization data for charts
     */
    public function getVisualizationData(array $params = []): array
    {
        $type = $params['type'] ?? 'quotations';
        $companyPrefix = $params['prefix'] ?? $_ENV['COMPANY_PREFIX'] ?? 'ERGN';
        
        try {
            switch ($type) {
                case 'quotations':
                    return $this->getQuotationsChart($companyPrefix);
                case 'invoices':
                    return $this->getInvoicesChart($companyPrefix);
                case 'purchase_orders':
                    return $this->getPurchaseOrdersChart($companyPrefix);
                case 'payments':
                    return $this->getPaymentsChart($companyPrefix);
                default:
                    return $this->errorResponse('Invalid visualization type', 400);
            }
        } catch (\PDOException $e) {
            $this->logger->error("Visualization failed: " . $e->getMessage());
            return $this->errorResponse('Database error', 500);
        }
    }
    
    private function getQuotationsChart(string $companyPrefix): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                CASE 
                    WHEN status IN ('approved', 'placed') THEN 'Placed'
                    WHEN status = 'rejected' THEN 'Rejected'
                    ELSE 'Pending'
                END as status_group,
                COUNT(*) as count
            FROM finance_consolidated 
            WHERE record_type = 'quotation' AND company_prefix = ?
            GROUP BY status_group
        ");
        $stmt->execute([$companyPrefix]);
        $data = $stmt->fetchAll();
        
        $chartData = ['Pending' => 0, 'Placed' => 0, 'Rejected' => 0];
        foreach ($data as $row) {
            $chartData[$row['status_group']] = (int)$row['count'];
        }
        
        return $this->successResponse([
            'labels' => array_keys($chartData),
            'data' => array_values($chartData)
        ]);
    }
    
    private function getInvoicesChart(string $companyPrefix): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                CASE 
                    WHEN outstanding_amount <= 0 THEN 'Paid'
                    WHEN due_date < CURDATE() AND outstanding_amount > 0 THEN 'Overdue'
                    ELSE 'Unpaid'
                END as status_group,
                COUNT(*) as count
            FROM finance_consolidated 
            WHERE record_type = 'invoice' AND company_prefix = ?
            GROUP BY status_group
        ");
        $stmt->execute([$companyPrefix]);
        $data = $stmt->fetchAll();
        
        $chartData = ['Paid' => 0, 'Unpaid' => 0, 'Overdue' => 0];
        foreach ($data as $row) {
            $chartData[$row['status_group']] = (int)$row['count'];
        }
        
        return $this->successResponse([
            'labels' => array_keys($chartData),
            'data' => array_values($chartData)
        ]);
    }
    
    private function getPurchaseOrdersChart(string $companyPrefix): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DATE(created_at) as date, SUM(amount) as total
            FROM finance_consolidated 
            WHERE record_type = 'purchase_order' AND company_prefix = ?
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 7
        ");
        $stmt->execute([$companyPrefix]);
        $data = $stmt->fetchAll();
        
        $labels = [];
        $values = [];
        foreach (array_reverse($data) as $row) {
            $labels[] = date('M j', strtotime($row['date']));
            $values[] = (float)$row['total'];
        }
        
        return $this->successResponse([
            'labels' => $labels,
            'data' => $values
        ]);
    }
    
    private function getPaymentsChart(string $companyPrefix): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DATE(created_at) as date, SUM(amount_paid) as total
            FROM finance_consolidated 
            WHERE record_type = 'invoice' AND amount_paid > 0 AND company_prefix = ?
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 7
        ");
        $stmt->execute([$companyPrefix]);
        $data = $stmt->fetchAll();
        
        $labels = [];
        $values = [];
        foreach (array_reverse($data) as $row) {
            $labels[] = date('M j', strtotime($row['date']));
            $values[] = (float)$row['total'];
        }
        
        return $this->successResponse([
            'labels' => $labels,
            'data' => $values
        ]);
    }
    
    /**
     * Get outstanding by customer data
     */
    public function getOutstandingByCustomer(array $params = []): array
    {
        $companyPrefix = $params['prefix'] ?? $_ENV['COMPANY_PREFIX'] ?? 'ERGN';
        $limit = (int)($params['limit'] ?? 10);
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT customer_name, SUM(outstanding_amount) as total_outstanding
                FROM finance_consolidated 
                WHERE record_type = 'invoice' AND outstanding_amount > 0 AND company_prefix = ?
                GROUP BY customer_name
                ORDER BY total_outstanding DESC
                LIMIT ?
            ");
            $stmt->execute([$companyPrefix, $limit]);
            $data = $stmt->fetchAll();
            
            $labels = [];
            $values = [];
            $total = 0;
            
            foreach ($data as $row) {
                $labels[] = $row['customer_name'];
                $values[] = (float)$row['total_outstanding'];
                $total += (float)$row['total_outstanding'];
            }
            
            return $this->successResponse([
                'labels' => $labels,
                'data' => $values,
                'total' => $total,
                'customerCount' => count($data)
            ]);
            
        } catch (\PDOException $e) {
            $this->logger->error("Outstanding by customer failed: " . $e->getMessage());
            return $this->errorResponse('Database error', 500);
        }
    }
    
    /**
     * Get aging buckets data
     */
    public function getAgingBuckets(array $params = []): array
    {
        $companyPrefix = $params['prefix'] ?? $_ENV['COMPANY_PREFIX'] ?? 'ERGN';
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    CASE 
                        WHEN DATEDIFF(CURDATE(), due_date) <= 30 THEN '0-30 Days'
                        WHEN DATEDIFF(CURDATE(), due_date) <= 60 THEN '31-60 Days'
                        WHEN DATEDIFF(CURDATE(), due_date) <= 90 THEN '61-90 Days'
                        ELSE '90+ Days'
                    END as aging_bucket,
                    SUM(outstanding_amount) as total_amount
                FROM finance_consolidated 
                WHERE record_type = 'invoice' 
                AND outstanding_amount > 0 
                AND due_date IS NOT NULL 
                AND company_prefix = ?
                GROUP BY aging_bucket
                ORDER BY 
                    CASE aging_bucket
                        WHEN '0-30 Days' THEN 1
                        WHEN '31-60 Days' THEN 2
                        WHEN '61-90 Days' THEN 3
                        WHEN '90+ Days' THEN 4
                    END
            ");
            $stmt->execute([$companyPrefix]);
            $data = $stmt->fetchAll();
            
            $buckets = ['0-30 Days' => 0, '31-60 Days' => 0, '61-90 Days' => 0, '90+ Days' => 0];
            foreach ($data as $row) {
                $buckets[$row['aging_bucket']] = (float)$row['total_amount'];
            }
            
            return $this->successResponse([
                'labels' => array_keys($buckets),
                'data' => array_values($buckets)
            ]);
            
        } catch (\PDOException $e) {
            $this->logger->error("Aging buckets failed: " . $e->getMessage());
            return $this->errorResponse('Database error', 500);
        }
    }
    
    /**
     * Get dashboard cash flow stats
     */
    public function getDashboardStats(string $companyPrefix): array
    {
        try {
            // Get comprehensive dashboard data
            $dashboardData = [];
            
            // Total invoice amount
            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM finance_consolidated WHERE record_type = 'invoice' AND company_prefix = ?");
            $stmt->execute([$companyPrefix]);
            $dashboardData['totalInvoiceAmount'] = (float)$stmt->fetch()['total'];
            
            // Invoice received (amount paid)
            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) as total FROM finance_consolidated WHERE record_type = 'invoice' AND company_prefix = ?");
            $stmt->execute([$companyPrefix]);
            $dashboardData['invoiceReceived'] = (float)$stmt->fetch()['total'];
            
            // Outstanding amount
            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(outstanding_amount), 0) as total FROM finance_consolidated WHERE record_type = 'invoice' AND outstanding_amount > 0 AND company_prefix = ?");
            $stmt->execute([$companyPrefix]);
            $dashboardData['pendingInvoiceAmount'] = (float)$stmt->fetch()['total'];
            
            // GST liability on outstanding invoices
            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(igst + cgst + sgst), 0) as total FROM finance_consolidated WHERE record_type = 'invoice' AND outstanding_amount > 0 AND company_prefix = ?");
            $stmt->execute([$companyPrefix]);
            $dashboardData['pendingGSTAmount'] = (float)$stmt->fetch()['total'];
            
            // PO commitments
            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM finance_consolidated WHERE record_type = 'purchase_order' AND company_prefix = ?");
            $stmt->execute([$companyPrefix]);
            $dashboardData['pendingPOValue'] = (float)$stmt->fetch()['total'];
            
            // Claimable amount (total invoice - paid)
            $dashboardData['claimableAmount'] = $dashboardData['totalInvoiceAmount'] - $dashboardData['invoiceReceived'];
            
            // Conversion funnel data
            $stmt = $this->pdo->prepare("SELECT record_type, COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM finance_consolidated WHERE company_prefix = ? GROUP BY record_type");
            $stmt->execute([$companyPrefix]);
            $funnelData = $stmt->fetchAll();
            
            $funnel = ['quotations' => 0, 'quotationValue' => 0, 'purchaseOrders' => 0, 'poValue' => 0, 'invoices' => 0, 'invoiceValue' => 0, 'payments' => 0, 'paymentValue' => 0];
            
            foreach ($funnelData as $row) {
                switch ($row['record_type']) {
                    case 'quotation':
                        $funnel['quotations'] = (int)$row['count'];
                        $funnel['quotationValue'] = (float)$row['total'];
                        break;
                    case 'purchase_order':
                        $funnel['purchaseOrders'] = (int)$row['count'];
                        $funnel['poValue'] = (float)$row['total'];
                        break;
                    case 'invoice':
                        $funnel['invoices'] = (int)$row['count'];
                        $funnel['invoiceValue'] = (float)$row['total'];
                        // Count payments as invoices with amount_paid > 0
                        $stmt2 = $this->pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(amount_paid), 0) as total FROM finance_consolidated WHERE record_type = 'invoice' AND amount_paid > 0 AND company_prefix = ?");
                        $stmt2->execute([$companyPrefix]);
                        $paymentData = $stmt2->fetch();
                        $funnel['payments'] = (int)$paymentData['count'];
                        $funnel['paymentValue'] = (float)$paymentData['total'];
                        break;
                }
            }
            
            // Calculate conversion rates
            $funnel['quotationToPO'] = $funnel['quotations'] > 0 ? round(($funnel['purchaseOrders'] / $funnel['quotations']) * 100, 1) : 0;
            $funnel['poToInvoice'] = $funnel['purchaseOrders'] > 0 ? round(($funnel['invoices'] / $funnel['purchaseOrders']) * 100, 1) : 0;
            $funnel['invoiceToPayment'] = $funnel['invoices'] > 0 ? round(($funnel['payments'] / $funnel['invoices']) * 100, 1) : 0;
            
            $dashboardData['conversionFunnel'] = $funnel;
            
            // Cash flow data
            $dashboardData['cashFlow'] = [
                'expectedInflow' => $dashboardData['pendingInvoiceAmount'],
                'poCommitments' => $dashboardData['pendingPOValue']
            ];
            
            return $this->successResponse($dashboardData);
            
        } catch (\PDOException $e) {
            $this->logger->error("Failed to fetch dashboard stats: " . $e->getMessage());
            return $this->errorResponse('Database error occurred', 500);
        }
    }
    
    /**
     * Format activity with icon and additional data
     */
    private function formatActivity(array $activity): array
    {
        return [
            'record_type' => $activity['record_type'],
            'icon' => $this->getActivityIcon($activity['record_type']),
            'document_number' => $activity['document_number'],
            'customer_name' => $activity['customer_name'],
            'customer_id' => $activity['customer_id'],
            'status' => $activity['status'],
            'amount' => (float)$activity['amount'],
            'outstanding_amount' => (float)$activity['outstanding_amount'],
            'due_date' => $activity['due_date'],
            'created_at' => $activity['created_at'],
            'formatted_amount' => number_format($activity['amount'], 2),
            'is_overdue' => $this->isOverdue($activity)
        ];
    }
    
    /**
     * Get icon for activity type
     */
    private function getActivityIcon(string $recordType): string
    {
        return match ($recordType) {
            'quotation' => '📝',
            'purchase_order' => '🛒',
            'invoice' => '💰',
            'payment' => '💳',
            default => '📄'
        };
    }
    
    /**
     * Check if activity is overdue
     */
    private function isOverdue(array $activity): bool
    {
        if ($activity['record_type'] !== 'invoice' || !$activity['due_date'] || $activity['outstanding_amount'] <= 0) {
            return false;
        }
        
        try {
            $dueDate = new \DateTime($activity['due_date']);
            $today = new \DateTime('today');
            return $today > $dueDate;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Success response format
     */
    private function successResponse(array $data): array
    {
        return [
            'success' => true,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Error response format
     */
    private function errorResponse(string $message, int $code = 400): array
    {
        return [
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Handle HTTP request and return JSON response
     */
    public function handleRequest(): void
    {
        // Set JSON headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: ' . ($_ENV['API_CORS_ORIGINS'] ?? '*'));
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // Allow GET and POST requests
        if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
            http_response_code(405);
            echo json_encode($this->errorResponse('Method not allowed', 405));
            exit;
        }
        
        // Parse query parameters
        $params = $_GET;
        
        // Route to appropriate method
        $action = $params['action'] ?? 'activities';
        
        try {
            switch ($action) {
                case 'activities':
                    $response = $this->getRecentActivities($params);
                    break;
                case 'stats':
                    if (!isset($params['prefix'])) {
                        $response = $this->errorResponse('Company prefix is required', 400);
                    } else {
                        $response = $this->getActivityStats($params['prefix']);
                    }
                    break;
                case 'dashboard':
                    if (!isset($params['prefix'])) {
                        $response = $this->errorResponse('Company prefix is required', 400);
                    } else {
                        $response = $this->getDashboardStats($params['prefix']);
                    }
                    break;
                case 'outstanding-invoices':
                    $response = $this->getOutstandingInvoices($params);
                    break;
                case 'funnel-containers':
                    $response = $this->getFunnelContainers($params);
                    break;
                case 'sync':
                    $response = $this->syncData($params);
                    break;
                case 'visualization':
                    $response = $this->getVisualizationData($params);
                    break;
                case 'outstanding-by-customer':
                    $response = $this->getOutstandingByCustomer($params);
                    break;
                case 'aging-buckets':
                    $response = $this->getAgingBuckets($params);
                    break;
                default:
                    $response = $this->errorResponse('Invalid action', 400);
            }
            
            http_response_code($response['success'] ? 200 : ($response['code'] ?? 400));
            echo json_encode($response);
            
        } catch (\Exception $e) {
            $this->logger->error("API request failed: " . $e->getMessage());
            http_response_code(500);
            echo json_encode($this->errorResponse('Internal server error', 500));
        }
    }
}
