<?php
$pdo = new PDO("mysql:host=localhost;dbname=ergon_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `reference_id` int DEFAULT NULL AFTER `action_type`");
    echo "Added reference_id column<br>";
} catch(Exception $e) { 
    echo "reference_id exists or error: " . $e->getMessage() . "<br>"; 
}

echo "Fix completed!";
?>