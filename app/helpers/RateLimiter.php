<?php
class RateLimiter {
    private $maxAttempts = 5;
    private $timeWindow = 900; // 15 minutes
    
    public function isBlocked($ip) {
        $attempts = $this->getAttempts($ip);
        return count($attempts) >= $this->maxAttempts;
    }
    
    public function recordAttempt($ip, $success = false) {
        if ($success) {
            $this->clearAttempts($ip);
        } else {
            $this->addAttempt($ip);
        }
    }
    
    private function getAttempts($ip) {
        $file = $this->getAttemptsFile($ip);
        if (!file_exists($file)) {
            return [];
        }
        
        $attempts = json_decode(file_get_contents($file), true) ?: [];
        $cutoff = time() - $this->timeWindow;
        
        return array_filter($attempts, function($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });
    }
    
    private function addAttempt($ip) {
        $attempts = $this->getAttempts($ip);
        $attempts[] = time();
        
        $file = $this->getAttemptsFile($ip);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($file, json_encode($attempts));
    }
    
    private function clearAttempts($ip) {
        $file = $this->getAttemptsFile($ip);
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    private function getAttemptsFile($ip) {
        $hash = md5($ip);
        return __DIR__ . '/../../storage/rate_limits/' . $hash . '.json';
    }
}
?>