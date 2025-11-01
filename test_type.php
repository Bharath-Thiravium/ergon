<?php
try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    
    // Check if type column exists
    $stmt = $db->query("DESCRIBE advances");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Advances table columns:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    // Check data
    $stmt = $db->query("SELECT id, type, amount, reason FROM advances LIMIT 5");
    $advances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nSample data:\n";
    foreach ($advances as $advance) {
        echo "ID: " . $advance['id'] . ", Type: '" . ($advance['type'] ?? 'NULL') . "', Amount: " . $advance['amount'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>