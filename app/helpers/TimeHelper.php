<?php
class TimeHelper {
    /**
     * Convert datetime to IST and format with AM/PM
     * @param string $datetime - MySQL datetime string
     * @return string - Formatted time in IST with AM/PM (hh:mm:ss AM/PM)
     */
    public static function formatToIST($datetime) {
        if (!$datetime || $datetime === '0000-00-00 00:00:00') {
            return '00:00:00 AM';
        }
        
        try {
            // Create DateTime object from the input
            $dt = new DateTime($datetime);
            
            // Set timezone to IST (UTC+5:30)
            $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
            
            // Format as hh:mm:ss AM/PM
            return $dt->format('h:i:s A');
        } catch (Exception $e) {
            error_log('TimeHelper formatToIST error: ' . $e->getMessage());
            return '00:00:00 AM';
        }
    }
    
    /**
     * Get current IST time
     * @return string - Current time in IST with AM/PM format
     */
    public static function getCurrentIST() {
        try {
            $dt = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
            return $dt->format('h:i:s A');
        } catch (Exception $e) {
            error_log('TimeHelper getCurrentIST error: ' . $e->getMessage());
            return date('h:i:s A');
        }
    }
}
?>