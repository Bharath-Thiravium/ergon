<?php
/**
 * POST /api/restore.php
 * Body: { "date_dir": "YYYY-MM-DD", "file": "backup_YYYY-MM-DD_HH-mm.zip" }
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$body    = json_decode(file_get_contents('php://input'), true);
$dateDir = trim($body['date_dir'] ?? '');
$file    = trim($body['file'] ?? '');

// ── Strict validation — prevent path traversal ────────────────────────────────
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDir) ||
    !preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}\.zip$/', $file)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid backup reference']);
    exit;
}

$backupPath = __DIR__ . '/../storage/backups/' . $dateDir . '/' . $file;
if (!file_exists($backupPath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Backup file not found']);
    exit;
}

// ── Run restore script ────────────────────────────────────────────────────────
$script = realpath(__DIR__ . '/../scripts/restore.php');

if (!$script) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Restore script not found']);
    exit;
}

$cmd      = 'php ' . escapeshellarg($script) . ' ' . escapeshellarg($dateDir) . ' ' . escapeshellarg($file) . ' 2>&1';
$output   = [];
$exitCode = 0;
exec($cmd, $output, $exitCode);

if ($exitCode !== 0) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Restore failed', 'detail' => implode("\n", $output)]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'System restored successfully from ' . $file]);
