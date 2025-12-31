<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::connect();
    
    $prefix = $_GET['prefix'] ?? '';
    $customer_id = $_GET['customer_id'] ?? '';
    $type = $_GET['type'] ?? '';
    
    if (empty($prefix)) {
        throw new Exception('Prefix is required');
    }
    
    $len = strlen($prefix);
    
    switch ($type) {
        case 'quotations':
            $sql = "SELECT 
                        SUM(CASE WHEN UPPER(status) IN ('PLACED','APPROVED','ACCEPTED') THEN 1 ELSE 0 END) AS placed_count,
                        SUM(CASE WHEN UPPER(status) IN ('REJECTED','CANCELLED') THEN 1 ELSE 0 END) AS rejected_count,
                        SUM(CASE WHEN UPPER(status) IN ('PENDING','DRAFT','OPEN','NEW') OR status IS NULL THEN 1 ELSE 0 END) AS pending_count
                    FROM finance_quotations
                    WHERE LEFT(quotation_number, $len) = ?";
            $params = [$prefix];
            if ($customer_id) {
                $sql .= " AND customer_id = ?";
                $params[] = $customer_id;
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'placed' => (int)$result['placed_count'],
                    'rejected' => (int)$result['rejected_count'],
                    'pending' => (int)$result['pending_count']
                ]
            ]);
            break;
            
        case 'po_claims':
            $sql = "SELECT
                        SUM(CASE WHEN total_amount > 0 AND (COALESCE(invoice_claimed_amount,0)/total_amount*100) < 40 THEN total_amount ELSE 0 END) AS below_40,
                        SUM(CASE WHEN total_amount > 0 AND (COALESCE(invoice_claimed_amount,0)/total_amount*100) BETWEEN 40 AND 60 THEN total_amount ELSE 0 END) AS between_40_60,
                        SUM(CASE WHEN total_amount > 0 AND (COALESCE(invoice_claimed_amount,0)/total_amount*100) BETWEEN 60 AND 80 THEN total_amount ELSE 0 END) AS between_60_80,
                        SUM(CASE WHEN total_amount > 0 AND (COALESCE(invoice_claimed_amount,0)/total_amount*100) > 80 AND (COALESCE(invoice_claimed_amount,0)/total_amount*100) < 100 THEN total_amount ELSE 0 END) AS above_80,
                        SUM(CASE WHEN total_amount > 0 AND (COALESCE(invoice_claimed_amount,0)/total_amount*100) = 100 THEN total_amount ELSE 0 END) AS full_claim,
                        SUM(COALESCE(invoice_claimed_amount,0)) AS total_paid,
                        SUM(total_amount) AS total_amount
                    FROM finance_purchase_orders
                    WHERE (LEFT(po_number, $len) = ? OR LEFT(internal_po_number, $len) = ?) AND total_amount > 0";
            $params = [$prefix, $prefix];
            if ($customer_id) {
                $sql .= " AND customer_id = ?";
                $params[] = $customer_id;
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $fulfillment_rate = $result['total_amount'] > 0 ? 
                round(($result['total_paid'] / $result['total_amount']) * 100, 2) : 0;
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'below_40' => (float)$result['below_40'],
                    'between_40_60' => (float)$result['between_40_60'],
                    'between_60_80' => (float)$result['between_60_80'],
                    'above_80' => (float)$result['above_80'],
                    'full_claim' => (float)$result['full_claim'],
                    'fulfillment_rate' => $fulfillment_rate
                ]
            ]);
            break;
            
        case 'invoices':
            $sql = "SELECT
                        SUM(total_amount) AS total_invoice_value,
                        SUM(total_amount - paid_amount) AS pending_invoice_value,
                        SUM(paid_amount) AS collected_amount
                    FROM finance_invoices
                    WHERE LEFT(invoice_number, $len) = ?";
            $params = [$prefix];
            if ($customer_id) {
                $sql .= " AND customer_id = ?";
                $params[] = $customer_id;
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $daily_sales = $result['total_invoice_value'] > 0 ? $result['total_invoice_value'] / 30 : 0;
            $dso = $daily_sales > 0 ? round($result['pending_invoice_value'] / $daily_sales, 1) : 0;
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_invoice_value' => (float)$result['total_invoice_value'],
                    'pending_invoice_value' => (float)$result['pending_invoice_value'],
                    'collected_amount' => (float)$result['collected_amount'],
                    'dso' => $dso
                ]
            ]);
            break;
            
        case 'customer_outstanding':
            $sql = "SELECT 
                        c.display_name AS customer_name,
                        SUM(i.total_amount - i.paid_amount) AS outstanding_amount
                    FROM finance_invoices i
                    JOIN finance_customer c ON i.customer_id = c.id
                    WHERE LEFT(i.invoice_number, $len) = ?
                      AND (i.total_amount - i.paid_amount) > 0";
            $params = [$prefix];
            if ($customer_id) {
                $sql .= " AND i.customer_id = ?";
                $params[] = $customer_id;
            }
            $sql .= " GROUP BY c.display_name ORDER BY outstanding_amount DESC LIMIT 10";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $customers
            ]);
            break;
            
        case 'claimable_gst':
            $sql = "SELECT
                        SUM((total_amount - paid_amount) + total_tax) AS claimable_with_gst
                    FROM finance_invoices
                    WHERE LEFT(invoice_number, $len) = ?
                      AND (total_amount - paid_amount) > 0";
            $params = [$prefix];
            if ($customer_id) {
                $sql .= " AND customer_id = ?";
                $params[] = $customer_id;
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'claimable_with_gst' => (float)$result['claimable_with_gst']
                ]
            ]);
            break;
            
        default:
            throw new Exception('Invalid analytics type');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
