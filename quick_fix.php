<?php
$pdo = new PDO("mysql:host=localhost;dbname=ergon_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `sender_id` int DEFAULT NULL AFTER `id`");
    echo "Added sender_id<br>";
} catch(Exception $e) { echo "sender_id exists<br>"; }

try {
    $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `receiver_id` int DEFAULT NULL AFTER `sender_id`");
    echo "Added receiver_id<br>";
} catch(Exception $e) { echo "receiver_id exists<br>"; }

try {
    $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `module_name` varchar(50) DEFAULT NULL AFTER `message`");
    echo "Added module_name<br>";
} catch(Exception $e) { echo "module_name exists<br>"; }

try {
    $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `action_type` varchar(50) DEFAULT NULL AFTER `module_name`");
    echo "Added action_type<br>";
} catch(Exception $e) { echo "action_type exists<br>"; }

$pdo->exec("UPDATE `notifications` SET `receiver_id` = `user_id` WHERE `receiver_id` IS NULL");
echo "Updated existing records<br>";
echo "Fix completed!";
?>