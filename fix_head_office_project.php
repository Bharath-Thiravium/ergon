<?php
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Head Office Project</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 600px; }
        .success { color: #22c55e; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-success { background: #22c55e; color: white; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Head Office Project</h1>
        
        <?php
        try {
            $db = Database::connect();
            
            if ($_POST['action'] ?? '' === 'create_project') {
                // Create Head Office project
                $stmt = $db->prepare("
                    INSERT INTO projects (name, description, latitude, longitude, checkin_radius, status, place, created_at) 
                    VALUES ('Head Office', 'Main office location for general attendance', 9.98156700, 78.14340000, 50, 'active', 'Thiruppalai, Madurai', NOW())
                ");
                $result = $stmt->execute();
                
                if ($result) {
                    $projectId = $db->lastInsertId();
                    echo '<div class="success">✅ Head Office project created successfully!</div>';
                    echo '<pre>Project ID: ' . $projectId . '</pre>';
                } else {
                    echo '<div class="error">❌ Failed to create project</div>';
                }
            }
            
            if ($_POST['action'] ?? '' === 'update_existing') {
                $projectId = $_POST['project_id'] ?? '';
                if ($projectId) {
                    $stmt = $db->prepare("UPDATE projects SET name = 'Head Office' WHERE id = ? AND status = 'active'");
                    $result = $stmt->execute([$projectId]);
                    
                    if ($result && $stmt->rowCount() > 0) {
                        echo '<div class="success">✅ Project updated to "Head Office"</div>';
                    } else {
                        echo '<div class="error">❌ Failed to update project</div>';
                    }
                }
            }
            
            // Show current projects
            echo '<h2>Current Active Projects:</h2>';
            $stmt = $db->prepare("SELECT id, name, place, latitude, longitude FROM projects WHERE status = 'active' ORDER BY id");
            $stmt->execute();
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<pre>';
            foreach ($projects as $project) {
                echo "ID: {$project['id']} | Name: {$project['name']} | Place: {$project['place']}\n";
            }
            echo '</pre>';
            
            // Check if Head Office exists
            $stmt = $db->prepare("SELECT id FROM projects WHERE name = 'Head Office' AND status = 'active'");
            $stmt->execute();
            $headOffice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($headOffice) {
                echo '<div class="success">✅ Head Office project exists (ID: ' . $headOffice['id'] . ')</div>';
            } else {
                echo '<div class="error">❌ Head Office project missing</div>';
                
                echo '<h2>Fix Options:</h2>';
                echo '<form method="post" style="margin: 10px 0;">';
                echo '<input type="hidden" name="action" value="create_project">';
                echo '<button type="submit" class="btn btn-primary">Create New "Head Office" Project</button>';
                echo '</form>';
                
                if (!empty($projects)) {
                    echo '<p>OR update existing project:</p>';
                    foreach ($projects as $project) {
                        echo '<form method="post" style="margin: 5px 0;">';
                        echo '<input type="hidden" name="action" value="update_existing">';
                        echo '<input type="hidden" name="project_id" value="' . $project['id'] . '">';
                        echo '<button type="submit" class="btn btn-success">Rename "' . $project['name'] . '" to "Head Office"</button>';
                        echo '</form>';
                    }
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="error">Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</body>
</html>
