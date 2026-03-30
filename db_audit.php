<?php
/**
 * ERGON Database Audit Script
 * READ-ONLY — zero writes, zero data modification
 * Audits: table existence, column mismatches, missing indexes,
 *         row counts, engine/charset, cross-table FK integrity,
 *         column name conflicts (check_in vs clock_in etc.)
 *
 * Access: http://localhost/ergon/db_audit.php
 * Restrict in production via IP check below.
 */

// ── Security: restrict to localhost / known IPs only ──────────────────────────
$allowed = ['127.0.0.1', '::1', '192.168.1.1'];
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!in_array($ip, $allowed) && php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Access denied.');
}

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(120);

// ── Bootstrap ─────────────────────────────────────────────────────────────────
require_once __DIR__ . '/app/config/environment.php';
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('<pre style="color:red">DB Connection failed: ' . $e->getMessage() . '</pre>');
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function q($db, $sql, $params = []) {
    $st = $db->prepare($sql);
    $st->execute($params);
    return $st;
}

function tableExists($db, $table) {
    $r = q($db, "SELECT COUNT(*) FROM information_schema.TABLES
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$table]);
    return (int)$r->fetchColumn() > 0;
}

function getColumns($db, $table) {
    $r = q($db, "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT,
                         COLUMN_KEY, EXTRA, CHARACTER_SET_NAME, COLLATION_NAME
                  FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                  ORDER BY ORDINAL_POSITION", [$table]);
    $cols = [];
    foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cols[$row['COLUMN_NAME']] = $row;
    }
    return $cols;
}

function getIndexes($db, $table) {
    $r = q($db, "SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE
                  FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                  ORDER BY INDEX_NAME, SEQ_IN_INDEX", [$table]);
    $idx = [];
    foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $idx[$row['INDEX_NAME']][] = $row['COLUMN_NAME'];
    }
    return $idx;
}

function getTableMeta($db, $table) {
    $r = q($db, "SELECT ENGINE, TABLE_COLLATION, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH,
                         CREATE_TIME, UPDATE_TIME
                  FROM information_schema.TABLES
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$table]);
    return $r->fetch(PDO::FETCH_ASSOC);
}

function rowCount($db, $table) {
    try {
        return (int)q($db, "SELECT COUNT(*) FROM `$table`")->fetchColumn();
    } catch (Exception $e) {
        return -1;
    }
}

