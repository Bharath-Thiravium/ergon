<?php
require_once __DIR__ . '/app/config/database.php';

$db = Database::connect();

// Drop ALL triggers
$db->exec("DROP TRIGGER IF EXISTS leave_notification_insert");
$db->exec("DROP TRIGGER IF EXISTS leave_notification_update");
$db->exec("DROP TRIGGER IF EXISTS expense_notification_insert");
$db->exec("DROP TRIGGER IF EXISTS expense_notification_update");
$db->exec("DROP TRIGGER IF EXISTS advance_notification_insert");
$db->exec("DROP TRIGGER IF EXISTS advance_notification_update");
$db->exec("DROP TRIGGER IF EXISTS task_notification_insert");
$db->exec("DROP TRIGGER IF EXISTS task_notification_update");

// Check for any remaining triggers
$stmt = $db->query("SHOW TRIGGERS");
while ($trigger = $stmt->fetch()) {
    $db->exec("DROP TRIGGER IF EXISTS " . $trigger['Trigger']);
}

echo "All triggers removed - try leave creation now";
?>