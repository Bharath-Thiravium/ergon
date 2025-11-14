<?php
require_once __DIR__ . '/app/config/database.php';
$db = Database::connect();
$db->exec("UPDATE attendance SET check_out = NULL WHERE check_out = '' OR check_out = '0000-00-00 00:00:00'");
echo "Cleaned empty check_out values";
?>