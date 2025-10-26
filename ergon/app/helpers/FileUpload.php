<?php
class FileUpload {
    
    public static function upload($file, $destination = 'uploads/') {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            return false;
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            return false;
        }
        
        $filename = uniqid() . '.' . $extension;
        $uploadPath = __DIR__ . '/../../public/' . $destination . $filename;
        
        if (!is_dir(dirname($uploadPath))) {
            mkdir(dirname($uploadPath), 0755, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $destination . $filename;
        }
        
        return false;
    }
    
    public static function delete($filePath) {
        $fullPath = __DIR__ . '/../../public/' . $filePath;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }
    
    public static function validateImage($file) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        return in_array($file['type'], $allowedTypes);
    }
}
?>