<?php
class TimezoneHelper {
    
    public static function getOwnerTimezone() {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT up.timezone FROM user_preferences up JOIN users u ON up.user_id = u.id WHERE u.role = 'owner' LIMIT 1");
            $stmt->execute();
            $ownerPrefs = $stmt->fetch();
            return $ownerPrefs['timezone'] ?? 'Asia/Kolkata';
        } catch (Exception $e) {
            return 'Asia/Kolkata';
        }
    }
    
    public static function utcToOwner($utcDatetimeStr) {
        if (!$utcDatetimeStr) return null;
        try {
            $dt = new DateTime($utcDatetimeStr, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone(self::getOwnerTimezone()));
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return $utcDatetimeStr;
        }
    }
    
    public static function nowUtc() {
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');
    }
    
    public static function displayTime($utcTime) {
        if (!$utcTime) return null;
        $ownerTime = self::utcToOwner($utcTime);
        return date('H:i', strtotime($ownerTime));
    }
}
?>