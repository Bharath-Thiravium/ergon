<?php
// Quick fix for gamification tables
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    // Read and execute the SQL file
    $sql = file_get_contents('database/fix_gamification.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->exec($statement);
        }
    }
    
    echo "Gamification tables created successfully!";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>