<?php
class Controller {
    
    protected function view($view, $data = []) {
        try {
            extract($data);
            $viewFile = __DIR__ . "/../../views/{$view}.php";
            
            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                error_log("View not found: {$viewFile}");
                echo "<h1>View Error</h1><p>View file not found: {$view}</p>";
            }
        } catch (Exception $e) {
            error_log("View error: " . $e->getMessage());
            echo "<h1>View Error</h1><p>" . $e->getMessage() . "</p>";
        }
    }
    
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function redirect($url) {
        if (strpos($url, 'http') !== 0 && strpos($url, '/ergon/') !== 0) {
            $url = '/ergon' . $url;
        }
        header("Location: {$url}");
        exit;
    }
    
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    protected function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }
    
    protected function requireRole($role) {
        $this->requireAuth();
        if ($_SESSION['role'] !== $role) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
    }
    
    protected function isAjaxRequest() {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
               (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
}
?>
