<?php
/**
 * Backup API
 *   GET  /api/backup.php        — list available backup files
 *   POST /api/backup.php        — create a new backup
 * Restricted to role = owner.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// ── Auth & role guard ─────────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated']);
    exit;
}

if (($_SESSION['role'] ?? '') !== 'owner') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

$backupRoot = __DIR__ . '/../storage/backups';

// ── GET: list backups ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $files = [];
    foreach (glob($backupRoot . '/*/*.zip') as $path) {
        $name = basename($path);
        // Only include final backup archives (backup_YYYY-MM-DD_HH-mm.zip)
        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}\.zip$/', $name)) {
            continue;
        }
        $files[] = [
            'name'     => $name,
            'date_dir' => basename(dirname($path)),
            'size'     => round(filesize($path) / 1024 / 1024, 2) . ' MB',
            'created'  => date('d M Y, h:i A', filemtime($path)),
            'ts'       => filemtime($path),
        ];
    }
    // Newest first
    usort($files, fn($a, $b) => $b['ts'] - $a['ts']);
    echo json_encode(['success' => true, 'backups' => $files]);
    exit;
}

// ── POST: create backup ───────────────────────────────────────────────────────
$script = realpath(__DIR__ . '/../scripts/backup.php');

if (!$script || !file_exists($script)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Backup script not found']);
    exit;
}

$output   = [];
$exitCode = 0;
exec('php ' . escapeshellarg($script) . ' 2>&1', $output, $exitCode);

if ($exitCode !== 0) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Backup failed', 'detail' => implode("\n", $output)]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Backup created successfully']);
