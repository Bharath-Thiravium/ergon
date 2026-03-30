<?php
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Nelson Data Check</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Nelson Data Check</h1>
        
        <?php
        try {
            $db = Database::connect();
            
            // Find Nelson's actual attendance records
            echo "<h2>Nelson's All Attendance Records:</h2>";
            $stmt = $db->prepare("
                SELECT id, user_id, project_id, check_in, check_out, location_name, latitude, longitude
                FROM attendance 
                WHERE user_id = (SELECT id FROM users WHERE name LIKE '%Nelson%' LIMIT 1)
                ORDER BY check_in DESC
                LIMIT 5
            ");
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($records)) {
                echo "<pre>No attendance records found for Nelson</pre>";
            } else {
                foreach ($records as $record) {
                    echo "<pre>";
                    echo "ID: {$record['id']}\n";
                    echo "Project ID: " . ($record['project_id'] ?: 'NULL') . "\n";
                    echo "Check In: {$record['check_in']}\n";
                    echo "Location Name: " . ($record['location_name'] ?: 'NULL') . "\n";
                    echo "GPS: ({$record['latitude']}, {$record['longitude']})\n";
                    echo "---\n";
                    echo "</pre>";
                }
                
                // Test query with actual attendance record
                echo "<h2>Test Query with Actual Record:</h2>";
                $latestRecord = $records[0];
                
                $stmt = $db->prepare("
                    SELECT 
                        a.id,
                        a.project_id,
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
                    WHERE a.id = ?
                ");
                $stmt->execute([$latestRecord['id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    echo "<pre>";
                    echo "Attendance ID: {$result['id']}\n";
                    echo "Project ID: " . ($result['project_id'] ?: 'NULL') . "\n";
                    echo "Project Name: {$result['project_name']}\n";
                    echo "Location: {$result['location_display']}\n";
                    echo "</pre>";
                }
            }
            
            // Check if Nelson has today's attendance
            echo "<h2>Today's Attendance Check:</h2>";
            $stmt = $db->prepare("
                SELECT COUNT(*) as count
                FROM attendance a
                JOIN users u ON a.user_id = u.id
                WHERE u.name LIKE '%Nelson%' AND DATE(a.check_in) = CURDATE()
            ");
            $stmt->execute();
            $todayCount = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<pre>Nelson's attendance records for today: {$todayCount['count']}</pre>";
            
        } catch (Exception $e) {
            echo '<div>Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</body>
</html>
