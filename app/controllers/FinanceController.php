<?php

require_once __DIR__ . '/../services/DataSyncService.php';
require_once __DIR__ . '/../middlewares/ModuleMiddleware.php';

class FinanceController {
    
    public function dashboard($request = null) {
        ModuleMiddleware::requireModule('finance');
        
        // Render finance dashboard view
        $title = 'Finance Dashboard';
        $active_page = 'finance';
        
        require_once __DIR__ . '/../../views/finance/dashboard.php';
    }
    
    public function syncData($request = null) {
        try {
            $syncService = new DataSyncService();
            $results = $syncService->syncAllTables();
            
            $totalRecords = 0;
            $errors = [];
            
            foreach ($results as $result) {
                $totalRecords += $result['records'];
                if ($result['status'] === 'error') {
                    $errors[] = $result['table'] . ': ' . $result['error'];
                }
            }
            
            if (empty($errors)) {
                return $this->jsonResponse(200, [
                    'success' => true,
                    'message' => 'Data sync completed successfully',
                    'records_synced' => $totalRecords,
                    'tables' => $results
                ]);
            } else {
                return $this->jsonResponse(207, [
                    'success' => false,
                    'message' => 'Data sync completed with errors',
                    'records_synced' => $totalRecords,
                    'errors' => $errors,
                    'tables' => $results
                ]);
            }
            
        } catch (Exception $e) {
            return $this->jsonResponse(500, [
                'success' => false,
                'message' => 'Data sync failed',
                'error' => $e->getMessage()
            ]);
        }
    }
    

    
    private function pgConnect() {
        $envFile = file_exists(__DIR__ . '/../../.env.production')
            ? __DIR__ . '/../../.env.production'
            : __DIR__ . '/../../.env';
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (strpos($line, '=') !== false && $line[0] !== '#') {
                [$k, $v] = explode('=', $line, 2);
                $_ENV[trim($k)] = trim($v);
            }
        }
        $pg = new PDO(
            "pgsql:host=72.60.218.167;port=5432;dbname=modernsap;connect_timeout=10",
            'postgres', $_ENV['SAP_PG_PASS'] ?? 'mango',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 15]
        );
        return $pg;
    }

    public function measurementSheet($request = null) {
        ModuleMiddleware::requireModule('finance');
        require_once __DIR__ . '/../config/database.php';
        $db = Database::connect();
        $purchase_orders = [];
        $error = null;
        try {
            $pg = $this->pgConnect();
            $stmt = $pg->query("
                SELECT po.*, c.name AS customer_name, co.name AS company_name, co.company_prefix,
                       po.invoice_claimed_amount
                FROM finance_purchase_orders po
                LEFT JOIN finance_customer c ON c.id = po.customer_id
                LEFT JOIN authentication_company co ON co.id = po.company_id
                ORDER BY po.po_date DESC, po.id DESC
            ");
            $purchase_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // attach RA bill count + opening balance flag from MySQL
            if (!empty($purchase_orders)) {
                $poIds = array_column($purchase_orders, 'id');
                $placeholders = implode(',', array_fill(0, count($poIds), '?'));
                $raStmt = $db->prepare("SELECT po_id, COUNT(*) as ra_count,
                    MAX(CASE WHEN ra_sequence=0 THEN 1 ELSE 0 END) as has_opening
                    FROM ra_bills WHERE po_id IN ($placeholders) GROUP BY po_id");
                $raStmt->execute($poIds);
                $raData = array_column($raStmt->fetchAll(PDO::FETCH_ASSOC), null, 'po_id');
                foreach ($purchase_orders as &$po) {
                    $po['ra_count']    = $raData[$po['id']]['ra_count']    ?? 0;
                    $po['has_opening'] = (bool)($raData[$po['id']]['has_opening'] ?? false);
                }
                unset($po);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        include __DIR__ . '/../../views/finance/measurement_sheet.php';
    }

    public function measurementSheetOpeningBalance($po_id = null) {
        ModuleMiddleware::requireModule('finance');
        require_once __DIR__ . '/../config/database.php';
        $db = Database::connect();
        $po = null; $items = []; $error = null; $alreadyImported = false;
        try {
            $safeId = (int)$po_id;
            // Check if opening balance already exists
            $chk = $db->prepare("SELECT id FROM ra_bills WHERE po_id=? AND ra_sequence=0 LIMIT 1");
            $chk->execute([$safeId]);
            $alreadyImported = (bool)$chk->fetchColumn();

            $pg = $this->pgConnect();
            $stmt = $pg->prepare("
                SELECT po.*, c.name AS customer_name,
                       co.name AS company_name, co.company_prefix
                FROM finance_purchase_orders po
                LEFT JOIN finance_customer c  ON c.id  = po.customer_id
                LEFT JOIN authentication_company co ON co.id = po.company_id
                WHERE po.id = ?
            ");
            $stmt->execute([$safeId]);
            $po = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$po) throw new Exception('PO not found');

            $iStmt = $pg->prepare("SELECT * FROM finance_purchase_order_items WHERE purchase_order_id=? ORDER BY line_number");
            $iStmt->execute([$safeId]);
            $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        include __DIR__ . '/../../views/finance/measurement_sheet_opening.php';
    }

    public function measurementSheetOpeningBalanceStore($po_id = null) {
        ModuleMiddleware::requireModule('finance');
        require_once __DIR__ . '/../config/database.php';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ergon/finance/measurement-sheet'); exit;
        }
        $db     = Database::connect();
        $safeId = (int)$po_id;
        try {
            // Prevent duplicate opening balance
            $chk = $db->prepare("SELECT id FROM ra_bills WHERE po_id=? AND ra_sequence=0 LIMIT 1");
            $chk->execute([$safeId]);
            if ($chk->fetchColumn()) {
                header("Location: /ergon/finance/measurement-sheet/$safeId/opening-balance?error=duplicate"); exit;
            }

            $lineItems    = $_POST['items'] ?? [];
            $totalClaimed = 0;
            foreach ($lineItems as $li) $totalClaimed += floatval($li['this_amount'] ?? 0);

            // Insert as sequence=0, ra_bill_number=RA-00 (opening balance marker)
            $ins = $db->prepare("INSERT INTO ra_bills
                (po_id, po_number, company_id, customer_id, ra_bill_number, ra_sequence,
                 bill_date, project, contractor, notes, total_claimed, status, created_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,'approved',?)");
            $ins->execute([
                $safeId,
                $_POST['po_number']   ?? '',
                $_POST['company_id']  ?? 0,
                $_POST['customer_id'] ?? 0,
                'RA-00', 0,
                $_POST['bill_date']   ?? date('Y-m-d'),
                $_POST['project']     ?? '',
                $_POST['contractor']  ?? '',
                'Opening balance — imported from previous system',
                $totalClaimed,
                $_SESSION['user_id'] ?? 0,
            ]);
            $raBillId = $db->lastInsertId();

            $iIns = $db->prepare("INSERT INTO ra_bill_items
                (ra_bill_id, po_item_id, line_number, product_name, description, unit,
                 po_quantity, po_unit_price, po_line_total,
                 prev_claimed_qty, prev_claimed_pct, prev_claimed_amount,
                 claim_type, this_qty, this_pct, this_amount,
                 cumulative_qty, cumulative_pct, cumulative_amount)
                VALUES (?,?,?,?,?,?,?,?,?,0,0,0,?,?,?,?,?,?,?)");
            foreach ($lineItems as $li) {
                $thisQty = floatval($li['this_qty']    ?? 0);
                $thisPct = floatval($li['this_pct']    ?? 0);
                $thisAmt = floatval($li['this_amount'] ?? 0);
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
                    $li['claim_type'] ?? 'quantity',
                    $thisQty, $thisPct, $thisAmt,
                    $thisQty, $thisPct, $thisAmt,  // cumulative = this (no prior)
                ]);
            }
            header("Location: /ergon/finance/measurement-sheet?imported=1"); exit;
        } catch (Exception $e) {
            error_log('Opening balance store error: ' . $e->getMessage());
            header("Location: /ergon/finance/measurement-sheet/$safeId/opening-balance?error=1"); exit;
        }
    }

    public function measurementSheetCreate($po_id = null) {
        ModuleMiddleware::requireModule('finance');
        require_once __DIR__ . '/../config/database.php';
        $db  = Database::connect();
        $po  = null; $items = []; $error = null; $nextSeq = 1;
        try {
            $pg   = $this->pgConnect();
            $safeId = (int)$po_id;
            $stmt = $pg->prepare("
                SELECT po.*, c.name AS customer_name, c.billing_address_line1, c.billing_city,
                       co.name AS company_name, co.company_prefix, co.address AS company_address,
                       co.gst_number AS company_gstin
                FROM finance_purchase_orders po
                LEFT JOIN finance_customer c  ON c.id  = po.customer_id
                LEFT JOIN authentication_company co ON co.id = po.company_id
                WHERE po.id = ?
            ");
            $stmt->execute([$safeId]);
            $po = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$po) throw new Exception('PO not found');

            $iStmt = $pg->prepare("
                SELECT * FROM finance_purchase_order_items
                WHERE purchase_order_id = ? ORDER BY line_number
            ");
            $iStmt->execute([$safeId]);
            $pgItems = $iStmt->fetchAll(PDO::FETCH_ASSOC);

            // next RA sequence
            $seqStmt = $db->prepare("SELECT COALESCE(MAX(ra_sequence),0)+1 FROM ra_bills WHERE po_id=?");
            $seqStmt->execute([$safeId]);
            $nextSeq = (int)$seqStmt->fetchColumn();

            // cumulative claimed per item from previous RA bills
            $prevStmt = $db->prepare("
                SELECT rbi.po_item_id,
                       SUM(rbi.this_qty) AS prev_qty,
                       SUM(rbi.this_pct) AS prev_pct,
                       SUM(rbi.this_amount) AS prev_amount
                FROM ra_bill_items rbi
                JOIN ra_bills rb ON rb.id = rbi.ra_bill_id
                WHERE rb.po_id = ?
                GROUP BY rbi.po_item_id
            ");
            $prevStmt->execute([$safeId]);
            $prevMap = array_column($prevStmt->fetchAll(PDO::FETCH_ASSOC), null, 'po_item_id');

            foreach ($pgItems as $item) {
                $prev = $prevMap[$item['id']] ?? [];
                $item['prev_claimed_qty']    = floatval($prev['prev_qty']    ?? 0);
                $item['prev_claimed_pct']    = floatval($prev['prev_pct']    ?? 0);
                $item['prev_claimed_amount'] = floatval($prev['prev_amount'] ?? 0);
                $items[] = $item;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        include __DIR__ . '/../../views/finance/measurement_sheet_create.php';
    }

    public function measurementSheetStore($po_id = null) {
        ModuleMiddleware::requireModule('finance');
        require_once __DIR__ . '/../config/database.php';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ergon/finance/measurement-sheet'); exit;
        }
        $db     = Database::connect();
        $safeId = (int)$po_id;
        try {
            // next sequence
            $seqStmt = $db->prepare("SELECT COALESCE(MAX(ra_sequence),0)+1 FROM ra_bills WHERE po_id=?");
            $seqStmt->execute([$safeId]);
            $seq = (int)$seqStmt->fetchColumn();
            $raNum = 'RA-' . str_pad($seq, 2, '0', STR_PAD_LEFT);

            $lineItems = $_POST['items'] ?? [];
            $totalClaimed = 0;
            foreach ($lineItems as $li) {
                $totalClaimed += floatval($li['this_amount'] ?? 0);
            }

            $ins = $db->prepare("INSERT INTO ra_bills
                (po_id, po_number, company_id, customer_id, ra_bill_number, ra_sequence,
                 bill_date, project, contractor, notes, total_claimed, status, created_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,'draft',?)");
            $ins->execute([
                $safeId,
                $_POST['po_number']   ?? '',
                $_POST['company_id']  ?? 0,
                $_POST['customer_id'] ?? 0,
                $raNum, $seq,
                $_POST['bill_date']   ?? date('Y-m-d'),
                $_POST['project']     ?? '',
                $_POST['contractor']  ?? '',
                $_POST['notes']       ?? '',
                $totalClaimed,
                $_SESSION['user_id']  ?? 0,
            ]);
            $raBillId = $db->lastInsertId();

            $iIns = $db->prepare("INSERT INTO ra_bill_items
                (ra_bill_id, po_item_id, line_number, product_name, description, unit,
                 po_quantity, po_unit_price, po_line_total,
                 prev_claimed_qty, prev_claimed_pct, prev_claimed_amount,
                 claim_type, this_qty, this_pct, this_amount,
                 cumulative_qty, cumulative_pct, cumulative_amount)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            foreach ($lineItems as $li) {
                $thisQty    = floatval($li['this_qty']    ?? 0);
                $thisPct    = floatval($li['this_pct']    ?? 0);
                $thisAmt    = floatval($li['this_amount'] ?? 0);
                $prevQty    = floatval($li['prev_claimed_qty']    ?? 0);
                $prevPct    = floatval($li['prev_claimed_pct']    ?? 0);
                $prevAmt    = floatval($li['prev_claimed_amount'] ?? 0);
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
            header("Location: /ergon/finance/measurement-sheet/{$raBillId}/print"); exit;
        } catch (Exception $e) {
            error_log('RA bill store error: ' . $e->getMessage());
            header('Location: /ergon/finance/measurement-sheet/' . $safeId . '/create?error=1'); exit;
        }
    }

    public function measurementSheetPrint($id = null) {
        ModuleMiddleware::requireModule('finance');
        require_once __DIR__ . '/../config/database.php';
        $db    = Database::connect();
        $ra    = null; $items = []; $po = null; $error = null;
        try {
            $safeId = (int)$id;
            $raStmt = $db->prepare("SELECT * FROM ra_bills WHERE id=? LIMIT 1");
            $raStmt->execute([$safeId]);
            $ra = $raStmt->fetch(PDO::FETCH_ASSOC);
            if (!$ra) throw new Exception('RA Bill not found');

            $iStmt = $db->prepare("SELECT * FROM ra_bill_items WHERE ra_bill_id=? ORDER BY line_number");
            $iStmt->execute([$safeId]);
            $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

            // fetch PO + company + customer from PG
            $pg   = $this->pgConnect();
            $pStmt = $pg->prepare("
                SELECT po.*, c.name AS customer_name, c.billing_address_line1, c.billing_city,
                       co.name AS company_name, co.company_prefix, co.address AS company_address,
                       co.gst_number AS company_gstin, co.logo AS company_logo
                FROM finance_purchase_orders po
                LEFT JOIN finance_customer c  ON c.id  = po.customer_id
                LEFT JOIN authentication_company co ON co.id = po.company_id
                WHERE po.id = ?
            ");
            $pStmt->execute([$ra['po_id']]);
            $po = $pStmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        include __DIR__ . '/../../views/finance/measurement_sheet_print.php';
    }

    private function jsonResponse($statusCode, $data) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
