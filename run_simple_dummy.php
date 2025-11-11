<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== INJECTING SIMPLE DUMMY DATA ===\n";
    
    // Run the simple dummy data script
    $sql = file_get_contents('simple_dummy_data.sql');
    $db->exec($sql);
    
    echo "✅ Tasks with follow-up data injected successfully!\n\n";
    
    // Show follow-up tasks
    echo "=== FOLLOW-UP TASKS ===\n";
    $stmt = $db->query("SELECT title, company_name, contact_person, contact_phone, project_name, followup_date FROM tasks WHERE followup_required = 1");
    $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($followups as $followup) {
        echo "📞 {$followup['title']}\n";
        echo "   Company: {$followup['company_name']}\n";
        echo "   Contact: {$followup['contact_person']} ({$followup['contact_phone']})\n";
        echo "   Project: {$followup['project_name']}\n";
        echo "   Date: {$followup['followup_date']}\n\n";
    }
    
    echo "🎉 You can now test the follow-up search functionality!\n";
    echo "Go to: http://localhost/ergon/followups/create\n";
    echo "Try typing: ABC, John, ERP, XYZ, Sarah, etc.\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>