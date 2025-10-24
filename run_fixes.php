<?php
/**
 * Database Fix Script
 * Run this to fix all database-related errors
 */

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>ERGON Database Fix Script</h2>\n";
    echo "<p>Running database fixes...</p>\n";
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/fix_all_errors.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            $conn->exec($statement);
            $success++;
            echo "<p style='color: green;'>✓ Executed: " . substr($statement, 0, 50) . "...</p>\n";
        } catch (Exception $e) {
            $errors++;
            echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>\n";
            echo "<p style='color: gray;'>Statement: " . substr($statement, 0, 100) . "...</p>\n";
        }
    }
    
    echo "<hr>\n";
    echo "<h3>Summary</h3>\n";
    echo "<p>Successful statements: <strong>$success</strong></p>\n";
    echo "<p>Errors: <strong>$errors</strong></p>\n";
    
    if ($errors === 0) {
        echo "<p style='color: green; font-weight: bold;'>✓ All fixes applied successfully!</p>\n";
        echo "<p><a href='/ergon/dashboard'>Go to Dashboard</a></p>\n";
    } else {
        echo "<p style='color: orange;'>⚠ Some errors occurred, but core fixes should be working.</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection error: " . $e->getMessage() . "</p>\n";
}
?>