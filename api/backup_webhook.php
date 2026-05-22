<?php
/**
 * Web-triggered backup endpoint for shared hosting (production).
 * Called by an external cron service (e.g. cron-job.org) daily.
 *
 * URLs:
 *   https://athenas.co.in/ergon/api/backup_webhook.php?token=ergon_athenas_bkp_2025
 *   https://aes.athenas.co.in/ergon/api/backup_webhook.php?token=ergon_aes_bkp_2025
 *   https://bkgreenenergy.com/ergon/api/backup_webhook.php?token=ergon_bkg_bkp_2025
 */

header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata');

define('RETENTION_DAYS', 45);
define('BACKUP_DIR', __DIR__ . '/../storage/backups/');

// ── Load .env ─────────────────────────────────────────────────────────────────
$envFile = __DIR__ . '/../.env';
$env = [];
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v);
        }
    }
}

$dbHost = $env['DB_HOST'] ?? 'localhost';
$dbName = $env['DB_NAME'] ?? '';
$dbUser = $env['DB_USER'] ?? '';
$dbPass = $env['DB_PASS'] ?? '';

// ── Security: token from .env ─────────────────────────────────────────────────
// Add BACKUP_WEBHOOK_TOKEN=your_secret to each server's .env
$expectedToken = $env['BACKUP_WEBHOOK_TOKEN'] ?? '';
if ($expectedToken === '' || ($_GET['token'] ?? '') !== $expectedToken) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Forbidden']));
}

// ── Ensure backup directory ───────────────────────────────────────────────────
if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}
$htaccess = BACKUP_DIR . '.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\nOptions -Indexes\n");
}

if (!$dbName) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'DB_NAME not configured']));
}

// ── Idempotent: skip if today's backup already exists ────────────────────────
$filename = 'backup_' . date('Y-m-d') . '_auto.sql';
$filepath = BACKUP_DIR . $filename;

if (file_exists($filepath) && filesize($filepath) > 100) {
    echo json_encode(['success' => true, 'message' => "Today's backup already exists: {$filename}", 'skipped' => true]);
    exit;
}

// ── PHP streaming dump (no exec/mysqldump needed) ────────────────────────────
@set_time_limit(300);
@ini_set('memory_limit', '256M');

try {
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
    $db  = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $fh = fopen($filepath, 'w');
    if (!$fh) throw new Exception('Cannot open backup file for writing');

    fwrite($fh, "-- ERGON Automated Backup (Web Cron)\n");
    fwrite($fh, "-- Generated : " . date('Y-m-d H:i:s') . "\n");
    fwrite($fh, "-- Database  : {$dbName}\n\n");
    fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

    $tables = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $create = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n" . $create[1] . ";\n\n");

        $cols = null;
        $chunk = [];
        $stmt = $db->query("SELECT * FROM `{$table}`");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($cols === null) $cols = '`' . implode('`, `', array_keys($row)) . '`';
            $chunk[] = '(' . implode(', ', array_map(fn($v) => $v === null ? 'NULL' : $db->quote((string)$v), $row)) . ')';
            if (count($chunk) >= 200) {
                fwrite($fh, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $chunk) . ";\n");
                $chunk = [];
            }
        }
        if ($chunk) {
            fwrite($fh, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $chunk) . ";\n");
        }
        fwrite($fh, "\n");
    }

    fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);

    $size = filesize($filepath);
    if ($size < 10) throw new Exception('Backup file is empty after writing');

    // ── Prune old backups ─────────────────────────────────────────────────────
    $cutoff  = time() - (RETENTION_DAYS * 86400);
    $deleted = 0;
    foreach (glob(BACKUP_DIR . '*.sql') ?: [] as $f) {
        if (filemtime($f) < $cutoff && @unlink($f)) $deleted++;
    }

    echo json_encode([
        'success'   => true,
        'filename'  => $filename,
        'size'      => round($size / 1024, 1) . ' KB',
        'tables'    => count($tables),
        'pruned'    => $deleted,
        'timestamp' => date('Y-m-d H:i:s'),
    ]);

} catch (Exception $e) {
    if (isset($fh) && is_resource($fh)) fclose($fh);
    if (file_exists($filepath)) @unlink($filepath);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
