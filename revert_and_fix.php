<?php
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Revert and Fix</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; background: #ef4444; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Revert and Fix Properly</h1>
        
        <?php
        try {
            $db = Database::connect();
            
            if ($_POST['action'] ?? '' === 'revert') {
                // Revert project 16 back to original
                $stmt = $db->prepare("UPDATE projects SET name = 'Main Office', place = 'Main Office' WHERE id = 16");
                $result = $stmt->execute();
                
                if ($result) {
                    echo "<div style='color: #22c55e;'>✅ Project 16 reverted to original values</div>";
                }
            }
            
            if ($_POST['action'] ?? '' === 'set_null') {
                // Set Nelson's attendance project_id to NULL so it fetches from settings
                $stmt = $db->prepare("UPDATE attendance SET project_id = NULL WHERE user_id = 57 AND DATE(check_in) = CURDATE()");
                $result = $stmt->execute();
                
                if ($result) {
                    echo "<div style='color: #22c55e;'>✅ Nelson's attendance project_id set to NULL</div>";
                }
            }
            
            // Show current status
            echo "<h2>Current Status:</h2>";
            
            // Project 16
            $stmt = $db->prepare("SELECT name, place FROM projects WHERE id = 16");
            $stmt->execute();
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<pre>Project 16: {$project['name']} - {$project['place']}</pre>";
            
            // Nelson's attendance
            $stmt = $db->prepare("SELECT project_id FROM attendance WHERE user_id = 57 AND DATE(check_in) = CURDATE()");
            $stmt->execute();
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<pre>Nelson's project_id: " . ($attendance['project_id'] ?: 'NULL') . "</pre>";
            
            // Test what will be displayed
            $stmt = $db->prepare("
                SELECT 
                    CASE 
                        WHEN p.name IS NOT NULL THEN p.name
                        WHEN a.project_id IS NULL THEN (SELECT location_title FROM settings LIMIT 1)
                        ELSE '----'
                    END as project_name,
                    CASE 
                        WHEN p.place IS NOT NULL THEN p.place
                        WHEN a.project_id IS NULL THEN (SELECT office_address FROM settings LIMIT 1)
                        ELSE 'Office'
                    END as location_display
                FROM attendance a
                LEFT JOIN projects p ON a.project_id = p.id
                WHERE a.user_id = 57 AND DATE(a.check_in) = CURDATE()
            ");
            $stmt->execute();
            $display = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($display) {
                echo "<pre>";
                echo "Will display:\n";
                echo "Project: {$display['project_name']}\n";
                echo "Location: {$display['location_display']}\n";
                echo "</pre>";
            }
            
            // Action buttons
            if (!isset($_POST['action'])) {
                echo "<h2>Actions:</h2>";
                echo "<form method='post' style='display: inline;'>";
                echo "<input type='hidden' name='action' value='revert'>";
                echo "<button type='submit' class='btn'>1. Revert Project 16</button>";
                echo "</form>";
                
                echo "<form method='post' style='display: inline;'>";
                echo "<input type='hidden' name='action' value='set_null'>";
                echo "<button type='submit' class='btn' style='background: #22c55e;'>2. Set Nelson's project_id to NULL</button>";
                echo "</form>";
            }
            
        } catch (Exception $e) {
            echo '<div>Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</body>
</html>
