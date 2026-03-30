<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== ALL PROJECTS CHECK ===\n\n";
    
    // Check all projects
    $stmt = $db->prepare("SELECT * FROM projects ORDER BY id");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total projects found: " . count($projects) . "\n\n";
    
    foreach ($projects as $project) {
        echo "ID: {$project['id']}, Name: {$project['name']}, Status: {$project['status']}\n";
        echo "  Location: ({$project['latitude']}, {$project['longitude']}) - Radius: {$project['checkin_radius']}m\n";
        echo "  Place: {$project['place']}\n\n";
    }
    
    // Check if there are any projects with ID 15 or similar names
    echo "=== SEARCHING FOR MARKET RESEARCH PROJECT ===\n";
    $stmt = $db->prepare("SELECT * FROM projects WHERE name LIKE '%Market%' OR name LIKE '%Research%' OR id = 15");
    $stmt->execute();
    $marketProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($marketProjects)) {
        echo "❌ No Market Research projects found\n";
    } else {
        foreach ($marketProjects as $project) {
            echo "Found: ID {$project['id']}, Name: {$project['name']}, Status: {$project['status']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
