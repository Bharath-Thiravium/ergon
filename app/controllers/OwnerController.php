<?php
/**
 * Owner Controller - Complete Role-Based Implementation
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/RoleManager.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Leave.php';
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../models/Advance.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Department.php';

class OwnerController extends Controller {
    
    public function dashboard() {
        AuthMiddleware::requireRole('owner');
        
        try {
            $db = Database::connect();
            date_default_timezone_set('Asia/Kolkata');
            $db->exec("SET time_zone = '+05:30'");

            // --- Attendance Today (uses check_in) ---
            $attToday    = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM attendance WHERE DATE(check_in) = CURDATE()")->fetchColumn();
            $totalActive = (int)$this->getTotalUsers($db);
            $onLeaveToday = (int)$db->query("SELECT COUNT(*) FROM leaves WHERE status='approved' AND CURDATE() BETWEEN start_date AND end_date")->fetchColumn();
            $absentToday = max(0, $totalActive - $attToday - $onLeaveToday);
            $lateToday   = (int)$db->query("SELECT COUNT(*) FROM attendance WHERE DATE(check_in)=CURDATE() AND TIME(check_in) > '09:30:00'")->fetchColumn();
            $attPct      = $totalActive > 0 ? round(($attToday / $totalActive) * 100) : 0;

            // --- Pending Approvals ---
            $pendingLeaves   = $this->getPendingLeavesCount($db);
            $pendingExpenses = $this->getPendingExpensesCount($db);
            $pendingAdvances = $this->getPendingAdvancesCount($db);
            $totalPendingApprovals = $pendingLeaves + $pendingExpenses + $pendingAdvances;

            // --- Finance KPIs ---
            $revenueThisMonth = $expensesThisMonth = $outstandingTotal = $tdsReceivable = $tdsReceived = 0;
            try {
                $revenueThisMonth  = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE MONTH(invoice_date)=MONTH(CURDATE()) AND YEAR(invoice_date)=YEAR(CURDATE())")->fetchColumn();
                $expensesThisMonth = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE status='approved' AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetchColumn();
                $outstandingTotal  = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE payment_status IN ('unpaid','partial','overdue')")->fetchColumn();
                // TDS = 2% of invoice amount (standard TDS on services)
                $tdsReceivable = (float)$db->query("SELECT COALESCE(SUM(amount)*0.02,0) FROM invoices WHERE payment_status IN ('unpaid','partial','overdue')")->fetchColumn();
                $tdsReceived   = (float)$db->query("SELECT COALESCE(SUM(tds_amount),0) FROM invoices WHERE tds_amount > 0")->fetchColumn();
            } catch (Exception $e) {}

            // --- Aging Buckets ---
            $agingBuckets = ['0_30'=>0,'31_60'=>0,'61_90'=>0,'90_plus'=>0];
            try {
                $stmt = $db->query("SELECT
                    SUM(CASE WHEN DATEDIFF(CURDATE(),due_date) BETWEEN 0 AND 30 THEN amount ELSE 0 END) as b0,
                    SUM(CASE WHEN DATEDIFF(CURDATE(),due_date) BETWEEN 31 AND 60 THEN amount ELSE 0 END) as b31,
                    SUM(CASE WHEN DATEDIFF(CURDATE(),due_date) BETWEEN 61 AND 90 THEN amount ELSE 0 END) as b61,
                    SUM(CASE WHEN DATEDIFF(CURDATE(),due_date) > 90 THEN amount ELSE 0 END) as b90
                    FROM invoices WHERE payment_status IN ('unpaid','partial','overdue') AND due_date < CURDATE()");
                $ab = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($ab) $agingBuckets = ['0_30'=>(float)$ab['b0'],'31_60'=>(float)$ab['b31'],'61_90'=>(float)$ab['b61'],'90_plus'=>(float)$ab['b90']];
            } catch (Exception $e) {}

            // --- Cash Ledger Summary ---
            $cashSummary = ['credits'=>0,'debits'=>0,'balance'=>0];
            try {
                $credits = (float)$db->query("SELECT COALESCE(SUM(amount_received),0) FROM invoices WHERE amount_received > 0")->fetchColumn();
                $debits  = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE status='approved'")->fetchColumn();
                $advPaid = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM advances WHERE status='approved'")->fetchColumn();
                $cashSummary = ['credits'=>$credits,'debits'=>$debits+$advPaid,'balance'=>$credits-$debits-$advPaid];
            } catch (Exception $e) {}

            // --- Overdue Invoices ---
            $overdueInvoices = [];
            try {
                $stmt = $db->query("SELECT customer_name, amount, due_date, DATEDIFF(CURDATE(),due_date) as days_overdue FROM invoices WHERE payment_status IN ('unpaid','overdue') AND due_date < CURDATE() ORDER BY days_overdue DESC LIMIT 5");
                $overdueInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}

            // --- Advances Outstanding ---
            $advancesOutstanding = [];
            try {
                $stmt = $db->query("SELECT u.name, a.amount, a.created_at, DATEDIFF(CURDATE(),a.created_at) as days_pending FROM advances a JOIN users u ON a.user_id=u.id WHERE a.status='approved' AND (a.recovered IS NULL OR a.recovered=0) ORDER BY days_pending DESC LIMIT 5");
                $advancesOutstanding = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}

            // --- Smart Alerts ---
            $crossAlerts = [];
            try {
                $stmt = $db->query("SELECT u.name FROM users u WHERE u.status='active' AND u.role='user' AND u.id NOT IN (SELECT user_id FROM attendance WHERE DATE(check_in)=CURDATE()) AND u.id NOT IN (SELECT user_id FROM leaves WHERE status='approved' AND CURDATE() BETWEEN start_date AND end_date) LIMIT 5");
                $absentNoLeave = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($absentNoLeave))
                    $crossAlerts[] = ['type'=>'danger','icon'=>'🚨','msg'=>count($absentNoLeave).' employee(s) absent with no leave: '.implode(', ',$absentNoLeave)];
            } catch (Exception $e) {}
            try {
                $cnt = $db->query("SELECT COUNT(DISTINCT t.assigned_to) FROM tasks t JOIN leaves l ON t.assigned_to=l.user_id WHERE l.status='approved' AND CURDATE() BETWEEN l.start_date AND l.end_date AND t.status NOT IN ('completed','cancelled')")->fetchColumn();
                if ($cnt > 0) $crossAlerts[] = ['type'=>'warning','icon'=>'⚠️','msg'=>"{$cnt} employee(s) on leave have open tasks assigned"];
            } catch (Exception $e) {}
            if ($revenueThisMonth > 0 && $expensesThisMonth > ($revenueThisMonth * 0.8))
                $crossAlerts[] = ['type'=>'warning','icon'=>'💸','msg'=>'Expenses are above 80% of revenue this month'];
            if ($outstandingTotal > 0)
                $crossAlerts[] = ['type'=>'danger','icon'=>'💰','msg'=>'₹'.number_format($outstandingTotal).' outstanding invoices need follow-up'];
            if ($agingBuckets['90_plus'] > 0)
                $crossAlerts[] = ['type'=>'danger','icon'=>'🔴','msg'=>'₹'.number_format($agingBuckets['90_plus']).' overdue 90+ days — high collection risk'];
            if ($totalPendingApprovals > 0)
                $crossAlerts[] = ['type'=>'info','icon'=>'📋','msg'>"{$totalPendingApprovals} request(s) pending your approval"];

            // --- Top Expense Categories ---
            $topExpenseCategories = [];
            try {
                $stmt = $db->query("SELECT category, SUM(amount) as total FROM expenses WHERE status='approved' AND MONTH(created_at)=MONTH(CURDATE()) GROUP BY category ORDER BY total DESC LIMIT 5");
                $topExpenseCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}

            // --- Attendance Behavior (late arrivals) ---
            $attendanceBehavior = [];
            try {
                $stmt = $db->query("SELECT u.name, COUNT(*) as late_count FROM attendance a JOIN users u ON a.user_id=u.id WHERE TIME(a.check_in)>'09:30:00' AND MONTH(a.check_in)=MONTH(CURDATE()) GROUP BY a.user_id ORDER BY late_count DESC LIMIT 5");
                $attendanceBehavior = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}

            // --- Recent Activities ---
            $recentActivities = [];
            try {
                $stmt = $db->query("SELECT al.action, al.description, al.created_at, u.name as user_name FROM activity_logs al LEFT JOIN users u ON al.user_id=u.id ORDER BY al.created_at DESC LIMIT 8");
                $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}

            $stats = [
                'total_users'      => $totalActive,
                'pending_leaves'   => $pendingLeaves,
                'pending_expenses' => $pendingExpenses,
                'pending_advances' => $pendingAdvances,
                'active_tasks'     => $this->getActiveTasks($db),
                'completion_rate'  => $this->getCompletionRate($db),
                'in_progress'      => $this->getInProgressTasksCount($db),
                'pending'          => $this->getPendingTasksCount($db),
                'ontime_rate'      => $this->getOntimeRate($db),
                'critical'         => $this->getCriticalTasksCount($db),
                'att_pct'          => $attPct,
            ];

            $this->view('owner/dashboard', [
                'data' => [
                    'stats'                => $stats,
                    'alerts'               => $crossAlerts,
                    'recent_activities'    => $recentActivities,
                    'att_today'            => $attToday,
                    'att_pct'              => $attPct,
                    'on_leave_today'       => $onLeaveToday,
                    'absent_today'         => $absentToday,
                    'late_today'           => $lateToday,
                    'total_pending'        => $totalPendingApprovals,
                    'revenue_month'        => $revenueThisMonth,
                    'expenses_month'       => $expensesThisMonth,
                    'outstanding_total'    => $outstandingTotal,
                    'tds_receivable'       => $tdsReceivable,
                    'tds_received'         => $tdsReceived,
                    'aging_buckets'        => $agingBuckets,
                    'cash_summary'         => $cashSummary,
                    'overdue_invoices'     => $overdueInvoices,
                    'advances_outstanding' => $advancesOutstanding,
                    'top_expense_cats'     => $topExpenseCategories,
                    'attendance_behavior'  => $attendanceBehavior,
                ],
                'active_page' => 'dashboard'
            ]);
            
        } catch (Exception $e) {
            error_log('Owner dashboard error: ' . $e->getMessage());
            $this->view('owner/dashboard', ['data'=>[], 'active_page'=>'dashboard']);
        }
    }
    
    public function approvals() {
        AuthMiddleware::requireRole('owner');
        
        try {
            $db = Database::connect();
            
            // Get all pending requests for owner approval
            $pendingLeaves = $this->getPendingLeaves($db);
            $pendingExpenses = $this->getPendingExpenses($db);
            $pendingAdvances = $this->getPendingAdvances($db);
            
            $this->view('owner/approvals', [
                'leaves' => $pendingLeaves,
                'expenses' => $pendingExpenses,
                'advances' => $pendingAdvances,
                'active_page' => 'approvals'
            ]);
            
        } catch (Exception $e) {
            error_log('Owner approvals error: ' . $e->getMessage());
            $this->view('owner/approvals', ['error' => 'Unable to load approvals: ' . $e->getMessage()]);
        }
    }
    
    public function createUser() {
        AuthMiddleware::requireRole('owner');
        
        if ($this->isPost()) {
            try {
                $userModel = new User();
                $result = $userModel->createEnhanced($_POST);
                
                if ($result) {
                    $this->json(['success' => true, 'message' => 'User created successfully', 'data' => $result]);
                } else {
                    $this->json(['success' => false, 'message' => 'Failed to create user']);
                }
            } catch (Exception $e) {
                $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $this->view('owner/create_user', ['active_page' => 'users']);
        }
    }
    
    public function manageUsers() {
        AuthMiddleware::requireRole('owner');
        
        try {
            $userModel = new User();
            $users = $userModel->getAll();
            
            $this->view('owner/manage_users', [
                'users' => $users,
                'active_page' => 'users'
            ]);
            
        } catch (Exception $e) {
            error_log('Manage users error: ' . $e->getMessage());
            $this->view('owner/manage_users', ['error' => 'Unable to load users']);
        }
    }
    
    public function assignRole() {
        AuthMiddleware::requireRole('owner');
        
        if ($this->isPost()) {
            try {
                $userModel = new User();
                $userId = $_POST['user_id'];
                $newRole = $_POST['role'];
                
                if ($userModel->update($userId, ['role' => $newRole])) {
                    $this->json(['success' => true, 'message' => 'Role updated successfully']);
                } else {
                    $this->json(['success' => false, 'message' => 'Failed to update role']);
                }
            } catch (Exception $e) {
                $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }
    
    public function finalApprove() {
        AuthMiddleware::requireRole('owner');
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        try {
            $type    = $_POST['type'];
            $id      = (int)$_POST['id'];
            $action  = $_POST['action']; // 'approve' or 'reject'
            $comments = $_POST['comments'] ?? '';
            
            $db = Database::connect();
            $this->ensureApprovalColumns($db);
            
            $status = $action === 'approve' ? 'approved' : 'rejected';
            
            switch ($type) {
                case 'leave':
                    $stmt = $db->prepare("UPDATE leaves SET status = ?, owner_approval = ?, owner_approved_by = ?, owner_approved_at = NOW(), owner_comments = ? WHERE id = ?");
                    break;
                case 'expense':
                    $stmt = $db->prepare("UPDATE expenses SET status = ?, owner_approval = ?, owner_approved_by = ?, owner_approved_at = NOW(), owner_comments = ? WHERE id = ?");
                    break;
                case 'advance':
                    $stmt = $db->prepare("UPDATE advances SET status = ?, owner_approval = ?, owner_approved_by = ?, owner_approved_at = NOW(), owner_comments = ? WHERE id = ?");
                    break;
                default:
                    throw new Exception('Invalid approval type');
            }
            
            $result = $stmt->execute([$status, $action, $_SESSION['user_id'], $comments, $id]);
            
            if ($result && $action === 'approve' && in_array($type, ['advance', 'expense'])) {
                $this->syncLedgerOnApproval($db, $type, $id);
            }
            
            if ($result) {
                $this->json(['success' => true, 'message' => ucfirst($type) . ' ' . $action . 'd successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to ' . $action . ' ' . $type]);
            }
            
        } catch (Exception $e) {
            error_log('Final approval error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function systemSettings() {
        AuthMiddleware::requireRole('owner');
        
        if ($this->isPost()) {
            try {
                $settings = $_POST['settings'];
                // Update system settings logic here
                $this->json(['success' => true, 'message' => 'Settings updated successfully']);
            } catch (Exception $e) {
                $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $this->view('owner/settings', ['active_page' => 'settings']);
        }
    }
    
    public function analytics() {
        AuthMiddleware::requireRole('owner');
        
        try {
            $db = Database::connect();
            
            $analytics = [
                'user_growth' => $this->getUserGrowthData($db),
                'task_completion' => $this->getTaskCompletionData($db),
                'attendance_trends' => $this->getAttendanceTrends($db),
                'department_performance' => $this->getDepartmentPerformance($db)
            ];
            
            $this->view('owner/analytics', [
                'analytics' => $analytics,
                'active_page' => 'analytics'
            ]);
            
        } catch (Exception $e) {
            error_log('Owner analytics error: ' . $e->getMessage());
            $this->view('owner/analytics', ['error' => 'Unable to load analytics']);
        }
    }
    
    // ── Owner Cash Ledger ────────────────────────────────────────────────────

    private function readLedgerFilters(): array {
        $fromDate        = !empty($_GET['from_date'])        ? $_GET['from_date']        : null;
        $toDate          = !empty($_GET['to_date'])          ? $_GET['to_date']          : null;
        $transactionType = !empty($_GET['transaction_type']) ? $_GET['transaction_type'] : null;
        $projectId       = !empty($_GET['project_id']) && is_numeric($_GET['project_id']) ? (int)$_GET['project_id'] : null;

        if ($fromDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) $fromDate = null;
        if ($toDate   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate))   $toDate   = null;
        if ($transactionType && !in_array($transactionType, ['expense', 'advance'])) $transactionType = null;

        return [$fromDate, $toDate, $transactionType, $projectId];
    }

    /**
     * Fetch all company-wide paid expenses + advances with optional filters.
     * Returns rows in chronological ASC order with balance_after attached.
     */
    private function fetchOwnerLedgerEntries(PDO $db, ?string $fromDate, ?string $toDate, ?string $transactionType, ?int $projectId): array {
        $expenseDateClause  = '';
        $advanceDateClause  = '';
        $expenseProjectClause = '';
        $advanceProjectClause = '';
        $dateParams         = [];

        if ($fromDate) {
            $expenseDateClause .= " AND COALESCE(e.expense_date, e.created_at) >= ?";
            $advanceDateClause .= " AND COALESCE(a.requested_date, a.paid_at, a.created_at) >= ?";
            $dateParams[]       = $fromDate;
        }
        if ($toDate) {
            $expenseDateClause .= " AND COALESCE(e.expense_date, e.created_at) <= ?";
            $advanceDateClause .= " AND COALESCE(a.requested_date, a.paid_at, a.created_at) <= ?";
            $dateParams[]       = $toDate . ' 23:59:59';
        }
        if ($projectId) {
            $expenseProjectClause = " AND e.project_id = ?";
            $advanceProjectClause = " AND a.project_id = ?";
        }

        $parts  = [];
        $params = [];

        if ($transactionType !== 'advance') {
            $parts[]  = "
                SELECT e.id as reference_id, 'expense' as reference_type, 'debit' as direction,
                       COALESCE(e.approved_amount, e.amount) as amount,
                       e.description, e.category, e.status,
                       COALESCE(e.expense_date, e.created_at) as created_at,
                       u.name as employee_name,
                       COALESCE(p.name, '') as project_name
                FROM expenses e
                JOIN users u ON e.user_id = u.id
                LEFT JOIN projects p ON e.project_id = p.id
                WHERE e.status = 'paid'
                  AND (e.source_advance_id IS NULL OR e.source_advance_id = 0)
                  {$expenseDateClause}
                  {$expenseProjectClause}";
            foreach ($dateParams as $p) $params[] = $p;
            if ($projectId) $params[] = $projectId;
        }

        if ($transactionType !== 'expense') {
            $parts[]  = "
                SELECT a.id as reference_id, 'advance' as reference_type, 'debit' as direction,
                       COALESCE(a.approved_amount, a.amount) as amount,
                       COALESCE(a.reason, CONCAT('Advance – ', a.type)) as description,
                       a.type as category, a.status,
                       COALESCE(a.requested_date, a.paid_at, a.created_at) as created_at,
                       u.name as employee_name,
                       COALESCE(p.name, '') as project_name
                FROM advances a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN projects p ON a.project_id = p.id
                WHERE a.status = 'paid'
                  {$advanceDateClause}
                  {$advanceProjectClause}";
            foreach ($dateParams as $p) $params[] = $p;
            if ($projectId) $params[] = $projectId;
        }

        if (empty($parts)) return [];

        $sql  = implode(" UNION ALL ", $parts) . " ORDER BY created_at ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach running balance (all owner entries are debits from company cash)
        $balance = 0;
        foreach ($rows as &$row) {
            $balance -= $row['amount'];   // every paid expense/advance is a cash outflow
            $row['balance_after'] = $balance;
        }
        unset($row);

        return $rows;
    }

    public function ownerCashLedger() {
        AuthMiddleware::requireRole('owner');

        try {
            $db = Database::connect();

            [$fromDate, $toDate, $transactionType, $projectId] = $this->readLedgerFilters();

            // Projects list for filter dropdown
            $projects = [];
            try {
                $projects = $db->query("SELECT id, name FROM projects ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}

            $rawEntries = $this->fetchOwnerLedgerEntries($db, $fromDate, $toDate, $transactionType, $projectId);

            // Reverse for display (most recent first)
            $entries      = array_reverse($rawEntries);
            $totalDebits  = array_sum(array_column($rawEntries, 'amount'));
            $expenseCount = count(array_filter($rawEntries, fn($r) => $r['reference_type'] === 'expense'));
            $advanceCount = count(array_filter($rawEntries, fn($r) => $r['reference_type'] === 'advance'));

            $csvParams = array_filter([
                'from_date'        => $fromDate ?? '',
                'to_date'          => $toDate ?? '',
                'transaction_type' => $transactionType ?? '',
                'project_id'       => $projectId ? (string)$projectId : '',
            ]);
            $csvUrl = '/ergon/owner/cash-ledger/download-csv' . ($csvParams ? '?' . http_build_query($csvParams) : '');

            $this->view('owner/cash_ledger', [
                'entries'         => $entries,
                'totalDebits'     => $totalDebits,
                'expenseCount'    => $expenseCount,
                'advanceCount'    => $advanceCount,
                'projects'        => $projects,
                'fromDate'        => $fromDate,
                'toDate'          => $toDate,
                'transactionType' => $transactionType,
                'projectId'       => $projectId,
                'isFiltered'      => (bool)($fromDate || $toDate || $transactionType || $projectId),
                'csvUrl'          => $csvUrl,
                'active_page'     => 'ledgers',
            ]);
        } catch (Exception $e) {
            error_log('Owner cash ledger error: ' . $e->getMessage());
            header('Location: /ergon/dashboard?error=ledger_failed');
            exit;
        }
    }

    public function ownerCashLedgerCsv() {
        AuthMiddleware::requireRole('owner');

        try {
            $db = Database::connect();

            [$fromDate, $toDate, $transactionType, $projectId] = $this->readLedgerFilters();

            // Identical query as the UI — guaranteed match
            $rows = $this->fetchOwnerLedgerEntries($db, $fromDate, $toDate, $transactionType, $projectId);

            $nameParts = ['cash_ledger'];
            if ($fromDate)        $nameParts[] = 'from_' . $fromDate;
            if ($toDate)          $nameParts[] = 'to_' . $toDate;
            if ($transactionType) $nameParts[] = $transactionType;
            if ($projectId)       $nameParts[] = 'project_' . $projectId;
            $filename = implode('_', $nameParts) . '.csv';

            $safe = fn($v) => $v ?? '';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Employee', 'Type', 'Project', 'Description', 'Category', 'Debit', 'Balance']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $safe($row['created_at']),
                    $safe($row['employee_name']),
                    $safe($row['reference_type']),
                    $safe($row['project_name']),
                    $safe($row['description']),
                    $safe($row['category']),
                    number_format($row['amount'], 2, '.', ''),
                    number_format($row['balance_after'], 2, '.', ''),
                ]);
            }
            fclose($out);
            exit;
        } catch (Exception $e) {
            error_log('Owner cash ledger CSV error: ' . $e->getMessage());
            http_response_code(500);
            exit('Export failed');
        }
    }

    // Legacy methods for backward compatibility
    public function approveRequest() {
        AuthMiddleware::requireRole('owner');
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        try {
            $type = $_POST['type'];
            $id   = (int)$_POST['id'];
            
            $db = Database::connect();
            
            switch ($type) {
                case 'leave':
                    $stmt = $db->prepare("UPDATE leaves SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
                    break;
                case 'expense':
                    $stmt = $db->prepare("UPDATE expenses SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
                    break;
                case 'advance':
                    $stmt = $db->prepare("UPDATE advances SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
                    break;
                default:
                    throw new Exception('Invalid approval type');
            }
            
            $result = $stmt->execute([$_SESSION['user_id'], $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                if (in_array($type, ['advance', 'expense'])) {
                    $this->syncLedgerOnApproval($db, $type, $id);
                }
                $this->json(['success' => true, 'message' => ucfirst($type) . ' approved successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to approve ' . $type . ' or already processed']);
            }
            
        } catch (Exception $e) {
            error_log('Owner approve request error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function rejectRequest() {
        AuthMiddleware::requireRole('owner');
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        try {
            $type = $_POST['type'];
            $id = (int)$_POST['id'];
            $reason = $_POST['remarks'] ?? 'Rejected by owner';
            
            $db = Database::connect();
            
            switch ($type) {
                case 'leave':
                    $stmt = $db->prepare("UPDATE leaves SET status = 'rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
                    break;
                case 'expense':
                    $stmt = $db->prepare("UPDATE expenses SET status = 'rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
                    break;
                case 'advance':
                    $stmt = $db->prepare("UPDATE advances SET status = 'rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
                    break;
                default:
                    throw new Exception('Invalid approval type');
            }
            
            $result = $stmt->execute([$reason, $_SESSION['user_id'], $id]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->json(['success' => true, 'message' => ucfirst($type) . ' rejected successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to reject ' . $type . ' or already processed']);
            }
            
        } catch (Exception $e) {
            error_log('Owner reject request error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function viewApproval($type, $id) {
        AuthMiddleware::requireRole('owner');
        
        try {
            $db = Database::connect();
            
            switch ($type) {
                case 'leave':
                    $stmt = $db->prepare("SELECT l.*, u.name as user_name FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.id = ?");
                    break;
                case 'expense':
                    $stmt = $db->prepare("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
                    break;
                case 'advance':
                    $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
                    break;
                default:
                    throw new Exception('Invalid approval type');
            }
            
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                throw new Exception('Item not found');
            }
            
            $this->view('owner/view_approval', [
                'type' => $type,
                'item' => $item,
                'active_page' => 'approvals'
            ]);
            
        } catch (Exception $e) {
            error_log('View approval error: ' . $e->getMessage());
            $this->redirect('/owner/approvals');
        }
    }
    
    public function deleteApproval($type, $id) {
        AuthMiddleware::requireRole('owner');
        
        if (!$this->isPost()) {
            $this->redirect('/owner/approvals');
            return;
        }
        
        try {
            $db = Database::connect();
            
            switch ($type) {
                case 'leave':
                    $stmt = $db->prepare("DELETE FROM leaves WHERE id = ?");
                    break;
                case 'expense':
                    $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
                    break;
                case 'advance':
                    $stmt = $db->prepare("DELETE FROM advances WHERE id = ?");
                    break;
                default:
                    throw new Exception('Invalid approval type');
            }
            
            if ($stmt->execute([$id])) {
                $this->json(['success' => true, 'message' => ucfirst($type) . ' deleted successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to delete ' . $type]);
            }
            
        } catch (Exception $e) {
            error_log('Delete approval error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Helper methods
    private function getTotalUsers($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
        return $stmt->fetchColumn();
    }
    
    private function getTotalAdmins($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'system_admin') AND status = 'active'");
        return $stmt->fetchColumn();
    }
    
    private function getTotalDepartments($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM departments WHERE status = 'active'");
        return $stmt->fetchColumn() ?: 0;
    }
    
    private function getPendingFinalApprovals($db) {
        $leaves = $db->query("SELECT COUNT(*) FROM leaves WHERE admin_approval = 'approved' AND owner_approval = 'pending'")->fetchColumn();
        $expenses = $db->query("SELECT COUNT(*) FROM expenses WHERE admin_approval = 'approved' AND owner_approval = 'pending'")->fetchColumn();
        $advances = $db->query("SELECT COUNT(*) FROM advances WHERE admin_approval = 'approved' AND owner_approval = 'pending'")->fetchColumn();
        return $leaves + $expenses + $advances;
    }
    
    private function getActiveTasks($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'in_progress')");
        return $stmt->fetchColumn();
    }
    
    private function getTodayAttendance($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM attendance WHERE DATE(clock_in) = CURDATE()");
        return $stmt->fetchColumn();
    }
    
    private function getMonthlyProductivity($db) {
        // Calculate productivity score based on task completion
        return 85; // Placeholder
    }
    
    private function getPendingLeaves($db, $level = 'all') {
        // Always fetch pending leaves for owner approval
        $stmt = $db->prepare("SELECT l.*, u.name as user_name, l.leave_type as type FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.status = 'pending' ORDER BY l.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPendingExpenses($db, $level = 'all') {
        // Always fetch pending expenses for owner approval
        $stmt = $db->prepare("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.status = 'pending' ORDER BY e.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPendingAdvances($db, $level = 'all') {
        // Always fetch pending advances for owner approval
        $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id WHERE a.status = 'pending' ORDER BY a.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getSystemAlerts($db) {
        $alerts = [];
        
        // Check for inactive users
        $inactiveUsers = $db->query("SELECT COUNT(*) FROM users WHERE status = 'inactive'")->fetchColumn();
        if ($inactiveUsers > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$inactiveUsers} inactive users need attention"
            ];
        }
        
        // Check for overdue tasks
        $overdueTasks = $db->query("SELECT COUNT(*) FROM tasks WHERE due_date < CURDATE() AND status != 'completed'")->fetchColumn();
        if ($overdueTasks > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "{$overdueTasks} tasks are overdue"
            ];
        }
        
        return $alerts;
    }
    
    private function getUserGrowthData($db) {
        // Return user growth data for charts
        return [];
    }
    
    private function getTaskCompletionData($db) {
        // Return task completion statistics
        return [];
    }
    
    private function getAttendanceTrends($db) {
        // Return attendance trend data
        return [];
    }
    
    private function getDepartmentPerformance($db) {
        // Return department-wise performance metrics
        return [];
    }
    
    private function getPendingLeavesCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'");
        return $stmt->fetchColumn();
    }
    
    private function getPendingExpensesCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM expenses WHERE status = 'pending'");
        return $stmt->fetchColumn();
    }
    
    private function getPendingAdvancesCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM advances WHERE status = 'pending'");
        return $stmt->fetchColumn();
    }
    
    private function getActiveProjectsCount($db) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE status = 'active'");
            $count = $stmt->fetchColumn();
            if ($count > 0) return $count;
        } catch (Exception $e) {
            // Projects table doesn't exist, fall back to tasks
        }
        
        try {
            $stmt = $db->query("SELECT COUNT(DISTINCT project_name) FROM tasks WHERE project_name IS NOT NULL AND project_name != '' AND status != 'completed'");
            return $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getCompletedTasksCount($db) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'");
            return $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getAverageProgress($db) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM tasks");
            $totalTasks = $stmt->fetchColumn();
            if ($totalTasks == 0) return 0;
            
            $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'");
            $completedTasks = $stmt->fetchColumn();
            
            return round(($completedTasks / $totalTasks) * 100);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getInProgressTasksCount($db) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status IN ('in_progress', 'assigned')");
            return $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getPendingTasksCount($db) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'not_started')");
            return $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getCompletionRate($db) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM tasks");
            $totalTasks = $stmt->fetchColumn();
            if ($totalTasks == 0) return 0;
            
            $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'");
            $completedTasks = $stmt->fetchColumn();
            
            return round(($completedTasks / $totalTasks) * 100);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getOverdueTasksCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE (due_date < CURDATE() OR deadline < CURDATE()) AND status NOT IN ('completed', 'cancelled')");
        return $stmt->fetchColumn();
    }
    
    private function getDueThisWeekCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE (due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) OR deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) AND status NOT IN ('completed', 'cancelled')");
        return $stmt->fetchColumn();
    }
    
    private function getDueTomorrowCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE (DATE(due_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) OR DATE(deadline) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)) AND status NOT IN ('completed', 'cancelled')");
        return $stmt->fetchColumn();
    }
    
    private function getRescheduledTasksCount($db) {
        // Count tasks that have been updated multiple times (approximation)
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE updated_at != created_at AND status != 'completed'");
        return $stmt->fetchColumn();
    }
    
    private function getCriticalTasksCount($db) {
        $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE priority = 'high' AND due_date < DATE_ADD(CURDATE(), INTERVAL 2 DAY) AND status != 'completed'");
        return $stmt->fetchColumn();
    }
    
    private function getOntimeRate($db) {
        $stmt = $db->query("
            SELECT 
                (COUNT(CASE WHEN status = 'completed' AND updated_at <= due_date THEN 1 END) * 100.0 / 
                 COUNT(CASE WHEN status = 'completed' THEN 1 END)) as ontime_rate
            FROM tasks 
            WHERE status = 'completed' AND due_date IS NOT NULL
        ");
        return round($stmt->fetchColumn() ?: 0);
    }
    
    /**
     * Write a ledger entry for an advance or expense immediately on approval.
     * Called from both approveRequest() and finalApprove().
     * Safe to call multiple times — LedgerHelper guards against duplicates via ledger_synced.
     */
    private function syncLedgerOnApproval(PDO $db, string $type, int $id): void {
        try {
            require_once __DIR__ . '/../helpers/LedgerHelper.php';
            LedgerHelper::ensureTable($db);

            if ($type === 'advance') {
                $row = $db->prepare("SELECT user_id, amount, approved_amount, requested_date FROM advances WHERE id = ? LIMIT 1");
                $row->execute([$id]);
                $rec = $row->fetch(PDO::FETCH_ASSOC);
                if (!$rec) return;
                $amount = !empty($rec['approved_amount']) ? floatval($rec['approved_amount']) : floatval($rec['amount']);
                LedgerHelper::recordEntry((int)$rec['user_id'], 'advance_payment', 'advance', $id, $amount, 'credit', $rec['requested_date'], $db);
            } else {
                $row = $db->prepare("SELECT user_id, amount, approved_amount, expense_date FROM expenses WHERE id = ? LIMIT 1");
                $row->execute([$id]);
                $rec = $row->fetch(PDO::FETCH_ASSOC);
                if (!$rec) return;
                $amount = !empty($rec['approved_amount']) ? floatval($rec['approved_amount']) : floatval($rec['amount']);
                LedgerHelper::recordEntry((int)$rec['user_id'], 'expense_payment', 'expense', $id, $amount, 'credit', $rec['expense_date'], $db);
            }
        } catch (Exception $e) {
            error_log('OwnerController::syncLedgerOnApproval error: ' . $e->getMessage());
        }
    }

    private function ensureApprovalColumns($db) {
        try {
            // Add missing columns for multi-level approval
            $tables = ['leaves', 'expenses', 'advances'];
            
            foreach ($tables as $table) {
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approved_by INT DEFAULT NULL", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_approved_at DATETIME DEFAULT NULL", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS admin_comments TEXT DEFAULT NULL", "Alter table");
                
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS owner_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS owner_approved_by INT DEFAULT NULL", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS owner_approved_at DATETIME DEFAULT NULL", "Alter table");
                DatabaseHelper::safeExec($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS owner_comments TEXT DEFAULT NULL", "Alter table");
            }
        } catch (Exception $e) {
            error_log('Column creation error: ' . $e->getMessage());
        }
    }
}
?>
