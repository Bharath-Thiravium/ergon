<?php
// Check what columns are missing in Hostinger vs what the edit form expects

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>Database Column Analysis</h2>";
    
    // Get current table structure
    $stmt = $conn->query("DESCRIBE users");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Existing Columns:</h3>";
    echo "<pre>";
    print_r($existingColumns);
    echo "</pre>";
    
    // Columns expected by edit form
    $expectedColumns = [
        'id', 'employee_id', 'name', 'email', 'phone', 'password', 'role', 'status',
        'date_of_birth', 'gender', 'address', 'emergency_contact',
        'designation', 'joining_date', 'salary', 'department',
        'created_at', 'updated_at'
    ];
    
    echo "<h3>Expected Columns:</h3>";
    echo "<pre>";
    print_r($expectedColumns);
    echo "</pre>";
    
    // Find missing columns
    $missingColumns = array_diff($expectedColumns, $existingColumns);
    
    echo "<h3>Missing Columns:</h3>";
    if (empty($missingColumns)) {
        echo "<p>✅ All columns exist</p>";
    } else {
        echo "<p>❌ Missing columns:</p>";
        echo "<pre>";
        print_r($missingColumns);
        echo "</pre>";
        
        // Generate SQL to add missing columns
        echo "<h3>SQL to Add Missing Columns:</h3>";
        echo "<pre>";
        foreach ($missingColumns as $column) {
            switch ($column) {
                case 'date_of_birth':
                    echo "ALTER TABLE users ADD COLUMN date_of_birth DATE NULL;\n";
                    break;
                case 'gender':
                    echo "ALTER TABLE users ADD COLUMN gender ENUM('male', 'female', 'other') NULL;\n";
                    break;
                case 'address':
                    echo "ALTER TABLE users ADD COLUMN address TEXT NULL;\n";
                    break;
                case 'emergency_contact':
                    echo "ALTER TABLE users ADD COLUMN emergency_contact VARCHAR(20) NULL;\n";
                    break;
                case 'designation':
                    echo "ALTER TABLE users ADD COLUMN designation VARCHAR(100) NULL;\n";
                    break;
                case 'joining_date':
                    echo "ALTER TABLE users ADD COLUMN joining_date DATE NULL;\n";
                    break;
                case 'salary':
                    echo "ALTER TABLE users ADD COLUMN salary DECIMAL(10,2) NULL;\n";
                    break;
            }
        }
        echo "</pre>";
    }
    
    // Test user data retrieval
    echo "<h3>User ID 14 Data:</h3>";
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = 14");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>