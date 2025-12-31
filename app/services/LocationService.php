<?php

class LocationService {
    
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // Earth radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c; // Distance in meters
    }
    
    public static function isWithinRadius($userLat, $userLon, $projectLat, $projectLon, $allowedRadius) {
        if (!$projectLat || !$projectLon) return true; // No location restriction
        
        $distance = self::calculateDistance($userLat, $userLon, $projectLat, $projectLon);
        return $distance <= $allowedRadius;
    }
    
    public static function getUserProjects($userId) {
        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT p.*, up.status as assignment_status 
            FROM projects p 
            JOIN user_projects up ON p.id = up.project_id 
            WHERE up.user_id = ? AND up.status = 'active' AND p.status = 'active'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
?>
