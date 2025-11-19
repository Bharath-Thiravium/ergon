<?php
// Check projects database setup
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "Database connection: SUCCESS\n";
    
    // Create projects table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        department_id INT,
        status VARCHAR(50) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_department (department_id)
    )";
    
    $db->exec($createTableSQL);
    echo "Projects table created/verified: SUCCESS\n";
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'projects'");
    $tableExists = $stmt->rowCount() > 0;
    echo "Projects table exists: " . ($tableExists ? "YES" : "NO") . "\n";
    
    if ($tableExists) {
        // Get table structure
        $stmt = $db->query("DESCRIBE projects");
        $columns = $stmt->fetchAll();
        echo "Table structure:\n";
        foreach ($columns as $column) {
            echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
        
        // Get current count
        $stmt = $db->query("SELECT COUNT(*) as count FROM projects");
        $result = $stmt->fetch();
        echo "Current projects count: " . $result['count'] . "\n";
        
        // Test insert
        $stmt = $db->prepare("INSERT INTO projects (name, description, status) VALUES (?, ?, ?)");
        $testInsert = $stmt->execute(['Test Project ' . date('Y-m-d H:i:s'), 'Test project for database verification', 'active']);
        echo "Test insert: " . ($testInsert ? "SUCCESS" : "FAILED") . "\n";
        
        if ($testInsert) {
            // Get updated count
            $stmt = $db->query("SELECT COUNT(*) as count FROM projects");
            $result = $stmt->fetch();
            echo "Updated projects count: " . $result['count'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>