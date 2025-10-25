<?php
class PerformanceBooster {
    public static function init() {
        // Minimal performance optimization
        if (extension_loaded('zlib') && !ob_get_level()) {
            ob_start('ob_gzhandler');
        }
    }
}
?>