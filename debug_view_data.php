<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

// Simulate the exact controller logic
$role = $_SESSION['role'] ?? 'admin';
$filterDate = date('Y-m-d');

echo "<h3>Debug View Data</h3>";
echo "Session role: " . ($role ?? 'not set') . "<br>";
echo "Filter date: $filterDate<br><br>";

try {
    $db = Database::connect();
    
    $roleFilter = ($role === 'owner') ? "u.role IN ('admin', 'user')" : "u.role = 'user'";
    
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.role,
            COALESCE(d.name, 'Not Assigned') as department,
            a.check_in,
            a.check_out,
            CASE 
                WHEN a.location_name = 'On Approved Leave' THEN 'On Leave'
                WHEN a.check_in IS NOT NULL THEN 'Present'
                ELSE 'Absent'
            END as status,
            CASE 
                WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                    ROUND(TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) / 60.0, 2)
                ELSE 0
            END as total_hours
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
        WHERE $roleFilter
        ORDER BY u.role DESC, u.name
    ");
    
    $stmt->execute([$filterDate]);
    $employeeAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Query returned: " . count($employeeAttendance) . " records<br>";
    echo "Empty check: " . (empty($employeeAttendance) ? 'TRUE (empty)' : 'FALSE (has data)') . "<br><br>";
    
    if (!empty($employeeAttendance)) {
        echo "<strong>Data that should be passed to view:</strong><br>";
        echo "<pre>" . print_r($employeeAttendance, true) . "</pre>";
    }
    
    // Test what the view would receive
    $employees = $employeeAttendance; // This is what gets passed as 'employees'
    echo "<br><strong>View variable \$employees:</strong><br>";
    echo "Count: " . count($employees) . "<br>";
    echo "Empty: " . (empty($employees) ? 'YES' : 'NO') . "<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>