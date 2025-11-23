<?php
class TimezoneHelper {
    
    public static function nowIst() {
        // Return current IST time directly
        return date('Y-m-d H:i:s', time() + 19800);
    }
    
    public static function displayTime($istTime) {
        if (!$istTime || $istTime === '0000-00-00 00:00:00') return null;
        // Time is already in IST, just format it
        return date('H:i', strtotime($istTime));
    }
    
    public static function getCurrentDate() {
        return date('Y-m-d', time() + 19800);
    }
}
?>