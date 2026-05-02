<?php

// Enhanced Measurement Sheet Controller Methods
// Add these methods to your existing FinanceController.php

class FinanceControllerEnhanced extends FinanceController {

    public function measurementSheetEnhanced($request = null) {
        ModuleMiddleware::requireModule('finance');
        require_once __DIR__ . '/../config/database.php';
        $db = Database::connect();
        $purchase_orders = [];
        $error = null;
        
        try {
            $pg = $this->pgConnect();
            $stmt = $pg->query("
                SELECT po.*, c.name AS customer_name, co.name AS company_name, co.company_prefix,
                       po.invoice_claimed_amount, po.total_amount
                FROM finance_purchase_orders po
                LEFT JOIN finance_customer c ON c.id = po.customer_id
                LEFT JOIN authentication_company co ON co.id = po.company_id
                ORDER BY po.po_date DESC, po.id DESC
            ");
            $purchase_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Attach RA bill count + opening balance flag from MySQL
            if (!empty($purchase_orders)) {
                $poIds = array_column($purchase_orders, 'id');
                $placeholders = implode(',', array_fill(0, count($poIds), '?'));
                $raStmt = $db->prepare("
                    SELECT po_id, COUNT(*) as ra_count,
                           MAX(CASE WHEN ra_sequence=0 THEN 1 ELSE 0 END) as has_opening,
                           SUM(CASE WHEN ra_sequence > 0 THEN total_claimed ELSE 0 END) as total_claimed
                    FROM ra_bills 
                    WHERE po_id IN ($placeholders) 
                    GROUP BY po_id
                ");
                $raStmt->execute($poIds);
                $raData = array_column($raStmt->fetchAll(PDO::FETCH_ASSOC), null, 'po_id');
                
                foreach ($purchase_orders as &$po) {
                    $po['ra_count'] = $raData[$po['id']]['ra_count'] ?? 0;
                    $po['has_opening'] = (bool)($raData[$po['id']]['has_opening'] ?? false);
                    $po['invoice_claimed_amount'] = $raData[$po['id']]['total_claimed'] ?? 0;
                }
                unset($po);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        
        include __DIR__ . '/../../views/finance/measurement_sheet_enhanced.php';
    }

    public function measurementSheetCreateEnhanced($po_id = null) {
        ModuleMiddleware::requireModule('finance');
        require_once __DIR__ . '/../config/database.php';
        $db = Database::connect();
        $po = null; 
        $items = []; 
        $error = null; 
        $nextSeq = 1;
        
        try {
            $pg = $this->pgConnect();
            $safeId = (int)$po_id;
            
            // Get PO details with enhanced fields
            $stmt = $pg->prepare("
                SELECT po.*, c.name AS customer_name, c.billing_address_line1, c.billing_city,
                       co.name AS company_name, co.company_prefix, co.address AS company_address,
                       co.gst_number AS company_gstin, co.logo AS company_logo
                FROM finance_purchase_orders po
                LEFT JOIN finance_customer c ON c.id = po.customer_id
                LEFT JOIN authentication_company co ON co.id = po.company_id
                WHERE po.id = ?
            ");
            $stmt->execute([$safeId]);
            $po = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$po) throw new Exception('PO not found');

            // Get PO items
            $iStmt = $pg->prepare("
                SELECT * FROM finance_purchase_order_items
                WHERE purchase_order_id = ? 
                ORDER BY line_number
            ");
            $iStmt->execute([$safeId]);
            $pgItems = $iStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get next RA sequence
            $seqStmt = $db->prepare("SELECT COALESCE(MAX(ra_sequence),0)+1 FROM ra_bills WHERE po_id=?");
            $seqStmt->execute([$safeId]);
            $nextSeq = (int)$seqStmt->fetchColumn();

            // Get cumulative claimed per item from previous RA bills
            $prevStmt = $db->prepare("
                SELECT rbi.po_item_id,
                       SUM(rbi.this_qty) AS prev_qty,
                       SUM(rbi.this_pct) AS prev_pct,
                       SUM(rbi.this_amount) AS prev_amount
                FROM ra_bill_items rbi
                JOIN ra_bills rb ON rb.id = rbi.ra_bill_id
                WHERE rb.po_id = ? AND rb.status != 'cancelled'
                GROUP BY rbi.po_item_id
            ");
            $prevStmt->execute([$safeId]);
            $prevMap = array_column($prevStmt->fetchAll(PDO::FETCH_ASSOC), null, 'po_item_id');

            // Merge PO items with previous claimed data
            foreach ($pgItems as $item) {
                $prev = $prevMap[$item['id']] ?? [];
                $item['prev_claimed_qty'] = floatval($prev['prev_qty'] ?? 0);
                $item['prev_claimed_pct'] = floatval($prev['prev_pct'] ?? 0);
                $item['prev_claimed_amount'] = floatval($prev['prev_amount'] ?? 0);
                
                // Calculate remaining quantities
                $item['remaining_qty'] = floatval($item['quantity']) - $item['prev_claimed_qty'];
                $item['remaining_amount'] = floatval($item['line_total']) - $item['prev_claimed_amount'];
                
                $items[] = $item;
            }
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        
        include __DIR__ . '/../../views/finance/measurement_sheet_create_enhanced.php';
    }

    public function measurementSheetStoreEnhanced($po_id = null) {
        ModuleMiddleware::requireModule('finance');
        require_once __DIR__ . '/../config/database.php';
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ergon/finance/measurement-sheet'); 
            exit;
        }
        
        $db = Database::connect();
        $safeId = (int)$po_id;
        
        try {
            $db->beginTransaction();
            
            // Get next sequence
            $seqStmt = $db->prepare("SELECT COALESCE(MAX(ra_sequence),0)+1 FROM ra_bills WHERE po_id=?");
            $seqStmt->execute([$safeId]);
            $seq = (int)$seqStmt->fetchColumn();
            $raNum = 'RA-' . str_pad($seq, 2, '0', STR_PAD_LEFT);

            $lineItems = $_POST['items'] ?? [];
            $totalClaimed = 0;
            
            foreach ($lineItems as $li) {
                $totalClaimed += floatval($li['this_amount'] ?? 0);
            }

            // Insert enhanced RA bill with additional fields
            $ins = $db->prepare("
                INSERT INTO ra_bills
                (po_id, po_number, company_id, customer_id, ra_bill_number, ra_sequence,
                 bill_date, project, contractor, notes, total_claimed, status, created_by,
                 work_order_date, site_engineer, project_manager, work_status, expected_completion)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            
            $ins->execute([
                $safeId,
                $_POST['po_number'] ?? '',
                $_POST['company_id'] ?? 0,
                $_POST['customer_id'] ?? 0,
                $raNum, 
                $seq,
                $_POST['bill_date'] ?? date('Y-m-d'),
                $_POST['project'] ?? '',
                $_POST['contractor'] ?? '',
                $_POST['notes'] ?? '',
                $totalClaimed,
                'draft',
                $_SESSION['user_id'] ?? 0,
                $_POST['work_order_date'] ?? null,
                $_POST['site_engineer'] ?? '',
                $_POST['project_manager'] ?? '',
                $_POST['work_status'] ?? 'in_progress',
                $_POST['expected_completion'] ?? null
            ]);
            
            $raBillId = $db->lastInsertId();

            // Insert line items with enhanced calculations
            $iIns = $db->prepare("
                INSERT INTO ra_bill_items
                (ra_bill_id, po_item_id, line_number, product_name, description, unit,
                 po_quantity, po_unit_price, po_line_total,
                 prev_claimed_qty, prev_claimed_pct, prev_claimed_amount,
                 claim_type, this_qty, this_pct, this_amount,
                 cumulative_qty, cumulative_pct, cumulative_amount)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            
            foreach ($lineItems as $li) {
                $thisQty = floatval($li['this_qty'] ?? 0);
                $thisPct = floatval($li['this_pct'] ?? 0);
                $thisAmt = floatval($li['this_amount'] ?? 0);
                $prevQty = floatval($li['prev_claimed_qty'] ?? 0);
                $prevPct = floatval($li['prev_claimed_pct'] ?? 0);
                $prevAmt = floatval($li['prev_claimed_amount'] ?? 0);
                
                $iIns->execute([
                    $raBillId,
                    $li['po_item_id'],
                    $li['line_number'],
                    $li['product_name'],
                    $li['description'] ?? '',
                    $li['unit'] ?? '',
                    floatval($li['po_quantity']),
                    floatval($li['po_unit_price']),
                    floatval($li['po_line_total']),
                    $prevQty, $prevPct, $prevAmt,
                    $li['claim_type'] ?? 'quantity',
                    $thisQty, $thisPct, $thisAmt,
                    $prevQty + $thisQty,
                    $prevPct + $thisPct,
                    $prevAmt + $thisAmt,
                ]);
            }
            
            $db->commit();
            
            // Redirect to enhanced print view
            header("Location: /ergon/finance/measurement-sheet/{$raBillId}/print-enhanced"); 
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log('Enhanced RA bill store error: ' . $e->getMessage());
            header('Location: /ergon/finance/measurement-sheet/' . $safeId . '/create-enhanced?error=1'); 
            exit;
        }
    }

    public function measurementSheetPrintEnhanced($id = null) {
        ModuleMiddleware::requireModule('finance');
        require_once __DIR__ . '/../config/database.php';
        $db = Database::connect();
        $ra = null; 
        $items = []; 
        $po = null; 
        $error = null;
        
        try {
            $safeId = (int)$id;
            
            // Get RA bill with enhanced fields
            $raStmt = $db->prepare("SELECT * FROM ra_bills WHERE id=? LIMIT 1");
            $raStmt->execute([$safeId]);
            $ra = $raStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ra) throw new Exception('RA Bill not found');

            // Get RA bill items
            $iStmt = $db->prepare("SELECT * FROM ra_bill_items WHERE ra_bill_id=? ORDER BY line_number");
            $iStmt->execute([$safeId]);
            $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get PO details from PostgreSQL
            $pg = $this->pgConnect();
            $pStmt = $pg->prepare("
                SELECT po.*, c.name AS customer_name, c.billing_address_line1, c.billing_city,
                       co.name AS company_name, co.company_prefix, co.address AS company_address,
                       co.gst_number AS company_gstin, co.logo AS company_logo
                FROM finance_purchase_orders po
                LEFT JOIN finance_customer c ON c.id = po.customer_id
                LEFT JOIN authentication_company co ON co.id = po.company_id
                WHERE po.id = ?
            ");
            $pStmt->execute([$ra['po_id']]);
            $po = $pStmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        
        include __DIR__ . '/../../views/finance/measurement_sheet_print_enhanced.php';
    }

    public function measurementSheetPreview($po_id = null) {
        ModuleMiddleware::requireModule('finance');
        
        // This method will generate a preview of the measurement sheet
        // without saving it to the database
        
        $preview_data = [
            'po_id' => $po_id,
            'preview_mode' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Return JSON response for AJAX preview
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'preview_url' => "/ergon/finance/measurement-sheet/{$po_id}/preview-render",
            'data' => $preview_data
        ]);
    }

    public function exportMeasurementSheet($id = null) {
        ModuleMiddleware::requireModule('finance');
        
        // Export measurement sheet to PDF/Excel
        try {
            $safeId = (int)$id;
            
            // Get RA bill data (reuse existing logic)
            // Generate PDF using a library like TCPDF or mPDF
            // Or export to Excel using PhpSpreadsheet
            
            $filename = "RA_Bill_{$safeId}_" . date('Y-m-d') . ".pdf";
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            // Generate and output PDF content
            echo "PDF content would be generated here";
            
        } catch (Exception $e) {
            header('Location: /ergon/finance/measurement-sheet?error=export_failed');
            exit;
        }
    }

    public function getMeasurementSheetStats() {
        ModuleMiddleware::requireModule('finance');
        require_once __DIR__ . '/../config/database.php';
        
        try {
            $db = Database::connect();
            
            // Get comprehensive stats
            $stats = [];
            
            // Total RA bills
            $stmt = $db->query("SELECT COUNT(*) as total_ra_bills FROM ra_bills WHERE status != 'cancelled'");
            $stats['total_ra_bills'] = $stmt->fetchColumn();
            
            // Total claimed amount
            $stmt = $db->query("SELECT SUM(total_claimed) as total_claimed FROM ra_bills WHERE status != 'cancelled'");
            $stats['total_claimed'] = floatval($stmt->fetchColumn());
            
            // Bills by status
            $stmt = $db->query("
                SELECT status, COUNT(*) as count 
                FROM ra_bills 
                GROUP BY status
            ");
            $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Recent activity
            $stmt = $db->query("
                SELECT ra_bill_number, total_claimed, created_at 
                FROM ra_bills 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stats['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($stats);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}

// Add these routes to your routes.php:
/*
$router->get('/finance/measurement-sheet-enhanced', 'FinanceController', 'measurementSheetEnhanced');
$router->get('/finance/measurement-sheet/{po_id}/create-enhanced', 'FinanceController', 'measurementSheetCreateEnhanced');
$router->post('/finance/measurement-sheet/{po_id}/store-enhanced', 'FinanceController', 'measurementSheetStoreEnhanced');
$router->get('/finance/measurement-sheet/{id}/print-enhanced', 'FinanceController', 'measurementSheetPrintEnhanced');
$router->get('/finance/measurement-sheet/{po_id}/preview', 'FinanceController', 'measurementSheetPreview');
$router->get('/finance/measurement-sheet/{id}/export', 'FinanceController', 'exportMeasurementSheet');
$router->get('/api/measurement-sheet/stats', 'FinanceController', 'getMeasurementSheetStats');
*/