<?php

require_once __DIR__ . '/../config/database.php';

class PerformanceOptimizer {
    
    public static function enableGzipCompression() {
        if (!ob_get_level()) {
            ob_start('ob_gzhandler');
        }
    }
    
    public static function enableCompression() {
        self::enableGzipCompression();
    }
    
    public static function setCacheHeaders($maxAge = 3600) {
        header('Cache-Control: public, max-age=' . $maxAge);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
    }
    
    public static function setHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
    
    public static function optimizeDatabase() {
        try {
            $db = Database::connect();
            $db->exec("OPTIMIZE TABLE users, tasks, attendance, notifications");
        } catch (Exception $e) {
            error_log('Database optimization failed: ' . $e->getMessage());
        }
    }
    
    public static function clearCache() {
        $cacheDir = __DIR__ . '/../../storage/cache/';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}