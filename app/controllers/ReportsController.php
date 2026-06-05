<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Task.php';

class ReportsController extends Controller {
    private $userModel;
    private $attendanceModel;
    private $taskModel;
    
    public function __construct() {
        try {
            $this->userModel = new User();
            $this->attendanceModel = new Attendance();
            $this->taskModel = new Task();
        } catch (Exception $e) {
            error_log('ReportsController init error: ' . $e->getMessage());
            $this->userModel = null;
            $this->attendanceModel = null;
            $this->taskModel = null;
        }
    }
    
    public function monthlyAttendance() {
        AuthMiddleware::requireAuth();
        if (!in_array($_SESSION['role'], ['admin', 'owner', 'company_owner'])) {
            http_response_code(403); echo 'Access denied'; exit;
        }

        require_once __DIR__ . '/../config/database.php';
        $db = Database::connect();

        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
        $year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
        $month = max(1, min(12, $month));
        $year  = max(2020, min((int)date('Y') + 1, $year));

        $firstDay    = new DateTime("$year-$month-01");
        $lastDay     = new DateTime($firstDay->format('Y-m-t'));
        $totalDays   = (int)$lastDay->format('d');
        $monthLabel  = $firstDay->format('F Y');

        // Working days (Mon–Sat, exclude Sun)
        $workingDays = 0;
        for ($d = 1; $d <= $totalDays; $d++) {
            $dow = (int)(new DateTime("$year-$month-$d"))->format('N');
            if ($dow !== 7) $workingDays++;
        }

        // All active users excluding only 'owner' role (FIXED: was excluding company_owner)
        $users = $db->query("
            SELECT id, name, role
            FROM users
            WHERE status = 'active'
              AND role NOT IN ('owner')
            ORDER BY FIELD(role,'admin','user','company_owner'), name
        ")->fetchAll(PDO::FETCH_ASSOC);


        // All attendance for the month
        $stmt = $db->prepare("
            SELECT user_id,
                   DATE(check_in)  AS day,
                   MIN(check_in)   AS first_in,
                   MAX(check_out)  AS last_out,
                   ROUND(
                     SUM(TIMESTAMPDIFF(MINUTE,
                       check_in,
                       COALESCE(check_out, NOW())
                     )) / 60, 2
                   ) AS hours
            FROM attendance
            WHERE DATE(check_in) BETWEEN ? AND ?
            GROUP BY user_id, DATE(check_in)
        ");
        $stmt->execute([$firstDay->format('Y-m-d'), $lastDay->format('Y-m-d')]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Approved leaves for the month
        $leaveStmt = $db->prepare("
            SELECT user_id, start_date, end_date
            FROM leaves
            WHERE status = 'approved'
              AND start_date <= ? AND end_date >= ?
        ");
        $leaveStmt->execute([$lastDay->format('Y-m-d'), $firstDay->format('Y-m-d')]);
        $leaves = $leaveStmt->fetchAll(PDO::FETCH_ASSOC);

        // Build leave lookup: [user_id][date] = true
        $leaveMap = [];
        foreach ($leaves as $lv) {
            $cur = new DateTime($lv['start_date']);
            $end = new DateTime($lv['end_date']);
            while ($cur <= $end) {
                $leaveMap[$lv['user_id']][$cur->format('Y-m-d')] = true;
                $cur->modify('+1 day');
            }
        }

        // Get holidays for the month
        $holidayStmt = $db->prepare("
            SELECT DISTINCT holiday_date
            FROM holidays
            WHERE is_active = 1
              AND holiday_date BETWEEN ? AND ?
        ");
        $holidayStmt->execute([$firstDay->format('Y-m-d'), $lastDay->format('Y-m-d')]);
        $holidays = $holidayStmt->fetchAll(PDO::FETCH_ASSOC);

        // Build holiday lookup: [date] = true
        $holidayMap = [];
        foreach ($holidays as $hol) {
            $holidayMap[$hol['holiday_date']] = true;
        }

        // Build attendance lookup: [user_id][date] = row
        $attMap = [];
        foreach ($rows as $r) {
            $attMap[$r['user_id']][$r['day']] = $r;
        }

        // Build day list
        $days = [];
        for ($d = 1; $d <= $totalDays; $d++) {
            $dt  = new DateTime("$year-$month-$d");
            $dow = (int)$dt->format('N');
            $days[] = [
                'date'    => $dt->format('Y-m-d'),
                'label'   => $dt->format('d'),
                'day'     => $dt->format('D'),
                'is_sun'  => $dow === 7,
                'is_sat'  => $dow === 6,
            ];
        }

        // Build summary per user
        $report = [];
        foreach ($users as $u) {
            $uid      = $u['id'];
            $present  = 0;
            $absent   = 0;
            $leave    = 0;
            $totalHrs = 0;
            $dayData  = [];

            foreach ($days as $day) {
                $date   = $day['date'];
                $isSun  = $day['is_sun'];
                $att    = $attMap[$uid][$date] ?? null;
                $onLeave = isset($leaveMap[$uid][$date]);
                $isHoliday = isset($holidayMap[$date]);

                if ($isSun) {
                    $dayData[$date] = 'WO'; // Week Off
                } elseif ($isHoliday) {
                    $dayData[$date] = 'H'; // Holiday
                } elseif ($att) {
                    $present++;
                    $totalHrs += floatval($att['hours']);
                    $dayData[$date] = [
                        'in'    => date('H:i', strtotime($att['first_in'])),
                        'out'   => $att['last_out'] ? date('H:i', strtotime($att['last_out'])) : '--',
                        'hours' => $att['hours'],
                    ];
                } elseif ($onLeave) {
                    $leave++;
                    $dayData[$date] = 'L';
                } else {
                    // Only count as absent if date is not in the future
                    if ($date <= date('Y-m-d')) {
                        $absent++;
                        $dayData[$date] = 'A';
                    } else {
                        $dayData[$date] = '-';
                    }
                }
            }

            $report[] = [
                'id'         => $uid,
                'name'       => $u['name'],
                'role'       => $u['role'],
                'present'    => $present,
                'absent'     => $absent,
                'leave'      => $leave,
                'total_hrs'  => round($totalHrs, 1),
                'att_pct'    => $workingDays > 0 ? round(($present / $workingDays) * 100) : 0,
                'days'       => $dayData,
            ];
        }

        // CSV export
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="attendance_' . $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.csv"');
            $out = fopen('php://output', 'w');
            // Header row
            $hdr = ['Employee', 'Role', 'Present', 'Absent', 'Leave', 'Total Hrs', 'Att%'];
            foreach ($days as $day) $hdr[] = $day['label'] . '(' . $day['day'] . ')';
            fputcsv($out, $hdr);
            foreach ($report as $r) {
                $row = [$r['name'], ucfirst($r['role']), $r['present'], $r['absent'], $r['leave'], $r['total_hrs'], $r['att_pct'] . '%'];
                foreach ($days as $day) {
                    $d = $r['days'][$day['date']] ?? '-';
                    $row[] = is_array($d) ? 'P(' . $d['in'] . '-' . $d['out'] . ')' : $d;
                }
                fputcsv($out, $row);
            }
            fclose($out);
            exit;
        }

        $this->view('reports/monthly_attendance', [
            'report'       => $report,
            'days'         => $days,
            'month'        => $month,
            'year'         => $year,
            'month_label'  => $monthLabel,
            'working_days' => $workingDays,
            'total_days'   => $totalDays,
            'active_page'  => 'reports',
        ]);
    }

    public function index() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        $data = [
            'attendance_summary' => $this->getAttendanceSummary(),
            'task_summary' => $this->getTaskSummary(),
            'user_performance' => $this->getUserPerformance(),
            'active_page' => 'reports'
        ];
        
        $this->view('reports/index', $data);
    }
    
    public function activity() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        try {
            $activity = $this->getActivityReport();
            $productivity = $this->getProductivitySummary();
        } catch (Exception $e) {
            error_log('Activity report error: ' . $e->getMessage());
            $activity = [];
            $productivity = [];
        }
        
        $data = [
            'activity' => $activity,
            'productivity' => $productivity,
            'active_page' => 'reports'
        ];
        
        $this->view('reports/activity', $data);
    }
    
    public function export() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        try {
            $this->ensureTablesExist();
            
            $data = [
                'attendance_summary' => $this->getAttendanceSummary(),
                'task_summary' => $this->getTaskSummary(),
                'user_performance' => $this->getUserPerformance()
            ];
            
            $csv = "ERGON Reports Export - " . date('Y-m-d H:i:s') . "\n\n";
            
            $csv .= "ATTENDANCE SUMMARY\n";
            $csv .= "Present Today," . ($data['attendance_summary']['total_present'] ?? 0) . "\n";
            $csv .= "Absent Today," . ($data['attendance_summary']['total_absent'] ?? 0) . "\n";
            $csv .= "Average Hours," . ($data['attendance_summary']['average_hours'] ?? 0) . "\n\n";
            
            $csv .= "TASK SUMMARY\n";
            $csv .= "Completed Tasks," . ($data['task_summary']['completed_tasks'] ?? 0) . "\n";
            $csv .= "Pending Tasks," . ($data['task_summary']['pending_tasks'] ?? 0) . "\n";
            $csv .= "Overdue Tasks," . ($data['task_summary']['overdue_tasks'] ?? 0) . "\n\n";
            
            $csv .= "USER PERFORMANCE\n";
            $csv .= "Employee,Tasks Completed,Attendance Rate\n";
            foreach ($data['user_performance'] as $user) {
                $csv .= ($user['name'] ?? 'N/A') . "," . ($user['tasks_completed'] ?? 0) . "," . ($user['attendance_rate'] ?? 0) . "%\n";
            }
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="ergon_report_' . date('Y-m-d') . '.csv"');
            echo $csv;
        } catch (Exception $e) {
            error_log('Export error: ' . $e->getMessage());
            header('Location: /ergon/reports?error=Export failed');
        }
        exit;
    }
    
    public function attendanceExport() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTablesExist();
            
            $stmt = $db->query("SELECT a.*, u.name as user_name, u.employee_id FROM attendance a JOIN users u ON a.user_id = u.id WHERE a.check_in >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ORDER BY a.check_in DESC");
            $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $csv = "ERGON Attendance Report - " . date('Y-m-d H:i:s') . "\n\n";
            $csv .= "Employee ID,Employee Name,Check In,Check Out,Hours Worked,Status,Date\n";
            
            foreach ($attendance as $record) {
                $checkIn = $record['check_in'] ? date('H:i', strtotime($record['check_in'])) : 'N/A';
                $checkOut = $record['check_out'] ? date('H:i', strtotime($record['check_out'])) : 'N/A';
                $hoursWorked = 0;
                
                if ($record['check_in'] && $record['check_out']) {
                    $start = new DateTime($record['check_in']);
                    $end = new DateTime($record['check_out']);
                    $diff = $start->diff($end);
                    $hoursWorked = $diff->h + ($diff->i / 60);
                    $hoursWorked = round($hoursWorked, 2);
                }
                
                $csv .= ($record['employee_id'] ?? 'N/A') . "," . 
                       ($record['user_name'] ?? 'N/A') . "," . 
                       $checkIn . "," . 
                       $checkOut . "," . 
                       $hoursWorked . "," . 
                       ($record['status'] ?? 'present') . "," . 
                       date('Y-m-d', strtotime($record['check_in'])) . "\n";
            }
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="ergon_attendance_' . date('Y-m-d') . '.csv"');
            echo $csv;
        } catch (Exception $e) {
            error_log('Attendance export error: ' . $e->getMessage());
            header('Location: /ergon/reports?error=Attendance export failed');
        }
        exit;
    }
    
    public function approvalsExport() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $this->ensureTablesExist();
            
            $csv = "ERGON Approvals Report - " . date('Y-m-d H:i:s') . "\n\n";
            
            try {
                $stmt = $db->query("SELECT l.*, u.name as user_name FROM leaves l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 100");
                $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $leaves = [];
            }
            
            $csv .= "LEAVE REQUESTS\n";
            $csv .= "Employee,Type,Start Date,End Date,Days,Status,Created Date\n";
            foreach ($leaves as $leave) {
                $csv .= ($leave['user_name'] ?? 'N/A') . "," . ($leave['leave_type'] ?? 'N/A') . "," . ($leave['start_date'] ?? 'N/A') . "," . ($leave['end_date'] ?? 'N/A') . "," . ($leave['days_requested'] ?? 0) . "," . ($leave['status'] ?? 'N/A') . "," . ($leave['created_at'] ?? 'N/A') . "\n";
            }
            
            try {
                $stmt = $db->query("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id ORDER BY e.created_at DESC LIMIT 100");
                $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $expenses = [];
            }
            
            $csv .= "\nEXPENSE CLAIMS\n";
            $csv .= "Employee,Category,Amount,Description,Status,Created Date\n";
            foreach ($expenses as $expense) {
                $csv .= ($expense['user_name'] ?? 'N/A') . "," . ($expense['category'] ?? 'N/A') . "," . ($expense['amount'] ?? 0) . "," . str_replace(',', ';', $expense['description'] ?? '') . "," . ($expense['status'] ?? 'N/A') . "," . ($expense['created_at'] ?? 'N/A') . "\n";
            }
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="ergon_approvals_' . date('Y-m-d') . '.csv"');
            echo $csv;
        } catch (Exception $e) {
            error_log('Approvals export error: ' . $e->getMessage());
            header('Location: /ergon/reports?error=Approvals export failed');
        }
        exit;
    }
    
    private function getAttendanceSummary() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $sql = "SELECT 
                        COUNT(DISTINCT user_id) as total_present,
                        (SELECT COUNT(*) FROM users WHERE status = 'active') - COUNT(DISTINCT user_id) as total_absent,
                        AVG(TIMESTAMPDIFF(HOUR, check_in, check_out)) as average_hours
                    FROM attendance 
                    WHERE DATE(check_in) = CURDATE() AND check_out IS NOT NULL";
            $stmt = $db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_present' => $result['total_present'] ?? 0,
                'total_absent' => $result['total_absent'] ?? 0,
                'average_hours' => round($result['average_hours'] ?? 0, 1)
            ];
        } catch (Exception $e) {
            error_log('getAttendanceSummary error: ' . $e->getMessage());
            return ['total_present' => 0, 'total_absent' => 0, 'average_hours' => 0];
        }
    }
    
    private function getTaskSummary() {
        try {
            if ($this->taskModel) {
                $stats = $this->taskModel->getTaskStats();
            } else {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                $sql = "SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as pending_tasks
                  FROM tasks";
                $stmt = $db->query($sql);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return [
                'completed_tasks' => $stats['completed_tasks'] ?? 0,
                'pending_tasks' => ($stats['pending_tasks'] ?? 0) + ($stats['in_progress_tasks'] ?? 0),
                'overdue_tasks' => 0,
                'completion_rate' => ($stats['total_tasks'] ?? 0) > 0 ? 
                    round((($stats['completed_tasks'] ?? 0) / ($stats['total_tasks'] ?? 1)) * 100, 1) : 0
            ];
        } catch (Exception $e) {
            error_log('getTaskSummary error: ' . $e->getMessage());
            return ['completed_tasks' => 0, 'pending_tasks' => 0, 'overdue_tasks' => 0, 'completion_rate' => 0];
        }
    }
    
    private function getUserPerformance() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $sql = "SELECT 
                        u.name,
                        COUNT(DISTINCT t.id) as tasks_completed,
                        COUNT(DISTINCT a.id) as attendance_days
                    FROM users u
                    LEFT JOIN tasks t ON u.id = t.assigned_to AND t.status = 'completed'
                    LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    WHERE u.status = 'active' AND u.role = 'user'
                    GROUP BY u.id, u.name
                    ORDER BY tasks_completed DESC
                    LIMIT 10";
            $stmt = $db->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as &$result) {
                $result['attendance_rate'] = round(($result['attendance_days'] / 30) * 100, 0);
            }
            
            return $results;
        } catch (Exception $e) {
            error_log('getUserPerformance error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getActivityReport() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $sql = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as activities
                    FROM activity_logs 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date DESC";
            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getActivityReport error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getProductivitySummary() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $sql = "SELECT 
                        u.name,
                        COUNT(t.id) as total_tasks,
                        AVG(t.progress) as avg_progress
                    FROM users u
                    LEFT JOIN tasks t ON u.id = t.assigned_to
                    WHERE u.status = 'active' AND u.role = 'user'
                    GROUP BY u.id, u.name
                    HAVING total_tasks > 0
                    ORDER BY avg_progress DESC
                    LIMIT 5";
            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getProductivitySummary error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function ensureTablesExist() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->query("SHOW TABLES LIKE 'attendance'");
            if ($stmt->rowCount() == 0) {
                $sql = "CREATE TABLE attendance (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    check_in DATETIME NOT NULL,
                    check_out DATETIME NULL,
                    latitude DECIMAL(10,8) NULL,
                    longitude DECIMAL(11,8) NULL,
                    location_name VARCHAR(255) NULL,
                    status VARCHAR(20) DEFAULT 'present',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                require_once __DIR__ . '/../helpers/DatabaseHelper.php';
                DatabaseHelper::safeExec($db, $sql, "Execute SQL");
            }
            
            $stmt = $db->query("SHOW TABLES LIKE 'tasks'");
            if ($stmt->rowCount() == 0) {
                $sql = "CREATE TABLE tasks (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    assigned_by INT NOT NULL,
                    assigned_to INT NOT NULL,
                    priority VARCHAR(20) DEFAULT 'medium',
                    status VARCHAR(20) DEFAULT 'assigned',
                    progress INT DEFAULT 0,
                    deadline DATE NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                require_once __DIR__ . '/../helpers/DatabaseHelper.php';
                DatabaseHelper::safeExec($db, $sql, "Execute SQL");
            }
            
            $stmt = $db->query("SHOW TABLES LIKE 'leaves'");
            if ($stmt->rowCount() == 0) {
                $sql = "CREATE TABLE leaves (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    leave_type VARCHAR(50) NOT NULL,
                    start_date DATE NOT NULL,
                    end_date DATE NOT NULL,
                    days_requested INT NOT NULL,
                    reason TEXT,
                    status VARCHAR(20) DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                require_once __DIR__ . '/../helpers/DatabaseHelper.php';
                DatabaseHelper::safeExec($db, $sql, "Execute SQL");
            }
            
            $stmt = $db->query("SHOW TABLES LIKE 'expenses'");
            if ($stmt->rowCount() == 0) {
                $sql = "CREATE TABLE expenses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    category VARCHAR(100) NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    description TEXT,
                    status VARCHAR(20) DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                require_once __DIR__ . '/../helpers/DatabaseHelper.php';
                DatabaseHelper::safeExec($db, $sql, "Execute SQL");
            }
        } catch (Exception $e) {
            error_log('ensureTablesExist error: ' . $e->getMessage());
        }
    }
}
?>
