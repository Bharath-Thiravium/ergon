<?php
/**
 * Migration Runner: employee_panel_improvements
 * Access once via browser: http://localhost/ergon/run_migration.php
 * DELETE this file after running.
 */

// ── Bootstrap ────────────────────────────────────────────────────────────────
define('ERGON_ROOT', __DIR__);
require_once __DIR__ . '/app/config/environment.php';
require_once __DIR__ . '/app/config/database.php';

// ── Simple auth guard — only localhost ───────────────────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1'])) {
    http_response_code(403);
    die('Access denied. Run from localhost only.');
}

// ── Run migrations ────────────────────────────────────────────────────────────
$results = [];
$dbOk = true;

try {
    $db = Database::connect();

    // Helper: check if a column exists
    $hasColumn = function(string $table, string $column) use ($db): bool {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?
              AND COLUMN_NAME  = ?
        ");
        $stmt->execute([$table, $column]);
        return (bool) $stmt->fetchColumn();
    };

    // ── 1. submission_timing on site_reports ─────────────────────────────────
    $label = 'Add submission_timing to site_reports';
    if ($hasColumn('site_reports', 'submission_timing')) {
        $results[] = ['status' => 'skip', 'label' => $label, 'msg' => 'Already applied'];
    } else {
        try {
            $db->exec("ALTER TABLE site_reports ADD COLUMN submission_timing ENUM('on_time','late') DEFAULT 'on_time' AFTER status");
            $results[] = ['status' => 'ok', 'label' => $label];
        } catch (PDOException $e) {
            $results[] = ['status' => 'error', 'label' => $label, 'msg' => $e->getMessage()];
        }
    }

    // ── 2. project_type on projects ───────────────────────────────────────────
    $label = 'Add project_type to projects';
    if ($hasColumn('projects', 'project_type')) {
        $results[] = ['status' => 'skip', 'label' => $label, 'msg' => 'Already applied'];
    } else {
        try {
            $db->exec("ALTER TABLE projects ADD COLUMN project_type VARCHAR(50) DEFAULT 'office' AFTER status");
            $results[] = ['status' => 'ok', 'label' => $label];
        } catch (PDOException $e) {
            $results[] = ['status' => 'error', 'label' => $label, 'msg' => $e->getMessage()];
        }
    }

    // ── 3. attendance_radius update ───────────────────────────────────────────
    $label = 'Update attendance_radius to 150m in settings';
    try {
        $db->exec('UPDATE settings SET attendance_radius = 150 WHERE attendance_radius <= 10');
        $results[] = ['status' => 'ok', 'label' => $label];
    } catch (PDOException $e) {
        $results[] = ['status' => 'error', 'label' => $label, 'msg' => $e->getMessage()];
    }

    // ── 4. checkin_radius default on projects ─────────────────────────────────
    $label = 'Set default checkin_radius 150m on projects';
    try {
        $db->exec('UPDATE projects SET checkin_radius = 150 WHERE checkin_radius IS NULL OR checkin_radius = 0');
        $results[] = ['status' => 'ok', 'label' => $label];
    } catch (PDOException $e) {
        $results[] = ['status' => 'error', 'label' => $label, 'msg' => $e->getMessage()];
    }

} catch (Exception $e) {
    $dbOk    = false;
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Migration Runner — ERGON</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#f1f5f9;display:flex;justify-content:center;padding:2rem}
  .card{background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08);width:100%;max-width:640px;overflow:hidden}
  .card-head{padding:1.25rem 1.5rem;background:#1e293b;color:#fff}
  .card-head h1{font-size:1.1rem;font-weight:700}
  .card-head p{font-size:.8rem;color:#94a3b8;margin-top:.25rem}
  .card-body{padding:1.5rem}
  .row{display:flex;align-items:flex-start;gap:.75rem;padding:.75rem 0;border-bottom:1px solid #f1f5f9}
  .row:last-child{border-bottom:none}
  .badge{flex-shrink:0;padding:.2rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
  .badge.ok{background:#d1fae5;color:#065f46}
  .badge.skip{background:#e0f2fe;color:#0369a1}
  .badge.error{background:#fee2e2;color:#991b1b}
  .label{font-size:.875rem;font-weight:600;color:#1e293b}
  .detail{font-size:.78rem;color:#64748b;margin-top:.2rem}
  .warn{margin-top:1.25rem;padding:.75rem 1rem;background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;font-size:.82rem;color:#92400e}
  .db-error{padding:1rem;background:#fee2e2;border-radius:8px;color:#991b1b;font-size:.875rem}
</style>
</head>
<body>
<div class="card">
  <div class="card-head">
    <h1>⚙️ Employee Panel Migration Runner</h1>
    <p>employee_panel_improvements.sql</p>
  </div>
  <div class="card-body">
    <?php if (!$dbOk): ?>
      <div class="db-error">❌ Database connection failed: <?= htmlspecialchars($dbError) ?></div>
    <?php else: ?>
      <?php foreach ($results as $r): ?>
      <div class="row">
        <span class="badge <?= $r['status'] ?>">
          <?= $r['status'] === 'ok' ? '✓ Done' : ($r['status'] === 'skip' ? '↷ Skip' : '✗ Error') ?>
        </span>
        <div>
          <div class="label"><?= htmlspecialchars($r['label']) ?></div>
          <?php if (!empty($r['msg'])): ?>
          <div class="detail"><?= htmlspecialchars($r['msg']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <?php $hasError = in_array('error', array_column($results, 'status')); ?>
      <?php if (!$hasError): ?>
      <div class="warn">
        ✅ Migration complete. <strong>Delete this file now:</strong><br>
        <code style="font-size:.8rem">c:\laragon\www\ergon\run_migration.php</code>
      </div>
      <?php else: ?>
      <div class="warn" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b">
        ⚠️ Some migrations failed. Check the errors above and fix manually if needed.
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
