<?php
/**
 * Cleanup Script — remove backup folders older than 48 days.
 * Intended to run via cron daily at 3:05 AM.
 */

$backupRoot = realpath(__DIR__ . '/../storage/backups');

if (!$backupRoot || !is_dir($backupRoot)) {
    exit("Cleanup skipped: backup directory not found.\n");
}

$cutoff  = time() - (48 * 24 * 60 * 60);
$removed = 0;

foreach (glob($backupRoot . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $folder) {
    // Only process YYYY-MM-DD named directories to avoid accidental deletion
    if (!preg_match('/\d{4}-\d{2}-\d{2}$/', basename($folder))) {
        continue;
    }
    if (filemtime($folder) < $cutoff) {
        // Recursively delete the folder
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        if (rmdir($folder)) {
            echo "Deleted: $folder\n";
            $removed++;
        }
    }
}

echo "Cleanup complete. Removed $removed folder(s).\n";
