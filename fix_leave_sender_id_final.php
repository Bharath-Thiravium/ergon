<?php
require_once __DIR__ . '/app/config/database.php';

$db = Database::connect();

// Check if there's a sender_id column in leaves table (there shouldn't be)
$stmt = $db->query("SHOW COLUMNS FROM leaves LIKE 'sender_id'");
if ($stmt->fetch()) {
    // Remove sender_id column from leaves table if it exists
    $db->exec("ALTER TABLE leaves DROP COLUMN sender_id");
    echo "Removed sender_id column from leaves table\n";
}

// Make notifications sender_id nullable
$db->exec("ALTER TABLE notifications MODIFY COLUMN sender_id INT NULL");

// Drop all notification triggers completely
$db->exec("DROP TRIGGER IF EXISTS leave_notification_insert");
$db->exec("DROP TRIGGER IF EXISTS leave_notification_update");

echo "Fixed leave creation - sender_id issue resolved";
?>