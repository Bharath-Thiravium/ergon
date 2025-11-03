<?php
// Fix department display in admin attendance panel
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>🔧 Department Display Fix</h2>";
    
    // 1. Check users table structure
    echo "<h3>1. Users Table Structure</h3>";
    $stmt = $db->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($userColumns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Check if departments table exists
    echo "<h3>2. Departments Table Check</h3>";
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'departments'");
        $departmentsExists = $stmt->rowCount() > 0;
        
        if ($departmentsExists) {
            echo "<p>✅ Departments table exists</p>";
            
            $stmt = $db->query("DESCRIBE departments");
            $deptColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
            foreach ($deptColumns as $col) {
                echo "<tr>";
                echo "<td>" . $col['Field'] . "</td>";
                echo "<td>" . $col['Type'] . "</td>";
                echo "<td>" . $col['Null'] . "</td>";
                echo "<td>" . $col['Default'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Show department data
            $stmt = $db->query("SELECT * FROM departments");
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>Department Data:</h4>";
            if (empty($departments)) {
                echo "<p>⚠️ No departments found. Creating default departments...</p>";
                
                // Create default departments
                $defaultDepts = [
                    ['name' => 'Human Resources', 'description' => 'HR Department'],
                    ['name' => 'Information Technology', 'description' => 'IT Department'],
                    ['name' => 'Finance', 'description' => 'Finance Department'],
                    ['name' => 'Operations', 'description' => 'Operations Department'],
                    ['name' => 'Sales', 'description' => 'Sales Department']
                ];
                
                foreach ($defaultDepts as $dept) {
                    $stmt = $db->prepare("INSERT INTO departments (name, description, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$dept['name'], $dept['description']]);
                }
                
                echo "<p>✅ Default departments created</p>";
                
                // Fetch again
                $stmt = $db->query("SELECT * FROM departments");
                $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Description</th></tr>";
            foreach ($departments as $dept) {
                echo "<tr>";
                echo "<td>" . $dept['id'] . "</td>";
                echo "<td>" . $dept['name'] . "</td>";
                echo "<td>" . ($dept['description'] ?? '') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } else {
            echo "<p>❌ Departments table does not exist. Creating...</p>";
            
            // Create departments table
            $db->exec("CREATE TABLE departments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            // Insert default departments
            $defaultDepts = [
                ['name' => 'Human Resources', 'description' => 'HR Department'],
                ['name' => 'Information Technology', 'description' => 'IT Department'],
                ['name' => 'Finance', 'description' => 'Finance Department'],
                ['name' => 'Operations', 'description' => 'Operations Department'],
                ['name' => 'Sales', 'description' => 'Sales Department']
            ];
            
            foreach ($defaultDepts as $dept) {
                $stmt = $db->prepare("INSERT INTO departments (name, description, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$dept['name'], $dept['description']]);
            }
            
            echo "<p>✅ Departments table created with default data</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking departments table: " . $e->getMessage() . "</p>";
    }
    
    // 3. Check user department assignments
    echo "<h3>3. User Department Assignments</h3>";
    $stmt = $db->query("SELECT u.id, u.name, u.department, d.name as dept_name FROM users u LEFT JOIN departments d ON u.department = d.id WHERE u.role = 'user' LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Department ID</th><th>Department Name</th><th>Status</th></tr>";
    foreach ($users as $user) {
        $status = $user['dept_name'] ? '✅ Assigned' : '⚠️ Not Assigned';
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . ($user['department'] ?? 'NULL') . "</td>";
        echo "<td>" . ($user['dept_name'] ?? 'Not Assigned') . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Test the attendance query with departments
    echo "<h3>4. Test Attendance Query with Departments</h3>";
    try {
        $filterDate = date('Y-m-d');
        $stmt = $db->prepare("
            SELECT 
                u.id,
                u.name,
                u.email,
                u.role,
                u.department as dept_id,
                COALESCE(d.name, 'Not Assigned') as department,
                a.check_in,
                a.check_out,
                CASE 
                    WHEN a.check_in IS NOT NULL THEN 'Present'
                    ELSE 'Absent'
                END as status
            FROM users u
            LEFT JOIN departments d ON u.department = d.id
            LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
            WHERE u.role = 'user'
            ORDER BY u.name
            LIMIT 5
        ");
        $stmt->execute([$filterDate]);
        $testResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>✅ Query executed successfully. Results:</p>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Name</th><th>Dept ID</th><th>Department</th><th>Status</th></tr>";
        foreach ($testResults as $result) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($result['name']) . "</td>";
            echo "<td>" . ($result['dept_id'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($result['department']) . "</td>";
            echo "<td>" . $result['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p>❌ Query test failed: " . $e->getMessage() . "</p>";
    }
    
    // 5. Fix unassigned users
    echo "<h3>5. Fix Unassigned Users</h3>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE (department IS NULL OR department = '' OR department = 'General') AND role = 'user'");
    $unassignedCount = $stmt->fetch()['count'];
    
    if ($unassignedCount > 0) {
        echo "<p>⚠️ Found $unassignedCount users without proper department assignment</p>";
        
        // Get first department ID for assignment
        $stmt = $db->query("SELECT id FROM departments ORDER BY id LIMIT 1");
        $firstDept = $stmt->fetch();
        
        if ($firstDept) {
            $stmt = $db->prepare("UPDATE users SET department = ? WHERE (department IS NULL OR department = '' OR department = 'General') AND role = 'user'");
            $stmt->execute([$firstDept['id']]);
            
            echo "<p>✅ Assigned unassigned users to department ID: " . $firstDept['id'] . "</p>";
        }
    } else {
        echo "<p>✅ All users have proper department assignments</p>";
    }
    
    echo "<h3>6. Summary</h3>";
    echo "<p>✅ Department display should now work correctly in admin attendance panel</p>";
    echo "<p>🔗 <a href='/ergon/attendance'>Test Admin Attendance Panel</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Database Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>