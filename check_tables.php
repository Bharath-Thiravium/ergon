<?php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>Current Table Structures</h2>";
    
    $tables = ['users', 'leaves', 'expenses', 'attendance'];
    
    foreach ($tables as $table) {
        echo "<h3>$table table:</h3>";
        try {
            $stmt = $conn->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>";
            foreach ($columns as $col) {
                echo $col['Field'] . " - " . $col['Type'] . "\n";
            }
            echo "</pre>";
        } catch (Exception $e) {
            echo "<p style='color:red;'>Table $table does not exist: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>