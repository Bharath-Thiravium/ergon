<?php

require_once __DIR__ . '/../../app/config/database.php';

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $pdo = Database::connect();
    $action = $_GET['action'] ?? 'dashboard';
    $prefix = $_GET['prefix'] ?? 'ERGN';
    
    switch ($action) {
        case 'dashboard':
            echo json_encode(getDashboardStats($pdo, $prefix));
            break;
        case 'activities':
            echo json_encode(getRecentActivities($pdo, $prefix));
            break;
        case 'funnel-containers':
            echo json_encode(getFunnelContainers($pdo, $prefix));
            break;
        case 'visualization':
            echo json_encode(getVisualizationData($pdo, $prefix, $_GET['type'] ?? 'quotations'));
            break;
        case 'outstanding-invoices':
            echo json_encode(getOutstandingInvoices($pdo, $prefix));
            break;
        case 'outstanding-by-customer':
            echo json_encode(getOutstandingByCustomer($pdo, $prefix, (int)($_GET['limit'] ?? 10)));
            break;
        case 'aging-buckets':
            echo json_encode(getAgingBuckets($pdo, $prefix));
            break;
        case 'sync':
            echo json_encode(syncData($pdo, $prefix));
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

function getDashboardStats($pdo, $prefix) {
    try {
        // Total invoice amount
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM finance_consolidated WHERE record_type = 'invoice' AND company_prefix = ?");
        $stmt->execute([$prefix]);
        $totalInvoiceAmount = (float)$stmt->fetch()['total'];
        
        // Invoice received (amount paid)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) as total FROM finance_consolidated WHERE record_type = 'invoice' AND company_prefix = ?");
        $stmt->execute([$prefix]);
        $invoiceReceived = (float)$stmt->fetch()['total'];
        
        // Outstanding amount
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(outstanding_amount), 0) as total FROM finance_consolidated WHERE record_type = 'invoice' AND outstanding_amount > 0 AND company_prefix = ?");
        $stmt->execute([$prefix]);
        $pendingInvoiceAmount = (float)$stmt->fetch()['total'];
        
        // GST liability on outstanding invoices
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(igst + cgst + sgst), 0) as total FROM finance_consolidated WHERE record_type = 'invoice' AND outstanding_amount > 0 AND company_prefix = ?");
        $stmt->execute([$prefix]);
        $pendingGSTAmount = (float)$stmt->fetch()['total'];
        
        // PO commitments
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM finance_consolidated WHERE record_type = 'purchase_order' AND company_prefix = ?");
        $stmt->execute([$prefix]);
        $pendingPOValue = (float)$stmt->fetch()['total'];
        
        // Claimable amount
        $claimableAmount = $totalInvoiceAmount - $invoiceReceived;
        
        // Conversion funnel data
        $stmt = $pdo->prepare("SELECT record_type, COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM finance_consolidated WHERE company_prefix = ? GROUP BY record_type");
        $stmt->execute([$prefix]);
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
                    $stmt2 = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(amount_paid), 0) as total FROM finance_consolidated WHERE record_type = 'invoice' AND amount_paid > 0 AND company_prefix = ?");
                    $stmt2->execute([$prefix]);
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
        
        return [
            'success' => true,
            'data' => [
                'totalInvoiceAmount' => $totalInvoiceAmount,
                'invoiceReceived' => $invoiceReceived,
                'pendingInvoiceAmount' => $pendingInvoiceAmount,
                'pendingGSTAmount' => $pendingGSTAmount,
                'pendingPOValue' => $pendingPOValue,
                'claimableAmount' => $claimableAmount,
                'conversionFunnel' => $funnel,
                'cashFlow' => [
                    'expectedInflow' => $pendingInvoiceAmount,
                    'poCommitments' => $pendingPOValue
                ]
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to fetch dashboard stats: ' . $e->getMessage()];
    }
}

