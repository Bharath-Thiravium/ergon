<?php
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Daily Tasks:</h2>";
    $stmt = $db->query("SELECT * FROM daily_tasks ORDER BY id DESC LIMIT 10");
    $tasks = $stmt->fetchAll();
    
    if (!empty($tasks)) {
        echo "<table border='1'>";
        echo "<tr>";
        foreach (array_keys($tasks[0]) as $header) {
            echo "<th>$header</th>";
        }
        echo "</tr>";
        
        foreach ($tasks as $task) {
            echo "<tr>";
            foreach ($task as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No daily tasks found.";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>