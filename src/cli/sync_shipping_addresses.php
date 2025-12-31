<?php
require_once __DIR__ . '/../../app/config/database.php';

$pg_dsn = 'pgsql:host=72.60.218.167;port=5432;dbname=modernsap';
$pg_user = 'postgres';
$pg_pass = 'mango';
$batch_size = 1000;

try {
    $pg_conn = new PDO($pg_dsn, $pg_user, $pg_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $mysql_db = Database::connect();
    
    $offset = 0;
    $total_synced = 0;
    
    while (true) {
        $query = "
            SELECT id, label, address_line1, address_line2, city, state, pincode, country, 
                   is_default, created_at, updated_at, customer_id
            FROM finance_customershippingaddress
            ORDER BY id
            LIMIT $batch_size OFFSET $offset
        ";
        
        $stmt = $pg_conn->query($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) break;
        
        foreach ($rows as $row) {
            $row['is_default'] = $row['is_default'] ? 1 : 0;
            $row['created_at'] = substr($row['created_at'], 0, 19);
            $row['updated_at'] = substr($row['updated_at'], 0, 19);
            
            $insert_sql = "
                INSERT INTO finance_customershippingaddress 
                (id, label, address_line1, address_line2, city, state, pincode, country, is_default, created_at, updated_at, customer_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                label = VALUES(label),
                address_line1 = VALUES(address_line1),
                address_line2 = VALUES(address_line2),
                city = VALUES(city),
                state = VALUES(state),
                pincode = VALUES(pincode),
                country = VALUES(country),
                is_default = VALUES(is_default),
                updated_at = VALUES(updated_at)
            ";
            
            $insert_stmt = $mysql_db->prepare($insert_sql);
            $insert_stmt->execute([
                $row['id'], $row['label'], $row['address_line1'], $row['address_line2'],
                $row['city'], $row['state'], $row['pincode'], $row['country'],
                $row['is_default'], $row['created_at'], $row['updated_at'], $row['customer_id']
            ]);
            
            $total_synced++;
        }
        
        $offset += $batch_size;
    }
    
    echo "Synced $total_synced shipping addresses\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
