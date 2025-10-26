<?php
class RateLimiter {
    private static $storage = [];
    
    public static function check($key, $maxAttempts = 5, $timeWindow = 900) {
        $now = time();
        $windowStart = $now - $timeWindow;
        
        if (!isset(self::$storage[$key])) {
            self::$storage[$key] = [];
        }
        
        self::$storage[$key] = array_filter(self::$storage[$key], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        return count(self::$storage[$key]) < $maxAttempts;
    }
    
    public static function hit($key) {
        if (!isset(self::$storage[$key])) {
            self::$storage[$key] = [];
        }
        
        self::$storage[$key][] = time();
    }
    
    public static function getRemainingAttempts($key, $maxAttempts = 5, $timeWindow = 900) {
        $now = time();
        $windowStart = $now - $timeWindow;
        
        if (!isset(self::$storage[$key])) {
            return $maxAttempts;
        }
        
        $attempts = array_filter(self::$storage[$key], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        return max(0, $maxAttempts - count($attempts));
    }
    
    public static function getTimeUntilReset($key, $timeWindow = 900) {
        if (!isset(self::$storage[$key]) || empty(self::$storage[$key])) {
            return 0;
        }
        
        $oldestAttempt = min(self::$storage[$key]);
        $resetTime = $oldestAttempt + $timeWindow;
        
        return max(0, $resetTime - time());
    }
    
    public static function clear($key) {
        unset(self::$storage[$key]);
    }
}
?>