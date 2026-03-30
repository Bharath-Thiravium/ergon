<?php
require_once __DIR__ . '/app/config/database.php';

$db = Database::connect();

// Fix record 29 - assign to Market Research project
$stmt = $db->prepare("UPDATE attendance SET project_id = 15 WHERE id = 29");
$stmt->execute();

echo "✅ Fixed record 29 with project_id = 15 (Market Research - Madurai)";
?>
