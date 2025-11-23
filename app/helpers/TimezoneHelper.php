<?php
class TimezoneHelper {
    
    public static function nowIst() {
        $dt = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        return $dt->format('Y-m-d H:i:s');
    }
    
    public static function displayTime($istTime) {
        if (!$istTime || $istTime === '0000-00-00 00:00:00') return null;
        $dt = new DateTime($istTime, new DateTimeZone('Asia/Kolkata'));
        return $dt->format('H:i');
    }
    
    public static function getCurrentDate() {
        $dt = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        return $dt->format('Y-m-d');
    }
}
?>