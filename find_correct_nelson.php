<?php
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Find Correct Nelson</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
        .success { color: #22c55e; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Find Correct Nelson</h1>
        
        <?php
        try {
            $db = Database::connect();
            
            // Find all users with Nelson in name
            echo "<h2>All Users with 'Nelson':</h2>";
            $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE name LIKE '%Nelson%' OR name LIKE '%nelson%'");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($users as $user) {
                echo "<pre>";
                echo "ID: {$user['id']}\n";
                echo "Name: {$user['name']}\n";
                echo "Email: {$user['email']}\n";
                echo "Role: {$user['role']}\n";
                
                // Check attendance for each user
                $stmt2 = $db->prepare("SELECT COUNT(*) as count FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE()");
                $stmt2->execute([$user['id']]);
                $todayCount = $stmt2->fetch(PDO::FETCH_ASSOC);
                echo "Today's attendance: {$todayCount['count']}\n";
                
                if ($todayCount['count'] > 0) {
                    // Get today's attendance details
                    $stmt3 = $db->prepare("
                        SELECT a.id, a.project_id, a.check_in, a.location_name,
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
                        WHERE a.user_id = ? AND DATE(a.check_in) = CURDATE()
                    ");
                    $stmt3->execute([$user['id']]);
                    $attendance = $stmt3->fetch(PDO::FETCH_ASSOC);
                    
                    if ($attendance) {
                        echo "Attendance ID: {$attendance['id']}\n";
                        echo "Project ID: " . ($attendance['project_id'] ?: 'NULL') . "\n";
                        echo "Project Name: {$attendance['project_name']}\n";
                        echo "Location: {$attendance['location_display']}\n";
                        echo "Check In: {$attendance['check_in']}\n";
                        
                        if ($attendance['project_name'] === 'Head Office') {
                            echo "<span class='success'>✅ This shows Head Office correctly!</span>\n";
                        }
                    }
                }
                echo "---\n";
                echo "</pre>";
            }
            
        } catch (Exception $e) {
            echo '<div>Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</body>
</html>
