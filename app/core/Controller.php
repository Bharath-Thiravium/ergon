<?php
class Controller {
    
    protected function view($view, $data = []) {
        extract($data);
        $viewFile = __DIR__ . "/../../views/{$view}.php";
        
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
        if (!str_starts_with($url, 'http') && !str_starts_with($url, '/Ergon/')) {
            $url = '/Ergon/public' . $url;
        }
        header("Location: {$url}");
        exit;
    }
    
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    protected function requireAuth() {
        session_start();
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
}
?>
