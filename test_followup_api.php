<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== TESTING FOLLOWUP API ===\n";
    
    // Check if tasks have followup data
    $stmt = $db->query("SELECT COUNT(*) as count FROM tasks WHERE followup_required = 1");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Tasks with followup_required = 1: $count\n\n";
    
    // Check actual followup data
    $stmt = $db->query("SELECT company_name, contact_person, project_name, contact_phone FROM tasks WHERE followup_required = 1 AND company_name IS NOT NULL LIMIT 10");
    $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== FOLLOWUP DATA IN DATABASE ===\n";
    foreach ($followups as $followup) {
        echo "Company: {$followup['company_name']}\n";
        echo "Contact: {$followup['contact_person']}\n";
        echo "Phone: {$followup['contact_phone']}\n";
        echo "Project: {$followup['project_name']}\n\n";
    }
    
    // Test the API endpoint directly
    echo "=== TESTING API ENDPOINT ===\n";
    
    // Simulate API call
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    require_once __DIR__ . '/app/controllers/ApiController.php';
    $api = new ApiController();
    
    ob_start();
    $api->followupDetails();
    $output = ob_get_clean();
    
    echo "API Response: $output\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>