// ── Expected schema registry ──────────────────────────────────────────────────
// Format: 'table' => ['required_columns' => [], 'required_indexes' => [], 'group' => '']
$EXPECTED = [

    // ── Core HR ───────────────────────────────────────────────────────────────
    'users' => [
        'group' => 'Core HR',
        'required_columns' => ['id','name','email','password','role','status','created_at'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'departments' => [
        'group' => 'Core HR',
        'required_columns' => ['id','name','status'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'settings' => [
        'group' => 'Core HR',
        'required_columns' => ['id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'enabled_modules' => [
        'group' => 'Core HR',
        'required_columns' => ['id','module_name'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'login_attempts' => [
        'group' => 'Core HR',
        'required_columns' => ['id','identifier','attempts','last_attempt'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'password_change_log' => [
        'group' => 'Core HR',
        'required_columns' => ['id','user_id','changed_at'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'user_preferences' => [
        'group' => 'Core HR',
        'required_columns' => ['id','user_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'user_sessions' => [
        'group' => 'Core HR',
        'required_columns' => ['id','user_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],

    // ── Attendance ────────────────────────────────────────────────────────────
    'attendance' => [
        'group' => 'Attendance',
        'required_columns' => ['id','user_id','check_in','check_out','status','created_at'],
        'required_indexes'  => ['PRIMARY'],
        // Detect legacy column name confusion
        'conflict_columns'  => [
            'clock_in'  => 'check_in',
            'clock_out' => 'check_out',
        ],
        'warn_columns'      => [],
    ],
    'attendance_conflicts' => [
        'group' => 'Attendance',
        'required_columns' => ['id','user_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'attendance_corrections' => [
        'group' => 'Attendance',
        'required_columns' => ['id','user_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'service_history' => [
        'group' => 'Attendance',
        'required_columns' => ['id','user_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],

    // ── Leaves ────────────────────────────────────────────────────────────────
    'leaves' => [
        'group' => 'Leaves',
        'required_columns' => ['id','user_id','leave_type','start_date','end_date','status','created_at'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],

    // ── Expenses & Advances ───────────────────────────────────────────────────
    'expenses' => [
        'group' => 'Expenses & Advances',
        'required_columns' => ['id','user_id','amount','status','created_at'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'advances' => [
        'group' => 'Expenses & Advances',
        'required_columns' => ['id','user_id','amount','status','created_at'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'approved_expenses' => [
        'group' => 'Expenses & Advances',
        'required_columns' => ['id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],

    // ── Tasks & Projects ──────────────────────────────────────────────────────
    'tasks' => [
        'group' => 'Tasks & Projects',
        'required_columns' => ['id','title','assigned_to','status','created_at'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'task_history' => [
        'group' => 'Tasks & Projects',
        'required_columns' => ['id','task_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'task_progress_history' => [
        'group' => 'Tasks & Projects',
        'required_columns' => ['id','task_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'task_categories' => [
        'group' => 'Tasks & Projects',
        'required_columns' => ['id','name'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'projects' => [
        'group' => 'Tasks & Projects',
        'required_columns' => ['id','name','status'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'project_subcategories' => [
        'group' => 'Tasks & Projects',
        'required_columns' => ['id','project_id','name'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'project_departments' => [
        'group' => 'Tasks & Projects',
        'required_columns' => ['id','project_id','department_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],

    // ── Daily Planner ─────────────────────────────────────────────────────────
    'daily_tasks' => [
        'group' => 'Daily Planner',
        'required_columns' => ['id','user_id','status','created_at'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'daily_task_history' => [
        'group' => 'Daily Planner',
        'required_columns' => ['id','daily_task_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'daily_planner' => [
        'group' => 'Daily Planner',
        'required_columns' => ['id','user_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'daily_workflow_status' => [
        'group' => 'Daily Planner',
        'required_columns' => ['id','user_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'sla_history' => [
        'group' => 'Daily Planner',
        'required_columns' => ['id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],

    // ── Notifications ─────────────────────────────────────────────────────────
    'notifications' => [
        'group' => 'Notifications',
        'required_columns' => ['id','user_id','message','is_read','created_at'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'notification_queue' => [
        'group' => 'Notifications',
        'required_columns' => ['id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'notification_preferences' => [
        'group' => 'Notifications',
        'required_columns' => ['id','user_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'notification_templates' => [
        'group' => 'Notifications',
        'required_columns' => ['id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'notification_audit_logs' => [
        'group' => 'Notifications',
        'required_columns' => ['id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],

    // ── Followups ─────────────────────────────────────────────────────────────
    'followups' => [
        'group' => 'Followups',
        'required_columns' => ['id','user_id','status','created_at'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'followup_history' => [
        'group' => 'Followups',
        'required_columns' => ['id','followup_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'contacts' => [
        'group' => 'Followups',
        'required_columns' => ['id','name'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],

    // ── Activity & Audit ──────────────────────────────────────────────────────
    'activity_logs' => [
        'group' => 'Activity & Audit',
        'required_columns' => ['id','user_id','action','created_at'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'rate_limit_log' => [
        'group' => 'Activity & Audit',
        'required_columns' => ['id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],

    // ── Gamification ──────────────────────────────────────────────────────────
    'badge_definitions' => [
        'group' => 'Gamification',
        'required_columns' => ['id','name'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'user_badges' => [
        'group' => 'Gamification',
        'required_columns' => ['id','user_id','badge_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'user_ledgers' => [
        'group' => 'Gamification',
        'required_columns' => ['id','user_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],

    // ── Site Reports ──────────────────────────────────────────────────────────
    'site_reports' => [
        'group' => 'Site Reports',
        'required_columns' => ['id','project_id','report_date','submitted_by','status'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'site_report_manpower' => [
        'group' => 'Site Reports',
        'required_columns' => ['id','report_id','category','count'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'site_report_machinery' => [
        'group' => 'Site Reports',
        'required_columns' => ['id','report_id','machine_type'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'site_report_tasks' => [
        'group' => 'Site Reports',
        'required_columns' => ['id','report_id','task_description'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'site_report_expenses' => [
        'group' => 'Site Reports',
        'required_columns' => ['id','report_id','amount','status'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],

    // ── Finance (MySQL mirror of PostgreSQL) ──────────────────────────────────
    'finance_companies' => [
        'group' => 'Finance',
        'required_columns' => ['company_id','company_prefix','company_name'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'finance_customer' => [
        'group' => 'Finance',
        'required_columns' => ['id','name','company_id'],
        'required_indexes'  => ['PRIMARY'],
        // finance_customers (plural) is the OLD table — flag if both exist
        'conflict_tables'   => ['finance_customers'],
        'warn_columns'      => [],
    ],
    'finance_invoices' => [
        'group' => 'Finance',
        'required_columns' => ['id','invoice_number','customer_id','company_id','total_amount','payment_status'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'finance_purchase_orders' => [
        'group' => 'Finance',
        'required_columns' => ['id','po_number','customer_id','company_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'finance_quotations' => [
        'group' => 'Finance',
        'required_columns' => ['id','quotation_number','customer_id','company_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'finance_payments' => [
        'group' => 'Finance',
        'required_columns' => ['id','payment_number','customer_id','company_id','amount'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'finance_consolidated' => [
        'group' => 'Finance',
        'required_columns' => ['id','record_type','document_number','company_prefix'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'finance_customershippingaddress' => [
        'group' => 'Finance',
        'required_columns' => ['id','customer_id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'invoices' => [
        'group' => 'Finance',
        'required_columns' => ['id'],
        'required_indexes'  => ['PRIMARY'],
        // invoices vs finance_invoices — flag dual existence
        'conflict_tables'   => ['finance_invoices'],
        'warn_columns'      => [],
    ],
    'sync_log' => [
        'group' => 'Finance',
        'required_columns' => ['id','table_name','sync_status'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
    'funnel_stats' => [
        'group' => 'Finance',
        'required_columns' => ['id'],
        'required_indexes'  => ['PRIMARY'],
        'warn_columns'      => [],
    ],
];

// ── Tables that should NOT coexist (duplicate/legacy pairs) ───────────────────
$CONFLICT_PAIRS = [
    ['finance_customer', 'finance_customers',  'finance_customers is the OLD schema; finance_customer is the current mirror'],
    ['invoices',         'finance_invoices',   'Raw invoices table conflicts with finance_invoices mirror — check which is active'],
    ['daily_plans',      'daily_tasks',        'daily_plans may be a legacy alias of daily_tasks'],
    ['daily_planner',    'daily_tasks',        'daily_planner and daily_tasks may overlap — verify distinct purpose'],
    ['followup_items',   'followups',          'followup_items may be legacy; followups is the active table'],
    ['accounts',         'user_ledgers',       'accounts may be a legacy finance table superseded by user_ledgers'],
    ['system_settings',  'settings',           'system_settings and settings may be duplicate config tables'],
];

// ── Audit engine ──────────────────────────────────────────────────────────────

$report = [
    'db_name'    => '',
    'db_version' => '',
    'run_at'     => date('Y-m-d H:i:s'),
    'groups'     => [],   // grouped table results
    'conflicts'  => [],   // dual-table conflicts
    'orphans'    => [],   // tables in DB not in registry
    'summary'    => ['total'=>0,'present'=>0,'missing'=>0,'warnings'=>0,'errors'=>0],
];

// DB meta
try {
    $report['db_name']    = q($db, "SELECT DATABASE()")->fetchColumn();
    $report['db_version'] = q($db, "SELECT VERSION()")->fetchColumn();
} catch (Exception $e) {}

// ── 1. Get every actual table in the database ─────────────────────────────────
$actualTables = [];
foreach (q($db, "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN) as $t) {
    $actualTables[$t] = true;
}

// ── 2. Audit each expected table ──────────────────────────────────────────────
foreach ($EXPECTED as $table => $spec) {
    $group   = $spec['group'];
    $exists  = isset($actualTables[$table]);
    $issues  = [];
    $notices = [];
    $meta    = null;
    $cols    = [];
    $indexes = [];
    $rows    = 0;

    $report['summary']['total']++;

    if (!$exists) {
        $report['summary']['missing']++;
        $report['summary']['errors']++;
        $issues[] = ['level'=>'ERROR', 'msg'=>"Table `$table` does NOT exist in the database"];
    } else {
        $report['summary']['present']++;
        $meta    = getTableMeta($db, $table);
        $cols    = getColumns($db, $table);
        $indexes = getIndexes($db, $table);
        $rows    = rowCount($db, $table);

        // 2a. Required columns
        foreach ($spec['required_columns'] as $col) {
            if (!isset($cols[$col])) {
                $issues[] = ['level'=>'ERROR', 'msg'=>"Missing required column `$col`"];
                $report['summary']['errors']++;
            }
        }

        // 2b. Required indexes
        foreach ($spec['required_indexes'] as $idx) {
            if (!isset($indexes[$idx])) {
                $issues[] = ['level'=>'WARN', 'msg'=>"Missing index `$idx`"];
                $report['summary']['warnings']++;
            }
        }

        // 2c. Column name conflicts (e.g. clock_in should be check_in)
        if (!empty($spec['conflict_columns'])) {
            foreach ($spec['conflict_columns'] as $wrong => $correct) {
                if (isset($cols[$wrong]) && !isset($cols[$correct])) {
                    $issues[] = ['level'=>'ERROR', 'msg'=>"Column `$wrong` found but should be `$correct` — wrong column name in use"];
                    $report['summary']['errors']++;
                } elseif (isset($cols[$wrong]) && isset($cols[$correct])) {
                    $issues[] = ['level'=>'WARN', 'msg'=>"Both `$wrong` AND `$correct` exist — duplicate/legacy column present"];
                    $report['summary']['warnings']++;
                }
            }
        }

        // 2d. Conflicting sibling tables
        if (!empty($spec['conflict_tables'])) {
            foreach ($spec['conflict_tables'] as $sibling) {
                if (isset($actualTables[$sibling])) {
                    $issues[] = ['level'=>'WARN', 'msg'=>"Sibling table `$sibling` also exists — possible duplicate schema"];
                    $report['summary']['warnings']++;
                }
            }
        }

        // 2e. Engine check (should be InnoDB)
        if ($meta && strtolower($meta['ENGINE'] ?? '') !== 'innodb') {
            $notices[] = ['level'=>'WARN', 'msg'=>"Engine is `{$meta['ENGINE']}` — expected InnoDB"];
            $report['summary']['warnings']++;
        }

        // 2f. Charset check (should be utf8mb4)
        if ($meta && !empty($meta['TABLE_COLLATION']) && strpos($meta['TABLE_COLLATION'], 'utf8mb4') === false) {
            $notices[] = ['level'=>'WARN', 'msg'=>"Collation `{$meta['TABLE_COLLATION']}` — expected utf8mb4_*"];
            $report['summary']['warnings']++;
        }

        // 2g. Suspicious zero-row tables (only warn for non-config tables)
        $skipZeroWarn = ['rate_limit_log','sla_history','daily_task_history','attendance_conflicts',
                         'attendance_corrections','notification_audit_logs','password_change_log',
                         'funnel_stats','sync_log','user_sessions'];
        if ($rows === 0 && !in_array($table, $skipZeroWarn)) {
            $notices[] = ['level'=>'INFO', 'msg'=>"Table is empty (0 rows) — may be unused or not yet populated"];
        }
    }

    $report['groups'][$group][] = [
        'table'   => $table,
        'exists'  => $exists,
        'rows'    => $rows,
        'meta'    => $meta,
        'cols'    => $cols,
        'indexes' => $indexes,
        'issues'  => $issues,
        'notices' => $notices,
    ];
}

// ── 3. Conflict pair checks ───────────────────────────────────────────────────
foreach ($CONFLICT_PAIRS as [$t1, $t2, $note]) {
    $e1 = isset($actualTables[$t1]);
    $e2 = isset($actualTables[$t2]);
    if ($e1 && $e2) {
        $r1 = rowCount($db, $t1);
        $r2 = rowCount($db, $t2);
        $report['conflicts'][] = [
            'table_a' => $t1, 'rows_a' => $r1,
            'table_b' => $t2, 'rows_b' => $r2,
            'note'    => $note,
        ];
        $report['summary']['warnings']++;
    }
}

// ── 4. Orphan tables (in DB but not in registry) ──────────────────────────────
$knownTables = array_keys($EXPECTED);
foreach (array_keys($actualTables) as $t) {
    if (!in_array($t, $knownTables)) {
        $rows = rowCount($db, $t);
        $meta = getTableMeta($db, $t);
        $report['orphans'][] = ['table'=>$t, 'rows'=>$rows, 'engine'=>$meta['ENGINE']??'?', 'collation'=>$meta['TABLE_COLLATION']??'?'];
    }
}

// ── 5. Cross-table FK integrity spot-checks (read-only COUNT queries) ─────────
$fkChecks = [
    ['label'=>'attendance.user_id → users.id',
     'sql'=>"SELECT COUNT(*) FROM attendance a LEFT JOIN users u ON a.user_id=u.id WHERE u.id IS NULL"],
    ['label'=>'tasks.assigned_to → users.id',
     'sql'=>"SELECT COUNT(*) FROM tasks t LEFT JOIN users u ON t.assigned_to=u.id WHERE u.id IS NULL"],
    ['label'=>'leaves.user_id → users.id',
     'sql'=>"SELECT COUNT(*) FROM leaves l LEFT JOIN users u ON l.user_id=u.id WHERE u.id IS NULL"],
    ['label'=>'expenses.user_id → users.id',
     'sql'=>"SELECT COUNT(*) FROM expenses e LEFT JOIN users u ON e.user_id=u.id WHERE u.id IS NULL"],
    ['label'=>'advances.user_id → users.id',
     'sql'=>"SELECT COUNT(*) FROM advances a LEFT JOIN users u ON a.user_id=u.id WHERE u.id IS NULL"],
    ['label'=>'notifications.user_id → users.id',
     'sql'=>"SELECT COUNT(*) FROM notifications n LEFT JOIN users u ON n.user_id=u.id WHERE u.id IS NULL"],
    ['label'=>'activity_logs.user_id → users.id',
     'sql'=>"SELECT COUNT(*) FROM activity_logs al LEFT JOIN users u ON al.user_id=u.id WHERE u.id IS NULL"],
    ['label'=>'daily_tasks.user_id → users.id',
     'sql'=>"SELECT COUNT(*) FROM daily_tasks dt LEFT JOIN users u ON dt.user_id=u.id WHERE u.id IS NULL"],
    ['label'=>'site_reports.submitted_by → users.id',
     'sql'=>"SELECT COUNT(*) FROM site_reports sr LEFT JOIN users u ON sr.submitted_by=u.id WHERE u.id IS NULL"],
    ['label'=>'finance_invoices.customer_id → finance_customer.id',
     'sql'=>"SELECT COUNT(*) FROM finance_invoices fi LEFT JOIN finance_customer fc ON fi.customer_id=fc.id WHERE fc.id IS NULL"],
];

$fkResults = [];
foreach ($fkChecks as $chk) {
    // Only run if both tables exist
    preg_match_all('/FROM\s+(\w+)|JOIN\s+(\w+)/', $chk['sql'], $m);
    $tablesInQuery = array_filter(array_merge($m[1], $m[2]));
    $allExist = true;
    foreach ($tablesInQuery as $t) {
        if (!isset($actualTables[$t])) { $allExist = false; break; }
    }
    if (!$allExist) {
        $fkResults[] = ['label'=>$chk['label'], 'orphans'=>null, 'skip'=>true];
        continue;
    }
    try {
        $orphans = (int)q($db, $chk['sql'])->fetchColumn();
        $fkResults[] = ['label'=>$chk['label'], 'orphans'=>$orphans, 'skip'=>false];
        if ($orphans > 0) $report['summary']['warnings']++;
    } catch (Exception $e) {
        $fkResults[] = ['label'=>$chk['label'], 'orphans'=>-1, 'skip'=>false, 'error'=>$e->getMessage()];
    }
}

// ── HTML Output ───────────────────────────────────────────────────────────────
$s = $report['summary'];
$scoreColor = $s['errors'] > 0 ? '#dc2626' : ($s['warnings'] > 0 ? '#d97706' : '#16a34a');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ERGON DB Audit — <?= htmlspecialchars($report['db_name']) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f1f5f9;color:#1e293b;font-size:13px}
.wrap{max-width:1300px;margin:0 auto;padding:20px}
h1{font-size:20px;font-weight:800;margin-bottom:4px}
.meta{color:#64748b;font-size:12px;margin-bottom:20px}
.summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:24px}
.sc{background:#fff;border-radius:10px;padding:14px 16px;box-shadow:0 1px 4px rgba(0,0,0,.07);text-align:center}
.sc__val{font-size:26px;font-weight:800}
.sc__lbl{font-size:11px;color:#64748b;margin-top:3px;text-transform:uppercase;letter-spacing:.4px}
.green{color:#16a34a}.red{color:#dc2626}.yellow{color:#d97706}.blue{color:#2563eb}.gray{color:#64748b}
.section{margin-bottom:28px}
.section-title{font-size:14px;font-weight:700;padding:8px 14px;background:#e2e8f0;border-radius:8px 8px 0 0;border-left:4px solid #3b82f6;display:flex;align-items:center;justify-content:space-between}
.section-title span{font-size:11px;font-weight:500;color:#64748b}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:0 0 8px 8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.07)}
th{background:#f8fafc;padding:8px 12px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap}
td{padding:8px 12px;border-bottom:1px solid #f1f5f9;vertical-align:top}
tr:last-child td{border-bottom:none}
tr:hover td{background:#f8fafc}
.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;white-space:nowrap}
.badge-ok{background:#dcfce7;color:#16a34a}
.badge-miss{background:#fee2e2;color:#dc2626}
.badge-warn{background:#fef9c3;color:#b45309}
.badge-info{background:#dbeafe;color:#1d4ed8}
.issue{padding:3px 0;font-size:12px}
.issue.ERROR{color:#dc2626}
.issue.WARN{color:#d97706}
.issue.INFO{color:#2563eb}
.cols-list{font-size:11px;color:#475569;line-height:1.8}
.cols-list .missing{color:#dc2626;font-weight:700}
.conflict-box{background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.07);padding:14px 16px;margin-bottom:10px;border-left:4px solid #f59e0b}
.fk-row{display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:12px}
.fk-row:last-child{border-bottom:none}
.orphan-table{background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.07);overflow:hidden;margin-bottom:0}
details summary{cursor:pointer;padding:6px 0;font-size:12px;color:#3b82f6;user-select:none}
details summary:hover{color:#1d4ed8}
.toc{background:#fff;border-radius:10px;padding:14px 18px;box-shadow:0 1px 4px rgba(0,0,0,.07);margin-bottom:24px;display:flex;flex-wrap:wrap;gap:8px}
.toc a{font-size:12px;color:#3b82f6;text-decoration:none;padding:3px 10px;border-radius:12px;background:#eff6ff}
.toc a:hover{background:#dbeafe}
</style>
</head>
<body>
<div class="wrap">

<h1>🗄️ ERGON Database Audit</h1>
<div class="meta">
    Database: <strong><?= htmlspecialchars($report['db_name']) ?></strong> &nbsp;|&nbsp;
    MySQL: <strong><?= htmlspecialchars($report['db_version']) ?></strong> &nbsp;|&nbsp;
    Run at: <strong><?= $report['run_at'] ?></strong> &nbsp;|&nbsp;
    Mode: <strong style="color:#16a34a">READ-ONLY</strong>
</div>

<!-- Summary cards -->
<div class="summary">
    <div class="sc"><div class="sc__val blue"><?= $s['total'] ?></div><div class="sc__lbl">Expected Tables</div></div>
    <div class="sc"><div class="sc__val green"><?= $s['present'] ?></div><div class="sc__lbl">Present</div></div>
    <div class="sc"><div class="sc__val red"><?= $s['missing'] ?></div><div class="sc__lbl">Missing</div></div>
    <div class="sc"><div class="sc__val yellow"><?= $s['warnings'] ?></div><div class="sc__lbl">Warnings</div></div>
    <div class="sc"><div class="sc__val red"><?= $s['errors'] ?></div><div class="sc__lbl">Errors</div></div>
    <div class="sc"><div class="sc__val gray"><?= count($report['orphans']) ?></div><div class="sc__lbl">Orphan Tables</div></div>
    <div class="sc"><div class="sc__val gray"><?= count($actualTables) ?></div><div class="sc__lbl">Total in DB</div></div>
    <div class="sc">
        <div class="sc__val" style="color:<?= $scoreColor ?>">
            <?= $s['errors'] > 0 ? 'FAIL' : ($s['warnings'] > 0 ? 'WARN' : 'PASS') ?>
        </div>
        <div class="sc__lbl">Overall Status</div>
    </div>
</div>

<!-- TOC -->
<div class="toc">
    <?php foreach (array_keys($report['groups']) as $g): ?>
    <a href="#grp-<?= urlencode($g) ?>"><?= htmlspecialchars($g) ?></a>
    <?php endforeach; ?>
    <a href="#conflicts">⚠️ Conflicts</a>
    <a href="#fk">🔗 FK Integrity</a>
    <a href="#orphans">👻 Orphans</a>
</div>

<!-- Per-group table results -->
<?php foreach ($report['groups'] as $group => $tables): ?>
<?php
$gErrors   = array_sum(array_map(fn($t) => count(array_filter($t['issues'], fn($i) => $i['level']==='ERROR')), $tables));
$gWarnings = array_sum(array_map(fn($t) => count(array_filter(array_merge($t['issues'],$t['notices']), fn($i) => $i['level']==='WARN')), $tables));
$gBadge    = $gErrors > 0 ? 'badge-miss' : ($gWarnings > 0 ? 'badge-warn' : 'badge-ok');
$gLabel    = $gErrors > 0 ? "$gErrors error(s)" : ($gWarnings > 0 ? "$gWarnings warning(s)" : 'OK');
?>
<div class="section" id="grp-<?= urlencode($group) ?>">
    <div class="section-title">
        <?= htmlspecialchars($group) ?>
        <span><span class="badge <?= $gBadge ?>"><?= $gLabel ?></span></span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Table</th>
                <th>Status</th>
                <th>Rows</th>
                <th>Engine</th>
                <th>Collation</th>
                <th>Columns (required)</th>
                <th>Indexes</th>
                <th>Issues / Notices</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tables as $t): ?>
        <?php
        $hasError = !empty(array_filter($t['issues'], fn($i) => $i['level']==='ERROR'));
        $hasWarn  = !empty(array_filter(array_merge($t['issues'],$t['notices']), fn($i) => $i['level']==='WARN'));
        $rowStyle = $hasError ? 'background:#fff5f5' : ($hasWarn ? 'background:#fffbeb' : '');
        ?>
        <tr style="<?= $rowStyle ?>">
            <td><strong><?= htmlspecialchars($t['table']) ?></strong></td>
            <td>
                <?php if ($t['exists']): ?>
                    <span class="badge badge-ok">✓ Exists</span>
                <?php else: ?>
                    <span class="badge badge-miss">✗ Missing</span>
                <?php endif; ?>
            </td>
            <td><?= $t['exists'] ? number_format($t['rows']) : '—' ?></td>
            <td><?= $t['exists'] ? htmlspecialchars($t['meta']['ENGINE'] ?? '?') : '—' ?></td>
            <td style="font-size:11px"><?= $t['exists'] ? htmlspecialchars($t['meta']['TABLE_COLLATION'] ?? '?') : '—' ?></td>
            <td>
                <?php if ($t['exists'] && !empty($EXPECTED[$t['table']]['required_columns'])): ?>
                <div class="cols-list">
                <?php foreach ($EXPECTED[$t['table']]['required_columns'] as $rc): ?>
                    <?php if (isset($t['cols'][$rc])): ?>
                        <span style="color:#16a34a">✓ <?= $rc ?></span>&nbsp;
                    <?php else: ?>
                        <span class="missing">✗ <?= $rc ?></span>&nbsp;
                    <?php endif; ?>
                <?php endforeach; ?>
                </div>
                <?php if (count($t['cols']) > 0): ?>
                <details><summary>All <?= count($t['cols']) ?> columns</summary>
                <div class="cols-list" style="margin-top:4px">
                <?php foreach ($t['cols'] as $cn => $cd): ?>
                    <span title="<?= htmlspecialchars($cd['COLUMN_TYPE']) ?>"><?= htmlspecialchars($cn) ?></span>
                    <small style="color:#94a3b8">(<?= htmlspecialchars($cd['COLUMN_TYPE']) ?>)</small>&nbsp;
                <?php endforeach; ?>
                </div></details>
                <?php endif; ?>
                <?php elseif (!$t['exists']): ?>
                <span style="color:#94a3b8">—</span>
                <?php endif; ?>
            </td>
            <td style="font-size:11px">
                <?php if ($t['exists'] && !empty($t['indexes'])): ?>
                <?php foreach ($t['indexes'] as $iname => $icols): ?>
                    <div><?= htmlspecialchars($iname) ?>: <span style="color:#64748b"><?= implode(', ', $icols) ?></span></div>
                <?php endforeach; ?>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td>
                <?php foreach ($t['issues'] as $iss): ?>
                    <div class="issue <?= $iss['level'] ?>">
                        <?= $iss['level']==='ERROR' ? '🔴' : '🟡' ?> <?= htmlspecialchars($iss['msg']) ?>
                    </div>
                <?php endforeach; ?>
                <?php foreach ($t['notices'] as $n): ?>
                    <div class="issue <?= $n['level'] ?>">
                        <?= $n['level']==='INFO' ? '🔵' : '🟡' ?> <?= htmlspecialchars($n['msg']) ?>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($t['issues']) && empty($t['notices'])): ?>
                    <span style="color:#16a34a;font-size:12px">✓ No issues</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

<!-- Conflict pairs -->
<div class="section" id="conflicts">
    <div class="section-title">
        ⚠️ Duplicate / Conflicting Table Pairs
        <span><?= count($report['conflicts']) ?> active conflict(s)</span>
    </div>
    <?php if (empty($report['conflicts'])): ?>
    <div style="background:#fff;padding:14px 16px;border-radius:0 0 8px 8px;box-shadow:0 1px 4px rgba(0,0,0,.07);color:#16a34a;font-size:13px">
        ✓ No conflicting table pairs found
    </div>
    <?php else: ?>
    <div style="background:#fff;padding:14px 16px;border-radius:0 0 8px 8px;box-shadow:0 1px 4px rgba(0,0,0,.07)">
        <?php foreach ($report['conflicts'] as $c): ?>
        <div class="conflict-box">
            <div style="font-weight:700;margin-bottom:6px">
                🟡 <code><?= htmlspecialchars($c['table_a']) ?></code>
                &nbsp;↔&nbsp;
                <code><?= htmlspecialchars($c['table_b']) ?></code>
            </div>
            <div style="font-size:12px;color:#92400e;margin-bottom:6px"><?= htmlspecialchars($c['note']) ?></div>
            <div style="font-size:12px;color:#64748b">
                <strong><?= htmlspecialchars($c['table_a']) ?></strong>: <?= number_format($c['rows_a']) ?> rows &nbsp;|&nbsp;
                <strong><?= htmlspecialchars($c['table_b']) ?></strong>: <?= number_format($c['rows_b']) ?> rows
            </div>
            <div style="font-size:11px;color:#b45309;margin-top:6px">
                ⚠️ Action required: verify which table is actively used by the application and drop or archive the other.
                This is READ-ONLY — no changes made.
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- FK Integrity -->
<div class="section" id="fk">
    <div class="section-title">
        🔗 Cross-Table FK Integrity (orphan row checks)
        <span>Read-only COUNT queries</span>
    </div>
    <div style="background:#fff;padding:14px 16px;border-radius:0 0 8px 8px;box-shadow:0 1px 4px rgba(0,0,0,.07)">
        <?php foreach ($fkResults as $fk): ?>
        <div class="fk-row">
            <div style="flex:1"><?= htmlspecialchars($fk['label']) ?></div>
            <?php if ($fk['skip']): ?>
                <span class="badge badge-info">SKIPPED — table(s) missing</span>
            <?php elseif (isset($fk['error'])): ?>
                <span class="badge badge-warn">ERROR: <?= htmlspecialchars($fk['error']) ?></span>
            <?php elseif ($fk['orphans'] === 0): ?>
                <span class="badge badge-ok">✓ 0 orphans</span>
            <?php else: ?>
                <span class="badge badge-miss">🔴 <?= number_format($fk['orphans']) ?> orphan row(s)</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Orphan tables -->
<div class="section" id="orphans">
    <div class="section-title">
        👻 Orphan Tables (in DB but not in audit registry)
        <span><?= count($report['orphans']) ?> table(s)</span>
    </div>
    <?php if (empty($report['orphans'])): ?>
    <div style="background:#fff;padding:14px 16px;border-radius:0 0 8px 8px;box-shadow:0 1px 4px rgba(0,0,0,.07);color:#16a34a;font-size:13px">
        ✓ No orphan tables
    </div>
    <?php else: ?>
    <table class="orphan-table">
        <thead>
            <tr>
                <th>Table Name</th>
                <th>Rows</th>
                <th>Engine</th>
                <th>Collation</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($report['orphans'] as $o): ?>
        <tr>
            <td><code><?= htmlspecialchars($o['table']) ?></code></td>
            <td><?= number_format($o['rows']) ?></td>
            <td><?= htmlspecialchars($o['engine']) ?></td>
            <td style="font-size:11px"><?= htmlspecialchars($o['collation']) ?></td>
            <td style="font-size:11px;color:#64748b">
                <?php if ($o['rows'] === 0): ?>
                    <span class="badge badge-warn">Empty — candidate for removal</span>
                <?php else: ?>
                    <span class="badge badge-info">Has data — review before action</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Footer -->
<div style="margin-top:30px;padding:14px 18px;background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.07);font-size:12px;color:#64748b">
    <strong>⚠️ Important:</strong> This script is <strong>100% read-only</strong> — it only runs SELECT and SHOW queries.
    No data was modified, inserted, or deleted. &nbsp;|&nbsp;
    Delete or restrict access to this file after use in production.
    &nbsp;|&nbsp; Generated: <?= $report['run_at'] ?>
</div>

</div><!-- .wrap -->
</body>
</html>
