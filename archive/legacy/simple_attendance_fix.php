<?php
// Simple fix - replace the entire getAllAttendanceByDate method

$controllerPath = __DIR__ . '/app/controllers/UnifiedAttendanceController.php';
$content = file_get_contents($controllerPath);

// Find and replace the entire method
$oldMethod = '/private function getAllAttendanceByDate\([^}]+\{[^}]+\}[^}]+\}/s';

$newMethod = 'private function getAllAttendanceByDate($selectedDate, $role, $userId) {
        try {
            // Role-based filtering
            if ($role === "user") {
                $userCondition = "AND u.id = $userId";
            } elseif ($role === "admin") {
                $userCondition = "AND u.role IN (\'user\', \'admin\') AND u.id != $userId";
            } else {
                $userCondition = "AND u.role IN (\'user\', \'admin\')";
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    u.id as user_id,
                    u.name,
                    u.email,
                    u.role,
                    a.id as attendance_id,
                    a.check_in,
                    a.check_out,
                    CASE 
                        WHEN a.check_in IS NOT NULL THEN \'Present\'
                        ELSE \'Absent\'
                    END as status,
                    COALESCE(TIME_FORMAT(a.check_in, \'%H:%i\'), \'00:00\') as check_in_time,
                    COALESCE(TIME_FORMAT(a.check_out, \'%H:%i\'), \'00:00\') as check_out_time,
                    CASE 
                        WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                            CONCAT(TIMESTAMPDIFF(HOUR, a.check_in, a.check_out), \'h \', 
                                   TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) % 60, \'m\')
                        ELSE \'0h 0m\'
                    END as working_hours
                FROM users u
                LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
                WHERE u.status != \'removed\' $userCondition
                ORDER BY u.role DESC, u.name
            ");
            $stmt->execute([$selectedDate]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($role === "owner") {
                $grouped = [\'admin\' => [], \'user\' => []];
                foreach ($records as $record) {
                    $userRole = $record[\'role\'] === \'admin\' ? \'admin\' : \'user\';
                    $grouped[$userRole][] = $record;
                }
                return $grouped;
            }
            
            return $records;
        } catch (Exception $e) {
            error_log(\'getAllAttendanceByDate error: \' . $e->getMessage());
            return [];
        }
    }';

$content = preg_replace($oldMethod, $newMethod, $content);
file_put_contents($controllerPath, $content);

echo "Fixed getAllAttendanceByDate method";
?>