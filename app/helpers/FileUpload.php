<?php
/**
 * Secure File Upload Handler
 * Provides comprehensive file upload security
 */

class FileUpload {
    
    private $allowedTypes = [
        'receipts' => ['jpg', 'jpeg', 'png', 'pdf'],
        'documents' => ['pdf', 'doc', 'docx'],
        'images' => ['jpg', 'jpeg', 'png', 'gif']
    ];
    
    private $maxSizes = [
        'receipts' => 5 * 1024 * 1024,  // 5MB
        'documents' => 10 * 1024 * 1024, // 10MB
        'images' => 2 * 1024 * 1024      // 2MB
    ];
    
    private $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    /**
     * Upload file securely
     */
    public function uploadFile($file, $type = 'receipts') {
        // Validate file exists and no upload errors
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error: ' . $this->getUploadErrorMessage($file['error']));
        }
        
        // Check file size
        if ($file['size'] > $this->maxSizes[$type]) {
            throw new Exception('File too large. Maximum size: ' . $this->formatBytes($this->maxSizes[$type]));
        }
        
        // Validate file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedTypes[$type])) {
            throw new Exception('Invalid file type. Allowed: ' . implode(', ', $this->allowedTypes[$type]));
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!isset($this->allowedMimes[$ext]) || $mimeType !== $this->allowedMimes[$ext]) {
            throw new Exception('MIME type mismatch. File may be corrupted or malicious.');
        }
        
        // Generate secure filename
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $uploadPath = $this->getUploadPath($type, $filename);
        
        // Ensure directory exists
        $dir = dirname($uploadPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Move file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        // Set secure permissions
        chmod($uploadPath, 0644);
        
        return $filename;
    }
    
    /**
     * Get upload path
     */
    private function getUploadPath($type, $filename) {
        $basePath = __DIR__ . '/../../storage/' . $type . '/';
        
        // Create date-based subdirectory
        $dateDir = date('Y/m/');
        $fullDir = $basePath . $dateDir;
        
        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }
        
        return $fullDir . $filename;
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($error) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        return $messages[$error] ?? 'Unknown upload error';
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Delete uploaded file
     */
    public function deleteFile($filename, $type = 'receipts') {
        $pattern = __DIR__ . '/../../storage/' . $type . '/**/' . $filename;
        $files = glob($pattern, GLOB_BRACE);
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
                return true;
            }
        }
        
        return false;
    }
}
?>