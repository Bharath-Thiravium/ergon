<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    require_once 'app/helpers/EmployeeHelper.php';
    require_once 'config/database.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get company name from settings
    $stmt = $conn->prepare("SELECT company_name FROM settings LIMIT 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    $companyName = $settings['company_name'] ?? 'ERGON Company';
    
    $employeeId = EmployeeHelper::generateEmployeeId($companyName);
    
    echo json_encode([
        'success' => true,
        'employee_id' => $employeeId,
        'company' => $companyName
    ]);
    
} catch (Exception $e) {
    error_log('Employee ID generation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>