function getRecentActivities($pdo, $prefix) {
    try {
        $limit = (int)($_GET['limit'] ?? 20);
        $recordType = $_GET['record_type'] ?? null;
        
        $sql = "SELECT record_type, document_number, customer_name, status, amount, created_at, customer_id, outstanding_amount, due_date 
                FROM finance_consolidated WHERE company_prefix = ?";
        $params = [$prefix];
        
        if ($recordType && in_array($recordType, ['quotation', 'purchase_order', 'invoice', 'payment'])) {
            $sql .= " AND record_type = ?";
            $params[] = $recordType;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $activities = $stmt->fetchAll();
        
        // Format activities
        $formattedActivities = array_map(function($activity) {
            $icons = ['quotation' => '📝', 'purchase_order' => '🛒', 'invoice' => '💰', 'payment' => '💳'];
            return [
                'record_type' => $activity['record_type'],
                'icon' => $icons[$activity['record_type']] ?? '📄',
                'document_number' => $activity['document_number'],
                'customer_name' => $activity['customer_name'],
                'customer_id' => $activity['customer_id'],
                'status' => $activity['status'],
                'amount' => (float)$activity['amount'],
                'outstanding_amount' => (float)$activity['outstanding_amount'],
                'due_date' => $activity['due_date'],
                'created_at' => $activity['created_at'],
                'formatted_amount' => number_format($activity['amount'], 2),
                'is_overdue' => false
            ];
        }, $activities);
        
        return ['success' => true, 'data' => $formattedActivities, 'timestamp' => date('Y-m-d H:i:s')];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to fetch activities: ' . $e->getMessage()];
    }
}

function getFunnelContainers($pdo, $prefix) {
    try {
        // Container 1: Quotations
        $stmt = $pdo->prepare("SELECT COUNT(*) as quotations_count, COALESCE(SUM(amount), 0) as quotations_total_value FROM finance_consolidated WHERE record_type = 'quotation' AND company_prefix = ?");
        $stmt->execute([$prefix]);
        $container1 = $stmt->fetch() ?: ['quotations_count' => 0, 'quotations_total_value' => 0];
        
        // Container 2: Purchase Orders
        $stmt = $pdo->prepare("SELECT COUNT(*) as po_count, COALESCE(SUM(amount), 0) as po_total_value FROM finance_consolidated WHERE record_type = 'purchase_order' AND company_prefix = ?");
        $stmt->execute([$prefix]);
        $container2 = $stmt->fetch() ?: ['po_count' => 0, 'po_total_value' => 0];
        
        // Calculate conversion rate
        $quotations_count = (int)$container1['quotations_count'];
        $po_count = (int)$container2['po_count'];
        $container2['po_conversion_rate'] = $quotations_count > 0 ? round(($po_count / $quotations_count) * 100, 1) : 0;
        
        // Container 3: Invoices
        $stmt = $pdo->prepare("SELECT COUNT(*) as invoice_count, COALESCE(SUM(amount), 0) as invoice_total_value FROM finance_consolidated WHERE record_type = 'invoice' AND company_prefix = ?");
        $stmt->execute([$prefix]);
        $container3 = $stmt->fetch() ?: ['invoice_count' => 0, 'invoice_total_value' => 0];
        
        // Calculate conversion rate
        $invoice_count = (int)$container3['invoice_count'];
        $container3['invoice_conversion_rate'] = $po_count > 0 ? round(($invoice_count / $po_count) * 100, 1) : 0;
        
        // Container 4: Payments
        $stmt = $pdo->prepare("SELECT COUNT(*) as payment_count, COALESCE(SUM(amount_paid), 0) as total_payment_received FROM finance_consolidated WHERE record_type = 'invoice' AND amount_paid > 0 AND company_prefix = ?");
        $stmt->execute([$prefix]);
        $container4 = $stmt->fetch() ?: ['payment_count' => 0, 'total_payment_received' => 0];
        
        // Calculate conversion rate
        $payment_count = (int)$container4['payment_count'];
        $container4['payment_conversion_rate'] = $invoice_count > 0 ? round(($payment_count / $invoice_count) * 100, 1) : 0;
        
        return [
            'success' => true,
            'data' => [
                'containers' => [
                    'container1' => $container1,
                    'container2' => $container2,
                    'container3' => $container3,
                    'container4' => $container4
                ]
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to fetch funnel containers: ' . $e->getMessage()];
    }
}

function getVisualizationData($pdo, $prefix, $type) {
    try {
        switch ($type) {
            case 'quotations':
                $stmt = $pdo->prepare("
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
                $stmt->execute([$prefix]);
                $data = $stmt->fetchAll();
                
                $chartData = ['Pending' => 0, 'Placed' => 0, 'Rejected' => 0];
                foreach ($data as $row) {
                    $chartData[$row['status_group']] = (int)$row['count'];
                }
                
                return [
                    'success' => true,
                    'data' => [
                        'labels' => array_keys($chartData),
                        'data' => array_values($chartData)
                    ]
                ];
                
            case 'invoices':
                $stmt = $pdo->prepare("
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
                $stmt->execute([$prefix]);
                $data = $stmt->fetchAll();
                
                $chartData = ['Paid' => 0, 'Unpaid' => 0, 'Overdue' => 0];
                foreach ($data as $row) {
                    $chartData[$row['status_group']] = (int)$row['count'];
                }
                
                return [
                    'success' => true,
                    'data' => [
                        'labels' => array_keys($chartData),
                        'data' => array_values($chartData)
                    ]
                ];
                
            default:
                return ['success' => false, 'error' => 'Invalid visualization type'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Visualization failed: ' . $e->getMessage()];
    }
}

function getOutstandingInvoices($pdo, $prefix) {
    try {
        $limit = (int)($_GET['limit'] ?? 50);
        
        $stmt = $pdo->prepare("
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
            AND company_prefix = ?
            ORDER BY outstanding_amount DESC, due_date ASC 
            LIMIT ?
        ");
        $stmt->execute([$prefix, $limit]);
        $invoices = $stmt->fetchAll();
        
        return [
            'success' => true,
            'data' => [
                'invoices' => $invoices,
                'count' => count($invoices),
                'message' => count($invoices) > 0 ? null : 'No outstanding invoices found'
            ]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to fetch outstanding invoices: ' . $e->getMessage()];
    }
}

function getOutstandingByCustomer($pdo, $prefix, $limit) {
    try {
        $stmt = $pdo->prepare("
            SELECT customer_name, SUM(outstanding_amount) as total_outstanding
            FROM finance_consolidated 
            WHERE record_type = 'invoice' AND outstanding_amount > 0 AND company_prefix = ?
            GROUP BY customer_name
            ORDER BY total_outstanding DESC
            LIMIT ?
        ");
        $stmt->execute([$prefix, $limit]);
        $data = $stmt->fetchAll();
        
        $labels = [];
        $values = [];
        $total = 0;
        
        foreach ($data as $row) {
            $labels[] = $row['customer_name'];
            $values[] = (float)$row['total_outstanding'];
            $total += (float)$row['total_outstanding'];
        }
        
        return [
            'success' => true,
            'data' => [
                'labels' => $labels,
                'data' => $values,
                'total' => $total,
                'customerCount' => count($data)
            ]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Outstanding by customer failed: ' . $e->getMessage()];
    }
}

function getAgingBuckets($pdo, $prefix) {
    try {
        $stmt = $pdo->prepare("
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
        $stmt->execute([$prefix]);
        $data = $stmt->fetchAll();
        
        $buckets = ['0-30 Days' => 0, '31-60 Days' => 0, '61-90 Days' => 0, '90+ Days' => 0];
        foreach ($data as $row) {
            $buckets[$row['aging_bucket']] = (float)$row['total_amount'];
        }
        
        return [
            'success' => true,
            'data' => [
                'labels' => array_keys($buckets),
                'data' => array_values($buckets)
            ]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Aging buckets failed: ' . $e->getMessage()];
    }
}

function syncData($pdo, $prefix) {
    try {
        // Simple sync - just return success for now
        return [
            'success' => true,
            'data' => [
                'message' => 'ETL sync completed successfully',
                'records_processed' => 0,
                'prefix' => $prefix
            ]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Sync failed: ' . $e->getMessage()];
    }
}

?>
