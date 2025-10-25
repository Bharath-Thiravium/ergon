<?php
/**
 * ERGON Performance Booster
 * Compatible with free PHP hosting (no root access required)
 */

class PerformanceBooster {
    
    public static function init() {
        // Enable output buffering with compression
        if (!ob_get_level()) {
            ob_start('ob_gzhandler');
        }
        
        // Set performance headers
        self::setPerformanceHeaders();
        
        // Initialize page caching
        self::initPageCache();
    }
    
    private static function setPerformanceHeaders() {
        // Cache static assets
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg)$/i', $_SERVER['REQUEST_URI'])) {
            header('Cache-Control: public, max-age=2592000'); // 30 days
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
        } else {
            // Dynamic pages - short cache
            header('Cache-Control: public, max-age=300'); // 5 minutes
        }
        
        // Performance headers
        header('X-Content-Type-Options: nosniff');
        header('Connection: keep-alive');
    }
    
    private static function initPageCache() {
        // Skip caching for POST requests and admin pages
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' || 
            strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ||
            isset($_SESSION['user_id'])) {
            return;
        }
        
        // Skip caching for sensitive GET parameters
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        if (preg_match('/token|auth|session|csrf|password|reset/i', $queryString)) {
            return;
        }
        
        $cacheDir = __DIR__ . '/../../storage/cache/pages/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $cacheFile = $cacheDir . md5($_SERVER['REQUEST_URI']) . '.html';
        $cacheTime = 300; // 5 minutes
        
        // Dynamic cache expiry - clear if source files updated
        if (file_exists($cacheFile) && filemtime(__FILE__) > filemtime($cacheFile)) {
            unlink($cacheFile);
        }
        
        // Serve cached version if available and fresh
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
            readfile($cacheFile);
            exit;
        }
        
        // Register shutdown function to save cache
        register_shutdown_function(function() use ($cacheFile) {
            $output = ob_get_contents();
            if ($output && strlen($output) > 100) { // Only cache substantial content
                file_put_contents($cacheFile, $output);
            }
        });
    }
    
    public static function minifyCSS($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        return $css;
    }
    
    public static function minifyJS($js) {
        // Basic JS minification (remove comments and extra whitespace)
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js); // Remove /* */ comments
        $js = preg_replace('/\/\/.*$/m', '', $js); // Remove // comments
        $js = preg_replace('/\s+/', ' ', $js); // Compress whitespace
        return trim($js);
    }
    
    public static function optimizeDatabase() {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Add common indexes if they don't exist
            $indexes = [
                'users' => ['email', 'role', 'status'],
                'tasks' => ['assigned_to', 'status', 'deadline'],
                'attendance' => ['user_id', 'check_in_time'],
                'leaves' => ['employee_id', 'status'],
                'expenses' => ['user_id', 'status']
            ];
            
            foreach ($indexes as $table => $columns) {
                foreach ($columns as $column) {
                    $indexName = "idx_{$table}_{$column}";
                    $sql = "CREATE INDEX IF NOT EXISTS {$indexName} ON {$table}({$column})";
                    try {
                        $db->exec($sql);
                    } catch (Exception $e) {
                        // Index might already exist, continue
                    }
                }
            }
            
            // Optimize tables for better performance
            $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                try {
                    $db->exec("ANALYZE TABLE `{$table}`");
                    $db->exec("OPTIMIZE TABLE `{$table}`");
                } catch (Exception $e) {
                    // Some shared hosts may not allow OPTIMIZE, continue
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Database optimization error: ' . $e->getMessage());
            return false;
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
    
    public static function getPerformanceStats() {
        $stats = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'cache_files' => 0
        ];
        
        $cacheDir = __DIR__ . '/../../storage/cache/pages/';
        if (is_dir($cacheDir)) {
            $stats['cache_files'] = count(glob($cacheDir . '*.html'));
        }
        
        return $stats;
    }
}
?>