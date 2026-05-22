<?php
/**
 * Backup Script — DB dump + uploads archive
 * Intended to run via cron or the /api/backup/run endpoint (owner only).
 */

// Load DB credentials from .env
$envFile = __DIR__ . '/../.env';
$env = [];
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v);
        }
    }
}

$dbHost = $env['DB_HOST'] ?? 'localhost';
$dbName = $env['DB_NAME'] ?? '';
$dbUser = $env['DB_USER'] ?? '';
$dbPass = $env['DB_PASS'] ?? '';

if (!$dbName) {
    exit("Backup aborted: DB_NAME not configured.\n");
}

$date      = date('Y-m-d_H-i');
$dateDir   = date('Y-m-d');
$backupDir = realpath(__DIR__ . '/../storage') . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $dateDir;

if (!is_dir($backupDir) && !mkdir($backupDir, 0750, true)) {
    exit("Backup aborted: cannot create directory $backupDir\n");
}

// ── 1. Database dump ──────────────────────────────────────────────────────────
$sqlFile = $backupDir . DIRECTORY_SEPARATOR . 'database.sql';
$dumped  = false;

$mysqldump = findMysqldump();
if ($mysqldump) {
    $passArg = $dbPass !== '' ? '-p' . escapeshellarg($dbPass) : '';
    $dumpCmd = sprintf(
        '%s --no-tablespaces --single-transaction --quick -h %s -u %s %s %s > %s 2>&1',
        escapeshellcmd($mysqldump),
        escapeshellarg($dbHost),
        escapeshellarg($dbUser),
        $passArg,
        escapeshellarg($dbName),
        escapeshellarg($sqlFile)
    );
    exec($dumpCmd, $dumpOut, $dumpCode);
    $dumped = ($dumpCode === 0 && file_exists($sqlFile) && filesize($sqlFile) > 100);
    if (!$dumped) {
        echo "mysqldump failed (exit $dumpCode). Falling back to PHP dump.\n";
    }
}

if (!$dumped) {
    phpDump($sqlFile, $dbHost, $dbName, $dbUser, $dbPass);
}

if (!file_exists($sqlFile) || filesize($sqlFile) < 10) {
    exit("Backup aborted: dump file missing or empty.\n");
}

// ── 2. Zip uploads + storage/proofs + storage/receipts ───────────────────────
$uploadsZip   = $backupDir . DIRECTORY_SEPARATOR . 'uploads.zip';
$uploadsPaths = [
    realpath(__DIR__ . '/../public/uploads'),
    realpath(__DIR__ . '/../storage/proofs'),
    realpath(__DIR__ . '/../storage/receipts'),
];

$zip = new ZipArchive();
if ($zip->open($uploadsZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    exit("Backup aborted: cannot create uploads.zip\n");
}

foreach ($uploadsPaths as $srcPath) {
    if (!$srcPath || !is_dir($srcPath)) {
        continue;
    }
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcPath, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if ($file->isFile()) {
            $relative = ltrim(str_replace($srcPath, '', $file->getPathname()), DIRECTORY_SEPARATOR . '/');
            $zip->addFile($file->getPathname(), basename($srcPath) . '/' . $relative);
        }
    }
}
$zip->close();

// ── 3. Final archive: database.sql + uploads.zip ─────────────────────────────
$finalZip  = $backupDir . DIRECTORY_SEPARATOR . 'backup_' . $date . '.zip';
$finalArchive = new ZipArchive();
if ($finalArchive->open($finalZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
    $finalArchive->addFile($sqlFile,    'database.sql');
    $finalArchive->addFile($uploadsZip, 'uploads.zip');
    $finalArchive->close();
}

// Remove intermediate files to save space
@unlink($sqlFile);
@unlink($uploadsZip);

echo "Backup complete: $finalZip\n";

// ── Helper functions ──────────────────────────────────────────────────────────

function findMysqldump(): ?string {
    $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $candidates = $isWin
        ? array_merge(glob('C:/laragon/bin/mysql/*/bin/mysqldump.exe') ?: [], ['C:/xampp/mysql/bin/mysqldump.exe'])
        : ['/usr/bin/mysqldump', '/usr/local/bin/mysqldump', '/opt/homebrew/bin/mysqldump', '/usr/local/mysql/bin/mysqldump'];
    foreach ($candidates as $c) {
        if (file_exists($c) && is_executable($c)) return $c;
    }
    if (function_exists('exec')) {
        $which = $isWin ? 'where' : 'which';
        exec($which . ' mysqldump 2>/dev/null', $out, $rc);
        if ($rc === 0 && !empty($out[0])) return trim($out[0]);
    }
    return null;
}

function phpDump(string $filepath, string $host, string $dbName, string $user, string $pass): void {
    @set_time_limit(300);
    @ini_set('memory_limit', '256M');
    $dsn = "mysql:host={$host};dbname={$dbName};charset=utf8mb4";
    $db  = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $fh  = fopen($filepath, 'w');
    if (!$fh) throw new Exception('Cannot open file for writing: ' . $filepath);
    fwrite($fh, "-- ERGON Backup (PHP PDO)\n-- Generated: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n");
    $tables = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $create = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n" . $create[1] . ";\n\n");
        $cols = null; $chunk = [];
        $stmt = $db->query("SELECT * FROM `{$table}`");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($cols === null) $cols = '`' . implode('`, `', array_keys($row)) . '`';
            $chunk[] = '(' . implode(', ', array_map(fn($v) => $v === null ? 'NULL' : $db->quote((string)$v), $row)) . ')';
            if (count($chunk) >= 200) {
                fwrite($fh, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $chunk) . ";\n");
                $chunk = [];
            }
        }
        if ($chunk) fwrite($fh, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $chunk) . ";\n");
        fwrite($fh, "\n");
    }
    fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);
}
