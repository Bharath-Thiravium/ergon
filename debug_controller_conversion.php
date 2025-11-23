<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/TimezoneHelper.php';

echo "<h2>Controller Conversion Debug</h2>";

try {
    $db = Database::connect();
    
    // Simulate the exact controller query
    $filterDate = date('Y-m-d');
    $roleFilter = "u.role = 'user'";
    
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
        WHERE $roleFilter AND u.status = 'active'
        ORDER BY u.role DESC, u.name
        LIMIT 3
    ");
    $stmt->execute([$filterDate]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Before Conversion:</h3>";
    foreach ($employees as $i => $employee) {
        echo "Employee $i:<br>";
        echo "- check_in: " . ($employee['check_in'] ?? 'NULL') . "<br>";
        echo "- check_out: " . ($employee['check_out'] ?? 'NULL') . "<br>";
    }
    
    // Apply the controller conversion
    foreach ($employees as &$employee) {
        echo "<h4>Converting Employee: " . $employee['name'] . "</h4>";
        
        if ($employee['check_in']) {
            echo "Original check_in: " . $employee['check_in'] . "<br>";
            $employee['check_in'] = TimezoneHelper::toIst($employee['check_in']);
            echo "Converted check_in: " . $employee['check_in'] . "<br>";
        }
        
        if ($employee['check_out']) {
            echo "Original check_out: " . $employee['check_out'] . "<br>";
            $employee['check_out'] = TimezoneHelper::toIst($employee['check_out']);
            echo "Converted check_out: " . $employee['check_out'] . "<br>";
        }
    }
    
    echo "<h3>After Conversion:</h3>";
    foreach ($employees as $i => $employee) {
        echo "Employee $i:<br>";
        echo "- check_in: " . ($employee['check_in'] ?? 'NULL') . "<br>";
        echo "- check_out: " . ($employee['check_out'] ?? 'NULL') . "<br>";
        echo "- Display check_in: " . TimezoneHelper::displayTime($employee['check_in']) . "<br>";
        echo "- Display check_out: " . TimezoneHelper::displayTime($employee['check_out']) . "<br>";
        echo "---<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>