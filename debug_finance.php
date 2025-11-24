<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== FINANCE DATABASE ANALYSIS ===\n\n";
    
    // Check quotations
    echo "QUOTATIONS SAMPLE:\n";
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $data = json_decode($result['data'], true);
        echo "Fields: " . implode(", ", array_keys($data)) . "\n\n";
        foreach ($data as $key => $value) {
            if (stripos($key, 'name') !== false || 
                stripos($key, 'company') !== false || 
                stripos($key, 'customer') !== false || 
                stripos($key, 'address') !== false || 
                stripos($key, 'location') !== false || 
                stripos($key, 'delivery') !== false || 
                stripos($key, 'shipping') !== false || 
                stripos($key, 'dispatch') !== false) {
                echo "$key: " . (is_string($value) ? $value : json_encode($value)) . "\n";
            }
        }
    }
    
    echo "\n\nCUSTOMERS SAMPLE:\n";
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_customers' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $data = json_decode($result['data'], true);
        echo "Fields: " . implode(", ", array_keys($data)) . "\n\n";
        foreach ($data as $key => $value) {
            if (stripos($key, 'name') !== false || 
                stripos($key, 'company') !== false || 
                stripos($key, 'customer') !== false || 
                stripos($key, 'address') !== false || 
                stripos($key, 'location') !== false || 
                stripos($key, 'delivery') !== false || 
                stripos($key, 'shipping') !== false || 
                stripos($key, 'dispatch') !== false) {
                echo "$key: " . (is_string($value) ? $value : json_encode($value)) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>