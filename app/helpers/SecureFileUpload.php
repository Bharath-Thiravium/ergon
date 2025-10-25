<?php
/**
 * Secure File Upload Handler
 */

class SecureFileUpload {
    private $allowedTypes = [
        'receipts' => [
            'mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
            'extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
            'maxSize' => 5 * 1024 * 1024 // 5MB
        ],
        'documents' => [
            'mimes' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'extensions' => ['pdf', 'doc', 'docx'],
            'maxSize' => 10 * 1024 * 1024 // 10MB
        ]
    ];
    
    private $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js', 'jar'];
    
    public function upload($file, $type = 'receipts') {
        if (!isset($this->allowedTypes[$type])) {
            throw new Exception('Invalid upload type');
        }
        
        $config = $this->allowedTypes[$type];
        
        // Basic validation
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error: ' . $file['error']);
        }
        
        // Size check
        if ($file['size'] > $config['maxSize']) {
            throw new Exception('File too large. Max size: ' . ($config['maxSize'] / 1024 / 1024) . 'MB');
        }
        
        // Extension check
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $config['extensions']) || in_array($ext, $this->dangerousExtensions)) {
            throw new Exception('Invalid file type');
        }
        
        // MIME type check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $config['mimes'])) {
            throw new Exception('MIME type mismatch. Detected: ' . $mimeType);
        }
        
        // Additional security checks
        $this->scanForMalware($file['tmp_name']);
        
        // Generate secure filename
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        
        // Store outside webroot
        $uploadDir = __DIR__ . '/../../storage/secure_uploads/' . $type . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadPath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        // Set restrictive permissions
        chmod($uploadPath, 0644);
        
        return $filename;
    }
    
    private function scanForMalware($filePath) {
        // Basic malware signature detection
        $content = file_get_contents($filePath, false, null, 0, 1024); // First 1KB
        
        $malwareSignatures = [
            '<?php', '<%', '<script', 'eval(', 'base64_decode', 'shell_exec', 'system(', 'exec('
        ];
        
        foreach ($malwareSignatures as $signature) {
            if (stripos($content, $signature) !== false) {
                throw new Exception('Potentially malicious file detected');
            }
        }
    }
    
    public function getSecureUrl($filename, $type) {
        // Return URL that goes through a secure download script
        return "/ergon/secure-download.php?file=" . urlencode($filename) . "&type=" . urlencode($type);
    }
}
?>