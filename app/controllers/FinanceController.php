<?php

require_once __DIR__ . '/../services/DataSyncService.php';

class FinanceController {
    
    public function dashboard($request) {
        // Render finance dashboard view
        $title = 'Finance Dashboard';
        $active_page = 'finance';
        
        require_once __DIR__ . '/../../views/finance/dashboard.php';
    }
    
    public function syncData($request) {
        try {
            $syncService = new DataSyncService();
            $results = $syncService->syncAllTables();
            
            $totalRecords = 0;
            $errors = [];
            
            foreach ($results as $result) {
                $totalRecords += $result['records'];
                if ($result['status'] === 'error') {
                    $errors[] = $result['table'] . ': ' . $result['error'];
                }
            }
            
            if (empty($errors)) {
                return $this->jsonResponse(200, [
                    'success' => true,
                    'message' => 'Data sync completed successfully',
                    'records_synced' => $totalRecords,
                    'tables' => $results
                ]);
            } else {
                return $this->jsonResponse(207, [
                    'success' => false,
                    'message' => 'Data sync completed with errors',
                    'records_synced' => $totalRecords,
                    'errors' => $errors,
                    'tables' => $results
                ]);
            }
            
        } catch (Exception $e) {
            return $this->jsonResponse(500, [
                'success' => false,
                'message' => 'Data sync failed',
                'error' => $e->getMessage()
            ]);
        }
    }
    

    
    private function jsonResponse($statusCode, $data) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}