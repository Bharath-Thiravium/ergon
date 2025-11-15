<?php
/**
 * Conservative Cleanup - Only moves files that are clearly safe to archive
 */

class ConservativeCleanup {
    private $projectRoot;
    private $archiveDir;
    private $safeToMovePatterns = [
        // Git files (version control artifacts)
        '/^\.git\//',
        
        // Test and debug files
        '/test.*\.php$/',
        '/debug.*\.php$/',
        '/_test\./',
        '/\.test\./',
        
        // Temporary/backup files
        '/\.bak$/',
        '/\.backup$/',
        '/\.tmp$/',
        '/~$/',
        
        // Documentation that's clearly outdated
        '/README.*\.md$/',
        '/COMPLETE.*\.md$/',
        '/FIX.*\.md$/',
        '/OPTIMIZATION.*\.md$/',
        '/RESTORATION.*\.md$/',
        '/CLEANUP.*\.md$/',
        '/MIGRATION.*\.md$/',
        '/AUDIT.*\.md$/',
        
        // Batch files for development
        '/\.bat$/',
        
        // HTML test files
        '/test.*\.html$/',
        '/diagnostic.*\.html$/',
        '/tooltip.*\.html$/',
        
        // SQL files (database scripts)
        '/\.sql$/',
        
        // Specific legacy files
        '/merge_duplicate_folder\.php$/',
        '/optimized-template\.html$/',
        '/run_debug\.bat$/',
        '/run_audit\.bat$/',
        '/audit_unused_files\.php$/',
    ];
    
    private $protectedFiles = [
        // Core application files
        'index.php',
        '.htaccess',
        '.env',
        '.env.example',
        'composer.json',
        'favicon.ico',
        
        // Active CSS/JS that might be dynamically loaded
        'ergon.css',
        'components.css',
        'critical.css',
        'utilities.css',
        'task-components.css',
        'action-button-clean.css',
        
        // Core application structure
        '/app/',
        '/views/',
        '/public/',
        '/assets/',
        '/api/',
        '/cron/',
        '/storage/',
        '/database/',
    ];
    
    public function __construct($projectRoot) {
        $this->projectRoot = rtrim($projectRoot, '/\\');
        $this->archiveDir = $this->projectRoot . DIRECTORY_SEPARATOR . '_archive_unused_files';
    }
    
    public function performConservativeCleanup($dryRun = true) {
        echo "🧹 Starting Conservative Cleanup...\n";
        echo "Project Root: {$this->projectRoot}\n";
        echo "Mode: " . ($dryRun ? "DRY RUN" : "LIVE CLEANUP") . "\n\n";
        
        $filesToMove = $this->identifySafeFiles();
        
        echo "📊 Analysis Results:\n";
        echo "- Files identified as safe to move: " . count($filesToMove) . "\n";
        
        $totalSize = 0;
        foreach ($filesToMove as $file) {
            $totalSize += filesize($file);
        }
        echo "- Total size to be archived: " . $this->formatBytes($totalSize) . "\n\n";
        
        if (!$dryRun && !empty($filesToMove)) {
            $this->moveFiles($filesToMove);
        } else {
            $this->showFileList($filesToMove);
        }
        
        return $filesToMove;
    }
    
    private function identifySafeFiles() {
        $allFiles = $this->getFilesRecursive($this->projectRoot);
        $safeFiles = [];
        
        foreach ($allFiles as $file) {
            if ($this->isSafeToMove($file)) {
                $safeFiles[] = $file;
            }
        }
        
        return $safeFiles;
    }
    
    private function isSafeToMove($filePath) {
        $relativePath = str_replace($this->projectRoot, '', $filePath);
        $relativePath = str_replace('\\', '/', $relativePath);
        $fileName = basename($filePath);
        
        // Check if file matches safe-to-move patterns
        foreach ($this->safeToMovePatterns as $pattern) {
            if (preg_match($pattern, $relativePath) || preg_match($pattern, $fileName)) {
                // Double-check it's not a protected file
                if (!$this->isProtectedFile($filePath)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    private function isProtectedFile($filePath) {
        $relativePath = str_replace($this->projectRoot, '', $filePath);
        $relativePath = str_replace('\\', '/', $relativePath);
        $fileName = basename($filePath);
        
        // Check protected files list
        if (in_array($fileName, $this->protectedFiles)) {
            return true;
        }
        
        // Check protected directories
        foreach ($this->protectedFiles as $protected) {
            if (strpos($protected, '/') !== false && strpos($relativePath, $protected) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    private function getFilesRecursive($dir) {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    private function showFileList($files) {
        echo "📋 Files to be moved:\n";
        foreach ($files as $file) {
            $relativePath = str_replace($this->projectRoot, '', $file);
            $size = $this->formatBytes(filesize($file));
            echo "  - {$relativePath} ({$size})\n";
        }
        echo "\n";
    }
    
    private function moveFiles($files) {
        if (!is_dir($this->archiveDir)) {
            mkdir($this->archiveDir, 0755, true);
        }
        
        $moved = 0;
        $log = [];
        
        foreach ($files as $file) {
            $relativePath = str_replace($this->projectRoot, '', $file);
            $archivePath = $this->archiveDir . $relativePath;
            
            // Create directory structure
            $archiveDir = dirname($archivePath);
            if (!is_dir($archiveDir)) {
                mkdir($archiveDir, 0755, true);
            }
            
            // Move file
            if (rename($file, $archivePath)) {
                $moved++;
                $log[] = "MOVED: {$relativePath} -> _archive_unused_files{$relativePath}";
                echo "✅ Moved: {$relativePath}\n";
            } else {
                $log[] = "FAILED: Could not move {$relativePath}";
                echo "❌ Failed: {$relativePath}\n";
            }
        }
        
        // Save log
        file_put_contents($this->archiveDir . DIRECTORY_SEPARATOR . 'CONSERVATIVE_CLEANUP_LOG.txt', implode("\n", $log));
        
        echo "\n🎉 Conservative cleanup complete!\n";
        echo "- Files moved: $moved\n";
        echo "- Archive location: {$this->archiveDir}\n";
        echo "- Log saved to: CONSERVATIVE_CLEANUP_LOG.txt\n";
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Usage
if (php_sapi_name() === 'cli') {
    $projectRoot = $argv[1] ?? 'C:\laragon\www\ergon';
    $dryRun = !isset($argv[2]) || $argv[2] !== '--live';
    
    $cleanup = new ConservativeCleanup($projectRoot);
    $cleanup->performConservativeCleanup($dryRun);
    
    if ($dryRun) {
        echo "\n✅ Conservative dry run complete. Run with --live flag to actually move files.\n";
        echo "Example: php conservative_cleanup.php \"C:\\laragon\\www\\ergon\" --live\n";
    }
}
?>