<?php
/**
 * Admin Panel Issues Fix - Issues #16-25
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "ADMIN PANEL FIXES\n";
    echo "================\n\n";
    
    // Fix 1: Ensure phone column exists in users table
    echo "1. Fixing user phone column...\n";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('phone', $columns)) {
        $db->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER email");
        echo "   ✅ Added phone column\n";
    }
    
    // Fix 2: Create departments table if missing
    echo "2. Ensuring departments table exists...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'departments'");
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $db->exec($sql);
        
        // Insert default departments
        $db->exec("INSERT INTO departments (name, description) VALUES 
                   ('IT', 'Information Technology'),
                   ('HR', 'Human Resources'),
                   ('Finance', 'Finance Department'),
                   ('Operations', 'Operations Department')");
        echo "   ✅ Created departments table with default data\n";
    }
    
    // Fix 3: Create projects table for task planner
    echo "3. Creating projects table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'projects'");
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            department_id INT,
            status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_department (department_id)
        )";
        $db->exec($sql);
        
        // Insert default projects
        $db->exec("INSERT INTO projects (name, description, department_id) VALUES 
                   ('Website Development', 'Company website project', 1),
                   ('HR System', 'Employee management system', 2),
                   ('Budget Planning', 'Annual budget planning', 3)");
        echo "   ✅ Created projects table\n";
    }
    
    // Fix 4: Create task_categories table
    echo "4. Creating task categories table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'task_categories'");
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE task_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            department_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_department (department_id)
        )";
        $db->exec($sql);
        
        // Insert default categories
        $db->exec("INSERT INTO task_categories (name, department_id) VALUES 
                   ('Development', 1),
                   ('Testing', 1),
                   ('Recruitment', 2),
                   ('Training', 2),
                   ('Accounting', 3),
                   ('Reporting', 3)");
        echo "   ✅ Created task categories table\n";
    }
    
    echo "\n✅ ALL ADMIN PANEL DATABASE FIXES APPLIED!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>