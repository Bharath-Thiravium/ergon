<?php

class TestController extends Controller {
    
    public function index() {
        $this->view('test/index');
    }
    
    public function database() {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT 1 as test");
            $result = $stmt->fetch();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Database connection successful',
                'data' => $result
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]);
        }
    }
}