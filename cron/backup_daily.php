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

    $dumped    = false;
    $mysqldump = findMysqldump();

    if ($mysqldump) {
        $log("Using mysqldump: {$mysqldump}");
        $passArg = $dbPass !== '' ? '-p' . escapeshellarg($dbPass) : '';
        $cmd = sprintf(
            '%s --no-tablespaces --single-transaction --quick -h %s -u %s %s %s > %s 2>/dev/null',
            escapeshellcmd($mysqldump),
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            $passArg,
            escapeshellarg($dbName),
            escapeshellarg($filepath)
        );
        exec($cmd, $output, $rc);
        if ($rc === 0 && file_exists($filepath) && filesize($filepath) > 100) {
            $dumped = true;
        } else {
            $log('mysqldump failed (rc=' . $rc . '). Falling back to PHP dump.');
        }
    }

    if (!$dumped) {
        $log('Using PHP streaming dump.');
        phpDump($filepath, $dbName, $log);
    }

    if (file_exists($filepath) && filesize($filepath) > 10) {
        $log('Backup created successfully. Size: ' . formatSize(filesize($filepath)));
    } else {
        $log('ERROR: Backup file missing or empty after creation.');
        exit(1);
    }
}

// ── Prune: rolling 45-day window ─────────────────────────────────────────────
$log('Pruning backups older than ' . RETENTION_DAYS . ' days...');
$cutoff  = time() - (RETENTION_DAYS * 86400);
$allSql  = glob(BACKUP_DIR . '*.sql') ?: [];
$deleted = 0;

foreach ($allSql as $file) {
    if (filemtime($file) < $cutoff) {
        if (@unlink($file)) {
            $log('Deleted expired backup: ' . basename($file));
            $deleted++;
        } else {
            $log('WARNING: Could not delete ' . basename($file));
        }
    }
}

$remaining = count(glob(BACKUP_DIR . '*.sql') ?: []);
$log("Pruning done. Deleted: {$deleted}. Remaining: {$remaining}.");
$log('Daily backup cron completed successfully.');
exit(0);

// ── Helper functions ──────────────────────────────────────────────────────────

function findMysqldump(): ?string {
    $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

    if ($isWin) {
        $candidates = array_merge(
            glob('C:/laragon/bin/mysql/*/bin/mysqldump.exe') ?: [],
            ['C:/xampp/mysql/bin/mysqldump.exe']
        );
    } else {
        $candidates = [
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
        ];
    }

    foreach ($candidates as $c) {
        if (file_exists($c) && is_executable($c)) return $c;
    }

    // PATH fallback (only if exec is available)
    if (function_exists('exec')) {
        $disabled = array_map('trim', explode(',', ini_get('disable_functions') ?? ''));
        if (!in_array('exec', $disabled)) {
            $which = $isWin ? 'where' : 'which';
            exec($which . ' mysqldump 2>/dev/null', $out, $rc);
            if ($rc === 0 && !empty($out[0])) return trim($out[0]);
        }
    }
    return null;
}

function phpDump(string $filepath, string $dbName, callable $log): void {
    @set_time_limit(300);
    @ini_set('memory_limit', '256M');

    try {
        $db = Database::connect();

        $fh = fopen($filepath, 'w');
        if (!$fh) {
            $log('ERROR: Cannot open file for writing: ' . $filepath);
            return;
        }

        fwrite($fh, "-- ERGON Automated Backup\n");
        fwrite($fh, "-- Generated : " . date('Y-m-d H:i:s') . "\n");
        fwrite($fh, "-- Database  : {$dbName}\n\n");
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

        $tables = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
        $log('Dumping ' . count($tables) . ' tables...');

        foreach ($tables as $table) {
            $create = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
            fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($fh, $create[1] . ";\n\n");

            $count = (int)$db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
            if ($count === 0) continue;

            $cols  = null;
            $chunk = [];
            $stmt  = $db->query("SELECT * FROM `{$table}`");

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($cols === null) {
                    $cols = '`' . implode('`, `', array_keys($row)) . '`';
                }
                $esc     = array_map(fn($v) => $v === null ? 'NULL' : $db->quote((string)$v), $row);
                $chunk[] = '(' . implode(', ', $esc) . ')';

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
