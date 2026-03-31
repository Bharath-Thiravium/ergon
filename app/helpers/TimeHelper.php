<?php
class TimeHelper {
    /**
     * Calculate working hours from check_in / check_out (both stored as IST strings).
     * Uses current IST time when check_out is NULL.
     * Returns formatted string like "2h 35m".
     */
    public static function calcWorkingHours(?string $checkIn, ?string $checkOut): string {
        if (!$checkIn || $checkIn === '0000-00-00 00:00:00') {
            return '0h 0m';
        }
        $inTs = strtotime($checkIn);
        if ($checkOut && $checkOut !== '0000-00-00 00:00:00') {
            $outTs = strtotime($checkOut);
        } else {
            // Current IST time as a timestamp
            $outTs = (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->getTimestamp();
        }
        $secs = max(0, $outTs - $inTs);
        $h    = (int) floor($secs / 3600);
        $m    = (int) floor(($secs % 3600) / 60);
        return "{$h}h {$m}m";
    }

    /**
     * Convert datetime to IST and format with AM/PM
     * @param string $datetime - MySQL datetime string stored in IST
     * @return string - Formatted time in IST with AM/PM (hh:mm:ss AM/PM)
     */
    public static function formatToIST($datetime) {
        if (!$datetime || $datetime === '0000-00-00 00:00:00') {
            return '00:00:00 AM';
        }
        
        try {
            // Times are stored in IST, format them in IST timezone
            $dt = new DateTime($datetime, new DateTimeZone('Asia/Kolkata'));
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
