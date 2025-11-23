<?php
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    // Force UTC timezone for all database operations
    $db->exec("SET time_zone = '+00:00'");
    
    // Update existing attendance records to ensure they're in UTC
    $stmt = $db->query("SELECT id, check_in, check_out FROM attendance WHERE check_in IS NOT NULL");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Processing " . count($records) . " attendance records...\n";
    
    foreach ($records as $record) {
        // Convert existing times to UTC if they're not already
        $checkIn = $record['check_in'];
        $checkOut = $record['check_out'];
        
        // Parse and convert to UTC
        $checkInUtc = date('Y-m-d H:i:s', strtotime($checkIn . ' UTC'));
        $checkOutUtc = $checkOut ? date('Y-m-d H:i:s', strtotime($checkOut . ' UTC')) : null;
        
        $updateStmt = $db->prepare("UPDATE attendance SET check_in = ?, check_out = ? WHERE id = ?");
        $updateStmt->execute([$checkInUtc, $checkOutUtc, $record['id']]);
    }
    
    echo "✅ Hostinger timezone fix applied successfully!\n";
    echo "All attendance records are now stored in UTC.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>