<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::connect();
    
    // Collect all full prefixes from different sources
    $allPrefixes = [];
    
    $tables = [
        ['table' => 'finance_customer', 'column' => 'customer_code'],
        ['table' => 'finance_invoices', 'column' => 'invoice_number'],
        ['table' => 'finance_quotations', 'column' => 'quotation_number'],
        ['table' => 'finance_purchase_orders', 'column' => 'internal_po_number']
    ];
    
    foreach ($tables as $source) {
        $stmt = $db->query("SELECT DISTINCT {$source['column']} as full_prefix FROM {$source['table']} WHERE {$source['column']} IS NOT NULL AND LENGTH({$source['column']}) >= 2");
        while ($row = $stmt->fetch()) {
            if ($row['full_prefix'] && strlen($row['full_prefix']) >= 2) {
                $allPrefixes[] = strtoupper($row['full_prefix']);
            }
        }
    }
    
    $allPrefixes = array_unique($allPrefixes);
    
    // Build hierarchical prefix tree
    function buildPrefixTree($prefixes, $currentLength = 2) {
        $tree = [];
        $grouped = [];
        
        // Group by current length
        foreach ($prefixes as $prefix) {
            if (strlen($prefix) >= $currentLength) {
                $key = substr($prefix, 0, $currentLength);
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [];
                }
                $grouped[$key][] = $prefix;
            }
        }
        
        foreach ($grouped as $key => $group) {
            if (count($group) == 1) {
                // Unique at this level
                $tree[$key] = null;
            } else {
                // Need to go deeper
                $nextLetters = [];
                foreach ($group as $prefix) {
                    if (strlen($prefix) > $currentLength) {
                        $nextLetters[] = substr($prefix, $currentLength, 1);
                    }
                }
                if (count(array_unique($nextLetters)) > 1) {
                    $tree[$key] = array_unique($nextLetters);
                } else {
                    $tree[$key] = null;
                }
            }
        }
        
        return $tree;
    }
    
    $prefixTree = buildPrefixTree($allPrefixes);
    $prefixes = array_keys($prefixTree);
    
    sort($prefixes);
    
    echo json_encode([
        'success' => true,
        'prefixes' => array_values($prefixes),
        'prefix_tree' => $prefixTree
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
