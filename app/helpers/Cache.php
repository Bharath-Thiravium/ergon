<?php
class Cache {
    private static $cacheDir = null;
    
    private static function getCacheDir() {
        if (self::$cacheDir === null) {
            self::$cacheDir = __DIR__ . '/../../storage/cache/';
            if (!is_dir(self::$cacheDir)) {
                mkdir(self::$cacheDir, 0755, true);
            }
        }
        return self::$cacheDir;
    }
    
    public static function get($key) {
        $file = self::getCacheDir() . md5($key) . '.cache';
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = file_get_contents($file);
        $data = unserialize($data);
        
        if ($data['expires'] < time()) {
            self::delete($key);
            return null;
        }
        
        return $data['value'];
    }
    
    public static function set($key, $value, $ttl = 3600) {
        $file = self::getCacheDir() . md5($key) . '.cache';
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }
    
    public static function delete($key) {
        $file = self::getCacheDir() . md5($key) . '.cache';
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }
    
    public static function clear() {
        $files = glob(self::getCacheDir() . '*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    public static function remember($key, $callback, $ttl = 3600) {
        $value = self::get($key);
        
        if ($value === null) {
            $value = $callback();
            self::set($key, $value, $ttl);
        }
        
        return $value;
    }
    
    public static function exists($key) {
        return self::get($key) !== null;
    }
    
    public static function increment($key, $value = 1) {
        $current = self::get($key) ?? 0;
        $new = $current + $value;
        self::set($key, $new);
        return $new;
    }
    
    public static function decrement($key, $value = 1) {
        return self::increment($key, -$value);
    }
}
?>
