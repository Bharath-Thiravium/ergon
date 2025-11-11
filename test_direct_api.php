<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== DIRECT API TEST ===\n";
    
    // Test the direct query
    $stmt = $db->query("SELECT DISTINCT company_name, contact_person, project_name, contact_phone FROM tasks WHERE followup_required = 1 AND company_name IS NOT NULL AND company_name != '' ORDER BY created_at DESC LIMIT 50");
    $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($followups) . " followup records\n\n";
    
    foreach ($followups as $followup) {
        echo "Company: {$followup['company_name']}\n";
        echo "Contact: {$followup['contact_person']}\n";
        echo "Phone: {$followup['contact_phone']}\n";
        echo "Project: {$followup['project_name']}\n\n";
    }
    
    echo "=== JSON OUTPUT ===\n";
    echo json_encode(['followups' => $followups]);
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>