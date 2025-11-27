<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== PROJECTS TABLE ===\n";
    $stmt = $db->query("SELECT * FROM projects LIMIT 5");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($projects);
    
    echo "\n=== TASKS TABLE (project_name field) ===\n";
    $stmt = $db->query("SELECT id, title, project_name, status FROM tasks WHERE project_name IS NOT NULL AND project_name != '' LIMIT 5");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($tasks);
    
    echo "\n=== PROJECT OVERVIEW QUERY ===\n";
    $stmt = $db->query("
        SELECT 
            p.name as project_name,
            p.status as project_status,
            p.description,
            d.name as department_name,
            COALESCE(COUNT(t.id), 0) as total_tasks,
            COALESCE(SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END), 0) as completed_tasks,
            COALESCE(SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END), 0) as in_progress_tasks,
            COALESCE(SUM(CASE WHEN t.status IN ('assigned', 'pending', 'not_started') THEN 1 ELSE 0 END), 0) as pending_tasks
        FROM projects p
        LEFT JOIN departments d ON p.department_id = d.id
        LEFT JOIN tasks t ON t.project_name = p.name
        WHERE p.status = 'active'
        GROUP BY p.id, p.name, p.status, p.description, d.name
        ORDER BY total_tasks DESC, p.created_at DESC
        LIMIT 10
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($results);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>