<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=ergon_db;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<h3>Database Tables:</h3>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table<br>";
    }
    
    echo "<h3>Tasks Table Structure:</h3>";
    if (in_array('tasks', $tables)) {
        $columns = $pdo->query("DESCRIBE tasks")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "- {$col['Field']} ({$col['Type']})<br>";
        }
        
        echo "<h3>Sample Tasks with Contact Data:</h3>";
        $tasks = $pdo->query("SELECT id, title, company_name, contact_person, contact_phone, project_name FROM tasks WHERE company_name IS NOT NULL OR contact_person IS NOT NULL LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tasks as $task) {
            echo "ID: {$task['id']}, Title: {$task['title']}, Company: {$task['company_name']}, Contact: {$task['contact_person']}, Phone: {$task['contact_phone']}<br>";
        }
    }
    
    echo "<h3>Contacts Table:</h3>";
    if (in_array('contacts', $tables)) {
        $contacts = $pdo->query("SELECT * FROM contacts LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($contacts as $contact) {
            echo "ID: {$contact['id']}, Name: {$contact['name']}, Company: {$contact['company']}, Phone: {$contact['phone']}<br>";
        }
    }
    
    echo "<h3>Followups Table:</h3>";
    if (in_array('followups', $tables)) {
        $followups = $pdo->query("SELECT f.*, c.name as contact_name FROM followups f LEFT JOIN contacts c ON f.contact_id = c.id LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($followups as $followup) {
            echo "ID: {$followup['id']}, Title: {$followup['title']}, Contact: {$followup['contact_name']}<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>