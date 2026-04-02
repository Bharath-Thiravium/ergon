<?php
require_once __DIR__ . '/../core/Controller.php';

class LedgerController extends Controller {

    // ── Shared helpers ────────────────────────────────────────────────────────

    private function readFilters() {
        return [
            'fromDate'        => !empty($_GET['from_date']) ? $_GET['from_date'] : null,
            'toDate'          => !empty($_GET['to_date'])   ? $_GET['to_date']   : null,
            'transactionType' => (!empty($_GET['transaction_type']) && $_GET['transaction_type'] !== 'all')
                                 ? $_GET['transaction_type'] : null,
        ];
    }

    /**
     * Fetch ledger rows for a user with optional date/type filters.
     * Returns rows in chronological ASC order with running balance_after set.
     */
    private function fetchEntries($db, $userId, $isOwner, $fromDate, $toDate, $transactionType) {
        $filterWhere  = '';
        $filterParams = [];
        if ($fromDate) {
            $filterWhere   .= ' AND DATE(t.created_at) >= ?';
            $filterParams[] = $fromDate;
        }
        if ($toDate) {
            $filterWhere   .= ' AND DATE(t.created_at) <= ?';
            $filterParams[] = $toDate;
        }
        if ($transactionType) {
            $filterWhere   .= ' AND t.reference_type = ?';
            $filterParams[] = $transactionType;
        }

        if ($isOwner) {
            $innerSql = "
                SELECT
                    e.id as reference_id,
                    'expense' as reference_type,
                    'debit' as direction,
                    e.amount,
                    e.description,
                    e.category,
                    e.status,
                    COALESCE(e.expense_date, e.created_at) as created_at,
                    pt.name as paid_to_name
                FROM expenses e
                LEFT JOIN users pt ON e.paid_to_user_id = pt.id
                WHERE e.user_id = ? AND e.status = 'paid'
                  AND (e.source_advance_id IS NULL OR e.source_advance_id = 0)
                UNION ALL
                SELECT
                    a.id,
                    'advance',
                    'debit',
                    COALESCE(a.approved_amount, a.amount),
                    CONCAT('Advance paid to ', u.name, ' (', a.type, ')'),
                    a.type,
                    a.status,
                    COALESCE(a.requested_date, a.paid_at, a.created_at),
                    u.name
                FROM advances a
                JOIN users u ON a.user_id = u.id
                WHERE a.status = 'paid'
            ";
            $innerParams = [$userId];
        } else {
            $innerSql = "
                SELECT
                    a.id as reference_id,
                    'advance' as reference_type,
                    'credit' as direction,
                    COALESCE(a.approved_amount, a.amount) as amount,
                    a.reason as description,
                    a.type as category,
                    a.status,
                    COALESCE(a.requested_date, a.paid_at, a.created_at) as created_at,
                    NULL as paid_to_name
                FROM advances a
                WHERE a.user_id = ? AND a.status = 'paid'
                UNION ALL
                SELECT
                    e.id,
                    'expense',
                    'debit',
                    COALESCE(e.approved_amount, e.amount),
                    e.description,
                    e.category,
                    e.status,
                    COALESCE(e.expense_date, e.created_at),
                    NULL
                FROM expenses e
                WHERE e.user_id = ? AND e.status = 'paid'
                  AND (e.source_advance_id IS NULL OR e.source_advance_id = 0)
            ";
            $innerParams = [$userId, $userId];
        }

        $sql  = "SELECT t.* FROM ({$innerSql}) t WHERE 1=1 {$filterWhere} ORDER BY t.created_at ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge($innerParams, $filterParams));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Append paid_to_name into description
        foreach ($rows as &$row) {
            if (!empty($row['paid_to_name'])) {
                $row['description'] .= ' → ' . $row['paid_to_name'];
            }
        }
        unset($row);

        // Compute running balance chronologically
        $running = 0;
        foreach ($rows as &$row) {
            $running += $row['direction'] === 'credit' ? $row['amount'] : -$row['amount'];
            $row['balance_after'] = $running;
        }
        unset($row);

        return ['rows' => $rows, 'running_balance' => $running];
    }

    // ── Public actions ────────────────────────────────────────────────────────

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

            $filters = $this->readFilters();
            $fromDate        = $filters['fromDate'];
            $toDate          = $filters['toDate'];
            $transactionType = $filters['transactionType'];

