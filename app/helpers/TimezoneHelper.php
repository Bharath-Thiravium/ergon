<?php
class TimezoneHelper {
    
    public static function setUserTimezone($userId) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT timezone FROM user_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            $userPrefs = $stmt->fetch();
            $timezone = $userPrefs['timezone'] ?? 'Asia/Kolkata';
            
            date_default_timezone_set($timezone);
            return $timezone;
        } catch (Exception $e) {
            date_default_timezone_set('Asia/Kolkata');
            return 'Asia/Kolkata';
        }
    }
    
    public static function getCurrentTime($userId) {
        self::setUserTimezone($userId);
        return date('Y-m-d H:i:s');
    }
    
    public static function getCurrentDate($userId) {
        self::setUserTimezone($userId);
        return date('Y-m-d');
    }
}
?>