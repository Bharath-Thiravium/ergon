<?php
class ProjectLocationSelector {
    
    /**
     * Handle multiple valid project locations scenario
     * @param float $userLat User latitude
     * @param float $userLng User longitude  
     * @param array $validLocations Array of locations user is within
     * @return array Selected location info
     */
    public static function selectBestLocation($userLat, $userLng, $validLocations) {
        if (empty($validLocations)) {
            return null;
        }
        
        // If only one location, return it
        if (count($validLocations) === 1) {
            return $validLocations[0];
        }
        
        // Multiple locations - apply selection logic
        $locationScores = [];
        
        foreach ($validLocations as $location) {
            $distance = LocationHelper::calculateDistance(
                $userLat, $userLng, 
                $location['lat'], $location['lng']
            );
            
            $score = [
                'location' => $location,
                'distance' => $distance,
                'priority' => self::getLocationPriority($location['type'])
            ];
            
            $locationScores[] = $score;
        }
        
        // Sort by priority first, then by distance
        usort($locationScores, function($a, $b) {
            if ($a['priority'] === $b['priority']) {
                return $a['distance'] <=> $b['distance']; // Closer is better
            }
            return $b['priority'] <=> $a['priority']; // Higher priority first
        });
        
        return $locationScores[0]['location'];
    }
    
    /**
     * Get location type priority (higher = more preferred)
     * @param string $type Location type
     * @return int Priority score
     */
    private static function getLocationPriority($type) {
        switch ($type) {
            case 'project':
                return 10; // Project locations have higher priority
            case 'office':
                return 5;  // Office is fallback
            default:
                return 1;
        }
    }
    
    /**
     * Get user-friendly selection message for multiple locations
     * @param array $validLocations Array of valid locations
     * @return string Message for user
     */
    public static function getMultiLocationMessage($validLocations) {
        if (count($validLocations) <= 1) {
            return '';
        }
        
        $locationNames = array_map(function($loc) {
            return $loc['name'];
        }, $validLocations);
        
        return "You are within range of multiple locations: " . 
               implode(', ', $locationNames) . 
               ". Selecting closest project location.";
    }
}
?>
