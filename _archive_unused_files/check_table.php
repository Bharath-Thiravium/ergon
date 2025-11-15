<?php
require_once __DIR__ . '/app/config/database.php';
$db = Database::connect();
$stmt = $db->query("DESCRIBE attendance");
while ($row = $stmt->fetch()) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Default'] . "\n";
}
?>