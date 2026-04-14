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
        // Resolve role so owner queries include paid_by scope.
        $roleStmt = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $roleStmt->execute([$id]);
        $role = $roleStmt->fetchColumn();
        $isOwner = in_array($role, ['owner', 'company_owner']);

        error_log("Ledger Fetch User ID: $id role: $role isOwner: " . ($isOwner ? 'yes' : 'no'));

        // For company_owner: include records where they are the recipient (user_id)
        // OR the payer (paid_by) — owners disburse advances/expenses to employees.
        // For regular users: filter by user_id only.
        if ($isOwner) {
            $advWhere  = ['(a.user_id = ? OR a.paid_by = ?)', "a.status IN ('approved','paid')"];
            $expWhere  = ['(e.user_id = ? OR e.paid_by = ?)', "e.status IN ('approved','paid')",
                          '(e.source_advance_id IS NULL OR e.source_advance_id = 0)'];
            $advParams = [$id, $id];
            $expParams = [$id, $id];
        } else {
            $advWhere  = ['a.user_id = ?', "a.status IN ('approved','paid')"];
            $expWhere  = ['e.user_id = ?', "e.status IN ('approved','paid')",
                          '(e.source_advance_id IS NULL OR e.source_advance_id = 0)'];
            $advParams = [$id];
            $expParams = [$id];
        }
        $manWhere  = ['ul.user_id = ?', "ul.reference_type = 'manual'"];
        $manParams = [$id];

        if ($fromDate) {
            $advWhere[]  = 'COALESCE(a.approved_at, a.requested_date, a.created_at) >= ?';
            $expWhere[]  = 'COALESCE(e.approved_at, e.expense_date, e.created_at) >= ?';
            $manWhere[]  = 'ul.created_at >= ?';
            $advParams[] = $fromDate . ' 00:00:00';
            $expParams[] = $fromDate . ' 00:00:00';
            $manParams[] = $fromDate . ' 00:00:00';
        }
        if ($toDate) {
            $advWhere[]  = 'COALESCE(a.approved_at, a.requested_date, a.created_at) <= ?';
            $expWhere[]  = 'COALESCE(e.approved_at, e.expense_date, e.created_at) <= ?';
            $manWhere[]  = 'ul.created_at <= ?';
            $advParams[] = $toDate . ' 23:59:59';
            $expParams[] = $toDate . ' 23:59:59';
            $manParams[] = $toDate . ' 23:59:59';
        }

        $parts  = [];
        $params = [];

        if ($transactionType !== 'expense' && $transactionType !== 'manual') {
            $parts[]  = "
                SELECT
                    a.id              AS reference_id,
                    'advance'         AS reference_type,
                    'advance_payment' AS entry_type,
                    'credit'          AS direction,
                    COALESCE(a.approved_amount, a.amount)                    AS amount,
                    COALESCE(a.reason, 'Advance')                            AS description,
                    COALESCE(a.type, 'advance')                              AS category,
                    a.status,
                    COALESCE(a.approved_at, a.requested_date, a.created_at) AS created_at
                FROM advances a
                WHERE " . implode(' AND ', $advWhere);
            $params = array_merge($params, $advParams);
        }

        if ($transactionType !== 'advance' && $transactionType !== 'manual') {
            $parts[]  = "
                SELECT
                    e.id              AS reference_id,
                    'expense'         AS reference_type,
                    'expense_payment' AS entry_type,
                    'credit'          AS direction,
                    COALESCE(e.approved_amount, e.amount)                         AS amount,
                    COALESCE(e.description, 'Expense')                            AS description,
                    COALESCE(e.category, 'expense')                               AS category,
                    e.status,
                    COALESCE(e.approved_at, e.expense_date, e.created_at)         AS created_at
                FROM expenses e
                WHERE " . implode(' AND ', $expWhere);
            $params = array_merge($params, $expParams);
        }

        if ($transactionType !== 'advance' && $transactionType !== 'expense') {
            $parts[]  = "
                SELECT
                    ul.id         AS reference_id,
                    'manual'      AS reference_type,
                    ul.entry_type AS entry_type,
                    ul.direction  AS direction,
                    ul.amount     AS amount,
                    NULL          AS description,
                    NULL          AS category,
                    'manual'      AS status,
                    ul.created_at AS created_at
                FROM user_ledgers ul
                WHERE " . implode(' AND ', $manWhere);
            $params = array_merge($params, $manParams);
        }

        if (empty($parts)) return [];

        $sql  = implode(' UNION ALL ', $parts)
              . ' ORDER BY created_at ASC, reference_type ASC, reference_id ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $running = 0.0;
        foreach ($rows as &$row) {
            $running += $row['direction'] === 'credit'
                ? floatval($row['amount'])
                : -floatval($row['amount']);
            $row['balance_after'] = $running;
        }
        unset($row);

        return $rows;
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
            LedgerHelper::ensureTable();

            [$fromDate, $toDate, $transactionType] = $this->readFilterParams();

            // Get user details
            $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                header('Location: /ergon/users?error=user_not_found');
                exit;
            }

            $isOwner    = in_array($user['role'], ['owner', 'company_owner']);
            LedgerHelper::ensureTable($db);
            $rawEntries = $this->fetchLedgerEntries($db, (int)$id, $fromDate, $toDate, $transactionType);

            // Current balance = final balance_after from the chronologically-ordered raw entries
            $currentBalance = empty($rawEntries) ? 0.0 : floatval(end($rawEntries)['balance_after']);

            // Reverse for display (most recent first)
            $entries = array_reverse($rawEntries);

            $totalCredits = 0.0;
            $totalDebits  = 0.0;
            $expenseCount = 0;
            $advanceCount = 0;
            $manualCount = 0;
            foreach ($rawEntries as $entry) {
                if ($entry['direction'] === 'credit') {
                    $totalCredits += floatval($entry['amount']);
                    if ($entry['reference_type'] === 'advance') $advanceCount++;
                    if ($entry['reference_type'] === 'expense') $expenseCount++;
                    if ($entry['reference_type'] === 'manual')  $manualCount++;
                } else {
                    $totalDebits += floatval($entry['amount']);
                }
            }

            $data = [
                'user'            => $user,
                'entries'         => $entries,
                'balance'         => $currentBalance,
                'totalCredits'    => $totalCredits,
                'totalDebits'     => $totalDebits,
                'netActivity'     => $totalCredits - $totalDebits,
                'expenseCount'    => $expenseCount,
                'advanceCount'    => $advanceCount,
                'manualCount'     => $manualCount,
                'user_id'         => $id,
                'fromDate'        => $fromDate,
                'toDate'          => $toDate,
                'transactionType' => $transactionType,
                'isFiltered'      => ($fromDate || $toDate || $transactionType),
                'active_page'     => 'ledgers',
            ];

            $this->view('ledgers/user', $data);
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
            $isOwner = in_array($user['role'], ['owner', 'company_owner']);

            $rows = $this->fetchLedgerEntries($db, (int)$id, $fromDate, $toDate, $transactionType);

            // Descriptive filename that reflects active filters
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
            fputcsv($out, ['Date', 'Type', 'Direction', 'Amount', 'Description', 'Category', 'Status', 'Balance']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $safe($row['created_at']),
                    $safe($row['reference_type']),
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

    public function projectLedger() {
        $this->requireAuth();
        
        error_log('Project Ledger - User role: ' . ($_SESSION['role'] ?? 'none'));
        
        if (!in_array($_SESSION['role'] ?? '', ['owner', 'company_owner', 'admin'])) {
            error_log('Project Ledger - Access denied for role: ' . ($_SESSION['role'] ?? 'none'));
            header('Location: /ergon/dashboard');
            exit;
        }

        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            error_log('Project Ledger - Database connected');

            $stmt = $db->query("SELECT id, name as project_name FROM projects ORDER BY name");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log('Project Ledger - Projects fetched: ' . count($projects));

            $data = ['projects' => $projects, 'active_page' => 'ledgers'];

            $project_id = isset($_GET['project_id']) && is_numeric($_GET['project_id']) ? $_GET['project_id'] : null;
            
            if ($project_id) {

                $stmt = $db->prepare("SELECT name as project_name, budget FROM projects WHERE id = ?");
                $stmt->execute([$project_id]);
                $project = $stmt->fetch(PDO::FETCH_ASSOC);

                $stmt = $db->prepare("
                    SELECT 'expense' as type, 'credit' as entry_type, e.id, e.user_id, u.name as user_name, e.description, 
                           COALESCE(ae.approved_amount, e.amount) as amount, e.status, e.created_at
                    FROM expenses e
                    JOIN users u ON e.user_id = u.id
                    LEFT JOIN approved_expenses ae ON e.id = ae.expense_id
                    WHERE e.project_id = ? AND e.status IN ('approved', 'paid')
                    UNION ALL
                    SELECT 'expense' as type, 'debit' as entry_type, e.id, e.user_id, u.name as user_name, e.description, 
                           COALESCE(ae.approved_amount, e.amount) as amount, e.status, e.created_at
                    FROM expenses e
                    JOIN users u ON e.user_id = u.id
                    LEFT JOIN approved_expenses ae ON e.id = ae.expense_id
                    WHERE e.project_id = ? AND e.status = 'paid'
                    UNION ALL
                    SELECT 'advance' as type, 'debit' as entry_type, a.id, a.user_id, u.name as user_name, a.reason as description, 
                           COALESCE(a.approved_amount, a.amount) as amount, a.status, a.created_at
                    FROM advances a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.project_id = ? AND a.status = 'paid'
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$project_id, $project_id, $project_id]);
                $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $total_credits = array_sum(array_column(array_filter($entries, fn($e) => $e['entry_type'] === 'credit'), 'amount'));
                $total_debits = array_sum(array_column(array_filter($entries, fn($e) => $e['entry_type'] === 'debit'), 'amount'));
                $budget = $project['budget'] ?? 0;
                $budget_remaining = $budget - $total_debits;
                
                $balance_type = $budget_remaining >= 0 ? 'Credit' : 'Debit';
                $balance_amount = abs($budget_remaining);
                
                $net_balance_raw = $total_credits - $total_debits;
                $net_balance_type = $net_balance_raw >= 0 ? 'Credit' : 'Debit';
                $net_balance_amount = abs($net_balance_raw);

                $data['project_id'] = $project_id;
                $data['project_name'] = $project['project_name'] ?? 'Unknown';
                $data['budget'] = $budget;
                $data['entries'] = $entries;
                $data['total_credits'] = $total_credits;
                $data['total_debits'] = $total_debits;
                $data['balance_type'] = $balance_type;
                $data['balance_amount'] = $balance_amount;
                $data['net_balance_type'] = $net_balance_type;
                $data['net_balance_amount'] = $net_balance_amount;
                $data['budget_remaining'] = $budget_remaining;
                $data['utilization'] = $budget > 0 ? ($total_debits / $budget) * 100 : 0;
            } else {
                // Show consolidated data for all projects
                $stmt = $db->query("
                    SELECT 'expense' as type, 'credit' as entry_type, e.id, e.user_id, u.name as user_name, e.description, 
                           COALESCE(ae.approved_amount, e.amount) as amount, e.status, e.created_at, p.name as project_name
                    FROM expenses e
                    JOIN users u ON e.user_id = u.id
                    LEFT JOIN projects p ON e.project_id = p.id
                    LEFT JOIN approved_expenses ae ON e.id = ae.expense_id
                    WHERE e.status IN ('approved', 'paid')
                    UNION ALL
                    SELECT 'expense' as type, 'debit' as entry_type, e.id, e.user_id, u.name as user_name, e.description, 
                           COALESCE(ae.approved_amount, e.amount) as amount, e.status, e.created_at, p.name as project_name
                    FROM expenses e
                    JOIN users u ON e.user_id = u.id
                    LEFT JOIN projects p ON e.project_id = p.id
                    LEFT JOIN approved_expenses ae ON e.id = ae.expense_id
                    WHERE e.status = 'paid'
                    UNION ALL
                    SELECT 'advance' as type, 'debit' as entry_type, a.id, a.user_id, u.name as user_name, a.reason as description, 
                           COALESCE(a.approved_amount, a.amount) as amount, a.status, a.created_at, p.name as project_name
                    FROM advances a
                    JOIN users u ON a.user_id = u.id
                    LEFT JOIN projects p ON a.project_id = p.id
                    WHERE a.status = 'paid'
                    ORDER BY created_at DESC
                ");
                $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $stmt = $db->query("SELECT COALESCE(SUM(budget), 0) as total_budget FROM projects");
                $budget = $stmt->fetch(PDO::FETCH_ASSOC)['total_budget'];

                $total_credits = array_sum(array_column(array_filter($entries, fn($e) => $e['entry_type'] === 'credit'), 'amount'));
                $total_debits = array_sum(array_column(array_filter($entries, fn($e) => $e['entry_type'] === 'debit'), 'amount'));
                $budget_remaining = $budget - $total_debits;
                
                $balance_type = $budget_remaining >= 0 ? 'Credit' : 'Debit';
                $balance_amount = abs($budget_remaining);
                
                $net_balance_raw = $total_credits - $total_debits;
                $net_balance_type = $net_balance_raw >= 0 ? 'Credit' : 'Debit';
                $net_balance_amount = abs($net_balance_raw);

                $data['project_id'] = null;
                $data['project_name'] = 'All Projects';
                $data['budget'] = $budget;
                $data['entries'] = $entries;
                $data['total_credits'] = $total_credits;
                $data['total_debits'] = $total_debits;
                $data['balance_type'] = $balance_type;
                $data['balance_amount'] = $balance_amount;
                $data['net_balance_type'] = $net_balance_type;
                $data['net_balance_amount'] = $net_balance_amount;
                $data['budget_remaining'] = $budget_remaining;
                $data['utilization'] = $budget > 0 ? ($total_debits / $budget) * 100 : 0;
            }

            $this->view('ledgers/project', $data);
        } catch (Exception $e) {
            error_log('Project ledger error: ' . $e->getMessage());
            header('Location: /ergon/dashboard?error=ledger_failed');
            exit;
        }
    }
}

?>
