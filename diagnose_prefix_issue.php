<?php
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::connect();
    $testPrefixes = ['SE', 'TC'];
    $results = [];
    
    foreach ($testPrefixes as $prefix) {
        $prefixData = ['prefix' => $prefix];
        
        // Check quotations
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations' LIMIT 10");
        $stmt->execute();
        $quotationSamples = [];
        $quotationMatches = 0;
        
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data = json_decode($row['data'], true);
            if (!$data) continue;
            
            $number = $data['quotation_number'] ?? $data['quote_number'] ?? '';
            $matches = stripos($number, $prefix) === 0;
            if ($matches) $quotationMatches++;
            
            $quotationSamples[] = [
                'number' => $number,
                'matches' => $matches,
                'amount' => floatval($data['total_amount'] ?? $data['amount'] ?? 0)
            ];
        }
        
        // Check POs
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders' LIMIT 10");
        $stmt->execute();
        $poSamples = [];
        $poMatches = 0;
        
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data = json_decode($row['data'], true);
            if (!$data) continue;
            
            $number = $data['po_number'] ?? $data['internal_po_number'] ?? '';
            $matches = stripos($number, $prefix) === 0;
            if ($matches) $poMatches++;
            
            $poSamples[] = [
                'number' => $number,
                'matches' => $matches,
                'amount' => floatval($data['total_amount'] ?? $data['amount'] ?? 0)
            ];
        }
        
        $prefixData['quotations'] = [
            'matches' => $quotationMatches,
            'samples' => array_slice($quotationSamples, 0, 5)
        ];
        
        $prefixData['purchase_orders'] = [
            'matches' => $poMatches,
            'samples' => array_slice($poSamples, 0, 5)
        ];
        
        $results[] = $prefixData;
    }
    
    echo json_encode($results, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>