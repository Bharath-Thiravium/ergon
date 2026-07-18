<?php
require_once __DIR__ . '/../core/Controller.php';

class LedgerController extends Controller {

    private function readFilterParams(): array {
        $fromDate        = !empty($_GET['from_date'])        ? $_GET['from_date']        : null;
        $toDate          = !empty($_GET['to_date'])          ? $_GET['to_date']          : null;
        $transactionType = !empty($_GET['transaction_type']) ? $_GET['transaction_type'] : null;

        if ($fromDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) $fromDate = null;
        if ($toDate   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate))   $toDate   = null;
        if ($transactionType && !in_array($transactionType, ['advance', 'expense', 'manual'])) $transactionType = null;

        return [$fromDate, $toDate, $transactionType];
    }

    private function fetchLedgerEntries(PDO $db, int $id, ?string $fromDate, ?string $toDate, ?string $transactionType): array {
        $roleStmt = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $roleStmt->execute([$id]);
        $role    = $roleStmt->fetchColumn();
        $isOwner = in_array($role, ['owner', 'company_owner']);

        $advWhere   = ['a.user_id = ?', "a.status IN ('approved','paid')"];
        $expWhere   = ['e.user_id = ?', "e.status IN ('approved','paid')"];
        $reimbWhere = ['e.user_id = ?', "e.status = 'paid'"];
        $advParams   = [$id];
        $expParams   = [$id];
        $reimbParams = [$id];
        $manWhere  = ['ul.user_id = ?', "ul.reference_type = 'manual'"];
        $manParams = [$id];

        if ($fromDate) {
            $advWhere[]   = 'COALESCE(a.requested_date, a.approved_at, a.created_at) >= ?';
            $expWhere[]   = 'COALESCE(e.expense_date, e.approved_at, e.created_at) >= ?';
            $reimbWhere[] = 'COALESCE(e.paid_at, e.approved_at, e.created_at) >= ?';
            $manWhere[]   = 'ul.created_at >= ?';
            $advParams[]   = $fromDate . ' 00:00:00';
            $expParams[]   = $fromDate . ' 00:00:00';
            $reimbParams[] = $fromDate . ' 00:00:00';
            $manParams[]   = $fromDate . ' 00:00:00';
        }
        if ($toDate) {
            $advWhere[]   = 'COALESCE(a.requested_date, a.approved_at, a.created_at) <= ?';
            $expWhere[]   = 'COALESCE(e.expense_date, e.approved_at, e.created_at) <= ?';
            $reimbWhere[] = 'COALESCE(e.paid_at, e.approved_at, e.created_at) <= ?';
            $manWhere[]   = 'ul.created_at <= ?';
            $advParams[]   = $toDate . ' 23:59:59';
            $expParams[]   = $toDate . ' 23:59:59';
            $reimbParams[] = $toDate . ' 23:59:59';
            $manParams[]   = $toDate . ' 23:59:59';
        }

        $parts  = [];
        $params = [];

        if ($transactionType !== 'expense' && $transactionType !== 'manual') {
            $parts[] = "
                SELECT
                    a.id              AS reference_id,
                    'advance'         AS reference_type,
                    'advance_payment' AS entry_type,
                    'credit'          AS direction,
                    COALESCE(a.approved_amount, a.amount)     AS amount,
                    COALESCE(a.reason, 'Advance')             AS description,
                    COALESCE(a.type, 'advance')               AS category,
                    a.status,
                    COALESCE(a.requested_date, a.approved_at, a.created_at) AS date
                FROM advances a
                WHERE " . implode(' AND ', $advWhere);
            $params = array_merge($params, $advParams);
        }

        if ($transactionType !== 'advance' && $transactionType !== 'manual') {
            // Debit: expense incurred (approved or paid)
            $parts[] = "
                SELECT
                    e.id              AS reference_id,
                    'expense'         AS reference_type,
                    'expense_payment' AS entry_type,
                    'debit'           AS direction,
                    COALESCE(e.approved_amount, e.amount)     AS amount,
                    COALESCE(e.description, 'Expense')        AS description,
                    COALESCE(e.category, 'expense')           AS category,
                    e.status,
                    COALESCE(e.expense_date, e.approved_at, e.created_at) AS date
                FROM expenses e
                WHERE " . implode(' AND ', $expWhere);
            $params = array_merge($params, $expParams);

            // Credit: reimbursement paid back to employee (only for non-owner users).
            // Owners disburse cash — the reimbursement credit is a company-internal mirror
            // entry that makes no sense in the owner's own ledger view.
            if (!$isOwner) {
                $parts[] = "
                    SELECT
                        e.id                    AS reference_id,
                        'expense'               AS reference_type,
                        'expense_reimbursement' AS entry_type,
                        'credit'                AS direction,
                        COALESCE(e.approved_amount, e.amount)                        AS amount,
                        CONCAT('Reimbursed: ', COALESCE(e.description, 'Expense'))   AS description,
                        COALESCE(e.category, 'expense')                              AS category,
                        e.status,
                        COALESCE(e.paid_at, e.approved_at, e.created_at)            AS date
                    FROM expenses e
                    WHERE " . implode(' AND ', $reimbWhere);
                $params = array_merge($params, $reimbParams);
            }
        }

        if ($transactionType !== 'advance' && $transactionType !== 'expense') {
            $parts[] = "
                SELECT
                    ul.id         AS reference_id,
                    'manual'      AS reference_type,
                    ul.entry_type AS entry_type,
                    ul.direction  AS direction,
                    ul.amount     AS amount,
                    NULL          AS description,
                    NULL          AS category,
                    'manual'      AS status,
                    ul.created_at AS date
                FROM user_ledgers ul
                WHERE " . implode(' AND ', $manWhere);
            $params = array_merge($params, $manParams);
        }

        if (empty($parts)) return [];

        $sql  = implode(' UNION ALL ', $parts)
              . ' ORDER BY date DESC, reference_type ASC, reference_id ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Reverse to chronological order so running balance accumulates correctly, then re-reverse for display.
        $rows    = array_reverse($rows);
        $running = 0.0;
        foreach ($rows as &$row) {
            $running += $row['direction'] === 'credit'
                ? floatval($row['amount'])
                : -floatval($row['amount']);
            $row['balance_after'] = $running;
        }
        unset($row);
        return array_reverse($rows);
    }

    public function userLedger($id = null) {
        $this->requireAuth();
        if (!$id || !is_numeric($id)) {
            header('Location: /ergon/users?error=invalid_user');
            exit;
        }

        try {
            require_once __DIR__ . '/../helpers/LedgerHelper.php';
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            LedgerHelper::ensureTable($db);

            [$fromDate, $toDate, $transactionType] = $this->readFilterParams();

            $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                header('Location: /ergon/users?error=user_not_found');
                exit;
            }

            $rawEntries = $this->fetchLedgerEntries($db, (int)$id, $fromDate, $toDate, $transactionType);

            // Rows are newest-first; first element carries the most recent running balance.
            $currentBalance = empty($rawEntries) ? 0.0 : floatval($rawEntries[0]['balance_after']);

            // Opening balance for the filtered period (before any entries in the range)
            $openingBalance = 0.0;
            if ($fromDate || $toDate) {
                $isOwnerUser = in_array($user['role'] ?? '', ['owner', 'company_owner']);
                $reimbSql = $isOwnerUser ? '' : "
                        UNION ALL

                        SELECT 'credit' AS direction, COALESCE(approved_amount, amount) AS amount
                        FROM expenses
                        WHERE user_id = ? AND status = 'paid'
                          AND COALESCE(paid_at, approved_at, created_at) < ?
                ";

                $openStmt = $db->prepare("
                    SELECT COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE 0 END) -
                                   SUM(CASE WHEN direction='debit' THEN amount ELSE 0 END), 0)
                    FROM (
                        SELECT 'credit' AS direction, COALESCE(approved_amount, amount) AS amount
                        FROM advances
                        WHERE user_id = ? AND status IN ('approved','paid')
                          AND COALESCE(requested_date, approved_at, created_at) < ?

                        UNION ALL

                        SELECT 'debit' AS direction, COALESCE(approved_amount, amount) AS amount
                        FROM expenses
                        WHERE user_id = ? AND status IN ('approved','paid')
                          AND COALESCE(expense_date, approved_at, created_at) < ?

                        {$reimbSql}

                        UNION ALL

                        SELECT direction, amount FROM user_ledgers
                        WHERE user_id = ? AND created_at < ?
                    ) t
                ");
                $filterStartDate = ($fromDate ?? '1900-01-01') . ' 00:00:00';
                $openParams = [$id, $filterStartDate, $id, $filterStartDate];
                if (!$isOwnerUser) $openParams = array_merge($openParams, [$id, $filterStartDate]);
                $openParams = array_merge($openParams, [$id, $filterStartDate]);
                $openStmt->execute($openParams);
                $openingBalance = floatval($openStmt->fetchColumn());
            }

            $totalCredits       = 0.0;
            $totalDebits        = 0.0;
            $expenseCount       = 0;
            $advanceCount       = 0;
            $reimbursementCount = 0;
            $manualCount        = 0;
            foreach ($rawEntries as $entry) {
                if ($entry['direction'] === 'credit') {
                    $totalCredits += floatval($entry['amount']);
                    if ($entry['reference_type'] === 'advance')            $advanceCount++;
                    if ($entry['entry_type']     === 'expense_reimbursement') $reimbursementCount++;
                    if ($entry['reference_type'] === 'manual')             $manualCount++;
                } else {
                    $totalDebits += floatval($entry['amount']);
                    if ($entry['reference_type'] === 'expense') $expenseCount++;
                    if ($entry['reference_type'] === 'manual')  $manualCount++;
                }
            }

            // Outstanding = advances given minus expenses incurred (all time, no double-counting)
            $outStmt = $db->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN src='advance' THEN amount ELSE 0 END), 0) AS advances_given,
                    COALESCE(SUM(CASE WHEN src='expense' THEN amount ELSE 0 END), 0) AS expenses_incurred
                FROM (
                    SELECT 'advance' AS src, COALESCE(approved_amount, amount) AS amount
                    FROM advances
                    WHERE user_id = ? AND status IN ('approved','paid')

                    UNION ALL

                    SELECT 'expense' AS src, COALESCE(approved_amount, amount) AS amount
                    FROM expenses
                    WHERE user_id = ? AND status IN ('approved','paid')
                ) t
            ");
            $outStmt->execute([$id, $id]);
            $outRow          = $outStmt->fetch(PDO::FETCH_ASSOC);
            // Positive = employee still has company money (advance > expenses)
            // Negative = company owes employee (expenses > advances)
            $trueOutstanding = floatval($outRow['advances_given']) - floatval($outRow['expenses_incurred']);

            $this->view('ledgers/user', [
                'user'               => $user,
                'entries'            => $rawEntries,
                'balance'            => $currentBalance,
                'openingBalance'     => $openingBalance,
                'outstanding'        => $trueOutstanding,
                'totalCredits'       => $totalCredits,
                'totalDebits'        => $totalDebits,
                'netActivity'        => $totalCredits - $totalDebits,
                'expenseCount'       => $expenseCount,
                'advanceCount'       => $advanceCount,
                'reimbursementCount' => $reimbursementCount,
                'manualCount'        => $manualCount,
                'user_id'            => $id,
                'fromDate'           => $fromDate,
                'toDate'             => $toDate,
                'transactionType'    => $transactionType,
                'isFiltered'         => ($fromDate || $toDate || $transactionType),
                'active_page'        => 'ledgers',
            ]);
        } catch (Exception $e) {
            error_log('Ledger view error: ' . $e->getMessage());
            header('Location: /ergon/users?error=ledger_failed');
            exit;
        }
    }

    public function downloadCsv($id = null) {
        $this->requireAuth();
        if (!$id || !is_numeric($id)) {
            http_response_code(400);
            exit('Invalid user');
        }

        try {
            require_once __DIR__ . '/../helpers/LedgerHelper.php';
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();

            $stmt = $db->prepare("SELECT id, name, role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) { http_response_code(404); exit('User not found'); }

            [$fromDate, $toDate, $transactionType] = $this->readFilterParams();

            $rows = $this->fetchLedgerEntries($db, (int)$id, $fromDate, $toDate, $transactionType);

            $nameParts = ['ledger', 'user', $id];
            if ($fromDate)        $nameParts[] = 'from_' . $fromDate;
            if ($toDate)          $nameParts[] = 'to_' . $toDate;
            if ($transactionType) $nameParts[] = $transactionType;
            $filename = implode('_', $nameParts) . '.csv';

            $safe = fn($v) => $v ?? '';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Type', 'Entry Type', 'Direction', 'Amount', 'Description', 'Category', 'Status', 'Balance']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $safe($row['date']),
                    $safe($row['reference_type']),
                    $safe($row['entry_type']),
                    $safe($row['direction']),
                    number_format($row['amount'], 2, '.', ''),
                    $safe($row['description']),
                    $safe($row['category']),
                    $safe($row['status']),
                    number_format($row['balance_after'], 2, '.', ''),
                ]);
            }
            fclose($out);
            exit;
        } catch (Exception $e) {
            error_log('Ledger CSV error: ' . $e->getMessage());
            http_response_code(500);
            exit('Export failed');
        }
    }

    public function createManualAdjustment($id = null) {
        $this->requireAuth();
        if (!$id || !is_numeric($id)) {
            http_response_code(400);
            $this->json(['success' => false, 'error' => 'Invalid user']);
            return;
        }

        // Only owners/admins can create adjustments
        if (!in_array($_SESSION['role'] ?? '', ['owner', 'company_owner', 'admin'])) {
            http_response_code(403);
            $this->json(['success' => false, 'error' => 'Access denied']);
            return;
        }

        if (!$this->isPost()) {
            http_response_code(400);
            $this->json(['success' => false, 'error' => 'POST required']);
            return;
        }

        try {
            require_once __DIR__ . '/../helpers/LedgerHelper.php';
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            LedgerHelper::ensureTable($db);

            $amount    = floatval($_POST['amount'] ?? 0);
            $direction = $_POST['direction'] ?? 'credit';
            $reason    = trim($_POST['reason'] ?? 'Manual adjustment');

            if ($amount <= 0) {
                $this->json(['success' => false, 'error' => 'Amount must be positive']);
                return;
            }

            if (!in_array($direction, ['credit', 'debit'])) {
                $this->json(['success' => false, 'error' => 'Invalid direction']);
                return;
            }

            $userId = intval($_SESSION['user_id']);
            $result = LedgerHelper::recordManualAdjustment(
                $id,
                $amount,
                $direction,
                $reason,
                'adjustment',
                $db,
                $userId
            );

            if ($result) {
                $this->json(['success' => true, 'message' => 'Adjustment recorded']);
            } else {
                $this->json(['success' => false, 'error' => 'Failed to record adjustment'], 500);
            }
        } catch (Exception $e) {
            error_log('Ledger adjustment error: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function reverseEntry($entryId = null) {
        $this->requireAuth();
        if (!$entryId || !is_numeric($entryId)) {
            http_response_code(400);
            $this->json(['success' => false, 'error' => 'Invalid entry']);
            return;
        }

        // Only owners/admins can reverse entries
        if (!in_array($_SESSION['role'] ?? '', ['owner', 'company_owner', 'admin'])) {
            http_response_code(403);
            $this->json(['success' => false, 'error' => 'Access denied']);
            return;
        }

        if (!$this->isPost()) {
            http_response_code(400);
            $this->json(['success' => false, 'error' => 'POST required']);
            return;
        }

        try {
            require_once __DIR__ . '/../helpers/LedgerHelper.php';
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();

            $userId = intval($_SESSION['user_id']);
            $result = LedgerHelper::reverseEntry($entryId, 'Reversed by admin', $db, $userId);

            if ($result) {
                $this->json(['success' => true, 'message' => 'Entry reversed']);
            } else {
                $this->json(['success' => false, 'error' => 'Failed to reverse entry'], 500);
            }
        } catch (Exception $e) {
            error_log('Ledger reversal error: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function projectLedger() {
        $this->requireAuth();

        if (!in_array($_SESSION['role'] ?? '', ['owner', 'company_owner', 'admin'])) {
            header('Location: /ergon/dashboard');
            exit;
        }

        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();

            $stmt     = $db->query("SELECT id, name as project_name FROM projects ORDER BY name");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data       = ['projects' => $projects, 'active_page' => 'ledgers'];
            $project_id = isset($_GET['project_id']) && is_numeric($_GET['project_id']) ? $_GET['project_id'] : null;

            if ($project_id) {
                $stmt = $db->prepare("SELECT name as project_name, budget FROM projects WHERE id = ?");
                $stmt->execute([$project_id]);
                $project = $stmt->fetch(PDO::FETCH_ASSOC);

                $stmt = $db->prepare("
                    SELECT 'expense' AS type, 'debit' AS entry_type,
                           e.id, e.user_id, u.name AS user_name, e.description,
                           COALESCE(ae.approved_amount, e.amount) AS amount,
                           e.status, e.created_at, e.paid_at
                    FROM expenses e
                    JOIN users u ON e.user_id = u.id
                    LEFT JOIN approved_expenses ae ON e.id = ae.expense_id
                    WHERE e.project_id = ? AND e.status = 'paid'
                    UNION ALL
                    SELECT 'advance' AS type, 'debit' AS entry_type,
                           a.id, a.user_id, u.name AS user_name, a.reason AS description,
                           COALESCE(a.approved_amount, a.amount) AS amount,
                           a.status, a.created_at, a.paid_at
                    FROM advances a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.project_id = ? AND a.status = 'paid'
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$project_id, $project_id]);
                $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $total_debits     = array_sum(array_column($entries, 'amount'));
                $total_credits    = 0.0;
                $budget           = floatval($project['budget'] ?? 0);
                $budget_remaining = $budget - $total_debits;

                $data = array_merge($data, [
                    'project_id'         => $project_id,
                    'project_name'       => $project['project_name'] ?? 'Unknown',
                    'budget'             => $budget,
                    'entries'            => $entries,
                    'total_credits'      => $total_credits,
                    'total_debits'       => $total_debits,
                    'balance_type'       => $budget_remaining >= 0 ? 'Credit' : 'Debit',
                    'balance_amount'     => abs($budget_remaining),
                    'net_balance_type'   => $total_debits > 0 ? 'Debit' : 'Credit',
                    'net_balance_amount' => $total_debits,
                    'budget_remaining'   => $budget_remaining,
                    'utilization'        => $budget > 0 ? ($total_debits / $budget) * 100 : 0,
                ]);
            } else {
                $stmt = $db->query("
                    SELECT 'expense' AS type, 'debit' AS entry_type,
                           e.id, e.user_id, u.name AS user_name, e.description,
                           COALESCE(ae.approved_amount, e.amount) AS amount,
                           e.status, e.created_at, p.name AS project_name, e.paid_at
                    FROM expenses e
                    JOIN users u ON e.user_id = u.id
                    LEFT JOIN projects p ON e.project_id = p.id
                    LEFT JOIN approved_expenses ae ON e.id = ae.expense_id
                    WHERE e.status = 'paid'
                    UNION ALL
                    SELECT 'advance' AS type, 'debit' AS entry_type,
                           a.id, a.user_id, u.name AS user_name, a.reason AS description,
                           COALESCE(a.approved_amount, a.amount) AS amount,
                           a.status, a.created_at, p.name AS project_name, a.paid_at
                    FROM advances a
                    JOIN users u ON a.user_id = u.id
                    LEFT JOIN projects p ON a.project_id = p.id
                    WHERE a.status = 'paid'
                    ORDER BY created_at DESC
                ");
                $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $budget       = floatval($db->query("SELECT COALESCE(SUM(budget), 0) FROM projects")->fetchColumn());
                $total_debits = array_sum(array_column($entries, 'amount'));
                $budget_remaining = $budget - $total_debits;

                $data = array_merge($data, [
                    'project_id'         => null,
                    'project_name'       => 'All Projects',
                    'budget'             => $budget,
                    'entries'            => $entries,
                    'total_credits'      => 0.0,
                    'total_debits'       => $total_debits,
                    'balance_type'       => $budget_remaining >= 0 ? 'Credit' : 'Debit',
                    'balance_amount'     => abs($budget_remaining),
                    'net_balance_type'   => $total_debits > 0 ? 'Debit' : 'Credit',
                    'net_balance_amount' => $total_debits,
                    'budget_remaining'   => $budget_remaining,
                    'utilization'        => $budget > 0 ? ($total_debits / $budget) * 100 : 0,
                ]);
            }

            $this->view('ledgers/project', $data);
        } catch (Exception $e) {
            error_log('Project ledger error: ' . $e->getMessage());
            header('Location: /ergon/dashboard?error=ledger_failed');
            exit;
        }
    }
}
