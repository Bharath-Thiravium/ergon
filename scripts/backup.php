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
$sqlFile  = $backupDir . DIRECTORY_SEPARATOR . 'database.sql';
$passArg  = $dbPass !== '' ? '-p' . escapeshellarg($dbPass) : '';
$dumpCmd  = sprintf(
    'mysqldump -h %s -u %s %s %s > %s 2>&1',
    escapeshellarg($dbHost),
    escapeshellarg($dbUser),
    $passArg,
    escapeshellarg($dbName),
    escapeshellarg($sqlFile)
);
exec($dumpCmd, $dumpOut, $dumpCode);

if ($dumpCode !== 0 || !file_exists($sqlFile)) {
    exit("Backup aborted: mysqldump failed (exit $dumpCode).\n" . implode("\n", $dumpOut) . "\n");
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
