<?php
/**
 * Ergon — Unified Migration Runner
 *
 * Runs every pending migration in a single browser request.
 * Each step is idempotent: already-applied changes are skipped, never re-run.
 *
 * Access: https://yourdomain.com/ergon/migrate.php
 * DELETE this file from the server once all migrations are confirmed green.
 */

define('ERGON_ROOT', __DIR__);
require_once __DIR__ . '/app/config/environment.php';
require_once __DIR__ . '/app/config/database.php';

// ── Auth guard ────────────────────────────────────────────────────────────────
const MIGRATE_SECRET = 'ergon_migrate_2025';

$authorized = ($_GET['token'] ?? '') === MIGRATE_SECRET
           || ($_POST['token'] ?? '') === MIGRATE_SECRET;

if (!$authorized) {
    $authError = isset($_POST['token']) ? 'Invalid token. Access denied.' : null;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Migration Runner — Ergon</title>
    <style>
      *{box-sizing:border-box;margin:0;padding:0}
      body{font-family:system-ui,sans-serif;background:#f1f5f9;display:flex;justify-content:center;align-items:center;min-height:100vh}
      .card{background:#fff;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,.1);width:100%;max-width:380px;overflow:hidden}
      .card-head{padding:1.25rem 1.5rem;background:#1e293b;color:#fff}
      .card-head h1{font-size:1.1rem;font-weight:700}
      .card-head p{font-size:.78rem;color:#94a3b8;margin-top:.2rem}
      .card-body{padding:1.5rem;display:flex;flex-direction:column;gap:.85rem}
      label{font-size:.83rem;font-weight:600;color:#374151}
      input{width:100%;padding:.65rem .85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9rem;margin-top:.3rem}
      input:focus{outline:none;border-color:#3b82f6}
      button{padding:.75rem;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer}
      button:hover{background:#2563eb}
      .err{color:#dc2626;font-size:.8rem;padding:.4rem .6rem;background:#fef2f2;border-radius:6px}
    </style>
    </head>
    <body>
    <div class="card">
      <div class="card-head">
        <h1>⚙️ Migration Runner</h1>
        <p>Ergon — Unified Database Migrations</p>
      </div>
      <div class="card-body">
        <form method="POST">
          <label>Migration token
            <input type="password" name="token" placeholder="Enter token" autofocus>
          </label>
          <?php if ($authError): ?>
          <div class="err">⚠ <?= htmlspecialchars($authError) ?></div>
          <?php endif; ?>
          <button type="submit" style="margin-top:.5rem">Run All Migrations →</button>
        </form>
      </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// ── Database connection ───────────────────────────────────────────────────────
$dbOk    = true;
$dbError = '';
$results = [];

try {
    $db = Database::connect();
} catch (Exception $e) {
    $dbOk    = false;
    $dbError = $e->getMessage();
}

// ── Helpers ───────────────────────────────────────────────────────────────────
/**
 * Check whether a table exists in the current database.
 */
function tableExists(PDO $db, string $table): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Check whether a column exists in a table.
 */
function columnExists(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Check whether an index exists on a table.
 */
function indexExists(PDO $db, string $table, string $indexName): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $stmt->execute([$table, $indexName]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Record a migration result.
 */
function result(array &$results, string $status, string $label, string $msg = ''): void
{
    $results[] = ['status' => $status, 'label' => $label, 'msg' => $msg];
}

/**
 * Run a single DDL/DML statement, recording ok/skip/error.
 * $skipPatterns: substrings in the PDO error message that mean "already done".
 */
function runSql(
    PDO    $db,
    array  &$results,
    string $label,
    string $sql,
    array  $skipPatterns = ['Duplicate column name', 'already exists', "Duplicate key name"]
): void {
    try {
        $db->exec($sql);
        result($results, 'ok', $label);
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        foreach ($skipPatterns as $pattern) {
            if (stripos($msg, $pattern) !== false) {
                result($results, 'skip', $label, 'Already applied');
                return;
            }
        }
        result($results, 'error', $label, $msg);
    }
}

// ── Run migrations ────────────────────────────────────────────────────────────
if ($dbOk) {

    // ══════════════════════════════════════════════════════════════════════════
    // 1. user_tokens  (persistent login — Capacitor mobile app)
    // ══════════════════════════════════════════════════════════════════════════
    $label = '[user_tokens] Create table';
    if (tableExists($db, 'user_tokens')) {
        result($results, 'skip', $label, 'Already applied');
    } else {
        runSql($db, $results, $label,
            "CREATE TABLE user_tokens (
                id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                user_id     INT UNSIGNED  NOT NULL,
                token_hash  CHAR(64)      NOT NULL COMMENT 'SHA-256 hex of the raw token',
                device_hint VARCHAR(255)  DEFAULT NULL COMMENT 'Optional UA snippet for audit',
                expires_at  DATETIME      NOT NULL,
                created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE  KEY uq_token_hash (token_hash),
                KEY     idx_user_id   (user_id),
                KEY     idx_expires   (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 2. users — security columns  (fix_login_columns)
    // ══════════════════════════════════════════════════════════════════════════
    $securityCols = [
        'locked_until'         => "ALTER TABLE users ADD COLUMN locked_until DATETIME NULL",
        'failed_attempts'      => "ALTER TABLE users ADD COLUMN failed_attempts INT NOT NULL DEFAULT 0",
        'last_ip'              => "ALTER TABLE users ADD COLUMN last_ip VARCHAR(45) NULL",
        'last_login'           => "ALTER TABLE users ADD COLUMN last_login DATETIME NULL",
        'password_changed_at'  => "ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL",
        'reset_token'          => "ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL",
        'reset_token_expires'  => "ALTER TABLE users ADD COLUMN reset_token_expires DATETIME NULL",
    ];
    foreach ($securityCols as $col => $sql) {
        $label = "[users] Add column: $col";
        if (columnExists($db, 'users', $col)) {
            result($results, 'skip', $label, 'Already applied');
        } else {
            runSql($db, $results, $label, $sql);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 3. login_attempts table  (fix_login_columns)
    // ══════════════════════════════════════════════════════════════════════════
    $label = '[login_attempts] Create table';
    if (tableExists($db, 'login_attempts')) {
        result($results, 'skip', $label, 'Already applied');
    } else {
        runSql($db, $results, $label,
            "CREATE TABLE login_attempts (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                email        VARCHAR(255) NULL,
                ip_address   VARCHAR(45)  NOT NULL DEFAULT '',
                user_agent   TEXT NULL,
                success      TINYINT(1)   DEFAULT 0,
                attempted_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email        (email),
                INDEX idx_ip           (ip_address),
                INDEX idx_attempted_at (attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 4. rate_limit_log table  (fix_login_columns)
    // ══════════════════════════════════════════════════════════════════════════
    $label = '[rate_limit_log] Create table';
    if (tableExists($db, 'rate_limit_log')) {
        result($results, 'skip', $label, 'Already applied');
    } else {
        runSql($db, $results, $label,
            "CREATE TABLE rate_limit_log (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                identifier   VARCHAR(255) NOT NULL,
                action       VARCHAR(64)  NOT NULL DEFAULT 'login',
                success      TINYINT(1)   DEFAULT 0,
                ip_address   VARCHAR(45)  NULL,
                attempted_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_identifier   (identifier),
                INDEX idx_action       (action),
                INDEX idx_attempted_at (attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 5. advances — admin entry columns  (add_admin_entry_columns)
    // ══════════════════════════════════════════════════════════════════════════
    $advanceCols = [
        'type'               => "ALTER TABLE advances ADD COLUMN type VARCHAR(100) NOT NULL DEFAULT 'General Advance' AFTER user_id",
        'project_id'         => "ALTER TABLE advances ADD COLUMN project_id INT NULL AFTER type",
        'approved_amount'    => "ALTER TABLE advances ADD COLUMN approved_amount DECIMAL(10,2) NULL AFTER approved_at",
        'paid_by'            => "ALTER TABLE advances ADD COLUMN paid_by INT NULL",
        'paid_at'            => "ALTER TABLE advances ADD COLUMN paid_at DATETIME NULL",
        'admin_approval'     => "ALTER TABLE advances ADD COLUMN admin_approval ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'",
        'admin_approved_by'  => "ALTER TABLE advances ADD COLUMN admin_approved_by INT NULL",
        'admin_approved_at'  => "ALTER TABLE advances ADD COLUMN admin_approved_at DATETIME NULL",
        'admin_comments'     => "ALTER TABLE advances ADD COLUMN admin_comments TEXT NULL",
    ];
    foreach ($advanceCols as $col => $sql) {
        $label = "[advances] Add column: $col";
        if (columnExists($db, 'advances', $col)) {
            result($results, 'skip', $label, 'Already applied');
        } else {
            runSql($db, $results, $label, $sql);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 6. expenses — admin entry columns  (add_admin_entry_columns)
    // ══════════════════════════════════════════════════════════════════════════
    $expenseCols = [
        'project_id'         => "ALTER TABLE expenses ADD COLUMN project_id INT NULL AFTER user_id",
        'approved_amount'    => "ALTER TABLE expenses ADD COLUMN approved_amount DECIMAL(10,2) NULL AFTER approved_at",
        'paid_by'            => "ALTER TABLE expenses ADD COLUMN paid_by INT NULL",
        'paid_at'            => "ALTER TABLE expenses ADD COLUMN paid_at DATETIME NULL",
        'admin_approval'     => "ALTER TABLE expenses ADD COLUMN admin_approval ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'",
        'admin_approved_by'  => "ALTER TABLE expenses ADD COLUMN admin_approved_by INT NULL",
        'admin_approved_at'  => "ALTER TABLE expenses ADD COLUMN admin_approved_at DATETIME NULL",
        'admin_comments'     => "ALTER TABLE expenses ADD COLUMN admin_comments TEXT NULL",
    ];
    foreach ($expenseCols as $col => $sql) {
        $label = "[expenses] Add column: $col";
        if (columnExists($db, 'expenses', $col)) {
            result($results, 'skip', $label, 'Already applied');
        } else {
            runSql($db, $results, $label, $sql);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 7. attendance — project & location columns  (update_attendance_table)
    // ══════════════════════════════════════════════════════════════════════════
    $attendanceCols = [
        'project_id'   => "ALTER TABLE attendance ADD COLUMN project_id INT NULL",
        'manual_entry' => "ALTER TABLE attendance ADD COLUMN manual_entry TINYINT(1) DEFAULT 0",
        'latitude'     => "ALTER TABLE attendance ADD COLUMN latitude DECIMAL(10,8) NULL",
        'longitude'    => "ALTER TABLE attendance ADD COLUMN longitude DECIMAL(11,8) NULL",
    ];
    foreach ($attendanceCols as $col => $sql) {
        $label = "[attendance] Add column: $col";
        if (columnExists($db, 'attendance', $col)) {
            result($results, 'skip', $label, 'Already applied');
        } else {
            runSql($db, $results, $label, $sql);
        }
    }

    $label = '[attendance] Add index: idx_project_id';
    if (indexExists($db, 'attendance', 'idx_project_id')) {
        result($results, 'skip', $label, 'Already applied');
    } else {
        runSql($db, $results, $label,
            "ALTER TABLE attendance ADD INDEX idx_project_id (project_id)"
        );
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 8. site_reports — submission_timing  (employee_panel_improvements)
    // ══════════════════════════════════════════════════════════════════════════
    $label = "[site_reports] Add column: submission_timing";
    if (columnExists($db, 'site_reports', 'submission_timing')) {
        result($results, 'skip', $label, 'Already applied');
    } else {
        runSql($db, $results, $label,
            "ALTER TABLE site_reports ADD COLUMN submission_timing ENUM('on_time','late') DEFAULT 'on_time' AFTER status"
        );
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 9. projects — project_type  (employee_panel_improvements)
    // ══════════════════════════════════════════════════════════════════════════
    $label = "[projects] Add column: project_type";
    if (columnExists($db, 'projects', 'project_type')) {
        result($results, 'skip', $label, 'Already applied');
    } else {
        runSql($db, $results, $label,
            "ALTER TABLE projects ADD COLUMN project_type VARCHAR(50) DEFAULT 'office' AFTER status"
        );
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 10. settings — attendance_radius floor  (employee_panel_improvements)
    // ══════════════════════════════════════════════════════════════════════════
    $label = '[settings] Set attendance_radius minimum 150 m';
    try {
        $affected = $db->exec('UPDATE settings SET attendance_radius = 150 WHERE attendance_radius < 150');
        result($results, 'ok', $label, $affected > 0 ? "$affected row(s) updated" : 'No rows needed updating');
    } catch (PDOException $e) {
        result($results, 'error', $label, $e->getMessage());
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 11. projects — checkin_radius floor  (employee_panel_improvements)
    // ══════════════════════════════════════════════════════════════════════════
    $label = '[projects] Set checkin_radius minimum 150 m';
    try {
        $affected = $db->exec('UPDATE projects SET checkin_radius = 150 WHERE checkin_radius IS NULL OR checkin_radius < 150');
        result($results, 'ok', $label, $affected > 0 ? "$affected row(s) updated" : 'No rows needed updating');
    } catch (PDOException $e) {
        result($results, 'error', $label, $e->getMessage());
    }
}

// ── Tally ─────────────────────────────────────────────────────────────────────
$counts = ['ok' => 0, 'skip' => 0, 'error' => 0];
foreach ($results as $r) {
    $counts[$r['status']]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Migration Runner — Ergon</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#f1f5f9;display:flex;justify-content:center;padding:2rem 1rem}
  .card{background:#fff;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,.08);width:100%;max-width:700px;overflow:hidden}
  .card-head{padding:1.25rem 1.5rem;background:#1e293b;color:#fff;display:flex;justify-content:space-between;align-items:flex-start;gap:1rem}
  .card-head h1{font-size:1.1rem;font-weight:700}
  .card-head p{font-size:.78rem;color:#94a3b8;margin-top:.2rem}
  .tally{display:flex;gap:.5rem;flex-shrink:0}
  .tally span{padding:.2rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700}
  .tally .ok{background:#d1fae5;color:#065f46}
  .tally .skip{background:#e0f2fe;color:#0369a1}
  .tally .error{background:#fee2e2;color:#991b1b}
  .card-body{padding:1.25rem 1.5rem}
  .row{display:flex;align-items:flex-start;gap:.75rem;padding:.6rem 0;border-bottom:1px solid #f1f5f9}
  .row:last-child{border-bottom:none}
  .badge{flex-shrink:0;padding:.18rem .55rem;border-radius:20px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-top:.1rem}
  .badge.ok{background:#d1fae5;color:#065f46}
  .badge.skip{background:#e0f2fe;color:#0369a1}
  .badge.error{background:#fee2e2;color:#991b1b}
  .label{font-size:.85rem;font-weight:600;color:#1e293b}
  .detail{font-size:.76rem;color:#64748b;margin-top:.15rem}
  .footer{margin-top:1.25rem;padding:.85rem 1rem;border-radius:8px;font-size:.82rem}
  .footer.success{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
  .footer.warning{background:#fef3c7;border:1px solid #fcd34d;color:#92400e}
  .footer.danger{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
  .footer code{font-size:.78rem;background:rgba(0,0,0,.06);padding:.1rem .35rem;border-radius:4px}
  .db-error{padding:1rem;background:#fee2e2;border-radius:8px;color:#991b1b;font-size:.875rem}
  .section-divider{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;padding:.75rem 0 .25rem;border-top:1px solid #e2e8f0;margin-top:.5rem}
</style>
</head>
<body>
<div class="card">
  <div class="card-head">
    <div>
      <h1>⚙️ Ergon — Migration Runner</h1>
      <p>All pending migrations · <?= date('Y-m-d H:i:s') ?></p>
    </div>
    <?php if ($dbOk): ?>
    <div class="tally">
      <span class="ok">✓ <?= $counts['ok'] ?> applied</span>
      <span class="skip">↷ <?= $counts['skip'] ?> skipped</span>
      <?php if ($counts['error']): ?>
      <span class="error">✗ <?= $counts['error'] ?> errors</span>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="card-body">
    <?php if (!$dbOk): ?>
      <div class="db-error">❌ Database connection failed: <?= htmlspecialchars($dbError) ?></div>

    <?php else: ?>

      <?php
      // Group rows by the bracketed prefix, e.g. "[user_tokens]"
      $currentGroup = null;
      foreach ($results as $r):
          preg_match('/^\[([^\]]+)\]/', $r['label'], $m);
          $group = $m[1] ?? 'General';
          if ($group !== $currentGroup):
              $currentGroup = $group;
      ?>
      <div class="section-divider"><?= htmlspecialchars($group) ?></div>
      <?php endif; ?>

      <div class="row">
        <span class="badge <?= $r['status'] ?>">
          <?= $r['status'] === 'ok' ? '✓ Done' : ($r['status'] === 'skip' ? '↷ Skip' : '✗ Error') ?>
        </span>
        <div>
          <div class="label"><?= htmlspecialchars(preg_replace('/^\[[^\]]+\]\s*/', '', $r['label'])) ?></div>
          <?php if (!empty($r['msg'])): ?>
          <div class="detail"><?= htmlspecialchars($r['msg']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <?php endforeach; ?>

      <?php if ($counts['error'] === 0): ?>
      <div class="footer success">
        ✅ All migrations completed successfully.
        <strong>Delete this file from the server:</strong>
        <code><?= htmlspecialchars(__FILE__) ?></code>
      </div>
      <?php else: ?>
      <div class="footer danger">
        ⚠️ <?= $counts['error'] ?> migration(s) failed. Review the errors above and fix manually if needed.
        Do <strong>not</strong> delete this file until all steps show ✓ Done or ↷ Skip.
      </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</div>
</body>
</html>