            $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                header('Location: /ergon/users?error=user_not_found');
                exit;
            }

            $isOwner = in_array($user['role'], ['owner', 'company_owner']);
            $result  = $this->fetchEntries($db, $id, $isOwner, $fromDate, $toDate, $transactionType);

            // Most-recent first for display
            $entries = array_reverse($result['rows']);

            $totalCredits = 0;
            $totalDebits  = 0;
            $expenseCount = 0;
            $advanceCount = 0;
            foreach ($entries as $entry) {
                if ($entry['direction'] === 'credit') {
                    $totalCredits += $entry['amount'];
                    if ($entry['reference_type'] === 'advance') $advanceCount++;
                } else {
                    $totalDebits += $entry['amount'];
                    if ($entry['reference_type'] === 'expense') $expenseCount++;
                }
            }

            $this->view('ledgers/user', [
                'user'            => $user,
                'entries'         => $entries,
                'balance'         => $result['running_balance'],
                'totalCredits'    => $totalCredits,
                'totalDebits'     => $totalDebits,
                'netActivity'     => $totalCredits - $totalDebits,
                'expenseCount'    => $expenseCount,
                'advanceCount'    => $advanceCount,
                'user_id'         => $id,
                'fromDate'        => $fromDate,
                'toDate'          => $toDate,
                'transactionType' => $transactionType,
                'active_page'     => 'ledgers',
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
            header('Location: /ergon/users?error=invalid_user');
            exit;
        }

        try {
            require_once __DIR__ . '/../helpers/LedgerHelper.php';
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();

            $stmt = $db->prepare("SELECT id, name, role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                header('Location: /ergon/users?error=user_not_found');
                exit;
            }

            $filters = $this->readFilters();
            $fromDate        = $filters['fromDate'];
            $toDate          = $filters['toDate'];
            $transactionType = $filters['transactionType'];

            $isOwner = in_array($user['role'], ['owner', 'company_owner']);
            $result  = $this->fetchEntries($db, $id, $isOwner, $fromDate, $toDate, $transactionType);

            // Most-recent first, matching UI order
            $rows = array_reverse($result['rows']);

            // Descriptive filename includes active filters
            $safeName = preg_replace('/[^a-z0-9]/i', '_', $user['name']);
            $parts    = ['ledger', $safeName];
            if ($fromDate)        $parts[] = 'from_' . $fromDate;
            if ($toDate)          $parts[] = 'to_' . $toDate;
            if ($transactionType) $parts[] = $transactionType;
            $parts[]  = date('Y-m-d');
            $filename = implode('_', $parts) . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Date', 'Type', 'Reference', 'Description', 'Category', 'Direction', 'Amount (INR)', 'Balance After', 'Status']);
            foreach ($rows as $row) {
                fputcsv($output, [
                    date('Y-m-d', strtotime($row['created_at'])),
                    ucfirst($row['reference_type']),
                    strtoupper($row['reference_type']) . ' #' . $row['reference_id'],
                    $row['description'] ?? '',
                    $row['category'] ?? '',
                    ucfirst($row['direction']),
                    number_format($row['amount'], 2, '.', ''),
                    number_format($row['balance_after'], 2, '.', ''),
                    ucfirst($row['status'] ?? ''),
                ]);
            }
            fclose($output);
            exit;
        } catch (Exception $e) {
            error_log('Ledger CSV download error: ' . $e->getMessage());
            header('Location: /ergon/ledgers/user/' . $id . '?error=csv_failed');
            exit;
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

            $stmt     = $db->query("SELECT id, name as project_name FROM projects ORDER BY name");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log('Project Ledger - Projects fetched: ' . count($projects));

            $data       = ['projects' => $projects, 'active_page' => 'ledgers'];
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

                $total_credits    = array_sum(array_column(array_filter($entries, fn($e) => $e['entry_type'] === 'credit'), 'amount'));
                $total_debits     = array_sum(array_column(array_filter($entries, fn($e) => $e['entry_type'] === 'debit'), 'amount'));
                $budget           = $project['budget'] ?? 0;
                $budget_remaining = $budget - $total_debits;
                $net_balance_raw  = $total_credits - $total_debits;

                $data['project_id']        = $project_id;
                $data['project_name']      = $project['project_name'] ?? 'Unknown';
                $data['budget']            = $budget;
                $data['entries']           = $entries;
                $data['total_credits']     = $total_credits;
                $data['total_debits']      = $total_debits;
                $data['balance_type']      = $budget_remaining >= 0 ? 'Credit' : 'Debit';
                $data['balance_amount']    = abs($budget_remaining);
                $data['net_balance_type']  = $net_balance_raw >= 0 ? 'Credit' : 'Debit';
                $data['net_balance_amount']= abs($net_balance_raw);
                $data['budget_remaining']  = $budget_remaining;
                $data['utilization']       = $budget > 0 ? ($total_debits / $budget) * 100 : 0;
            } else {
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

                $stmt   = $db->query("SELECT COALESCE(SUM(budget), 0) as total_budget FROM projects");
                $budget = $stmt->fetch(PDO::FETCH_ASSOC)['total_budget'];

                $total_credits    = array_sum(array_column(array_filter($entries, fn($e) => $e['entry_type'] === 'credit'), 'amount'));
                $total_debits     = array_sum(array_column(array_filter($entries, fn($e) => $e['entry_type'] === 'debit'), 'amount'));
                $budget_remaining = $budget - $total_debits;
                $net_balance_raw  = $total_credits - $total_debits;

                $data['project_id']        = null;
                $data['project_name']      = 'All Projects';
                $data['budget']            = $budget;
                $data['entries']           = $entries;
                $data['total_credits']     = $total_credits;
                $data['total_debits']      = $total_debits;
                $data['balance_type']      = $budget_remaining >= 0 ? 'Credit' : 'Debit';
                $data['balance_amount']    = abs($budget_remaining);
                $data['net_balance_type']  = $net_balance_raw >= 0 ? 'Credit' : 'Debit';
                $data['net_balance_amount']= abs($net_balance_raw);
                $data['budget_remaining']  = $budget_remaining;
                $data['utilization']       = $budget > 0 ? ($total_debits / $budget) * 100 : 0;
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
