<?php
class RateLimiter {
    public static function check() {
        // Simple rate limiter - allow all requests for now
        return true;
    }
}
?>