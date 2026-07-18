<?php
// DELETE THIS FILE AFTER USE
require_once __DIR__ . '/app/config/database.php';
$db = Database::connect();
$id = intval($_GET['id'] ?? 60);

echo "<pre>";

// 1. User info
$r = $db->prepare("SELECT id, name, role FROM users WHERE id = ?");
$r->execute([$id]);
echo "=== USER ===\n";
print_r($r->fetch(PDO::FETCH_ASSOC));

// 2. All advances for this user
$r = $db->prepare("SELECT id, user_id, paid_by, amount, approved_amount, status, reason FROM advances WHERE user_id = ? OR paid_by = ? ORDER BY id DESC");
$r->execute([$id, $id]);
echo "\n=== ADVANCES (user_id OR paid_by = $id) ===\n";
print_r($r->fetchAll(PDO::FETCH_ASSOC));

// 3. All expenses for this user
$r = $db->prepare("SELECT id, user_id, paid_by, amount, approved_amount, status, description, source_advance_id FROM expenses WHERE user_id = ? OR paid_by = ? ORDER BY id DESC");
$r->execute([$id, $id]);
echo "\n=== EXPENSES (user_id OR paid_by = $id) ===\n";
print_r($r->fetchAll(PDO::FETCH_ASSOC));

echo "</pre>";
