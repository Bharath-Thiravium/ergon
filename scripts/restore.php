<?php
/**
 * Restore Script
 * Usage: php scripts/restore.php <date_dir> <backup_filename>
 * Example: php scripts/restore.php 2025-06-01 backup_2025-06-01_03-00.zip
 */

if (PHP_SAPI !== 'cli' && !defined('RESTORE_INTERNAL')) {
    exit("Direct access not allowed.\n");
}

$dateDir  = $argv[1] ?? null;
$fileName = $argv[2] ?? null;

if (!$dateDir || !$fileName) {
    exit("Usage: php restore.php <date_dir> <backup_filename>\n");
}

// Validate inputs — prevent directory traversal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDir) ||
    !preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}\.zip$/', $fileName)) {
    exit("Invalid backup path.\n");
}

// Load .env
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
    exit("Restore aborted: DB_NAME not configured.\n");
}

$backupZip = realpath(__DIR__ . '/../storage/backups/' . $dateDir . '/' . $fileName);

if (!$backupZip || !file_exists($backupZip)) {
    exit("Restore aborted: backup file not found.\n");
}

// ── Extract to a temp directory ───────────────────────────────────────────────
$tmpDir = sys_get_temp_dir() . '/ergon_restore_' . time();
if (!mkdir($tmpDir, 0750, true)) {
    exit("Restore aborted: cannot create temp directory.\n");
}

$zip = new ZipArchive();
if ($zip->open($backupZip) !== true) {
    exit("Restore aborted: cannot open backup zip.\n");
}
$zip->extractTo($tmpDir);
$zip->close();

// ── 1. Restore database ───────────────────────────────────────────────────────
$sqlFile = $tmpDir . '/database.sql';
if (!file_exists($sqlFile)) {
    exit("Restore aborted: database.sql not found in backup.\n");
}

$passArg   = $dbPass !== '' ? '-p' . escapeshellarg($dbPass) : '';
$importCmd = sprintf(
    'mysql -h %s -u %s %s %s < %s 2>&1',
    escapeshellarg($dbHost),
    escapeshellarg($dbUser),
    $passArg,
    escapeshellarg($dbName),
    escapeshellarg($sqlFile)
);
exec($importCmd, $importOut, $importCode);

if ($importCode !== 0) {
    exit("Restore aborted: mysql import failed.\n" . implode("\n", $importOut) . "\n");
}
echo "✔ Database restored.\n";

// ── 2. Restore uploads ────────────────────────────────────────────────────────
$uploadsZip = $tmpDir . '/uploads.zip';
if (file_exists($uploadsZip)) {
    $uz = new ZipArchive();
    if ($uz->open($uploadsZip) === true) {
        // Extract each folder back to its original location
        $targets = [
            'uploads'  => realpath(__DIR__ . '/../public/uploads'),
            'proofs'   => realpath(__DIR__ . '/../storage/proofs'),
            'receipts' => realpath(__DIR__ . '/../storage/receipts'),
        ];

        for ($i = 0; $i < $uz->numFiles; $i++) {
            $entry    = $uz->getNameIndex($i);
            $parts    = explode('/', $entry, 2);
            $folder   = $parts[0];
            $relative = $parts[1] ?? '';

            if (!isset($targets[$folder]) || $relative === '' || substr($relative, -1) === '/') {
                continue;
            }

            $dest = $targets[$folder] . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $destDir = dirname($dest);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0750, true);
            }
            file_put_contents($dest, $uz->getFromIndex($i));
        }
        $uz->close();
        echo "✔ Uploads restored.\n";
    }
}

// ── 3. Cleanup temp ───────────────────────────────────────────────────────────
$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);
foreach ($iter as $f) {
    $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
}
rmdir($tmpDir);

echo "Restore complete.\n";
