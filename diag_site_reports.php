<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$envFile = __DIR__ . '/.env.production';
foreach (file($envFile) as $line) {
    $line = trim($line);
    if (!$line || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $_ENV[trim($k)] = trim($v);
}

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'], $_ENV['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "DB OK: " . $_ENV['DB_NAME'] . "\n";

    // Check tables exist
    foreach (['site_reports','site_report_manpower','site_report_machinery','site_report_tasks','site_report_expenses'] as $t) {
        $r = $pdo->query("SHOW TABLES LIKE '$t'")->fetch();
        echo ($r ? "TABLE OK: $t" : "MISSING: $t") . "\n";
    }

    // Test the index query
    $stmt = $pdo->prepare("
        SELECT sr.*, u.name AS submitted_by_name,
               p.name AS project_name,
               COALESCE(SUM(sre.amount),0) AS total_expenses_requested
        FROM site_reports sr
        LEFT JOIN users u ON u.id = sr.submitted_by
        LEFT JOIN projects p ON p.id = sr.project_id
        LEFT JOIN site_report_expenses sre ON sre.report_id = sr.id
        GROUP BY sr.id
        ORDER BY sr.report_date DESC, sr.created_at DESC
        LIMIT 100
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "QUERY OK: " . count($rows) . " rows\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Check view files exist
foreach (['index','create','view','summary'] as $v) {
    $path = __DIR__ . "/views/site_reports/{$v}.php";
    echo (file_exists($path) ? "VIEW OK: $v" : "VIEW MISSING: $v") . "\n";
}

// Check controller exists
$ctrl = __DIR__ . '/app/controllers/SiteReportController.php';
echo (file_exists($ctrl) ? "CONTROLLER OK" : "CONTROLLER MISSING") . "\n";

// Check for syntax error in controller
$out = shell_exec('php -l ' . escapeshellarg($ctrl) . ' 2>&1');
echo "SYNTAX: $out";
