<?php
/**
 * Deep Folder Audit & Safe Cleanup Tool
 * Identifies unused, unlinked, and orphaned files for safe archival
 */

class ProjectAuditor {
    private $projectRoot;
    private $archiveDir;
    private $usedFiles = [];
    private $allFiles = [];
    private $auditLog = [];
    private $protectedPatterns = [
        // Core system files
        '/\.htaccess$/',
        '/\.env/',
        '/composer\.json$/',
        '/index\.php$/',
        '/favicon\.ico$/',
        
        // Build and config files
        '/\.gitignore$/',
        '/\.gitkeep$/',
        
        // Session files (temporary)
        '/storage\/sessions\/sess_/',
        
        // Database files
        '/\.sql$/',
        
        // Documentation
        '/README\.md$/',
        '/\.md$/',
        
        // Vendor and dependencies
        '/vendor\//',
        '/node_modules\//',
        
        // Upload directories
        '/uploads\//',
        '/storage\//',
        '/cache\//',
        '/logs\//'
    ];
    
    public function __construct($projectRoot) {
        $this->projectRoot = rtrim($projectRoot, '/\\');
        $this->archiveDir = $this->projectRoot . DIRECTORY_SEPARATOR . '_archive_unused_files';
    }
    
    public function audit($dryRun = true) {
        echo "🔍 Starting Deep Folder Audit...\n";
        echo "Project Root: {$this->projectRoot}\n";
        echo "Mode: " . ($dryRun ? "DRY RUN" : "LIVE CLEANUP") . "\n\n";
        
        // Step 1: Scan all files
        $this->scanAllFiles();
        
        // Step 2: Analyze file usage
        $this->analyzeFileUsage();
        
        // Step 3: Identify unused files
        $unusedFiles = $this->identifyUnusedFiles();
        
        // Step 4: Generate report
        $this->generateReport($unusedFiles);
        
        // Step 5: Move files (if not dry run)
        if (!$dryRun && !empty($unusedFiles)) {
            $this->moveUnusedFiles($unusedFiles);
        }
        
        return $unusedFiles;
    }
    
    private function scanAllFiles() {
        echo "📁 Scanning all files...\n";
        $this->allFiles = $this->getFilesRecursive($this->projectRoot);
        echo "Found " . count($this->allFiles) . " files\n\n";
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
    
    private function analyzeFileUsage() {
        echo "🔗 Analyzing file usage patterns...\n";
        
        foreach ($this->allFiles as $file) {
            $this->analyzeFile($file);
        }
        
        echo "Analyzed " . count($this->usedFiles) . " file references\n\n";
    }
    
    private function analyzeFile($filePath) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $content = @file_get_contents($filePath);
        
        if ($content === false) return;
        
        // Analyze based on file type
        switch ($extension) {
            case 'php':
                $this->analyzePhpFile($content, $filePath);
                break;
            case 'js':
                $this->analyzeJsFile($content, $filePath);
                break;
            case 'css':
                $this->analyzeCssFile($content, $filePath);
                break;
            case 'html':
                $this->analyzeHtmlFile($content, $filePath);
                break;
        }
    }
    
