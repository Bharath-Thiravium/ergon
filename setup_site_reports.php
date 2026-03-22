<?php
// Show ALL errors — remove after use
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 60);

header('Content-Type: text/plain; charset=utf-8');
if (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);

echo "=== Site Report Module Setup ===\n\n";

// ── Step 0: Load DB credentials from .env.production ─────────────────────────
$envFile = __DIR__ . '/.env.production';
if (!file_exists($envFile)) {
    die("ERR: .env.production not found at $envFile\nUpload it to the server root first.\n");
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v);
}

$host   = $env['DB_HOST'] ?? 'localhost';
$dbname = $env['DB_NAME'] ?? '';
$user   = $env['DB_USER'] ?? '';
$pass   = $env['DB_PASS'] ?? '';

echo "DB Host: $host\n";
echo "DB Name: $dbname\n";
echo "DB User: $user\n\n";

// ── Step 1: Connect ───────────────────────────────────────────────────────────
try {
    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "Connected to MySQL OK\n\n";
} catch (Exception $e) {
    die("ERR: DB connection failed — " . $e->getMessage() . "\n");
}

// ── Helpers ───────────────────────────────────────────────────────────────────
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

// ── Create Tables ─────────────────────────────────────────────────────────────
echo "--- Creating tables ---\n";

run($db, 'site_reports', "
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

run($db, 'site_report_manpower', "
CREATE TABLE IF NOT EXISTS site_report_manpower (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    category ENUM('engineer','supervisor','ac_dc_team','mms_team','civil_mason','local_labour','driver_operator','other') NOT NULL,
    count INT DEFAULT 0,
    names JSON DEFAULT NULL,
    FOREIGN KEY (report_id) REFERENCES site_reports(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

run($db, 'site_report_machinery', "
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

run($db, 'site_report_tasks', "
CREATE TABLE IF NOT EXISTS site_report_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    task_description TEXT NOT NULL,
    sort_order TINYINT DEFAULT 0,
    FOREIGN KEY (report_id) REFERENCES site_reports(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

run($db, 'site_report_expenses', "
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
echo "\n--- Verifying ---\n";
foreach (['site_reports','site_report_manpower','site_report_machinery','site_report_tasks','site_report_expenses'] as $t) {
    try {
        $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "OK:   $t ($count rows)\n";
    } catch (Exception $e) {
        echo "MISSING: $t\n";
    }
}

echo "\n=== DONE: $ok created, $skip skipped ===\n";
echo "Delete this file from the server now.\n";
