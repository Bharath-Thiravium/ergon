<?php
class RateLimiter {
    public static function check($maxRequests = 100, $windowSeconds = 60) {
        $now = time();
        
        if (!isset($_SESSION['api_requests'])) {
            $_SESSION['api_requests'] = [];
        }
        
        // Clean old requests
        $_SESSION['api_requests'] = array_filter($_SESSION['api_requests'], 
            fn($timestamp) => ($now - $timestamp) < $windowSeconds
        );
        
        // Check limit
        if (count($_SESSION['api_requests']) >= $maxRequests) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'Too many requests']);
            exit;
        }
        
        // Log current request
        $_SESSION['api_requests'][] = $now;
    }
}
?>