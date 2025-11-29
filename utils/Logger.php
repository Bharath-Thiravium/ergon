<?php

class Logger {
    public static function info($message, $context = []) {
        error_log("INFO: $message " . json_encode($context));
    }
    
    public static function error($message, $context = []) {
        error_log("ERROR: $message " . json_encode($context));
    }
    
    public static function warning($message, $context = []) {
        error_log("WARNING: $message " . json_encode($context));
    }
}