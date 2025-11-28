<?php
class LocationHelper {
    
    /**
     * Calculate distance between two coordinates using Haversine formula
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point  
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in meters
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
    
    /**
     * Check if user is within allowed attendance radius
     * @param float $userLat User's current latitude
     * @param float $userLon User's current longitude
     * @param array $settings Office settings with base_location_lat, base_location_lng, attendance_radius
     * @return array Result with 'allowed' boolean and 'distance' in meters
     */
    public static function isWithinAttendanceRadius($userLat, $userLon, $settings) {
        if (!$userLat || !$userLon || !$settings['base_location_lat'] || !$settings['base_location_lng']) {
            return ['allowed' => false, 'distance' => null, 'error' => 'Missing location data'];
        }
        
        $distance = self::calculateDistance(
            $userLat, 
            $userLon, 
            $settings['base_location_lat'], 
            $settings['base_location_lng']
        );
        
        $allowedRadius = $settings['attendance_radius'] ?? 200;
        
        return [
            'allowed' => $distance <= $allowedRadius,
            'distance' => round($distance, 2),
            'allowed_radius' => $allowedRadius
        ];
    }
    
    /**
     * Get office settings from database
     * @param PDO $db Database connection
     * @return array Office settings
     */
    public static function getOfficeSettings($db) {
        try {
            $stmt = $db->query("SELECT * FROM settings LIMIT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: [
                'base_location_lat' => 0,
                'base_location_lng' => 0,
                'attendance_radius' => 200
            ];
        } catch (Exception $e) {
            error_log('LocationHelper: Failed to get office settings - ' . $e->getMessage());
            return [
                'base_location_lat' => 0,
                'base_location_lng' => 0,
                'attendance_radius' => 200
            ];
        }
    }
}
?>