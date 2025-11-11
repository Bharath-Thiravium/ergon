<?php
header('Content-Type: application/json');
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Test unified workflow followup system
    $stmt = $db->query("
        SELECT 
            t.id, t.title, t.description, t.priority, t.status, t.task_category,
            t.company_name, t.contact_person, t.project_name, t.contact_phone,
            u.name as assigned_user, t.created_at
        FROM tasks t 
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE (t.followup_required = 1 OR t.task_category LIKE '%follow%' OR t.title LIKE '%follow%')
        ORDER BY t.created_at DESC
        LIMIT 20
    ");
    $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Also get daily planner data
    $stmt = $db->query("
        SELECT 
            dp.id, dp.title, dp.date, dp.completion_status, dp.notes,
            u.name as user_name
        FROM daily_planner dp
        LEFT JOIN users u ON dp.user_id = u.id
        WHERE dp.date >= CURDATE() - INTERVAL 7 DAY
        ORDER BY dp.date DESC, dp.priority_order
        LIMIT 10
    ");
    $plannerData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get evening updates
    $stmt = $db->query("
        SELECT 
            eu.id, eu.title, eu.planner_date, eu.overall_productivity,
            u.name as user_name, eu.created_at
        FROM evening_updates eu
        LEFT JOIN users u ON eu.user_id = u.id
        ORDER BY eu.created_at DESC
        LIMIT 5
    ");
    $eveningUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'followups' => $followups,
        'planner_data' => $plannerData,
        'evening_updates' => $eveningUpdates,
        'unified_workflow_status' => 'active'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'followups' => []]);
}
?>