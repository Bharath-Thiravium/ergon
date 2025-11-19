<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Simulate the exact query from AttendanceController
    $role = $_SESSION['role'] ?? 'admin'; // Assume admin role
    $filterDate = date('Y-m-d');
    
    echo "<h3>Testing Attendance Query</h3>";
    echo "Current role: $role<br>";
    echo "Filter date: $filterDate<br><br>";
    
    // Admin sees only employees (role = 'user')
    $roleFilter = ($role === 'owner') ? "u.role IN ('admin', 'user')" : "u.role = 'user'";
    echo "Role filter: $roleFilter<br><br>";
    
    $query = "
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
    ";
    
    echo "Query:<br><pre>" . htmlspecialchars($query) . "</pre><br>";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$filterDate]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Results: " . count($employees) . " employees found<br><br>";
    
    if (count($employees) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
        foreach ($employees as $emp) {
            echo "<tr>";
            echo "<td>{$emp['name']}</td>";
            echo "<td>{$emp['email']}</td>";
            echo "<td>{$emp['role']}</td>";
            echo "<td>{$emp['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No employees found with the current query<br>";
        
        // Test what users exist with role 'user'
        $stmt = $db->query("SELECT name, email, role FROM users WHERE role = 'user'");
        $users = $stmt->fetchAll();
        echo "<br>Users with role 'user': " . count($users) . "<br>";
        foreach ($users as $user) {
            echo "- {$user['name']} ({$user['email']})<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>