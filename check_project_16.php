<?php
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Project 16</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; background: #3b82f6; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Check Project 16</h1>
        
        <?php
        try {
            $db = Database::connect();
            
            // Check what project ID 16 is
            echo "<h2>Project ID 16 Details:</h2>";
            $stmt = $db->prepare("SELECT * FROM projects WHERE id = 16");
            $stmt->execute();
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($project) {
                echo "<pre>";
                echo "ID: {$project['id']}\n";
                echo "Name: {$project['name']}\n";
                echo "Place: {$project['place']}\n";
                echo "Status: {$project['status']}\n";
                echo "GPS: ({$project['latitude']}, {$project['longitude']})\n";
                echo "Radius: {$project['checkin_radius']}m\n";
                echo "</pre>";
            } else {
                echo "<pre>Project ID 16 not found</pre>";
            }
            
            // Show settings for comparison
            echo "<h2>Settings Values:</h2>";
            $stmt = $db->prepare("SELECT location_title, office_address FROM settings LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<pre>";
            echo "Location Title: {$settings['location_title']}\n";
            echo "Office Address: {$settings['office_address']}\n";
            echo "</pre>";
            
            // Fix option
            if ($project && $_POST['action'] ?? '' === 'update_project') {
                $stmt = $db->prepare("UPDATE projects SET name = ?, place = ? WHERE id = 16");
                $result = $stmt->execute([$settings['location_title'], $settings['office_address']]);
                
                if ($result) {
                    echo "<div style='color: #22c55e; font-weight: bold;'>✅ Project 16 updated successfully!</div>";
                    
                    // Refresh project data
                    $stmt = $db->prepare("SELECT * FROM projects WHERE id = 16");
                    $stmt->execute();
                    $project = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo "<h2>Updated Project 16:</h2>";
                    echo "<pre>";
                    echo "Name: {$project['name']}\n";
                    echo "Place: {$project['place']}\n";
                    echo "</pre>";
                } else {
                    echo "<div style='color: #ef4444;'>❌ Failed to update project</div>";
                }
            }
            
            if ($project && !isset($_POST['action'])) {
                echo "<h2>Fix Option:</h2>";
                echo "<p>Update Project 16 to match settings values:</p>";
                echo "<form method='post'>";
                echo "<input type='hidden' name='action' value='update_project'>";
                echo "<button type='submit' class='btn'>Update Project 16 to '{$settings['location_title']}' and '{$settings['office_address']}'</button>";
                echo "</form>";
            }
            
        } catch (Exception $e) {
            echo '<div>Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</body>
</html>
