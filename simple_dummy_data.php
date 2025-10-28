<?php
require_once 'app/config/database.php';

try {
    $pdo = Database::connect();
    
    // Clear existing data
    $pdo->exec("DELETE FROM followup_history");
    $pdo->exec("DELETE FROM followups");
    $pdo->exec("ALTER TABLE followups AUTO_INCREMENT = 1");
    
    // Simple insert with only valid enum values
    $sql = "INSERT INTO followups (user_id, title, company_name, contact_person, contact_phone, project_name, follow_up_date, original_date, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    $data = [
        [1, 'Website Redesign', 'TechCorp', 'John Smith', '555-0101', 'Website Project', '2024-01-20', '2024-01-20', 'Discuss website requirements', 'pending'],
        [1, 'Mobile App Quote', 'StartupXYZ', 'Sarah Johnson', '555-0102', 'Mobile App', '2024-01-15', '2024-01-15', 'Mobile app development proposal', 'pending'],
        [1, 'Database Migration', 'TechCorp', 'John Smith', '555-0101', 'Legacy Upgrade', '2024-01-25', '2024-01-25', 'Database migration planning', 'pending'],
        [1, 'Security Audit', 'FinanceFirst', 'Michael Brown', '555-0103', 'Security Project', '2024-01-22', '2024-01-22', 'Security audit results', 'in_progress'],
        [1, 'E-commerce Demo', 'RetailMax', 'Emily Davis', '555-0104', 'Online Store', '2024-01-15', '2024-01-15', 'E-commerce platform demo', 'completed'],
        [1, 'Cloud Migration', 'ManufacturingPro', 'David Wilson', '555-0105', 'Infrastructure', '2024-01-28', '2024-01-20', 'Cloud migration strategy', 'postponed'],
        [1, 'API Integration', 'StartupXYZ', 'Sarah Johnson', '555-0102', 'Integrations', '2024-01-24', '2024-01-24', 'API integration support', 'pending'],
        [1, 'Training Session', 'FinanceFirst', 'Michael Brown', '555-0103', 'Training', '2024-01-30', '2024-01-25', 'Staff training scheduling', 'pending'],
        [1, 'Performance Review', 'RetailMax', 'Emily Davis', '555-0104', 'Optimization', '2024-01-26', '2024-01-26', 'Performance optimization', 'pending'],
        [1, 'Contract Renewal', 'ManufacturingPro', 'David Wilson', '555-0105', 'Support Contract', '2024-01-16', '2024-01-16', 'Annual contract renewal', 'completed']
    ];
    
    foreach ($data as $row) {
        $stmt->execute($row);
    }
    
    // Add simple history
    $historyStmt = $pdo->prepare("INSERT INTO followup_history (followup_id, action, notes, created_by) VALUES (?, ?, ?, ?)");
    $historyStmt->execute([1, 'created', 'Follow-up created', 1]);
    $historyStmt->execute([5, 'completed', 'Demo completed successfully', 1]);
    $historyStmt->execute([6, 'postponed', 'Client requested delay', 1]);
    $historyStmt->execute([10, 'completed', 'Contract renewed', 1]);
    
    echo "<h3>✅ Dummy Data Inserted Successfully!</h3>";
    echo "<p>10 followups with 4 history records created.</p>";
    echo "<p><a href='/ergon/followups'>View Followups</a></p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>