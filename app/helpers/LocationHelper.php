<?php
class LocationHelper {
    
    const DEFAULT_RADIUS = 50; // Default attendance radius in meters
    const MIN_RADIUS = 5;      // Minimum allowed radius
    const MAX_RADIUS = 1000;   // Maximum allowed radius
    
    /**
     * Calculate distance between two coordinates using Haversine formula
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point  
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in meters
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        // Validate coordinates
        if (!is_numeric($lat1) || !is_numeric($lon1) || !is_numeric($lat2) || !is_numeric($lon2)) {
            return PHP_FLOAT_MAX; // Return very large distance for invalid coordinates
        }
        
        // Convert to float to ensure precision
        $lat1 = floatval($lat1);
        $lon1 = floatval($lon1);
        $lat2 = floatval($lat2);
        $lon2 = floatval($lon2);
        
        // Check for zero coordinates (likely invalid)
        if ($lat1 == 0 && $lon1 == 0) {
            return PHP_FLOAT_MAX;
        }
        if ($lat2 == 0 && $lon2 == 0) {
            return PHP_FLOAT_MAX;
        }
        
        $earthRadius = 6371000; // Earth's radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        $distance = $earthRadius * $c;
        
        // Log the calculation for debugging
        error_log("[DISTANCE_DEBUG] ({$lat1}, {$lon1}) to ({$lat2}, {$lon2}) = {$distance}m");
        
        return $distance;
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
        
        $allowedRadius = $settings['attendance_radius'] ?? 50;
        
        return [
            'allowed' => $distance <= $allowedRadius,
            'distance' => round($distance, 2),
            'allowed_radius' => $allowedRadius,
            'user_coords' => "({$userLat}, {$userLon})",
            'office_coords' => "({$settings['base_location_lat']}, {$settings['base_location_lng']})"
        ];
    }
    
    /**
     * Validate user location against multiple allowed locations
     * @param float $userLat User's current latitude
     * @param float $userLon User's current longitude
     * @param array $allowedLocations Array of locations with lat, lng, radius
     * @return array Validation result
     */
    public static function validateMultipleLocations($userLat, $userLon, $allowedLocations) {
        if (!$userLat || !$userLon) {
            return ['allowed' => false, 'error' => 'GPS coordinates are required'];
        }
        
        if (empty($allowedLocations)) {
            return ['allowed' => false, 'error' => 'No attendance locations configured'];
        }
        
        $validationResults = [];
        
        foreach ($allowedLocations as $location) {
            if (!isset($location['lat']) || !isset($location['lng']) || !isset($location['radius'])) {
                continue;
            }
            
            $distance = self::calculateDistance($userLat, $userLon, $location['lat'], $location['lng']);
            $validationResults[] = [
                'name' => $location['name'] ?? 'Unknown Location',
                'distance' => round($distance, 2),
                'radius' => $location['radius'],
                'allowed' => $distance <= $location['radius']
            ];
            
            // If within any location, return success
            if ($distance <= $location['radius']) {
                return [
                    'allowed' => true,
                    'location' => $location,
                    'distance' => round($distance, 2)
                ];
            }
        }
        
        // Generate detailed error message
        $errorMsg = "You are outside all allowed areas:\n";
        foreach ($validationResults as $result) {
            $errorMsg .= "• {$result['name']}: {$result['distance']}m away (max {$result['radius']}m)\n";
        }
        
        return [
            'allowed' => false,
            'error' => $errorMsg,
            'validation_results' => $validationResults
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
                'attendance_radius' => 50,
                'location_title' => 'Main Office'
            ];
        } catch (Exception $e) {
            error_log('LocationHelper: Failed to get office settings - ' . $e->getMessage());
            return [
                'base_location_lat' => 0,
                'base_location_lng' => 0,
                'attendance_radius' => 50,
                'location_title' => 'Main Office'
            ];
        }
    }
    
    /**
     * Get all allowed attendance locations (office + projects)
     * @param PDO $db Database connection
     * @return array Array of allowed locations
     */
    public static function getAllowedLocations($db) {
        $locations = [];
        
        try {
            // Get office location
            $settings = self::getOfficeSettings($db);
            if ($settings['base_location_lat'] != 0 && $settings['base_location_lng'] != 0) {
                $locations[] = [
                    'name' => $settings['location_title'] ?? 'Main Office',
                    'lat' => $settings['base_location_lat'],
                    'lng' => $settings['base_location_lng'],
                    'radius' => $settings['attendance_radius'],
                    'type' => 'office'
                ];
            }
            
            // Get project locations
            $stmt = $db->query("SELECT id, name, latitude, longitude, checkin_radius, location_title FROM projects WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND status = 'active'");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($projects as $project) {
                if ($project['latitude'] != 0 && $project['longitude'] != 0) {
                    $locations[] = [
                        'name' => $project['location_title'] ?: $project['name'] . ' Site',
                        'lat' => $project['latitude'],
                        'lng' => $project['longitude'],
                        'radius' => $project['checkin_radius'],
                        'type' => 'project',
                        'project_id' => $project['id']
                    ];
                }
            }
            
        } catch (Exception $e) {
            error_log('LocationHelper: Failed to get allowed locations - ' . $e->getMessage());
        }
        
        return $locations;
    }
}
?>
