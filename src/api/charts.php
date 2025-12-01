<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once __DIR__ . '/../../app/config/database.php';
    
    $chart = $_GET['chart'] ?? '';
    $prefix = $_GET['prefix'] ?? '';
    if (!$chart) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Chart type required']);
        exit;
    }
    if (!$prefix) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Prefix required']);
        exit;
    }
    
    $mysql = Database::connect();
    $result = [];
    
    switch ($chart) {
        case 'quotations':
            try {
                $sql = "SELECT COALESCE(status, 'unknown') as status, COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM finance_consolidated WHERE company_prefix = ? AND record_type = 'quotation' GROUP BY status";
                $stmt = $mysql->prepare($sql);
                $stmt->execute([$prefix]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $result = ['pending' => 0, 'placed' => 0, 'rejected' => 0, 'total' => 0];
                foreach ($rows as $row) {
                    $status = strtolower($row['status'] ?? '');
                    if ($status === 'pending') $result['pending'] = (int)$row['count'];
                    elseif ($status === 'placed') $result['placed'] = (int)$row['count'];
                    elseif ($status === 'rejected') $result['rejected'] = (int)$row['count'];
                    $result['total'] += (float)$row['total'];
                }
            } catch (Exception $e) {
                $result = ['pending' => 0, 'placed' => 0, 'rejected' => 0, 'total' => 0];
            }
            break;
            
        case 'purchase_orders':
            try {
                $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COALESCE(SUM(amount), 0) as amount FROM finance_consolidated WHERE company_prefix = ? AND record_type = 'purchase_order' AND created_at IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month";
                $stmt = $mysql->prepare($sql);
                $stmt->execute([$prefix]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $result = array_map(fn($r) => (float)$r['amount'], $rows);
            } catch (Exception $e) {
                $result = [];
            }
            break;
            
        case 'invoices':
            try {
                $sql = "SELECT COALESCE(status, 'unknown') as status, COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM finance_consolidated WHERE company_prefix = ? AND record_type = 'invoice' GROUP BY status";
                $stmt = $mysql->prepare($sql);
                $stmt->execute([$prefix]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $result = ['paid' => 0, 'unpaid' => 0, 'overdue' => 0, 'total' => 0];
                foreach ($rows as $row) {
                    $status = strtolower($row['status'] ?? '');
                    if ($status === 'paid') $result['paid'] = (int)$row['count'];
                    elseif ($status === 'unpaid') $result['unpaid'] = (int)$row['count'];
                    elseif ($status === 'overdue') $result['overdue'] = (int)$row['count'];
                    $result['total'] += (float)$row['total'];
                }
            } catch (Exception $e) {
                $result = ['paid' => 0, 'unpaid' => 0, 'overdue' => 0, 'total' => 0];
            }
            break;
            
        case 'outstanding':
            try {
                $sql = "SELECT COALESCE(customer_name, 'Unknown') as customer_name, COALESCE(SUM(outstanding_amount), 0) as outstanding FROM finance_consolidated WHERE company_prefix = ? AND record_type = 'invoice' AND outstanding_amount > 0 GROUP BY customer_id, customer_name ORDER BY outstanding DESC LIMIT 5";
                $stmt = $mysql->prepare($sql);
                $stmt->execute([$prefix]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $result = array_map(fn($r) => ['customer' => $r['customer_name'], 'amount' => (float)$r['outstanding']], $rows);
            } catch (Exception $e) {
                $result = [];
            }
            break;
            
        case 'aging':
            try {
                $sql = "SELECT SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 0 AND 30 THEN outstanding_amount ELSE 0 END) as current, SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 31 AND 60 THEN outstanding_amount ELSE 0 END) as watch, SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 61 AND 90 THEN outstanding_amount ELSE 0 END) as concern, SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) > 90 THEN outstanding_amount ELSE 0 END) as critical FROM finance_consolidated WHERE company_prefix = ? AND record_type = 'invoice' AND outstanding_amount > 0";
                $stmt = $mysql->prepare($sql);
                $stmt->execute([$prefix]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $result = [
                    'current' => (float)($row['current'] ?? 0),
                    'watch' => (float)($row['watch'] ?? 0),
                    'concern' => (float)($row['concern'] ?? 0),
                    'critical' => (float)($row['critical'] ?? 0)
                ];
            } catch (Exception $e) {
                $result = ['current' => 0, 'watch' => 0, 'concern' => 0, 'critical' => 0];
            }
            break;
            
        case 'payments':
            try {
                $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COALESCE(SUM(amount), 0) as total FROM finance_consolidated WHERE company_prefix = ? AND record_type = 'payment' AND created_at IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month";
                $stmt = $mysql->prepare($sql);
                $stmt->execute([$prefix]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $result = array_map(fn($r) => (float)$r['total'], $rows);
            } catch (Exception $e) {
                $result = [];
            }
            break;
    }
    
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    error_log('Charts API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
