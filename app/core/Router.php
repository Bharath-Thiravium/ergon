<?php
class Router {
    private $routes = [];
    
    public function get($path, $controller, $method) {
        $this->routes['GET'][$path] = ['controller' => $controller, 'method' => $method];
    }
    
    public function post($path, $controller, $method) {
        $this->routes['POST'][$path] = ['controller' => $controller, 'method' => $method];
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Log for debugging
        error_log("Router Debug - Original URI: " . $_SERVER['REQUEST_URI']);
        error_log("Router Debug - Method: " . $method);
        error_log("Router Debug - Parsed Path: " . $path);
        
        // Determine base path based on environment
        $isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'athenas.co.in') !== false;
        $isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
        
        if ($isLocalhost) {
            // For localhost, remove /ergon prefix
            $basePath = '/ergon';
            if (strpos($path, $basePath) === 0) {
                $path = substr($path, strlen($basePath));
            }
        } else {
            // For production, handle both base and public URLs
            $basePath = '/ergon';
            $publicBasePath = $basePath . '/public';
            
            if (strpos($path, $publicBasePath) === 0) {
                $path = substr($path, strlen($publicBasePath));
            } elseif (strpos($path, $basePath) === 0) {
                $path = substr($path, strlen($basePath));
            }
        }
        
        if (empty($path) || $path[0] !== '/') {
            $path = '/' . $path;
        }
        
        if (empty($path)) $path = '/';
        
        // Remove trailing slash for consistency (except for root)
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }
        
        error_log("Router Debug - Final Path: " . $path);
        
        // Check for exact match first
        if (isset($this->routes[$method][$path])) {
            error_log("Router Debug - Exact match found for: " . $path);
            $this->executeRoute($this->routes[$method][$path]);
            return;
        }
        
        // Check for pattern matches
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            if ($this->matchRoute($route, $path)) {
                error_log("Router Debug - Pattern match found: " . $route . " for path: " . $path);
                $this->executeRoute($handler, $this->extractParams($route, $path));
                return;
            }
        }
        
        error_log("Router Debug - No route found for: " . $path);
        $this->notFound();
    }
    
    private function matchRoute($route, $path) {
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
        return preg_match('#^' . $pattern . '$#', $path);
    }
    
    private function extractParams($route, $path) {
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
        preg_match('#^' . $pattern . '$#', $path, $matches);
        return array_slice($matches, 1);
    }
    
    private function executeRoute($route, $params = []) {
        $controllerName = $route['controller'];
        $method = $route['method'];
        
        $controllerFile = __DIR__ . "/../controllers/{$controllerName}.php";
        
        if (!file_exists($controllerFile)) {
            $this->notFound();
            return;
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controllerName)) {
            $this->notFound();
            return;
        }
        
        try {
            $controller = new $controllerName();
            
            if (!method_exists($controller, $method)) {
                $this->notFound();
                return;
            }
            
            call_user_func_array([$controller, $method], $params);
            
        } catch (Exception $e) {
            error_log("Controller Error: " . $e->getMessage());
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo "Internal Server Error";
        }
    }
    
    private function notFound() {
        if (!headers_sent()) {
            http_response_code(404);
        }
        if ($this->isApiRequest()) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode(['error' => 'Endpoint not found']);
        } else {
            echo "<!DOCTYPE html><html><head><title>404 - Page Not Found</title></head>";
            echo "<body><h1>404 - Page Not Found</h1>";
            echo "<p>The requested page could not be found.</p>";
            $isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'athenas.co.in') !== false;
            $basePath = '/ergon';
            echo "<a href='{$basePath}/login'>Return to Login</a></body></html>";
        }
    }
    
    private function isApiRequest() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return strpos($path, '/api/') !== false;
    }
}
?>
