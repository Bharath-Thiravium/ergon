<?php
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    // Look for any table that might contain user login information
    $tables_to_check = ['users', 'user', 'employees', 'staff', 'accounts', 'login', 'auth'];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $db->query("SELECT * FROM $table LIMIT 3");
            $data = $stmt->fetchAll();
            
            if (!empty($data)) {
                echo "<h2>Found table: $table</h2>";
                echo "<table border='1'>";
                echo "<tr>";
                foreach (array_keys($data[0]) as $header) {
                    echo "<th>$header</th>";
                }
                echo "</tr>";
                
                foreach ($data as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (Exception $e) {
            // Table doesn't exist, continue
        }
    }
    
    // Also check if there's a table with email/password columns
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Checking all tables for email/password columns:</h2>";
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            
            $hasEmail = false;
            $hasPassword = false;
            
            foreach ($columns as $column) {
                if (stripos($column['Field'], 'email') !== false) $hasEmail = true;
                if (stripos($column['Field'], 'password') !== false) $hasPassword = true;
            }
            
            if ($hasEmail || $hasPassword) {
                echo "<p><strong>$table</strong> - Email: " . ($hasEmail ? 'Yes' : 'No') . ", Password: " . ($hasPassword ? 'Yes' : 'No') . "</p>";
                
                // Show sample data
                $stmt = $db->query("SELECT * FROM $table LIMIT 2");
                $data = $stmt->fetchAll();
                
                if (!empty($data)) {
                    echo "<table border='1' style='margin: 10px 0;'>";
                    echo "<tr>";
                    foreach (array_keys($data[0]) as $header) {
                        echo "<th>$header</th>";
                    }
                    echo "</tr>";
                    
                    foreach ($data as $row) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            }
        } catch (Exception $e) {
            // Skip
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>