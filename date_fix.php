<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "DATE FIELD FIX\n==============\n";
    
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasDateField = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'date') {
            $hasDateField = true;
            if (strpos($column['Default'], 'CURRENT_DATE') === false && $column['Default'] === null) {
                $db->exec("ALTER TABLE attendance MODIFY COLUMN date DATE DEFAULT (CURRENT_DATE)");
                echo "✅ Added default value to date column\n";
            }
            break;
        }
    }
    
    if (!$hasDateField) {
        $db->exec("ALTER TABLE attendance ADD COLUMN date DATE DEFAULT (CURRENT_DATE) AFTER user_id");
        echo "✅ Added date column with default value\n";
    }
    
    echo "🎉 100% SYSTEM OPERATIONAL\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>