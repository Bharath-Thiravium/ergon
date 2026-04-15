<?php
/**
 * SQL Migration Runner
 * Owner-only. Runs a .sql file from the /sql directory against MySQL.
 * Access: /ergon/run-migration?file=ra_bills.sql
 */

require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/environment.php';
require_once __DIR__ . '/app/config/database.php';

// ── Auth: owner only ──────────────────────────────────────────────────────────
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: /ergon/login'); exit;
}
if (!in_array($_SESSION['role'], ['owner', 'company_owner'])) {
    http_response_code(403);
    die('<h2>403 — Owner access required.</h2>');
}

// ── Allowed directory ─────────────────────────────────────────────────────────
$sqlDir  = __DIR__ . '/sql/';
$file    = basename($_GET['file'] ?? '');          // strip any path traversal
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

// List available .sql files if no file specified
if (!$file) {
    $files = glob($sqlDir . '*.sql');
    echo '<h2 style="font-family:sans-serif">SQL Migration Runner</h2>';
    echo '<p style="font-family:sans-serif;color:#6b7280">Select a file to run:</p><ul style="font-family:monospace">';
    foreach ($files as $f) {
        $name = basename($f);
        echo '<li><a href="?file=' . urlencode($name) . '">' . htmlspecialchars($name) . '</a></li>';
    }
    echo '</ul>';
    exit;
}

// Validate file exists and is a .sql file
if (!preg_match('/\.sql$/i', $file) || !file_exists($sqlDir . $file)) {
    http_response_code(404);
    die('<h2>File not found: ' . htmlspecialchars($file) . '</h2>');
}

$sqlContent = file_get_contents($sqlDir . $file);

// ── Confirmation screen ───────────────────────────────────────────────────────
if (!$confirm) {
    $lineCount = substr_count($sqlContent, "\n");
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <title>Run Migration</title>
    <style>
        body{font-family:sans-serif;max-width:700px;margin:40px auto;padding:0 20px;}
        pre{background:#f3f4f6;padding:16px;border-radius:8px;overflow:auto;max-height:300px;font-size:12px;}
        .btn{display:inline-block;padding:10px 24px;border-radius:8px;font-weight:700;text-decoration:none;font-size:14px;}
        .btn-run{background:#dc2626;color:#fff;}
        .btn-cancel{background:#6b7280;color:#fff;margin-left:10px;}
        .warn{background:#fef2f2;border:1px solid #fca5a5;padding:12px 16px;border-radius:8px;margin-bottom:16px;color:#dc2626;}
    </style></head><body>
    <h2>Run Migration: ' . htmlspecialchars($file) . '</h2>
    <div class="warn">⚠️ This will execute the SQL below against the <strong>live database</strong>. This cannot be undone.</div>
    <p style="color:#6b7280;font-size:13px;">' . $lineCount . ' lines</p>
    <pre>' . htmlspecialchars($sqlContent) . '</pre>
    <a href="?file=' . urlencode($file) . '&confirm=yes" class="btn btn-run">▶ Run Now</a>
    <a href="?file=" class="btn btn-cancel">Cancel</a>
    </body></html>';
    exit;
}

// ── Execute ───────────────────────────────────────────────────────────────────
$db = Database::connect();

// Split on semicolons, skip empty/comment-only lines
$statements = array_filter(
    array_map('trim', explode(';', $sqlContent)),
    fn($s) => strlen(preg_replace('/^--.*$/m', '', $s)) > 5
);

$results = [];
foreach ($statements as $sql) {
    $preview = substr(preg_replace('/\s+/', ' ', $sql), 0, 80);
    try {
        $db->exec($sql);
        $results[] = ['ok' => true,  'sql' => $preview];
    } catch (PDOException $e) {
        $results[] = ['ok' => false, 'sql' => $preview, 'err' => $e->getMessage()];
    }
}

$ok  = count(array_filter($results, fn($r) => $r['ok']));
$err = count($results) - $ok;

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migration Result</title>
<style>
    body{font-family:sans-serif;max-width:800px;margin:40px auto;padding:0 20px;}
    .row{padding:8px 12px;border-radius:6px;margin-bottom:6px;font-size:13px;font-family:monospace;}
    .ok{background:#ecfdf5;color:#065f46;border-left:4px solid #059669;}
    .fail{background:#fef2f2;color:#991b1b;border-left:4px solid #dc2626;}
    .summary{padding:12px 16px;border-radius:8px;font-weight:700;margin-bottom:16px;}
    .s-ok{background:#ecfdf5;color:#065f46;}
    .s-err{background:#fef2f2;color:#991b1b;}
    a{color:#000080;}
</style></head><body>
<h2>Migration Result: ' . htmlspecialchars($file) . '</h2>
<div class="summary ' . ($err > 0 ? 's-err' : 's-ok') . '">
    ✅ ' . $ok . ' statement(s) succeeded &nbsp;|&nbsp; ❌ ' . $err . ' failed
</div>';

foreach ($results as $r) {
    $cls = $r['ok'] ? 'ok' : 'fail';
    $icon = $r['ok'] ? '✅' : '❌';
    echo '<div class="row ' . $cls . '">' . $icon . ' ' . htmlspecialchars($r['sql']);
    if (!$r['ok']) echo '<br><small>' . htmlspecialchars($r['err']) . '</small>';
    echo '</div>';
}

echo '<br><a href="?file=">← Back to file list</a></body></html>';
