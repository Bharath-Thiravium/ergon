<?php
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Controller Debug</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
        .error { color: #ef4444; }
        .success { color: #22c55e; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Controller Debug</h1>
        
        <?php
        try {
            $db = Database::connect();
            
            // Check routes.php to see which controller handles /attendance
            echo "<h2>Routes Configuration:</h2>";
            $routesFile = __DIR__ . '/app/config/routes.php';
            if (file_exists($routesFile)) {
                $content = file_get_contents($routesFile);
                preg_match_all('/attendance.*Controller.*index/', $content, $matches);
                echo "<pre>";
                foreach ($matches[0] as $match) {
                    echo $match . "\n";
                }
                echo "</pre>";
            }
            
            // Test all attendance controllers to see which query is actually used
            echo "<h2>Testing All Controllers:</h2>";
            
            // Test SimpleAttendanceController query
            echo "<h3>SimpleAttendanceController Query:</h3>";
            try {
                $stmt = $db->prepare("
                    SELECT 
                        u.id as user_id,
                        u.name,
                        a.project_id,
                        CASE 
                            WHEN p.name IS NOT NULL THEN p.name
                            WHEN a.check_in IS NOT NULL AND a.project_id IS NULL THEN (SELECT location_title FROM settings LIMIT 1)
                            ELSE '----'
                        END as project_name,
                        CASE 
                            WHEN p.place IS NOT NULL THEN p.place
                            WHEN a.check_in IS NOT NULL AND a.project_id IS NULL THEN (SELECT office_address FROM settings LIMIT 1)
                            ELSE 'Office'
                        END as location_display
                    FROM users u
                    LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = CURDATE()
                    LEFT JOIN projects p ON a.project_id = p.id
                    WHERE u.name LIKE '%Nelson%'
                ");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    echo "<pre>";
                    echo "Project Name: {$result['project_name']}\n";
                    echo "Location: {$result['location_display']}\n";
                    echo "</pre>";
                } else {
                    echo "<pre class='error'>No result</pre>";
                }
            } catch (Exception $e) {
                echo "<pre class='error'>Error: " . $e->getMessage() . "</pre>";
            }
            
            // Test AttendanceController query
            echo "<h3>AttendanceController Query:</h3>";
            try {
                $stmt = $db->prepare("
                    SELECT a.*, u.name as user_name, 
                           COALESCE(p.place, 'Office') as location_display, 
                           COALESCE(p.name, '----') as project_name
                    FROM attendance a 
                    LEFT JOIN users u ON a.user_id = u.id 
                    LEFT JOIN projects p ON a.project_id = p.id 
                    WHERE u.name LIKE '%Nelson%' AND DATE(a.check_in) = CURDATE()
                ");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    echo "<pre>";
                    echo "Project Name: {$result['project_name']}\n";
                    echo "Location: {$result['location_display']}\n";
                    echo "</pre>";
                } else {
                    echo "<pre class='error'>No result</pre>";
                }
            } catch (Exception $e) {
                echo "<pre class='error'>Error: " . $e->getMessage() . "</pre>";
            }
            
            // Check which files exist
            echo "<h2>Controller Files:</h2>";
            $controllers = [
                'SimpleAttendanceController.php',
                'AttendanceController.php', 
                'EnhancedAttendanceController.php',
                'UnifiedAttendanceController.php'
            ];
            
            foreach ($controllers as $controller) {
                $path = __DIR__ . '/app/controllers/' . $controller;
                $exists = file_exists($path) ? '✅' : '❌';
                echo "<pre>{$exists} {$controller}</pre>";
            }
            
        } catch (Exception $e) {
            echo '<div class="error">Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</body>
</html>
