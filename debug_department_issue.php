<?php
/**
 * Debug Department Display Issue in Attendance
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>🔍 Debug Department Issue</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    $db = Database::connect();
    
    // Check users table structure
    echo "<h2>1. Users Table Structure</h2>";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Check sample user data
    echo "<h2>2. Sample User Data</h2>";
    $stmt = $db->query("SELECT id, name, email, role, department FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Department</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>" . ($user['department'] ?: '<em>NULL/Empty</em>') . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Check if departments table exists
    echo "<h2>3. Department Table Check</h2>";
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'departments'");
        if ($stmt->rowCount() > 0) {
            echo "<span class='success'>✅ Departments table exists</span><br>";
            
            $stmt = $db->query("SELECT * FROM departments LIMIT 5");
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse:collapse;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Description</th></tr>";
            foreach ($departments as $dept) {
                echo "<tr>";
                echo "<td>{$dept['id']}</td>";
                echo "<td>{$dept['name']}</td>";
                echo "<td>" . ($dept['description'] ?? '') . "</td>";
                echo "</tr>";
            }
            echo "</table><br>";
        } else {
            echo "<span class='error'>❌ Departments table does not exist</span><br>";
        }
    } catch (Exception $e) {
        echo "<span class='error'>❌ Error checking departments table: " . $e->getMessage() . "</span><br>";
    }
    
    // Test the exact query from AttendanceController
    echo "<h2>4. Test Attendance Query</h2>";
    
    $filterDate = date('Y-m-d');
    $roleFilter = "u.role = 'user'";
    
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.role,
            u.department,
            a.check_in,
            a.check_out,
            CASE 
                WHEN a.check_in IS NOT NULL THEN 'Present'
                ELSE 'Absent'
            END as status,
            CASE 
                WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                    ROUND(TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) / 60.0, 2)
                ELSE 0
            END as total_hours
        FROM users u
        LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
        WHERE $roleFilter
        ORDER BY u.role DESC, u.name
    ");
    $stmt->execute([$filterDate]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<span class='info'>Query returned " . count($employees) . " employees</span><br>";
    
    if (count($employees) > 0) {
        echo "<table border='1' style='border-collapse:collapse;'>";
        echo "<tr><th>Name</th><th>Role</th><th>Department (Raw)</th><th>Status</th></tr>";
        foreach ($employees as $emp) {
            echo "<tr>";
            echo "<td>{$emp['name']}</td>";
            echo "<td>{$emp['role']}</td>";
            echo "<td>" . ($emp['department'] ? $emp['department'] : '<em style="color:red;">NULL/Empty</em>') . "</td>";
            echo "<td>{$emp['status']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // Check if department is stored as ID reference
    echo "<h2>5. Check Department ID Reference</h2>";
    
    $stmt = $db->query("SELECT id, name, department FROM users WHERE department IS NOT NULL AND department != '' LIMIT 3");
    $usersWithDept = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($usersWithDept as $user) {
        echo "<span class='info'>User: {$user['name']} - Department value: '{$user['department']}'</span><br>";
        
        // Check if it's a numeric ID
        if (is_numeric($user['department'])) {
            echo "<span class='info'>  → Appears to be department ID, checking departments table...</span><br>";
            
            try {
                $stmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
                $stmt->execute([$user['department']]);
                $deptName = $stmt->fetchColumn();
                
                if ($deptName) {
                    echo "<span class='success'>  → Department ID {$user['department']} = '{$deptName}'</span><br>";
                } else {
                    echo "<span class='error'>  → Department ID {$user['department']} not found in departments table</span><br>";
                }
            } catch (Exception $e) {
                echo "<span class='error'>  → Error looking up department: " . $e->getMessage() . "</span><br>";
            }
        }
    }
    
    echo "<h2>6. Recommended Fix</h2>";
    
    if (count($usersWithDept) > 0 && is_numeric($usersWithDept[0]['department'])) {
        echo "<div style='background:#fff3cd;padding:15px;border-radius:5px;'>";
        echo "<h3>🔧 Issue Identified: Department stored as ID</h3>";
        echo "<p>The department field contains department IDs, not names. Need to JOIN with departments table.</p>";
        echo "<a href='?action=fix_query' style='background:#007bff;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;'>Apply Fix</a>";
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;'>";
        echo "<h3>❌ Issue: No department data found</h3>";
        echo "<p>Users don't have department values set. Check user creation/edit process.</p>";
        echo "</div>";
    }
    
    // Apply fix if requested
    if (isset($_GET['action']) && $_GET['action'] === 'fix_query') {
        echo "<h2>7. Applying Fix</h2>";
        
        $fixedQuery = "
        SELECT 
            u.id,
            u.name,
            u.email,
            u.role,
            COALESCE(d.name, u.department, 'Not Assigned') as department,
            a.check_in,
            a.check_out,
            CASE 
                WHEN a.check_in IS NOT NULL THEN 'Present'
                ELSE 'Absent'
            END as status,
            CASE 
                WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                    ROUND(TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) / 60.0, 2)
                ELSE 0
            END as total_hours
        FROM users u
        LEFT JOIN departments d ON u.department = d.id
        LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
        WHERE u.role = 'user'
        ORDER BY u.role DESC, u.name";
        
        echo "<div style='background:#f8f9fa;padding:10px;border-radius:4px;font-family:monospace;font-size:12px;'>";
        echo htmlspecialchars($fixedQuery);
        echo "</div>";
        
        echo "<p><strong>This query will:</strong></p>";
        echo "<ul>";
        echo "<li>JOIN with departments table to get department names</li>";
        echo "<li>Use COALESCE to handle both ID and name formats</li>";
        echo "<li>Show 'Not Assigned' if no department is set</li>";
        echo "</ul>";
        
        echo "<a href='/ergon/attendance' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;'>Test Fixed Attendance Page</a>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
}
?>