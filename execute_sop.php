<?php
/**
 * ERGON SOP Execution Script
 * Quick execution of standard operating procedures
 */

echo "ERGON SOP EXECUTION SCRIPT\n";
echo str_repeat("=", 30) . "\n\n";

$action = $argv[1] ?? 'help';

switch ($action) {
    case 'health':
        echo "Running system health check...\n";
        include 'comprehensive_evaluation.php';
        break;
        
    case 'fix':
        echo "Applying all fixes...\n";
        include 'fix_all_issues.php';
        break;
        
    case 'test':
        echo "Running test suite...\n";
        include 'test_fixes.php';
        break;
        
    case 'monitor':
        echo "Monitoring error logs (Ctrl+C to stop)...\n";
        $logFile = __DIR__ . '/storage/logs/error.log';
        if (file_exists($logFile)) {
            $handle = fopen($logFile, 'r');
            fseek($handle, -1024, SEEK_END);
            while (true) {
                $line = fgets($handle);
                if ($line) {
                    echo $line;
                }
                usleep(100000); // 0.1 second
            }
        } else {
            echo "Error log file not found.\n";
        }
        break;
        
    case 'emergency':
        echo "EMERGENCY PROTOCOL ACTIVATED\n";
        echo "1. Creating backup...\n";
        $backupFile = "emergency_backup_" . date('Y-m-d_H-i-s') . ".sql";
        exec("mysqldump ergon_db > $backupFile 2>/dev/null", $output, $return);
        if ($return === 0) {
            echo "   ✅ Backup created: $backupFile\n";
        } else {
            echo "   ⚠️  Backup failed, proceeding anyway...\n";
        }
        
        echo "2. Running emergency fixes...\n";
        include 'fix_all_issues.php';
        
        echo "3. Validating system...\n";
        include 'comprehensive_evaluation.php';
        break;
        
    case 'help':
    default:
        echo "ERGON SOP Commands:\n\n";
        echo "php execute_sop.php health     - Run system health check\n";
        echo "php execute_sop.php fix       - Apply all fixes\n";
        echo "php execute_sop.php test      - Run test suite\n";
        echo "php execute_sop.php monitor   - Monitor error logs\n";
        echo "php execute_sop.php emergency - Emergency protocol\n";
        echo "php execute_sop.php help      - Show this help\n\n";
        
        echo "Quick Status Check:\n";
        try {
            require_once __DIR__ . '/app/config/database.php';
            Database::connect();
            echo "✅ Database: Connected\n";
        } catch (Exception $e) {
            echo "❌ Database: Failed\n";
        }
        
        $criticalFiles = [
            'app/controllers/UsersController.php',
            'app/controllers/TasksController.php',
            'app/controllers/SettingsController.php'
        ];
        
        foreach ($criticalFiles as $file) {
            if (file_exists(__DIR__ . '/' . $file)) {
                echo "✅ $file: Exists\n";
            } else {
                echo "❌ $file: Missing\n";
            }
        }
        break;
}
?>