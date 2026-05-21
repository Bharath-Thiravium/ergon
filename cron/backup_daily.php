#!/usr/bin/env php
<?php
/**
 * Daily Automated Database Backup
 * Schedule: 0 3 * * *  (every day at 3:00 AM)
 *
 * - Creates a timestamped .sql backup in storage/backups/
 * - Keeps exactly 45 days of backups (rolling window)
 * - On day 46 the day-1 backup is deleted automatically
 * - Falls back to PHP-based dump if mysqldump is not found
 *
 * Linux/Mac cron:
 *   0 3 * * * /usr/bin/php /path/to/ergon/cron/backup_daily.php >> /var/log/ergon_backup.log 2>&1
 *
 * Windows Task Scheduler:
 *   Program : php.exe
 *   Arguments: C:\laragon\www\ergon\cron\backup_daily.php
 *   Trigger  : Daily at 03:00
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

date_default_timezone_set('Asia/Kolkata');

define('RETENTION_DAYS', 45);
define('BACKUP_DIR', __DIR__ . '/../storage/backups/');
define('LOG_PREFIX', '[' . date('Y-m-d H:i:s') . '] ');

$log = function(string $msg) {
    echo LOG_PREFIX . $msg . PHP_EOL;
};

// ── Bootstrap ─────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../app/config/database.php';

// ── Ensure backup directory exists ───────────────────────────────────────────
if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
    $log('Created backup directory: ' . BACKUP_DIR);
}

// Block direct web access
$htaccess = BACKUP_DIR . '.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\n");
}

// ── Read DB config ────────────────────────────────────────────────────────────
$cfg    = Database::getPostgreSQLConfig()['mysql'];
$dbHost = $cfg['host'];
$dbName = $cfg['database'];
$dbUser = $cfg['username'];
$dbPass = $cfg['password'];

if (empty($dbName)) {
    $log('ERROR: DB_NAME not configured. Aborting.');
    exit(1);
}

// ── Build backup filename ─────────────────────────────────────────────────────
$filename = 'backup_' . date('Y-m-d') . '_auto.sql';
$filepath = BACKUP_DIR . $filename;

// Skip if today's auto backup already exists (idempotent)
if (file_exists($filepath) && filesize($filepath) > 100) {
    $log("Today's backup already exists ({$filename}). Skipping creation.");
} else {
    $log("Starting backup → {$filename}");

    $mysqldump = findMysqldump();

    if ($mysqldump) {
        $log("Using mysqldump: {$mysqldump}");
        $passArg = $dbPass !== '' ? '-p' . escapeshellarg($dbPass) : '';
        $cmd = sprintf(
            '%s --no-tablespaces -h %s -u %s %s %s > %s 2>&1',
            escapeshellcmd($mysqldump),
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            $passArg,
            escapeshellarg($dbName),
            escapeshellarg($filepath)
        );
        exec($cmd, $output, $rc);

        if ($rc !== 0 || !file_exists($filepath) || filesize($filepath) < 100) {
            $log('mysqldump failed (rc=' . $rc . '). Falling back to PHP dump.');
            if (!empty($output)) $log('mysqldump output: ' . implode(' | ', $output));
            phpDump($filepath, $dbName, $log);
        }
    } else {
        $log('mysqldump not found. Using PHP dump.');
        phpDump($filepath, $dbName, $log);
    }

    if (file_exists($filepath) && filesize($filepath) > 10) {
        $size = formatSize(filesize($filepath));
        $log("Backup created successfully. Size: {$size}");
    } else {
        $log('ERROR: Backup file missing or empty after creation.');
        exit(1);
    }
}

// ── Prune: delete backups older than RETENTION_DAYS (rolling 45-day window) ──
$log('Pruning backups older than ' . RETENTION_DAYS . ' days...');
$cutoff  = time() - (RETENTION_DAYS * 86400);
$allSql  = glob(BACKUP_DIR . '*.sql') ?: [];
$deleted = 0;

foreach ($allSql as $file) {
    if (filemtime($file) < $cutoff) {
        $fname = basename($file);
        if (@unlink($file)) {
            $log("Deleted expired backup: {$fname}");
            $deleted++;
        } else {
            $log("WARNING: Could not delete {$fname}");
        }
    }
}

$remaining = count(glob(BACKUP_DIR . '*.sql') ?: []);
$log("Pruning done. Deleted: {$deleted}. Remaining backups: {$remaining}.");
$log('Daily backup cron completed successfully.');
exit(0);

// ── Helper functions ──────────────────────────────────────────────────────────

function findMysqldump(): ?string {
    $candidates = ['mysqldump'];
    // Auto-detect Laragon MySQL versions
    $laragon = glob('C:/laragon/bin/mysql/*/bin/mysqldump.exe') ?: [];
    $candidates = array_merge($laragon, $candidates, [
        'C:/xampp/mysql/bin/mysqldump.exe',
        '/usr/bin/mysqldump',
        '/usr/local/bin/mysqldump',
        '/opt/homebrew/bin/mysqldump',
    ]);
    foreach ($candidates as $c) {
        if (strpos($c, '/') === false && strpos($c, '\\') === false) {
            // Plain command — check PATH
            $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
            exec(($isWin ? 'where ' : 'which ') . escapeshellarg($c) . ' 2>nul', $out, $rc);
            if ($rc === 0 && !empty($out)) return $c;
        } elseif (file_exists($c)) {
            return $c;
        }
    }
    return null;
}

function phpDump(string $filepath, string $dbName, callable $log): void {
    try {
        $db = Database::connect();
        $out  = "-- ERGON Automated Backup\n";
        $out .= "-- Generated : " . date('Y-m-d H:i:s') . "\n";
        $out .= "-- Database  : {$dbName}\n\n";
        $out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $create = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
            $out .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $out .= $create[1] . ";\n\n";

            $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) {
                $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
                foreach (array_chunk($rows, 200) as $chunk) {
                    $vals = [];
                    foreach ($chunk as $row) {
                        $esc   = array_map(fn($v) => $v === null ? 'NULL' : $db->quote((string)$v), $row);
                        $vals[] = '(' . implode(', ', $esc) . ')';
                    }
                    $out .= "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $vals) . ";\n";
                }
                $out .= "\n";
            }
        }
        $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($filepath, $out);
        $log('PHP dump completed for ' . count($tables) . ' tables.');
    } catch (Exception $e) {
        $log('PHP dump error: ' . $e->getMessage());
    }
}

function formatSize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
