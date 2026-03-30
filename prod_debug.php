<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Production Debug<br><br>";
    
    // Check projects
    $stmt = $db->prepare("SELECT id, name, place, status FROM projects");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Projects:<br>";
    foreach ($projects as $p) {
        echo "ID:{$p['id']} Name:'{$p['name']}' Place:'{$p['place']}' Status:{$p['status']}<br>";
    }
    
    // Check Nelson's attendance
    $stmt = $db->prepare("SELECT a.*, p.name as pname, p.place FROM attendance a LEFT JOIN projects p ON a.project_id = p.id WHERE a.check_in LIKE '2025-12-15%' ORDER BY a.check_in DESC");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<br>Today's Attendance:<br>";
    foreach ($records as $r) {
        echo "User:{$r['user_id']} ProjectID:{$r['project_id']} ProjName:'{$r['pname']}' ProjPlace:'{$r['place']}'<br>";
    }
    
    // Test the exact query from controller
    $stmt = $db->prepare("SELECT name, place FROM projects WHERE status = 'active' ORDER BY name ASC LIMIT 1");
    $stmt->execute();
    $default = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<br>Default: Location:'{$default['place']}' Project:'{$default['name']}'<br>";
    
    $stmt = $db->prepare("SELECT COALESCE(p.place, ?) as location_display, COALESCE(p.name, ?) as project_name FROM attendance a LEFT JOIN projects p ON a.project_id = p.id WHERE a.check_in LIKE '2025-12-15%' LIMIT 1");
    $stmt->execute([$default['place'], $default['name']]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Query Result: Location:'{$test['location_display']}' Project:'{$test['project_name']}'<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
