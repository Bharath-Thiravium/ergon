<?php
class TimezoneHelper {
    
    public static function toIst($utcTime) {
        if (!$utcTime || $utcTime === '0000-00-00 00:00:00') return null;
        
        // Force UTC interpretation for Hostinger
        $timestamp = strtotime($utcTime . ' UTC');
        if ($timestamp === false) {
            // Fallback if strtotime fails
            $timestamp = strtotime($utcTime);
        }
        return date('Y-m-d H:i:s', $timestamp + 19800);
    }
    
    public static function displayTime($utcTime) {
        if (!$utcTime || $utcTime === '0000-00-00 00:00:00') return null;
        
        // Force UTC interpretation for Hostinger
        $timestamp = strtotime($utcTime . ' UTC');
        if ($timestamp === false) {
            // Fallback if strtotime fails
            $timestamp = strtotime($utcTime);
        }
        return date('H:i', $timestamp + 19800);
    }
    
    public static function nowUtc() {
        return gmdate('Y-m-d H:i:s');
    }
    
    public static function getCurrentDate() {
        return date('Y-m-d', time() + 19800);
    }
}
?>