    private function analyzePhpFile($content, $filePath) {
        // Include/require statements
        preg_match_all('/(?:include|require)(?:_once)?\s*\(?[\'"]([^\'"]+)[\'"]/', $content, $matches);
        foreach ($matches[1] as $includePath) {
            $this->markFileAsUsed($this->resolvePath($includePath, $filePath));
        }
        
        // View includes
        preg_match_all('/@include\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
        foreach ($matches[1] as $viewPath) {
            $this->markFileAsUsed($this->resolveViewPath($viewPath));
        }
        
        // File references
        preg_match_all('/[\'"]([^\'"\s]+\.(css|js|png|jpg|jpeg|gif|svg|ico))[\'"]/', $content, $matches);
        foreach ($matches[1] as $assetPath) {
            $this->markFileAsUsed($this->resolvePath($assetPath, $filePath));
        }
    }
    
    private function analyzeJsFile($content, $filePath) {
        // Import statements
        preg_match_all('/import\s+.*?from\s+[\'"]([^\'"]+)[\'"]/', $content, $matches);
        foreach ($matches[1] as $importPath) {
            $this->markFileAsUsed($this->resolvePath($importPath, $filePath));
        }
        
        // Require statements
        preg_match_all('/require\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
        foreach ($matches[1] as $requirePath) {
            $this->markFileAsUsed($this->resolvePath($requirePath, $filePath));
        }
    }
    
    private function analyzeCssFile($content, $filePath) {
        // @import statements
        preg_match_all('/@import\s+[\'"]([^\'"]+)[\'"]/', $content, $matches);
        foreach ($matches[1] as $importPath) {
            $this->markFileAsUsed($this->resolvePath($importPath, $filePath));
        }
        
        // URL references
        preg_match_all('/url\s*\(\s*[\'"]?([^\'")\s]+)[\'"]?\s*\)/', $content, $matches);
        foreach ($matches[1] as $urlPath) {
            $this->markFileAsUsed($this->resolvePath($urlPath, $filePath));
        }
    }
    
    private function analyzeHtmlFile($content, $filePath) {
        // Link tags (CSS)
        preg_match_all('/<link[^>]+href=[\'"]([^\'"]+)[\'"]/', $content, $matches);
        foreach ($matches[1] as $href) {
            $this->markFileAsUsed($this->resolvePath($href, $filePath));
        }
        
        // Script tags
        preg_match_all('/<script[^>]+src=[\'"]([^\'"]+)[\'"]/', $content, $matches);
        foreach ($matches[1] as $src) {
            $this->markFileAsUsed($this->resolvePath($src, $filePath));
        }
        
        // Image tags
        preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/', $content, $matches);
        foreach ($matches[1] as $src) {
            $this->markFileAsUsed($this->resolvePath($src, $filePath));
        }
    }
    
    private function resolvePath($path, $basePath) {
        // Skip external URLs
        if (preg_match('/^https?:\/\//', $path)) {
            return null;
        }
        
        // Handle absolute paths from project root
        if (strpos($path, '/') === 0) {
            return $this->projectRoot . str_replace('/', DIRECTORY_SEPARATOR, $path);
        }
        
        // Handle relative paths
        $baseDir = dirname($basePath);
        return realpath($baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    }
    
    private function resolveViewPath($viewPath) {
        // Convert view path to file path (Laravel-style)
        $filePath = str_replace('.', DIRECTORY_SEPARATOR, $viewPath) . '.php';
        return $this->projectRoot . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $filePath;
    }
    
    private function markFileAsUsed($filePath) {
        if ($filePath && file_exists($filePath)) {
            $this->usedFiles[realpath($filePath)] = true;
        }
    }
    
    private function identifyUnusedFiles() {
        echo "🗑️ Identifying unused files...\n";
        
        $unusedFiles = [];
        
        foreach ($this->allFiles as $file) {
            $realPath = realpath($file);
            
            // Skip if file is marked as used
            if (isset($this->usedFiles[$realPath])) {
                continue;
            }
            
            // Skip protected files
            if ($this->isProtectedFile($file)) {
                continue;
            }
            
            // Determine reason for being unused
            $reason = $this->determineUnusedReason($file);
            
            $unusedFiles[] = [
                'path' => $file,
                'reason' => $reason,
                'size' => filesize($file),
                'modified' => filemtime($file)
            ];
        }
        
        echo "Found " . count($unusedFiles) . " unused files\n\n";
        return $unusedFiles;
    }
    
    private function isProtectedFile($filePath) {
        $relativePath = str_replace($this->projectRoot, '', $filePath);
        $relativePath = str_replace('\\', '/', $relativePath);
        
        foreach ($this->protectedPatterns as $pattern) {
            if (preg_match($pattern, $relativePath)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function determineUnusedReason($filePath) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $fileName = basename($filePath);
        
        // Check for legacy indicators
        if (preg_match('/(old|backup|copy|temp|test|debug|sample)/', $fileName)) {
            return 'legacy';
        }
        
        // Check for orphaned components
        if (in_array($extension, ['php', 'js', 'vue', 'ts'])) {
            return 'orphaned';
        }
        
        // Check for unused assets
        if (in_array($extension, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico'])) {
            return 'unused';
        }
        
        return 'unlinked';
    }
    
    private function generateReport($unusedFiles) {
        echo "📊 Generating audit report...\n";
        
        $report = "# Deep Folder Audit Report\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $totalSize = 0;
        $reasonCounts = [];
        
        foreach ($unusedFiles as $file) {
            $totalSize += $file['size'];
            $reasonCounts[$file['reason']] = ($reasonCounts[$file['reason']] ?? 0) + 1;
        }
        
        $report .= "## Summary\n";
        $report .= "- Total unused files: " . count($unusedFiles) . "\n";
        $report .= "- Total size: " . $this->formatBytes($totalSize) . "\n\n";
        
        $report .= "## By Category\n";
        foreach ($reasonCounts as $reason => $count) {
            $report .= "- " . ucfirst($reason) . ": $count files\n";
        }
        $report .= "\n";
        
        $report .= "## Detailed List\n";
        foreach ($unusedFiles as $file) {
            $relativePath = str_replace($this->projectRoot, '', $file['path']);
            $report .= "- `{$relativePath}` ({$file['reason']}) - " . $this->formatBytes($file['size']) . "\n";
        }
        
        file_put_contents($this->projectRoot . DIRECTORY_SEPARATOR . 'AUDIT_REPORT.md', $report);
        echo "Report saved to AUDIT_REPORT.md\n\n";
    }
    
    private function moveUnusedFiles($unusedFiles) {
        echo "📦 Moving unused files to archive...\n";
        
        if (!is_dir($this->archiveDir)) {
            mkdir($this->archiveDir, 0755, true);
        }
        
        $moved = 0;
        foreach ($unusedFiles as $file) {
            $relativePath = str_replace($this->projectRoot, '', $file['path']);
            $archivePath = $this->archiveDir . $relativePath;
            
            // Create directory structure
            $archiveDir = dirname($archivePath);
            if (!is_dir($archiveDir)) {
                mkdir($archiveDir, 0755, true);
            }
            
            // Move file
            if (rename($file['path'], $archivePath)) {
                $moved++;
                $this->auditLog[] = "MOVED: {$relativePath} -> _archive_unused_files{$relativePath} ({$file['reason']})";
            } else {
                $this->auditLog[] = "FAILED: Could not move {$relativePath}";
            }
        }
        
        // Save audit log
        file_put_contents($this->archiveDir . DIRECTORY_SEPARATOR . 'MOVE_LOG.txt', implode("\n", $this->auditLog));
        
        echo "Moved $moved files to archive\n";
        echo "Archive location: {$this->archiveDir}\n";
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
    
    $auditor = new ProjectAuditor($projectRoot);
    $unusedFiles = $auditor->audit($dryRun);
    
    if ($dryRun) {
        echo "\n✅ Dry run complete. Run with --live flag to actually move files.\n";
        echo "Example: php audit_unused_files.php \"C:\\laragon\\www\\ergon\" --live\n";
    }
}
?>