<?php
require_once __DIR__ . '/../core/Controller.php';

class TestController extends Controller {
    
    public function index() {
        $this->json(['message' => 'Test endpoint working', 'timestamp' => time()]);
    }
    
    public function status() {
        $this->json([
            'status' => 'OK',
            'version' => '2.0.0',
            'environment' => 'development'
        ]);
    }
}
?>
