<?php
/**
 * Base Controller Class
 * All controllers extend this class
 */

class Controller {
    
    protected function view($view, $data = []) {
        extract($data);
        $viewFile = __DIR__ . "/../views/{$view}.php";
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            throw new Exception("View {$view} not found");
        }
    }
    
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function redirect($url) {
        // Ensure proper URL format
        if (!str_starts_with($url, 'http') && !str_starts_with($url, '/ergon/')) {
            $url = '/ergon' . $url;
        }
        header("Location: {$url}");
        exit;
    }
    
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    protected function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
?>