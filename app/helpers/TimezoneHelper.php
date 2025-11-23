<?php
class TimezoneHelper {
    
    public static function displayTime($dbTime) {
        if (!$dbTime) return null;
        
        // Direct conversion: add 5.5 hours to database time for IST
        $timestamp = strtotime($dbTime);
        $istTimestamp = $timestamp + (5.5 * 3600); // Add 5.5 hours
        return date('H:i', $istTimestamp);
    }
    
    public static function nowUtc() {
        return gmdate('Y-m-d H:i:s');
    }
    
    public static function getCurrentDate() {
        // Get current IST date
        $istTimestamp = time() + (5.5 * 3600);
        return gmdate('Y-m-d', $istTimestamp);
    }
}
?>