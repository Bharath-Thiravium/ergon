<?php
require_once __DIR__ . '/../core/Controller.php';

class LedgerController extends Controller {
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

            // Get user details
            $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                header('Location: /ergon/users?error=user_not_found');
                exit;
            }

            $isOwner = in_array($user['role'], ['owner', 'company_owner']);

            // Always query directly from source tables — never use user_ledgers as display source.
            // This ensures deletions/edits are instantly reflected with no stale data.
            if ($isOwner) {
                // Owner sees: their own direct expenses + ALL paid advances (owner pays all advances)
                $stmt = $db->prepare("
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
                        a.id as reference_id,
                        'advance' as reference_type,
                        'debit' as direction,
                        COALESCE(a.approved_amount, a.amount) as amount,
                        CONCAT('Advance paid to ', u.name, ' (', a.type, ')') as description,
                        a.type as category,
                        a.status,
                        COALESCE(a.requested_date, a.paid_at, a.created_at) as created_at,
                        u.name as paid_to_name
                    FROM advances a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.status = 'paid'
                    ORDER BY created_at ASC
                ");
                $stmt->execute([$id]);
            } else {
                // Regular user sees: advances credited to them + expenses debited from them
                $stmt = $db->prepare("
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
                        e.id as reference_id,
                        'expense' as reference_type,
                        'debit' as direction,
                        COALESCE(e.approved_amount, e.amount) as amount,
                        e.description,
                        e.category,
                        e.status,
                        COALESCE(e.expense_date, e.created_at) as created_at,
                        NULL as paid_to_name
                    FROM expenses e
                    WHERE e.user_id = ? AND e.status = 'paid'
                      AND (e.source_advance_id IS NULL OR e.source_advance_id = 0)
                    ORDER BY created_at ASC
                ");
                $stmt->execute([$id, $id]);
            }
            $rawEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Append paid_to_name to description for owner's advance-payment expenses
            foreach ($rawEntries as &$entry) {
                if (!empty($entry['paid_to_name'])) {
                    $entry['description'] .= ' → ' . $entry['paid_to_name'];
                }
            }
            unset($entry);

            // Recalculate running balance in chronological order
            $runningBalance = 0;
            $entries = [];
            
            foreach ($rawEntries as $entry) {
                if ($entry['direction'] === 'credit') {
                    $runningBalance += $entry['amount'];
                } else {
                    $runningBalance -= $entry['amount'];
                }
                
                // Update the balance_after to the correct running balance
                $entry['balance_after'] = $runningBalance;
                $entries[] = $entry;
            }
            
            // Reverse the array to show most recent first for display
            $entries = array_reverse($entries);

            // Calculate summary statistics
            $totalCredits = 0;
            $totalDebits = 0;
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

            $balance = $runningBalance; // Use the calculated running balance
            $netActivity = $totalCredits - $totalDebits;

            $data = [
                'user' => $user,
                'entries' => $entries,
                'balance' => $balance,
                'totalCredits' => $totalCredits,
                'totalDebits' => $totalDebits,
                'netActivity' => $netActivity,
                'expenseCount' => $expenseCount,
                'advanceCount' => $advanceCount,
                'user_id' => $id,
                'active_page' => 'ledgers'
            ];

            $this->view('ledgers/user', $data);
        } catch (Exception $e) {
            error_log('Ledger view error: ' . $e->getMessage());
            header('Location: /ergon/users?error=ledger_failed');
            exit;
        }
    }

    public function projectLedger() {
        $this->requireAuth();
        
        error_log('Project Ledger - User role: ' . ($_SESSION['role'] ?? 'none'));
        
        if (!in_array($_SESSION['role'] ?? '', ['owner', 'admin'])) {
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
