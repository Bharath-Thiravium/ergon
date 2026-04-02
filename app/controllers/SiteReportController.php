<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class SiteReportController extends Controller {

    private $db;

    public function __construct() {
        $this->db = Database::connect();
        // Force IST for all date/time operations in this controller
        date_default_timezone_set('Asia/Kolkata');
        $this->db->exec("SET time_zone = '+05:30'");
    }

    // GET /site-reports  — list all reports (admin/owner)
    // GET /site-reports  — list own reports (supervisor)
    public function index($request = []) {
        $this->requireAuth();
        $userId = $_SESSION['user_id'];
        $role   = $_SESSION['role'] ?? 'user';

        $canViewAllReports = in_array($role, ['admin', 'owner', 'company_owner'], true);
        $where = $canViewAllReports ? '' : 'WHERE sr.submitted_by = ?';
        $params = $canViewAllReports ? [] : [$userId];

        $reports = $this->db->prepare("
            SELECT sr.*, u.name AS submitted_by_name,
                   p.name AS project_name,
                   COALESCE(SUM(sre.amount),0) AS total_expenses_requested
            FROM site_reports sr
            LEFT JOIN users u ON u.id = sr.submitted_by
            LEFT JOIN projects p ON p.id = sr.project_id
            LEFT JOIN site_report_expenses sre ON sre.report_id = sr.id
            $where
            GROUP BY sr.id
            ORDER BY sr.report_date DESC, sr.created_at DESC
            LIMIT 100
        ");
        $reports->execute($params);
        $reports = $reports->fetchAll(PDO::FETCH_ASSOC);

        $title       = 'Site Daily Reports';
        $active_page = 'site_reports';
        ob_start();
        require_once __DIR__ . '/../../views/site_reports/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/dashboard.php';
    }

    // GET /site-reports/create
    public function create($request = []) {
        $this->requireAuth();
        $projects = $this->db->query("SELECT id, name FROM projects ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

        $title       = 'Submit Site Report';
        $active_page = 'site_reports';
        ob_start();
        require_once __DIR__ . '/../../views/site_reports/create.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/dashboard.php';
    }

    // Reporting window: 9:00 AM – 6:00 PM IST, with grace until 9:00 AM next morning
    private function getReportSubmissionStatus(): array {
        $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $hour = (int)$now->format('G');
        $minute = (int)$now->format('i');
        $totalMins = $hour * 60 + $minute;
        $windowStart = 9 * 60;   // 9:00 AM
        $windowEnd   = 18 * 60;  // 6:00 PM
        $graceEnd    = 9 * 60;   // next day 9:00 AM

        if ($totalMins >= $windowStart && $totalMins < $windowEnd) {
            $minsLeft = $windowEnd - $totalMins;
            return ['allowed' => true, 'report_status' => 'on_time', 'mins_left' => $minsLeft];
        }
        // Grace: after 6 PM until midnight, or midnight until 9 AM next day
        if ($totalMins >= $windowEnd || $totalMins < $graceEnd) {
            return ['allowed' => true, 'report_status' => 'late', 'mins_left' => 0];
        }
        return ['allowed' => false, 'report_status' => 'blocked', 'mins_left' => 0];
    }

    // GET /site-reports/window-status  (AJAX)
    public function windowStatus($request = []) {
        $this->requireAuth();
        header('Content-Type: application/json');
        echo json_encode($this->getReportSubmissionStatus());
        exit;
    }

    // POST /site-reports/store
    public function store($request = []) {
        $this->requireAuth();
        if (!$this->isPost()) { $this->redirect('/site-reports/create'); return; }

        $p = $_POST;

        // ── Server-side WhatsApp parsing ───────────────────────────────────────────────
        // If raw WhatsApp text was submitted (paste tab), parse it server-side.
        // This ensures consistent output regardless of JS execution.
        require_once __DIR__ . '/../services/WhatsAppParser.php';
        if (!empty(trim($p['wa_raw'] ?? ''))) {
            $parsed = WhatsAppParser::parseSiteReport($p['wa_raw']);
            // Fill in any fields the client didn’t populate
            if (empty($p['report_date']) && $parsed['date'])   $p['report_date']    = $parsed['date'];
            if (empty($p['site_name'])   && $parsed['site'])   $p['site_name']      = $parsed['site'];
            if (empty($p['total_manpower']) && $parsed['total_manpower']) $p['total_manpower'] = $parsed['total_manpower'];
            // Merge parsed tasks into tasks array
            if (!empty($parsed['tasks'])) {
                $p['tasks'] = array_merge($p['tasks'] ?? [], $parsed['tasks']);
            }
            // Merge manpower counts
            foreach ($parsed['manpower_counts'] as $cat => $count) {
                if (empty($p['mp'][$cat]['count'])) $p['mp'][$cat]['count'] = $count;
            }
            foreach ($parsed['manpower_names'] as $cat => $names) {
                if (empty($p['mp'][$cat]['names'])) $p['mp'][$cat]['names'] = implode("\n", $names);
            }
            foreach ($parsed['machinery'] as $mach => $count) {
                if (empty($p['mach'][$mach]['count'])) $p['mach'][$mach]['count'] = $count;
            }
        }

        // Always clean the remarks field to strip WhatsApp noise
        if (!empty($p['remarks'])) {
            $p['remarks'] = WhatsAppParser::clean($p['remarks']);
        }

        // Enforce reporting window (skip for admin/owner)
        $role = $_SESSION['role'] ?? 'user';
        if (!in_array($role, ['admin', 'owner', 'company_owner'])) {
            $windowStatus = $this->getReportSubmissionStatus();
            if (!$windowStatus['allowed']) {
                $this->redirect('/site-reports/create?error=window_closed');
                return;
            }
        } else {
            $windowStatus = ['report_status' => 'on_time'];
        }

        try {
            $this->db->beginTransaction();

            // 1. Main report row
            $stmt = $this->db->prepare("
                INSERT INTO site_reports
                    (company_id, project_id, site_name, report_date, submitted_by, total_manpower, remarks, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted')
            ");
            $stmt->execute([
                $_SESSION['company_id'] ?? null,
                $p['project_id'] ?: null,
                trim($p['site_name']),
                $p['report_date'],
                $_SESSION['user_id'],
                (int)($p['total_manpower'] ?? 0),
                trim($p['remarks'] ?? ''),
            ]);
            $reportId = $this->db->lastInsertId();

            // 2. Manpower rows
            $categories = [
                'engineer','supervisor','ac_dc_team','mms_team',
                'civil_mason','local_labour','driver_operator','other'
            ];
            $mpStmt = $this->db->prepare("
                INSERT INTO site_report_manpower (report_id, category, count, names)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($categories as $cat) {
                $count = (int)($p['mp'][$cat]['count'] ?? 0);
                $names = trim($p['mp'][$cat]['names'] ?? '');
                if ($count > 0 || $names !== '') {
                    $namesJson = $names ? json_encode(array_filter(array_map('trim', explode("\n", $names)))) : null;
                    $mpStmt->execute([$reportId, $cat, $count, $namesJson]);
                }
            }

            // 3. Machinery rows
            $machines = ['tractor','jcb','hydra','tata_ace','dg','crane','other'];
            $mStmt = $this->db->prepare("
                INSERT INTO site_report_machinery (report_id, machine_type, count, hours_worked, fuel_litres, remarks)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($machines as $m) {
                $count = (int)($p['mach'][$m]['count'] ?? 0);
                if ($count > 0) {
                    $mStmt->execute([
                        $reportId, $m, $count,
                        $p['mach'][$m]['hours'] ?: null,
                        $p['mach'][$m]['fuel']  ?: null,
                        trim($p['mach'][$m]['remarks'] ?? ''),
                    ]);
                }
            }

            // 4. Tasks
            $tStmt = $this->db->prepare("
                INSERT INTO site_report_tasks (report_id, task_description, sort_order) VALUES (?, ?, ?)
            ");
            foreach (($p['tasks'] ?? []) as $i => $task) {
                $task = trim($task);
                if ($task !== '') $tStmt->execute([$reportId, $task, $i]);
            }

            // 5. Expense requests
            $eStmt = $this->db->prepare("
                INSERT INTO site_report_expenses (report_id, description, amount, expense_type)
                VALUES (?, ?, ?, ?)
            ");
            foreach (($p['expenses'] ?? []) as $exp) {
                $desc   = trim($exp['description'] ?? '');
                $amount = (float)($exp['amount'] ?? 0);
                if ($desc !== '' && $amount > 0) {
                    $eStmt->execute([$reportId, $desc, $amount, $exp['type'] ?? 'other']);
                }
            }

            $this->db->commit();
            $this->redirect('/site-reports/view/' . $reportId);

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('SiteReport store error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            $this->redirect('/site-reports/create?error=' . urlencode($e->getMessage()));
        }
    }

    // GET /site-reports/view/{id}
    public function viewReport($request = []) {
        $this->requireAuth();
        $id = is_array($request) ? (int)($request['id'] ?? $request[0] ?? 0) : (int)$request;

        $report = $this->db->prepare("
            SELECT sr.*, u.name AS submitted_by_name, p.name AS project_name
            FROM site_reports sr
            LEFT JOIN users u ON u.id = sr.submitted_by
            LEFT JOIN projects p ON p.id = sr.project_id
            WHERE sr.id = ?
        ");
        $report->execute([$id]);
        $report = $report->fetch(PDO::FETCH_ASSOC);
        if (!$report) { http_response_code(404); echo 'Report not found'; return; }

        $manpower  = $this->db->prepare("SELECT * FROM site_report_manpower WHERE report_id = ? ORDER BY id");
        $manpower->execute([$id]); $manpower = $manpower->fetchAll(PDO::FETCH_ASSOC);

        $machinery = $this->db->prepare("SELECT * FROM site_report_machinery WHERE report_id = ? ORDER BY id");
        $machinery->execute([$id]); $machinery = $machinery->fetchAll(PDO::FETCH_ASSOC);

        $tasks     = $this->db->prepare("SELECT * FROM site_report_tasks WHERE report_id = ? ORDER BY sort_order");
        $tasks->execute([$id]); $tasks = $tasks->fetchAll(PDO::FETCH_ASSOC);

        $expenses  = $this->db->prepare("SELECT * FROM site_report_expenses WHERE report_id = ? ORDER BY id");
        $expenses->execute([$id]); $expenses = $expenses->fetchAll(PDO::FETCH_ASSOC);

        $title       = 'Site Report — ' . $report['report_date'];
        $active_page = 'site_reports';
        ob_start();
        require_once __DIR__ . '/../../views/site_reports/view.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/dashboard.php';
    }

    // POST /site-reports/expense/approve  (admin only)
    public function approveExpense($request = []) {
        $this->requireAuth();
        header('Content-Type: application/json');

        $expenseId = (int)($_POST['expense_id'] ?? 0);
        $action    = $_POST['action'] ?? ''; // 'approved' or 'rejected'

        if (!in_array($action, ['approved','rejected'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid action']); return;
        }

        $this->db->prepare("UPDATE site_report_expenses SET status = ? WHERE id = ?")
                 ->execute([$action, $expenseId]);

        echo json_encode(['success' => true]);
    }

    // GET /site-reports/summary  — aggregate view for management
    public function summary($request = []) {
        $this->requireAuth();

        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $rows = $this->db->prepare("
            SELECT sr.report_date, sr.site_name, sr.total_manpower,
                   p.name AS project_name,
                   COALESCE(SUM(sre.amount),0) AS total_expenses_requested,
                   COUNT(DISTINCT sre.id) AS expense_items
            FROM site_reports sr
            LEFT JOIN projects p ON p.id = sr.project_id
            LEFT JOIN site_report_expenses sre ON sre.report_id = sr.id
            WHERE sr.report_date BETWEEN ? AND ?
            GROUP BY sr.id
            ORDER BY sr.report_date DESC, sr.site_name
        ");
        $rows->execute([$from, $to]);
        $rows = $rows->fetchAll(PDO::FETCH_ASSOC);

        $title       = 'Site Reports Summary';
        $active_page = 'site_reports';
        ob_start();
        require_once __DIR__ . '/../../views/site_reports/summary.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../../views/layouts/dashboard.php';
    }
}
