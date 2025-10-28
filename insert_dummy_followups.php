<?php
require_once 'app/config/database.php';

try {
    $pdo = Database::connect();
    
    // Clear existing data
    $pdo->exec("DELETE FROM followup_history");
    $pdo->exec("DELETE FROM followups");
    $pdo->exec("ALTER TABLE followups AUTO_INCREMENT = 1");
    
    // Insert dummy followups
    $followups = [
        [1, 'Website Redesign Discussion', 'TechCorp Solutions', 'John Smith', '+1-555-0101', 'Corporate Website', '2024-01-20', '2024-01-20', 'Discuss new website requirements and timeline. Client wants modern responsive design with e-commerce integration.', 'pending'],
        [1, 'Mobile App Development Quote', 'StartupXYZ', 'Sarah Johnson', '+1-555-0102', 'Mobile App Project', '2024-01-15', '2024-01-15', 'Follow up on mobile app development proposal. They need iOS and Android apps for their delivery service.', 'pending'],
        [1, 'Database Migration Planning', 'TechCorp Solutions', 'John Smith', '+1-555-0101', 'Legacy System Upgrade', '2024-01-25', '2024-01-25', 'Plan the migration of legacy database to cloud infrastructure. Critical for their digital transformation.', 'pending'],
        [1, 'Security Audit Results', 'FinanceFirst Bank', 'Michael Brown', '+1-555-0103', 'Security Assessment', '2024-01-22', '2024-01-22', 'Present security audit findings and remediation plan. High priority due to compliance requirements.', 'in_progress'],
        [1, 'E-commerce Platform Demo', 'RetailMax Inc', 'Emily Davis', '+1-555-0104', 'Online Store Setup', '2024-01-15', '2024-01-15', 'Demonstrate e-commerce platform capabilities and pricing options. They want to launch by Q2.', 'completed'],
        [1, 'Cloud Migration Strategy', 'ManufacturingPro', 'David Wilson', '+1-555-0105', 'Infrastructure Modernization', '2024-01-28', '2024-01-20', 'Discuss cloud migration roadmap and cost analysis. They have 200+ servers to migrate.', 'postponed'],
        [1, 'API Integration Support', 'StartupXYZ', 'Sarah Johnson', '+1-555-0102', 'Third-party Integrations', '2024-01-24', '2024-01-24', 'Help with payment gateway and shipping API integrations. Technical support needed urgently.', 'pending'],
        [1, 'Training Session Scheduling', 'FinanceFirst Bank', 'Michael Brown', '+1-555-0103', 'Staff Training Program', '2024-01-30', '2024-01-25', 'Schedule cybersecurity training for 50+ employees. Must complete before audit deadline.', 'pending'],
        [1, 'Performance Optimization Review', 'RetailMax Inc', 'Emily Davis', '+1-555-0104', 'Website Performance', '2024-01-26', '2024-01-26', 'Review website performance metrics and optimization recommendations. Site speed is critical for sales.', 'pending'],
        [1, 'Contract Renewal Discussion', 'ManufacturingPro', 'David Wilson', '+1-555-0105', 'Annual Support Contract', '2024-01-16', '2024-01-16', 'Discuss renewal terms for annual support contract. They want to add more services this year.', 'completed']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO followups (user_id, title, company_name, contact_person, contact_phone, project_name, follow_up_date, original_date, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($followups as $i => $followup) {
        try {
            $stmt->execute($followup);
        } catch (Exception $e) {
            echo "Error inserting followup " . ($i+1) . ": " . $e->getMessage() . "<br>";
        }
    }
    
    // Add some history records
    $histories = [
        [1, 'created', null, 'Follow-up created', 'Initial creation', 1],
        [2, 'created', null, 'Follow-up created', 'Initial creation', 1],
        [2, 'postponed', '2024-01-15', '2024-01-18', 'Client requested delay due to budget review', 1],
        [3, 'created', null, 'Follow-up created', 'Initial creation', 1],
        [4, 'created', null, 'Follow-up created', 'Initial creation', 1],
        [4, 'in_progress', 'pending', 'in_progress', 'Started working on security audit presentation', 1],
        [5, 'created', null, 'Follow-up created', 'Initial creation', 1],
        [5, 'completed', 'pending', 'completed', 'Successfully demonstrated platform, client signed contract', 1],
        [6, 'created', null, 'Follow-up created', 'Initial creation', 1],
        [6, 'postponed', '2024-01-20', '2024-01-28', 'Client needs more time for internal approvals', 1],
        [8, 'created', null, 'Follow-up created', 'Initial creation', 1],
        [8, 'rescheduled', '2024-01-25', '2024-01-30', 'Moved to accommodate client schedule', 1],
        [10, 'created', null, 'Follow-up created', 'Initial creation', 1],
        [10, 'completed', 'pending', 'completed', 'Contract renewed with additional services', 1]
    ];
    
    $historyStmt = $pdo->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($histories as $i => $history) {
        try {
            $historyStmt->execute($history);
        } catch (Exception $e) {
            echo "Error inserting history " . ($i+1) . ": " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h3>✅ Dummy Data Inserted Successfully!</h3>";
    echo "<p>Inserted 10 followups with various statuses and 14 history records.</p>";
    echo "<p><strong>Data includes:</strong></p>";
    echo "<ul>";
    echo "<li>5 different companies</li>";
    echo "<li>5 different contact persons</li>";
    echo "<li>Various project types</li>";
    echo "<li>All status types (pending, completed, postponed, etc.)</li>";
    echo "<li>Overdue, today, and future dates</li>";
    echo "<li>Detailed descriptions</li>";
    echo "<li>History tracking for actions</li>";
    echo "</ul>";
    echo "<p><a href='/ergon/followups' class='btn btn--primary'>View Followups</a></p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>