<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::connect();
    
    $departmentId = $_GET['department_id'] ?? null;
    
    if ($departmentId) {
        // Get department name first
        $deptStmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
        $deptStmt->execute([$departmentId]);
        $dept = $deptStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dept) {
            $stmt = $db->prepare("SELECT id, category_name, department_name FROM task_categories WHERE department_name = ? AND is_active = 1 ORDER BY category_name ASC");
            $stmt->execute([$dept['name']]);
        } else {
            $stmt = $db->query("SELECT id, category_name, department_name FROM task_categories WHERE is_active = 1 ORDER BY category_name ASC LIMIT 0");
        }
    } else {
        $stmt = $db->query("SELECT id, category_name, department_name FROM task_categories WHERE is_active = 1 ORDER BY category_name ASC");
    }
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'categories' => $categories]);
    
} catch (Exception $e) {
    error_log('Task categories API error: ' . $e->getMessage());
    http_response_code(200);
    echo json_encode(['success' => true, 'categories' => []]);
}
