<?php
require_once 'app/config/database.php';
$db = Database::connect();
$stmt = $db->prepare('
    SELECT e.id, e.description, e.status, e.created_at, e.project_id, e.amount, 
           ae.approved_amount, u.name as user_name, e.user_id
    FROM expenses e 
    JOIN users u ON e.user_id = u.id 
    LEFT JOIN approved_expenses ae ON e.id = ae.expense_id 
    WHERE e.project_id = 17 
    ORDER BY e.created_at DESC LIMIT 20
');
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h2>Project 17 Expenses (Top 20)</h2><pre>" . json_encode($rows, JSON_PRETTY_PRINT) . "</pre>";

$count = $db->prepare("SELECT COUNT(*) FROM expenses WHERE project_id=17 AND status='approved'");
$count->execute(); echo "<p>Approved: " . $count->fetchColumn() . "</p>";

$count = $db->prepare("SELECT COUNT(*) FROM expenses WHERE project_id=17 AND status='paid'");
$count->execute(); echo "<p>Paid: " . $count->fetchColumn() . "</p>";
?>

