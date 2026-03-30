<?php
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Fixed Query</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
        .success { color: #22c55e; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Test Fixed Query</h1>
        
        <?php
        try {
            $db = Database::connect();
            
            echo "<h2>Current Query Result (Nelson with recent attendance):</h2>";
            
            // Test with Nelson's recent attendance (not today)
            $stmt = $db->prepare("
                SELECT 
                    u.id as user_id,
                    u.name,
                    a.project_id,
                    a.check_in,
                    CASE 
                        WHEN p.name IS NOT NULL THEN CONCAT('Project: ', p.name)
                        WHEN a.check_in IS NOT NULL AND (a.project_id IS NULL OR a.project_id = 0) THEN (SELECT location_title FROM settings LIMIT 1)
                        ELSE '----'
                    END as project_name,
                    CASE 
                        WHEN p.place IS NOT NULL THEN p.place
                        WHEN a.check_in IS NOT NULL AND (a.project_id IS NULL OR a.project_id = 0) THEN (SELECT office_address FROM settings LIMIT 1)
                        ELSE 'Office'
                    END as location_display
                FROM users u
                LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                LEFT JOIN projects p ON a.project_id = p.id
                WHERE u.name LIKE '%Nelson%'
                ORDER BY a.check_in DESC
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                echo "<pre>";
                echo "User: {$result['name']}\n";
                echo "Project ID: " . ($result['project_id'] ?: 'NULL') . "\n";
                echo "Check In: " . ($result['check_in'] ?: 'NULL') . "\n";
                echo "Project Name: {$result['project_name']}\n";
                echo "Location: {$result['location_display']}\n";
                echo "</pre>";
                
                if ($result['project_name'] === 'Head Office' && $result['location_display'] === 'Thiruppalai, Madurai') {
                    echo "<div class='success'>✅ Query is working correctly!</div>";
                } else {
                    echo "<div>❌ Query needs adjustment</div>";
                }
            } else {
                echo "<pre>No result found</pre>";
            }
            
            // Show settings for reference
            echo "<h2>Settings Reference:</h2>";
            $stmt = $db->prepare("SELECT location_title, office_address FROM settings LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<pre>";
            echo "Location Title: {$settings['location_title']}\n";
            echo "Office Address: {$settings['office_address']}\n";
            echo "</pre>";
            
        } catch (Exception $e) {
            echo '<div>Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</body>
</html>
