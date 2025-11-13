<?php
/**
 * Security cleanup task to remove old logs and unlock expired accounts
 * Run this script via cron job: 0 */6 * * * php /path/to/ergon/app/tasks/SecurityCleanup.php
 */

require_once __DIR__ . '/../services/SecurityService.php';
require_once __DIR__ . '/../config/database.php';

class SecurityCleanup {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::connect();
    }
    
    public function run() {
        echo "Starting security cleanup...\n";
        
        $this->unlockExpiredAccounts();
        $this->cleanupOldLogs();
        $this->cleanupExpiredTokens();
        
        echo "Security cleanup completed.\n";
    }
    
    private function unlockExpiredAccounts() {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET locked_until = NULL, failed_attempts = 0 
                WHERE locked_until IS NOT NULL AND locked_until < NOW()
            ");
            $stmt->execute();
            $count = $stmt->rowCount();
            echo "Unlocked {$count} expired accounts.\n";
        } catch (Exception $e) {
            echo "Error unlocking accounts: " . $e->getMessage() . "\n";
        }
    }
    
    private function cleanupOldLogs() {
        try {
            $securityService = new SecurityService();
            $securityService->cleanupOldLogs();
            echo "Cleaned up old security logs.\n";
        } catch (Exception $e) {
            echo "Error cleaning up logs: " . $e->getMessage() . "\n";
        }
    }
    
    private function cleanupExpiredTokens() {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET reset_token = NULL, reset_token_expires = NULL 
                WHERE reset_token_expires IS NOT NULL AND reset_token_expires < NOW()
            ");
            $stmt->execute();
            $count = $stmt->rowCount();
            echo "Cleaned up {$count} expired reset tokens.\n";
        } catch (Exception $e) {
            echo "Error cleaning up tokens: " . $e->getMessage() . "\n";
        }
    }
}

// Run the cleanup if called directly
if (php_sapi_name() === 'cli') {
    $cleanup = new SecurityCleanup();
    $cleanup->run();
}
?>