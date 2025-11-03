<?php
/**
 * Fix User Departments - Assign departments to users who don't have them
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>🔧 Fix User Departments</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    $db = Database::connect();
    
    // Check if departments table exists, create if not
    echo "<h2>Step 1: Ensure Departments Table</h2>";
    
    $stmt = $db->query("SHOW TABLES LIKE 'departments'");
    if ($stmt->rowCount() == 0) {
        echo "<span class='info'>Creating departments table...</span><br>";
        
        $db->exec("
            CREATE TABLE departments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Insert default departments
        $defaultDepts = [
            ['IT Department', 'Information Technology'],
            ['HR Department', 'Human Resources'],
            ['Sales Department', 'Sales and Marketing'],
            ['Finance Department', 'Finance and Accounting'],
            ['Operations Department', 'Operations and Management']
        ];
        
        foreach ($defaultDepts as $dept) {
            $stmt = $db->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
            $stmt->execute($dept);
            echo "<span class='success'>✅ Created department: {$dept[0]}</span><br>";
        }
    } else {
        echo "<span class='success'>✅ Departments table exists</span><br>";
    }
    
    // Get available departments
    $stmt = $db->query("SELECT id, name FROM departments ORDER BY name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Step 2: Available Departments</h2>";
    foreach ($departments as $dept) {
        echo "<span class='info'>ID {$dept['id']}: {$dept['name']}</span><br>";
    }
    
    // Check users without departments
    echo "<h2>Step 3: Users Without Departments</h2>";
    
    $stmt = $db->query("SELECT id, name, email, role FROM users WHERE department IS NULL OR department = ''");
    $usersWithoutDept = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<span class='info'>Found " . count($usersWithoutDept) . " users without departments</span><br>";
    
    if (count($usersWithoutDept) > 0) {
        echo "<table border='1' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
        foreach ($usersWithoutDept as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        echo "<h2>Step 4: Auto-Assign Departments</h2>";
        echo "<a href='?action=auto_assign' style='background:#007bff;color:white;padding:8px 16px;text-decoration:none;border-radius:4px;'>Auto-Assign Departments</a><br><br>";
    }
    
    // Auto-assign departments
    if (isset($_GET['action']) && $_GET['action'] === 'auto_assign') {
        echo "<h3>Auto-Assigning Departments...</h3>";
        
        $deptIndex = 0;
        foreach ($usersWithoutDept as $user) {
            $assignedDept = $departments[$deptIndex % count($departments)];
            
            $stmt = $db->prepare("UPDATE users SET department = ? WHERE id = ?");
            $stmt->execute([$assignedDept['id'], $user['id']]);
            
            echo "<span class='success'>✅ Assigned {$user['name']} to {$assignedDept['name']}</span><br>";
            $deptIndex++;
        }
        
        echo "<h3>✅ Department Assignment Complete!</h3>";
        echo "<a href='/ergon/attendance' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;'>Test Attendance Page</a>";
    }
    
    // Show current user-department mapping
    echo "<h2>Step 5: Current User-Department Mapping</h2>";
    
    $stmt = $db->query("
        SELECT u.id, u.name, u.role, d.name as department_name 
        FROM users u 
        LEFT JOIN departments d ON u.department = d.id 
        ORDER BY u.name
    ");
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Role</th><th>Department</th></tr>";
    foreach ($allUsers as $user) {
        $deptDisplay = $user['department_name'] ?: '<em style="color:red;">Not Assigned</em>';
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>$deptDisplay</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
}
?>