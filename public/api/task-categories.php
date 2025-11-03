<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../app/config/database.php';

try {
    $db = Database::connect();
    
    $departmentId = $_GET['department_id'] ?? null;
    
    if (!$departmentId) {
        echo json_encode(['error' => 'Department ID is required']);
        exit;
    }
    
    // Get department name first
    $stmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->execute([$departmentId]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$department) {
        echo json_encode(['error' => 'Department not found']);
        exit;
    }
    
    // Get task categories for this department
    $stmt = $db->prepare("SELECT DISTINCT category_name, description FROM task_categories WHERE department_name = ? AND is_active = 1 ORDER BY category_name");
    $stmt->execute([$department['name']]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['categories' => $categories]);
    
} catch (Exception $e) {
    error_log('Task categories API error: ' . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch categories']);
}
?>