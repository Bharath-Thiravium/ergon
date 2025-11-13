<?php
class PerformanceOptimizer {
    
    public static function optimizeDatabase() {
        $db = Database::connect();
        
        // Enable query cache
        $db->exec("SET SESSION query_cache_type = ON");
        $db->exec("SET SESSION query_cache_size = 67108864"); // 64MB
        
        // Optimize connection
        $db->exec("SET SESSION wait_timeout = 300");
        $db->exec("SET SESSION interactive_timeout = 300");
    }
    
    public static function enableGzipCompression() {
        if (!ob_get_level()) {
            ob_start('ob_gzhandler');
        }
    }
    
    public static function setCacheHeaders($seconds = 3600) {
        header("Cache-Control: public, max-age=$seconds");
        header("Expires: " . gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
        header("Last-Modified: " . gmdate('D, d M Y H:i:s', filemtime(__FILE__)) . ' GMT');
    }
    
    public static function minifyCSS($css) {
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = str_replace(['; ', ' {', '{ ', ' }', '} ', ': '], [';', '{', '{', '}', '}', ':'], $css);
        return trim($css);
    }
    
    public static function optimizeImages($uploadDir) {
        $files = glob($uploadDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        foreach ($files as $file) {
            if (filesize($file) > 500000) { // 500KB
                self::compressImage($file);
            }
        }
    }
    
    private static function compressImage($file) {
        $info = getimagesize($file);
        if ($info === false) return;
        
        $image = null;
        switch ($info['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($file);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file);
                break;
        }
        
        if ($image) {
            imagejpeg($image, $file, 75); // 75% quality
            imagedestroy($image);
        }
    }
}
?>