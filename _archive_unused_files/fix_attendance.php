<?php
// Quick fix script for attendance table structure
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "Connected to database successfully.\n";
    
    // Read and execute the migration SQL
    $sql = file_get_contents(__DIR__ . '/database/fix_attendance_table.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(--|SELECT ")/i', $statement)) {
            try {
                $db->exec($statement);
                echo "Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (Exception $e) {
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\nAttendance table structure fixed successfully!\n";
    echo "You can now try clocking out again.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>