<?php
/**
 * backfill_user_ledgers.php
 *
 * One-time script: syncs all approved/paid advances and expenses that were
 * never written to user_ledgers (ledger_synced = 0 or NULL).
 *
 * Safe to run multiple times — LedgerHelper skips rows where ledger_synced = 1.
 *
 * Run from project root:
 *   php scripts/backfill_user_ledgers.php
 *
 * Or visit in browser (owner-only guard below):
 *   https://yourdomain.com/ergon/scripts/backfill_user_ledgers.php
 */

// ── Bootstrap ────────────────────────────────────────────────────────────────

define('ERGON_ROOT', dirname(__DIR__));

require_once ERGON_ROOT . '/app/config/environment.php';
require_once ERGON_ROOT . '/app/config/database.php';
require_once ERGON_ROOT . '/app/helpers/DatabaseHelper.php';
require_once ERGON_ROOT . '/app/helpers/LedgerHelper.php';

// Browser guard — only allow if running from CLI or logged-in owner
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    session_start();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, ['owner', 'company_owner'])) {
        http_response_code(403);
        exit('Access denied. Run from CLI or log in as owner.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function log_line(string $msg): void {
    echo $msg . PHP_EOL;
    if (php_sapi_name() !== 'cli') flush();
}

// ── Connect ──────────────────────────────────────────────────────────────────

try {
    $db = Database::connect();
    LedgerHelper::ensureTable($db);
    log_line('[OK] Database connected, user_ledgers table ensured.');
} catch (Exception $e) {
    log_line('[FATAL] Cannot connect to database: ' . $e->getMessage());
    exit(1);
}

// Ensure ledger_synced column exists on both tables
try { $db->exec("ALTER TABLE advances ADD COLUMN ledger_synced TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE expenses ADD COLUMN ledger_synced TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

// ── Counters ─────────────────────────────────────────────────────────────────

$synced   = 0;
$skipped  = 0;
$failed   = 0;

// ── ADVANCES ─────────────────────────────────────────────────────────────────

log_line('');
log_line('--- Backfilling ADVANCES ---');

$stmt = $db->query(
    "SELECT id, user_id,
            COALESCE(approved_amount, amount) AS amount,
            COALESCE(approved_at, created_at) AS entry_date
     FROM advances
     WHERE status IN ('approved', 'paid')
       AND (ledger_synced IS NULL OR ledger_synced = 0)
     ORDER BY COALESCE(approved_at, created_at) ASC"
);
$advances = $stmt->fetchAll(PDO::FETCH_ASSOC);

log_line('Found ' . count($advances) . ' unsynced advance(s).');

foreach ($advances as $row) {
    $ok = LedgerHelper::recordEntry(
        (int)   $row['user_id'],
                'advance_payment',
                'advance',
        (int)   $row['id'],
        (float) $row['amount'],
                'credit',
                $row['entry_date'],
                $db
    );

    if ($ok) {
        $synced++;
        log_line("  [SYNCED] advance id={$row['id']} user_id={$row['user_id']} amount={$row['amount']}");
    } else {
        $failed++;
        log_line("  [FAILED] advance id={$row['id']} user_id={$row['user_id']}");
    }
}

// ── EXPENSES ─────────────────────────────────────────────────────────────────

log_line('');
log_line('--- Backfilling EXPENSES ---');

$stmt = $db->query(
    "SELECT id, user_id,
            COALESCE(approved_amount, amount) AS amount,
            COALESCE(approved_at, expense_date, created_at) AS entry_date
     FROM expenses
     WHERE status IN ('approved', 'paid')
       AND (ledger_synced IS NULL OR ledger_synced = 0)
       AND (source_advance_id IS NULL OR source_advance_id = 0)
     ORDER BY COALESCE(approved_at, expense_date, created_at) ASC"
);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

log_line('Found ' . count($expenses) . ' unsynced expense(s).');

foreach ($expenses as $row) {
    $ok = LedgerHelper::recordEntry(
        (int)   $row['user_id'],
                'expense_payment',
                'expense',
        (int)   $row['id'],
        (float) $row['amount'],
                'credit',
                $row['entry_date'],
                $db
    );

    if ($ok) {
        $synced++;
        log_line("  [SYNCED] expense id={$row['id']} user_id={$row['user_id']} amount={$row['amount']}");
    } else {
        $failed++;
        log_line("  [FAILED] expense id={$row['id']} user_id={$row['user_id']}");
    }
}

// ── Summary ──────────────────────────────────────────────────────────────────

log_line('');
log_line('=== BACKFILL COMPLETE ===');
log_line("Synced : {$synced}");
log_line("Skipped: {$skipped}  (already had ledger_synced=1)");
log_line("Failed : {$failed}");

// Verification counts
$totalLedger   = (int) $db->query("SELECT COUNT(*) FROM user_ledgers")->fetchColumn();
$totalAdvances = (int) $db->query("SELECT COUNT(*) FROM advances WHERE status IN ('approved','paid') AND ledger_synced = 1")->fetchColumn();
$totalExpenses = (int) $db->query("SELECT COUNT(*) FROM expenses WHERE status IN ('approved','paid') AND ledger_synced = 1 AND (source_advance_id IS NULL OR source_advance_id = 0)")->fetchColumn();

log_line('');
log_line('=== VERIFICATION ===');
log_line("user_ledgers total rows : {$totalLedger}");
log_line("advances  ledger_synced=1: {$totalAdvances}");
log_line("expenses  ledger_synced=1: {$totalExpenses}");

if ($failed > 0) {
    log_line('');
    log_line('[WARNING] Some records failed. Check PHP error log for details.');
    exit(1);
}

exit(0);
