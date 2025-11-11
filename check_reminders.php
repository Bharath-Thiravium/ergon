<?php
header('Content-Type: application/json');
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Check if dummy data exists
    $taskCount = $db->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $plannerCount = $db->query("SELECT COUNT(*) FROM daily_planner")->fetchColumn();
    $updateCount = $db->query("SELECT COUNT(*) FROM evening_updates")->fetchColumn();
    
    // Add minimal dummy data if none exists
    if ($taskCount == 0) {
        $sql = file_get_contents(__DIR__ . '/database/minimal_dummy_data.sql');
        $db->exec($sql);
        
        $taskCount = $db->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
        $updateCount = $db->query("SELECT COUNT(*) FROM evening_updates")->fetchColumn();
    }
    
    echo json_encode([
        'status' => 'success',
        'data_populated' => true,
        'tasks' => $taskCount,
        'planner_entries' => $plannerCount,
        'evening_updates' => $updateCount,
        'message' => 'Dummy data ready for unified workflow'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>