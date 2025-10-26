<?php
class PerformanceBooster {
    
    public static function enableCompression() {
        if (!ob_get_level()) {
            ob_start('ob_gzhandler');
        }
    }
    
    public static function setCacheHeaders($maxAge = 3600) {
        header("Cache-Control: public, max-age={$maxAge}");
        header("Expires: " . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
        header("Last-Modified: " . gmdate('D, d M Y H:i:s', filemtime(__FILE__)) . ' GMT');
    }
    
    public static function setNoCacheHeaders() {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
    }
    
    public static function minifyHTML($html) {
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        return trim($html);
    }
    
    public static function optimizeDatabase() {
        try {
            $db = Database::connect();
            
            $tables = ['users', 'tasks', 'attendance', 'leaves', 'expenses'];
            
            foreach ($tables as $table) {
                $db->exec("OPTIMIZE TABLE {$table}");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Database optimization error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function preloadCriticalResources() {
        echo '<link rel="preload" href="/ergon/assets/css/ergon.min.css" as="style">';
        echo '<link rel="preload" href="/ergon/assets/js/ergon-core.min.js" as="script">';
        echo '<link rel="dns-prefetch" href="//cdn.jsdelivr.net">';
        echo '<link rel="dns-prefetch" href="//cdnjs.cloudflare.com">';
    }
    
    public static function lazyLoadImages($html) {
        return preg_replace('/<img([^>]*?)src=/i', '<img$1loading="lazy" src=', $html);
    }
    
    public static function getMemoryUsage() {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit')
        ];
    }
    
    public static function getExecutionTime() {
        return microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
    }
}
?>
