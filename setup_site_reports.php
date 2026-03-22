<?php
ini_set('max_execution_time', 60);
require_once __DIR__ . '/app/config/database.php';
header('Content-Type: text/plain');
if (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);

$db = Database::connect();
$ok = 0; $skip = 0;

function run($db, $label, $sql) {
    global $ok, $skip;
    try {
        $db->exec($sql);
        echo "OK:   $label\n"; flush();
        $ok++;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'already exists') !== false) {
            echo "SKIP: $label (already exists)\n"; flush();
            $skip++;
        } else {
            echo "ERR:  $label — $msg\n"; flush();
        }
    }
}

function addCol($db, $table, $column, $definition) {
    global $ok, $skip;
    $rows = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->fetchAll();
    if ($rows) {
        echo "SKIP: $table.$column (already exists)\n"; flush();
        $skip++;
    } else {
        try {
            $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "OK:   $table.$column added\n"; flush();
            $ok++;
        } catch (Exception $e) {
            echo "ERR:  $table.$column — " . $e->getMessage() . "\n"; flush();
        }
    }
}

echo "=== Site Report Module Setup ===\n\n";

// ── Table 1: site_reports ─────────────────────────────────────────────────────
echo "--- site_reports ---\n";
run($db, 'CREATE site_reports', "
CREATE TABLE IF NOT EXISTS site_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT DEFAULT NULL,
    project_id INT DEFAULT NULL,
    site_name VARCHAR(255) NOT NULL,
    report_date DATE NOT NULL,
    submitted_by INT NOT NULL,
    total_manpower INT DEFAULT 0,
    remarks TEXT DEFAULT NULL,
    status ENUM('draft','submitted','acknowledged') DEFAULT 'submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_site_date (site_name(100), report_date),
    INDEX idx_report_date (report_date),
    INDEX idx_project_id (project_id),
    INDEX idx_submitted_by (submitted_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ── Table 2: site_report_manpower ─────────────────────────────────────────────
echo "\n--- site_report_manpower ---\n";
run($db, 'CREATE site_report_manpower', "
CREATE TABLE IF NOT EXISTS site_report_manpower (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    category ENUM(
        'engineer','supervisor','ac_dc_team','mms_team',
        'civil_mason','local_labour','driver_operator','other'
    ) NOT NULL,
    count INT DEFAULT 0,
    names JSON DEFAULT NULL,
    FOREIGN KEY (report_id) REFERENCES site_reports(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ── Table 3: site_report_machinery ────────────────────────────────────────────
echo "\n--- site_report_machinery ---\n";
run($db, 'CREATE site_report_machinery', "
CREATE TABLE IF NOT EXISTS site_report_machinery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    machine_type ENUM('tractor','jcb','hydra','tata_ace','dg','crane','other') NOT NULL,
    machine_label VARCHAR(100) DEFAULT NULL,
    count INT DEFAULT 0,
    hours_worked DECIMAL(5,2) DEFAULT NULL,
    fuel_litres DECIMAL(8,2) DEFAULT NULL,
    remarks VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (report_id) REFERENCES site_reports(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ── Table 4: site_report_tasks ────────────────────────────────────────────────
echo "\n--- site_report_tasks ---\n";
run($db, 'CREATE site_report_tasks', "
CREATE TABLE IF NOT EXISTS site_report_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    task_description TEXT NOT NULL,
    sort_order TINYINT DEFAULT 0,
    FOREIGN KEY (report_id) REFERENCES site_reports(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ── Table 5: site_report_expenses ─────────────────────────────────────────────
echo "\n--- site_report_expenses ---\n";
run($db, 'CREATE site_report_expenses', "
CREATE TABLE IF NOT EXISTS site_report_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_type ENUM('labour','machinery','transport','fuel','site_expense','advance','other') DEFAULT 'other',
    status ENUM('pending','approved','rejected','processed') DEFAULT 'pending',
    linked_expense_id INT DEFAULT NULL,
    FOREIGN KEY (report_id) REFERENCES site_reports(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ── Verify ────────────────────────────────────────────────────────────────────
echo "\n=== Verify table counts ===\n";
$tables = ['site_reports','site_report_manpower','site_report_machinery','site_report_tasks','site_report_expenses'];
foreach ($tables as $t) {
    try {
        $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "$t: $count rows\n";
    } catch (Exception $e) {
        echo "MISSING: $t — " . $e->getMessage() . "\n";
    }
}

echo "\n=== DONE: $ok created, $skip skipped ===\n";
echo "Safe to delete this file after running.\n";
