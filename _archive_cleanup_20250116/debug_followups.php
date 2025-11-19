<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Followups Table Structure</h2>";
    $stmt = $db->query("SHOW COLUMNS FROM followups");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($columns, true) . "</pre>";
    
    echo "<h2>All Followups Records</h2>";
    $stmt = $db->query("SELECT * FROM followups ORDER BY created_at DESC LIMIT 10");
    $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($followups, true) . "</pre>";
    
    echo "<h2>Tasks with Follow-up Required</h2>";
    $stmt = $db->query("SELECT id, title, followup_required FROM tasks WHERE followup_required = 1 ORDER BY created_at DESC LIMIT 5");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($tasks, true) . "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>