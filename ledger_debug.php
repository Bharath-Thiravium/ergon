<?php
// DELETE THIS FILE AFTER USE
require_once __DIR__ . '/app/config/database.php';
$db = Database::connect();
$id = intval($_GET['id'] ?? 60);

echo "<pre>";

// What the outstanding query sees
$stmt = $db->prepare("
    SELECT id, user_id, paid_by, amount, approved_amount, status, description, expense_date, approved_at, created_at
    FROM expenses
    WHERE user_id = ? AND status IN ('approved','paid')
    ORDER BY id DESC
");
$stmt->execute([$id]);
echo "=== EXPENSES matching outstanding query (user_id=$id, status approved/paid) ===\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

// What fetchLedgerEntries expense query sees
$stmt = $db->prepare("
    SELECT id, user_id, paid_by, amount, approved_amount, status, description,
           COALESCE(expense_date, approved_at, created_at) AS resolved_date
    FROM expenses
    WHERE user_id = ? AND status IN ('approved','paid')
    ORDER BY COALESCE(expense_date, approved_at, created_at) DESC
");
$stmt->execute([$id]);
echo "\n=== EXPENSES with resolved_date ===\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "</pre>";
