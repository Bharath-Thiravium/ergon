<?php
/**
 * CDN Helper for Future Scalability
 * Optional CDN integration for static assets
 */

class CDNHelper {
    
    // Configure CDN URL (can be GitHub Pages, jsDelivr, or any CDN)
    private static $cdnUrl = '';
    private static $enabled = false;
    
    public static function init($cdnUrl = '', $enabled = false) {
        self::$cdnUrl = rtrim($cdnUrl, '/');
        self::$enabled = $enabled;
    }
    
    public static function asset($path) {
        if (!self::$enabled || empty(self::$cdnUrl)) {
            return $path;
        }
        
        // Only serve static assets from CDN
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2)$/i', $path)) {
            return self::$cdnUrl . '/' . ltrim($path, '/');
        }
        
        return $path;
    }
    
    public static function css($path) {
        $url = self::asset($path);
        $version = file_exists($path) ? filemtime($path) : time();
        return $url . '?v=' . $version;
    }
    
    public static function js($path) {
        $url = self::asset($path);
        $version = file_exists($path) ? filemtime($path) : time();
        return $url . '?v=' . $version;
    }
    
    // Preload critical resources
    public static function preload($resources) {
        foreach ($resources as $resource) {
            $url = self::asset($resource['url']);
            $as = $resource['as'] ?? 'style';
            echo "<link rel=\"preload\" href=\"{$url}\" as=\"{$as}\">\n";
        }
    }
}

// Example usage:
// CDNHelper::init('https://cdn.jsdelivr.net/gh/username/repo@main/public', false);
// echo CDNHelper::css('/assets/css/ergon-combined.min.css');
?>