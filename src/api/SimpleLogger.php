<?php

namespace Ergon\FinanceSync\Api;

class SimpleLogger
{
    public function emergency($message, array $context = []) { error_log("EMERGENCY: $message"); }
    public function alert($message, array $context = []) { error_log("ALERT: $message"); }
    public function critical($message, array $context = []) { error_log("CRITICAL: $message"); }
    public function error($message, array $context = []) { error_log("ERROR: $message"); }
    public function warning($message, array $context = []) { error_log("WARNING: $message"); }
    public function notice($message, array $context = []) { error_log("NOTICE: $message"); }
    public function info($message, array $context = []) { error_log("INFO: $message"); }
    public function debug($message, array $context = []) { error_log("DEBUG: $message"); }
    public function log($level, $message, array $context = []) { error_log("$level: $message"); }
}
