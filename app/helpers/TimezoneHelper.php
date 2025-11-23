<?php
class TimezoneHelper {
    
    public static function toIst($utcTime) {
        if (!$utcTime || $utcTime === '0000-00-00 00:00:00') return null;
        return date('Y-m-d H:i:s', strtotime($utcTime) + 19800);
    }
    
    public static function displayTime($utcTime) {
        if (!$utcTime || $utcTime === '0000-00-00 00:00:00') return null;
        return date('H:i', strtotime($utcTime) + 19800);
    }
    
    public static function nowUtc() {
        return gmdate('Y-m-d H:i:s');
    }
    
    public static function getCurrentDate() {
        return date('Y-m-d', time() + 19800);
    }
}
?>