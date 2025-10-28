<?php
require_once 'app/config/database.php';

try {
    $pdo = Database::connect();
    
    // Get current max ID to calculate new IDs
    $maxId = $pdo->query("SELECT COALESCE(MAX(id), 0) FROM followups")->fetchColumn();
    
    $sql = "INSERT INTO followups (user_id, title, company_name, contact_person, contact_phone, project_name, follow_up_date, original_date, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    $sarahFollowups = [
        [1, 'Mobile App UI/UX Review', 'StartupXYZ', 'Sarah Johnson', '555-0102', 'Mobile App Design', '2024-01-21', '2024-01-18', 'Review and finalize the user interface design for the mobile application. Focus on user experience and accessibility features.', 'completed'],
        [1, 'Payment Gateway Integration', 'TechFlow Solutions', 'Sarah Johnson', '555-0102', 'E-commerce Backend', '2024-01-23', '2024-01-20', 'Integrate Stripe and PayPal payment gateways into the mobile app. Ensure secure transaction processing and error handling.', 'in_progress'],
        [1, 'App Store Submission Process', 'Digital Ventures', 'Sarah Johnson', '555-0102', 'App Publishing', '2024-01-15', '2024-01-15', 'Discuss app store submission requirements for both iOS and Android platforms. Prepare marketing materials and descriptions.', 'postponed'],
        [1, 'Beta Testing Coordination', 'StartupXYZ', 'Sarah Johnson', '555-0102', 'Quality Assurance', '2024-01-29', '2024-01-25', 'Coordinate beta testing with selected users. Set up feedback collection system and bug tracking process.', 'pending'],
        [1, 'Push Notification Setup', 'TechFlow Solutions', 'Sarah Johnson', '555-0102', 'Mobile Features', '2024-01-31', '2024-01-31', 'Configure push notification system for order updates, promotions, and user engagement. Test delivery across devices.', 'cancelled'],
        [1, 'Database Optimization Review', 'Digital Ventures', 'Sarah Johnson', '555-0102', 'Performance Tuning', '2024-02-02', '2024-01-28', 'Review database queries and optimize performance for mobile app. Focus on reducing load times and improving user experience.', 'in_progress'],
        [1, 'Security Audit Follow-up', 'StartupXYZ', 'Sarah Johnson', '555-0102', 'Security Assessment', '2024-02-05', '2024-02-05', 'Follow up on security audit recommendations. Implement additional security measures for user data protection and API security.', 'completed'],
        [1, 'Marketing Integration Planning', 'TechFlow Solutions', 'Sarah Johnson', '555-0102', 'Marketing Tools', '2024-01-12', '2024-01-10', 'Plan integration with marketing tools like Google Analytics, Facebook Pixel, and email marketing platforms for user tracking.', 'postponed'],
        [1, 'Launch Strategy Discussion', 'Digital Ventures', 'Sarah Johnson', '555-0102', 'Product Launch', '2024-02-10', '2024-02-08', 'Discuss go-to-market strategy, launch timeline, and post-launch support plan. Coordinate with marketing and sales teams.', 'pending'],
        [1, 'Post-Launch Support Planning', 'StartupXYZ', 'Sarah Johnson', '555-0102', 'Maintenance Contract', '2024-02-12', '2024-02-12', 'Plan ongoing support and maintenance services. Discuss SLA requirements, update schedules, and feature enhancement roadmap.', 'pending']
    ];
    
    $followupIds = [];
    foreach ($sarahFollowups as $followup) {
        $stmt->execute($followup);
        $followupIds[] = $pdo->lastInsertId();
    }
    
    // Add diverse history records
    $historyStmt = $pdo->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    
    $histories = [
        [$followupIds[0], 'created', null, 'Follow-up created', 'Initial UI/UX review task created', 1],
        [$followupIds[0], 'in_progress', 'pending', 'in_progress', 'Started working on UI mockups', 1],
        [$followupIds[0], 'completed', 'in_progress', 'completed', 'UI/UX review completed successfully, client approved designs', 1],
        
        [$followupIds[1], 'created', null, 'Follow-up created', 'Payment gateway integration task created', 1],
        [$followupIds[1], 'in_progress', 'pending', 'in_progress', 'Started Stripe integration development', 1],
        
        [$followupIds[2], 'created', null, 'Follow-up created', 'App store submission planning initiated', 1],
        [$followupIds[2], 'postponed', '2024-01-15', '2024-01-27', 'Client requested delay for additional features', 1],
        
        [$followupIds[3], 'created', null, 'Follow-up created', 'Beta testing coordination setup', 1],
        
        [$followupIds[4], 'created', null, 'Follow-up created', 'Push notification system planning', 1],
        [$followupIds[4], 'cancelled', 'pending', 'cancelled', 'Client decided to use third-party service instead', 1],
        
        [$followupIds[5], 'created', null, 'Follow-up created', 'Database optimization review scheduled', 1],
        [$followupIds[5], 'in_progress', 'pending', 'in_progress', 'Started performance analysis and query optimization', 1],
        
        [$followupIds[6], 'created', null, 'Follow-up created', 'Security audit follow-up scheduled', 1],
        [$followupIds[6], 'completed', 'pending', 'completed', 'All security recommendations implemented successfully', 1],
        
        [$followupIds[7], 'created', null, 'Follow-up created', 'Marketing integration planning started', 1],
        [$followupIds[7], 'postponed', '2024-01-12', '2024-02-07', 'Postponed until after app launch for better focus', 1],
        
        [$followupIds[8], 'created', null, 'Follow-up created', 'Launch strategy discussion planned', 1],
        
        [$followupIds[9], 'created', null, 'Follow-up created', 'Support planning initiated for post-launch phase', 1]
    ];
    
    foreach ($histories as $history) {
        $historyStmt->execute($history);
    }
    
    echo "<h3>✅ Added 10 Diverse Followups for Sarah Johnson!</h3>";
    echo "<p><strong>Features:</strong></p>";
    echo "<ul>";
    echo "<li>3 different companies (StartupXYZ, TechFlow Solutions, Digital Ventures)</li>";
    echo "<li>All 6 status types: completed, in_progress, postponed, pending, cancelled</li>";
    echo "<li>Various dates (overdue, current, future)</li>";
    echo "<li>18 detailed history records showing status changes</li>";
    echo "<li>Different project types and descriptions</li>";
    echo "</ul>";
    echo "<p><strong>Perfect for testing consolidated view!</strong></p>";
    echo "<p><a href='/ergon/followups'>View Followups</a></p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>