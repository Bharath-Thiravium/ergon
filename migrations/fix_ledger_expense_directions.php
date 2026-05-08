<?php
/**
 * Fix existing user_ledger rows where expense entries were incorrectly
 * stored as 'credit' instead of 'debit', then recalculate all balance_after
 * values per user in chronological order.
 *
 * Run once: php migrations/fix_ledger_expense_directions.php
 */

require_once __DIR__ . '/../app/config/database.php';

$db = Database::connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Fix direction on misclassified expense rows
$fixed = $db->exec("
    UPDATE user_ledgers
    SET direction = 'debit'
    WHERE reference_type = 'expense'
      AND entry_type     = 'expense_payment'
      AND direction      = 'credit'
");
echo "Fixed $fixed expense rows (credit → debit)\n";

// 2. Recalculate balance_after for every user in chronological order
$users = $db->query("SELECT DISTINCT user_id FROM user_ledgers ORDER BY user_id")->fetchAll(PDO::FETCH_COLUMN);

$sel = $db->prepare("
    SELECT id, direction, amount
    FROM user_ledgers
    WHERE user_id = ?
    ORDER BY created_at ASC, id ASC
");
$upd = $db->prepare("UPDATE user_ledgers SET balance_after = ? WHERE id = ?");

foreach ($users as $userId) {
    $sel->execute([$userId]);
    $rows    = $sel->fetchAll(PDO::FETCH_ASSOC);
    $running = 0.0;
    foreach ($rows as $row) {
        $running += $row['direction'] === 'credit' ? floatval($row['amount']) : -floatval($row['amount']);
        $upd->execute([$running, $row['id']]);
    }
    echo "Recalculated balances for user_id=$userId (" . count($rows) . " rows), final balance=$running\n";
}

echo "\nDone.\n";
