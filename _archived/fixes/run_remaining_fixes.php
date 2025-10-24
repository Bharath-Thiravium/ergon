<?php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>Running Remaining Fixes</h2>";
    
    // Read and execute the remaining fixes
    $sql = file_get_contents(__DIR__ . '/fix_remaining_errors.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;
        
        try {
            $conn->exec($statement);
            $success++;
            echo "<p style='color: green;'>✓ " . substr($statement, 0, 60) . "...</p>";
        } catch (Exception $e) {
            // Check if it's just a duplicate column error (which is OK)
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<p style='color: orange;'>⚠ Column already exists (OK): " . substr($statement, 0, 60) . "...</p>";
            } else {
                $errors++;
                echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<hr>";
    echo "<h3>Final Status</h3>";
    echo "<p>Successful: $success</p>";
    echo "<p>Errors: $errors</p>";
    
    if ($errors === 0) {
        echo "<p style='color: green; font-weight: bold;'>✓ All remaining fixes applied!</p>";
    }
    
    echo "<p><a href='/ergon/test_fixes.php'>Test All Fixes</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>