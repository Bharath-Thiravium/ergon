<?php
class Cache {
    private static $cache = [];
    private static $cacheDir = __DIR__ . '/../../storage/cache/';
    
    public static function get($key) {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        $file = self::$cacheDir . md5($key) . '.cache';
        if (file_exists($file) && (time() - filemtime($file)) < 300) { // 5 min cache
            $data = unserialize(file_get_contents($file));
            self::$cache[$key] = $data;
            return $data;
        }
        
        return null;
    }
    
    public static function set($key, $data) {
        self::$cache[$key] = $data;
        
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
        
        file_put_contents(self::$cacheDir . md5($key) . '.cache', serialize($data));
    }
    
    public static function clear($key = null) {
        if ($key) {
            unset(self::$cache[$key]);
            $file = self::$cacheDir . md5($key) . '.cache';
            if (file_exists($file)) unlink($file);
        } else {
            self::$cache = [];
            array_map('unlink', glob(self::$cacheDir . '*.cache'));
        }
    }
}