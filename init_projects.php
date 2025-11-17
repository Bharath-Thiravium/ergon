<?php
// Initialize Projects Table
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Create projects table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        department_id INT,
        status VARCHAR(50) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_department (department_id)
    )");
    
    // Check if table exists and has data
    $stmt = $db->query("SELECT COUNT(*) as count FROM projects");
    $result = $stmt->fetch();
    
    echo "Projects table initialized successfully!\n";
    echo "Current projects count: " . $result['count'] . "\n";
    
    // Add some sample projects if none exist
    if ($result['count'] == 0) {
        $db->exec("INSERT INTO projects (name, description, status) VALUES 
            ('Website Development', 'Company website redesign project', 'active'),
            ('Mobile App', 'Customer mobile application', 'active'),
            ('Database Migration', 'Legacy system database migration', 'on_hold')");
        
        echo "Sample projects added!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>