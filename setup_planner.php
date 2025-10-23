<?php
/**
 * Setup Daily Planner Database Tables
 * Run this script to create the required tables for the daily planner feature
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Setting up Daily Planner tables...\n";
    
    // Read and execute the department schema first
    $departmentSchema = file_get_contents('database/departments_schema.sql');
    $conn->exec($departmentSchema);
    echo "✓ Department tables created/updated\n";
    
    // Read and execute the daily planner schema
    $plannerSchema = file_get_contents('database/daily_planner_schema.sql');
    $conn->exec($plannerSchema);
    echo "✓ Daily planner tables created\n";
    
    // Update users table to include department_id if not exists
    $conn->exec("ALTER TABLE users ADD COLUMN department_id INT DEFAULT NULL");
    $conn->exec("ALTER TABLE users ADD FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL");
    echo "✓ Users table updated with department reference\n";
    
    echo "\n🎉 Daily Planner setup completed successfully!\n";
    echo "You can now access the enhanced calendar at: /ergon/planner/calendar\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "⚠️ Some columns already exist, continuing...\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>