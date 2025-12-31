<?php
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Nelson Attendance Debug</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Nelson Attendance Debug</h1>
        
        <?php
        try {
            $db = Database::connect();
            
            // Get Nelson's user ID
            $stmt = $db->prepare("SELECT id, name FROM users WHERE name LIKE '%Nelson%' LIMIT 1");
            $stmt->execute();
            $nelson = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($nelson) {
                echo "<h2>Nelson's User Info:</h2>";
                echo "<pre>User ID: {$nelson['id']}\nName: {$nelson['name']}</pre>";
                
                // Get Nelson's recent attendance
                echo "<h2>Nelson's Recent Attendance (Raw Data):</h2>";
                $stmt = $db->prepare("
                    SELECT id, user_id, project_id, check_in, check_out, location_name, latitude, longitude
                    FROM attendance 
                    WHERE user_id = ? 
                    AND DATE(check_in) >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)
                    ORDER BY check_in DESC
                ");
                $stmt->execute([$nelson['id']]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($records as $record) {
                    echo "<pre>";
                    echo "ID: {$record['id']}\n";
                    echo "Project ID: " . ($record['project_id'] ?: 'NULL') . "\n";
                    echo "Location Name: " . ($record['location_name'] ?: 'NULL') . "\n";
                    echo "GPS: ({$record['latitude']}, {$record['longitude']})\n";
                    echo "Check In: {$record['check_in']}\n";
                    echo "Check Out: " . ($record['check_out'] ?: 'NULL') . "\n";
                    echo "</pre>";
                }
                
                // Test the query used by SimpleAttendanceController
                echo "<h2>Query Result (What UI Shows):</h2>";
                $stmt = $db->prepare("
                    SELECT 
                        u.id as user_id,
                        u.name,
                        a.project_id,
                        CASE 
                            WHEN p.name IS NOT NULL THEN p.name
                            WHEN a.check_in IS NOT NULL THEN (SELECT location_title FROM settings LIMIT 1)
                            ELSE '----'
                        END as project_name,
                        CASE 
                            WHEN p.place IS NOT NULL THEN p.place
                            WHEN a.check_in IS NOT NULL THEN (SELECT office_address FROM settings LIMIT 1)
                            ELSE 'Office'
                        END as location_display
                    FROM users u
                    LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = CURDATE()
                    LEFT JOIN projects p ON a.project_id = p.id
                    WHERE u.id = ?
                ");
                $stmt->execute([$nelson['id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    echo "<pre>";
                    echo "User: {$result['name']}\n";
                    echo "Project ID: " . ($result['project_id'] ?: 'NULL') . "\n";
                    echo "Project Name (UI): {$result['project_name']}\n";
                    echo "Location Display (UI): {$result['location_display']}\n";
                    echo "</pre>";
                } else {
                    echo "<pre>No attendance record found for today</pre>";
                }
                
                // Show settings values
                echo "<h2>Settings Values:</h2>";
                $stmt = $db->prepare("SELECT location_title, office_address FROM settings LIMIT 1");
                $stmt->execute();
                $settings = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<pre>";
                echo "Location Title: " . ($settings['location_title'] ?: 'NULL') . "\n";
                echo "Office Address: " . ($settings['office_address'] ?: 'NULL') . "\n";
                echo "</pre>";
                
            } else {
                echo "<p>Nelson not found in users table</p>";
            }
            
        } catch (Exception $e) {
            echo '<div>Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</body>
</html>
