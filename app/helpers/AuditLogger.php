<?php
class AuditLogger {
    public static function logLogin($userId, $success) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'action' => $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        self::writeLog($logData);
    }
    
    public static function logLogout($userId) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'action' => 'LOGOUT',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        self::writeLog($logData);
    }
    
    private static function writeLog($data) {
        $logFile = __DIR__ . '/../../storage/logs/audit.log';
        $dir = dirname($logFile);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $logEntry = json_encode($data) . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>