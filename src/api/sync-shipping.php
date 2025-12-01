<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

try {
    $pg_dsn = 'pgsql:host=72.60.218.167;port=5432;dbname=modernsap';
    $pg_user = 'postgres';
    $pg_pass = 'mango';
    
    $pg = new PDO($pg_dsn, $pg_user, $pg_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $mysql = Database::connect();
    
    $query = "SELECT id, label, address_line1, address_line2, city, state, pincode, country, is_default, created_at, updated_at, customer_id FROM finance_customershippingaddress";
    $stmt = $pg->query($query);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $inserted = 0;
    foreach ($rows as $row) {
        $row['is_default'] = $row['is_default'] ? 1 : 0;
        $row['created_at'] = substr($row['created_at'], 0, 19);
        $row['updated_at'] = substr($row['updated_at'], 0, 19);
        
        $sql = "INSERT INTO finance_customershippingaddress (id, label, address_line1, address_line2, city, state, pincode, country, is_default, created_at, updated_at, customer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label), address_line1 = VALUES(address_line1), address_line2 = VALUES(address_line2), city = VALUES(city), state = VALUES(state), pincode = VALUES(pincode), country = VALUES(country), is_default = VALUES(is_default), updated_at = VALUES(updated_at)";
        
        $stmt = $mysql->prepare($sql);
        $stmt->execute([$row['id'], $row['label'], $row['address_line1'], $row['address_line2'], $row['city'], $row['state'], $row['pincode'], $row['country'], $row['is_default'], $row['created_at'], $row['updated_at'], $row['customer_id']]);
        $inserted++;
    }
    
    echo json_encode(['success' => true, 'message' => "Synced $inserted shipping addresses"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
