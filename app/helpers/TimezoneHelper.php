<?php
class TimezoneHelper {
    
    public static function setSystemTimezone() {
        try {
            $db = Database::connect();
            // Get owner's timezone as system timezone
            $stmt = $db->prepare("SELECT up.timezone FROM user_preferences up JOIN users u ON up.user_id = u.id WHERE u.role = 'owner' LIMIT 1");
            $stmt->execute();
            $ownerPrefs = $stmt->fetch();
            $timezone = $ownerPrefs['timezone'] ?? 'Asia/Kolkata';
            
            date_default_timezone_set($timezone);
            return $timezone;
        } catch (Exception $e) {
            date_default_timezone_set('Asia/Kolkata');
            return 'Asia/Kolkata';
        }
    }
    
    public static function getCurrentTime() {
        self::setSystemTimezone();
        return date('Y-m-d H:i:s');
    }
    
    public static function getCurrentDate() {
        self::setSystemTimezone();
        return date('Y-m-d');
    }
}
?>