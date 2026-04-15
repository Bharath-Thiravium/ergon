<?php
/**
 * SQL Migration Runner — token-protected, no login required.
 * Usage: /ergon/run-migration.php?token=YOUR_TOKEN&file=ra_bills.sql
 *
 * Set MIGRATION_TOKEN in your .env.production, OR define it below as fallback.
 */

// ── Token: read from any env file that has it, fallback to constant ───────────
$validToken = null;

foreach ([__DIR__ . '/.env.production', __DIR__ . '/.env'] as $envFile) {
    if (!file_exists($envFile)) continue;
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if (strncmp($line, 'MIGRATION_TOKEN=', 16) === 0) {
            $validToken = trim(substr($line, 16));
            break 2;
        }
    }
}

// ── If token not in any env file, show setup instructions ────────────────────
if (!$validToken) {
    http_response_code(503);
    $suggested = bin2hex(random_bytes(16));
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Setup Required</title>
    <style>body{font-family:sans-serif;max-width:600px;margin:40px auto;padding:0 20px;}
    pre{background:#f3f4f6;padding:12px;border-radius:6px;font-size:13px;}
    code{background:#f3f4f6;padding:2px 6px;border-radius:4px;}</style></head><body>
    <h2>⚙️ Setup Required</h2>
    <p>Add <code>MIGRATION_TOKEN</code> to your <strong>.env.production</strong> file on the server:</p>
    <pre>MIGRATION_TOKEN=' . $suggested . '</pre>
    <p>Then access:</p>
    <pre>/ergon/run-migration.php?token=' . $suggested . '&amp;file=ra_bills.sql</pre>
    </body></html>');
}

// ── Validate token ────────────────────────────────────────────────────────────
if (($_GET['token'] ?? '') !== $validToken) {
    http_response_code(403);
    die('<h2>403 — Invalid or missing token.</h2>
         <p>Usage: <code>/ergon/run-migration.php?token=YOUR_TOKEN&amp;file=ra_bills.sql</code></p>');
}

// ── DB connect ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/app/config/environment.php';
require_once __DIR__ . '/app/config/database.php';
$db = Database::connect();

// ── File handling ─────────────────────────────────────────────────────────────
$sqlDir  = __DIR__ . '/sql/';
$file    = basename($_GET['file'] ?? '');
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if (!$file) {
    $files = glob($sqlDir . '*.sql');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migration Runner</title>
    <style>body{font-family:sans-serif;max-width:600px;margin:40px auto;padding:0 20px;}
    h2{color:#111827;}li{margin:8px 0;}a{color:#000080;font-weight:600;text-decoration:none;}
    a:hover{text-decoration:underline;}</style></head><body>
    <h2>🗄️ SQL Migration Runner</h2>
    <p style="color:#6b7280">Select a file to run:</p><ul>';
    foreach ($files as $f) {
        $name = basename($f);
        echo '<li><a href="?token=' . urlencode($validToken) . '&file=' . urlencode($name) . '">'
           . htmlspecialchars($name) . '</a></li>';
    }
    echo '</ul></body></html>';
    exit;
}

if (!preg_match('/\.sql$/i', $file) || !file_exists($sqlDir . $file)) {
    http_response_code(404);
    die('<h2>File not found: ' . htmlspecialchars($file) . '</h2>');
}

$sqlContent = file_get_contents($sqlDir . $file);

// ── Confirmation screen ───────────────────────────────────────────────────────
if (!$confirm) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Run Migration</title>
    <style>
        body{font-family:sans-serif;max-width:720px;margin:40px auto;padding:0 20px;}
        pre{background:#f3f4f6;padding:16px;border-radius:8px;overflow:auto;max-height:320px;font-size:12px;}
        .btn{display:inline-block;padding:10px 24px;border-radius:8px;font-weight:700;text-decoration:none;font-size:14px;}
        .btn-run{background:#dc2626;color:#fff;}
        .btn-cancel{background:#6b7280;color:#fff;margin-left:10px;}
        .warn{background:#fef2f2;border:1px solid #fca5a5;padding:12px 16px;border-radius:8px;margin-bottom:16px;color:#dc2626;}
    </style></head><body>
    <h2>Run Migration: ' . htmlspecialchars($file) . '</h2>
    <div class="warn">⚠️ This will execute the SQL below against the <strong>live database</strong>.</div>
    <pre>' . htmlspecialchars($sqlContent) . '</pre>
    <a href="?token=' . urlencode($validToken) . '&file=' . urlencode($file) . '&confirm=yes" class="btn btn-run">▶ Run Now</a>
    <a href="?token=' . urlencode($validToken) . '" class="btn btn-cancel">Cancel</a>
    </body></html>';
    exit;
}

// ── Execute ───────────────────────────────────────────────────────────────────
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
    .s-ok{background:#ecfdf5;color:#065f46;} .s-err{background:#fef2f2;color:#991b1b;}
    a{color:#000080;}
</style></head><body>
<h2>Result: ' . htmlspecialchars($file) . '</h2>
<div class="summary ' . ($err > 0 ? 's-err' : 's-ok') . '">
    ✅ ' . $ok . ' succeeded &nbsp;|&nbsp; ❌ ' . $err . ' failed
</div>';

foreach ($results as $r) {
    echo '<div class="row ' . ($r['ok'] ? 'ok' : 'fail') . '">'
       . ($r['ok'] ? '✅' : '❌') . ' ' . htmlspecialchars($r['sql']);
    if (!$r['ok']) echo '<br><small>' . htmlspecialchars($r['err']) . '</small>';
    echo '</div>';
}

echo '<br><a href="?token=' . urlencode($validToken) . '">← Back to file list</a></body></html>';
