<?php
/**
 * Merge Duplicate Folder Script
 * Combines the duplicated ergon folder inside the main ergon project
 */

class FolderMerger {
    private $sourceDir;
    private $targetDir;
    private $mergedFiles = 0;
    private $skippedFiles = 0;
    private $errors = [];
    
    public function __construct() {
        $this->sourceDir = __DIR__ . DIRECTORY_SEPARATOR . 'ergon';
        $this->targetDir = __DIR__;
    }
    
    public function merge() {
        echo "Starting folder merge process...\n";
        echo "Source: {$this->sourceDir}\n";
        echo "Target: {$this->targetDir}\n\n";
        
        if (!is_dir($this->sourceDir)) {
            echo "Error: Source directory does not exist.\n";
            return false;
        }
        
        $this->mergeDirectory($this->sourceDir, $this->targetDir);
        
        echo "\nMerge completed!\n";
        echo "Files merged: {$this->mergedFiles}\n";
        echo "Files skipped: {$this->skippedFiles}\n";
        
        if (!empty($this->errors)) {
            echo "Errors encountered:\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
        }
        
        // Remove the duplicate folder after successful merge
        if (empty($this->errors)) {
            echo "\nRemoving duplicate folder...\n";
            $this->removeDirectory($this->sourceDir);
            echo "Duplicate folder removed successfully.\n";
        }
        
        return empty($this->errors);
    }
    
    private function mergeDirectory($sourceDir, $targetDir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $sourcePath = $item->getPathname();
            $relativePath = substr($sourcePath, strlen($sourceDir) + 1);
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $relativePath;
            
            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    if (!mkdir($targetPath, 0755, true)) {
                        $this->errors[] = "Failed to create directory: $targetPath";
                        continue;
                    }
                }
            } else {
                $this->mergeFile($sourcePath, $targetPath);
            }
        }
    }
    
    private function mergeFile($sourcePath, $targetPath) {
        // Skip if target file exists and is identical
        if (file_exists($targetPath)) {
            if (filesize($sourcePath) === filesize($targetPath) && 
                md5_file($sourcePath) === md5_file($targetPath)) {
                $this->skippedFiles++;
                return;
            }
            
            // Check if source file is newer
            if (filemtime($sourcePath) <= filemtime($targetPath)) {
                $this->skippedFiles++;
                return;
            }
            
            // Backup existing file
            $backupPath = $targetPath . '.backup.' . date('Y-m-d-H-i-s');
            if (!copy($targetPath, $backupPath)) {
                $this->errors[] = "Failed to backup file: $targetPath";
                return;
            }
        }
        
        // Create target directory if it doesn't exist
        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                $this->errors[] = "Failed to create directory: $targetDir";
                return;
            }
        }
        
        // Copy the file
        if (copy($sourcePath, $targetPath)) {
            $this->mergedFiles++;
            echo "Merged: " . basename($sourcePath) . "\n";
        } else {
            $this->errors[] = "Failed to copy file: $sourcePath to $targetPath";
        }
    }
    
    private function removeDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        
        rmdir($dir);
    }
}

// Run the merger
$merger = new FolderMerger();
$success = $merger->merge();

if ($success) {
    echo "\n✓ Folder merge completed successfully!\n";
    echo "The duplicate 'ergon' folder has been merged and removed.\n";
} else {
    echo "\n✗ Folder merge completed with errors.\n";
    echo "Please review the errors above and fix them manually.\n";
}
?>