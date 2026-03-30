<?php
require_once __DIR__ . '/app/config/database.php';

function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Production Settings Debug</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: #22c55e; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .info { color: #3b82f6; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Production Settings Project Debug</h1>
        
        <?php
        try {
            $db = Database::connect();
            
            // 1. Settings Table
            echo '<div class="section">';
            echo '<h2>1. Settings Table</h2>';
            $stmt = $db->prepare("SELECT base_location_lat, base_location_lng, attendance_radius, location_title, office_address FROM settings LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($settings) {
                echo '<pre>';
                echo "Location Title: " . ($settings['location_title'] ?: '<span class="error">NULL</span>') . "\n";
                echo "Office Address: " . ($settings['office_address'] ?: '<span class="error">NULL</span>') . "\n";
                echo "GPS Coordinates: ({$settings['base_location_lat']}, {$settings['base_location_lng']})\n";
                echo "Attendance Radius: {$settings['attendance_radius']}m";
                echo '</pre>';
            } else {
                echo '<span class="error">❌ No settings found</span>';
            }
            echo '</div>';
            
            // 2. Project Matching
            echo '<div class="section">';
            echo '<h2>2. Project Matching by Location Title</h2>';
            if ($settings && $settings['location_title']) {
                $stmt = $db->prepare("SELECT id, name, status FROM projects WHERE name = ? AND status = 'active'");
                $stmt->execute([$settings['location_title']]);
                $project = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($project) {
                    echo '<span class="success">✅ Found matching project:</span><br>';
                    echo "<pre>Project ID: {$project['id']}\nProject Name: {$project['name']}\nStatus: {$project['status']}</pre>";
                } else {
                    echo '<span class="error">❌ No active project found with name: ' . $settings['location_title'] . '</span>';
                }
            } else {
                echo '<span class="error">❌ location_title is empty in settings</span>';
            }
            echo '</div>';
            
            // 3. Recent Attendance GPS Test
            echo '<div class="section">';
            echo '<h2>3. Recent Attendance GPS Test</h2>';
            $stmt = $db->prepare("
                SELECT latitude, longitude, check_in, user_id, project_id
                FROM attendance 
                WHERE latitude IS NOT NULL AND longitude IS NOT NULL 
                AND latitude != 0 AND longitude != 0
                AND DATE(check_in) >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)
                ORDER BY check_in DESC 
                LIMIT 3
            ");
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($records)) {
                echo '<span class="info">No recent attendance records with GPS found</span>';
            } else {
                foreach ($records as $record) {
                    $distance = calculateDistance($record['latitude'], $record['longitude'], $settings['base_location_lat'], $settings['base_location_lng']);
                    $withinRadius = $distance <= $settings['attendance_radius'];
                    $status = $withinRadius ? '<span class="success">✅ MATCH</span>' : '<span class="error">❌ NO MATCH</span>';
                    
                    echo '<pre>';
                    echo "User ID: {$record['user_id']} | Project ID: " . ($record['project_id'] ?: 'NULL') . "\n";
                    echo "GPS: ({$record['latitude']}, {$record['longitude']})\n";
                    echo "Distance to settings location: " . round($distance, 2) . "m | " . $status . "\n";
                    echo "Date: {$record['check_in']}\n";
                    echo '</pre>';
                }
            }
            echo '</div>';
            
            // 4. Process Simulation
            echo '<div class="section">';
            echo '<h2>4. Attendance Process Simulation</h2>';
            if (!empty($records) && $settings) {
                $testRecord = $records[0];
                $distance = calculateDistance($testRecord['latitude'], $testRecord['longitude'], $settings['base_location_lat'], $settings['base_location_lng']);
                
                echo '<pre>';
                echo "Testing with latest GPS: ({$testRecord['latitude']}, {$testRecord['longitude']})\n";
                echo "Distance to settings location: " . round($distance, 2) . "m\n\n";
                
                if ($distance <= $settings['attendance_radius']) {
                    echo '<span class="success">✅ Within radius - checking for project...</span>' . "\n";
                    
                    if ($settings['location_title']) {
                        $stmt = $db->prepare("SELECT id FROM projects WHERE name = ? AND status = 'active'");
                        $stmt->execute([$settings['location_title']]);
                        $project = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($project) {
                            echo '<span class="success">✅ Would assign project_id: ' . $project['id'] . '</span>' . "\n";
                            echo '<span class="success">✅ Would use location: ' . ($settings['office_address'] ?: 'NULL') . '</span>' . "\n";
                            echo '<span class="success">✅ Would use project name: ' . $settings['location_title'] . '</span>' . "\n";
                        } else {
                            echo '<span class="error">❌ Would assign project_id: NULL (no matching project)</span>' . "\n";
                        }
                    } else {
                        echo '<span class="error">❌ location_title is empty - would assign NULL</span>' . "\n";
                    }
                } else {
                    echo '<span class="error">❌ Outside radius - would reject attendance</span>' . "\n";
                }
                echo '</pre>';
            } else {
                echo '<span class="info">No test data available</span>';
            }
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="section"><span class="error">Error: ' . $e->getMessage() . '</span></div>';
        }
        ?>
    </div>
</body>
</html>
