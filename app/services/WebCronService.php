<?php

class WebCronService {
    
    public function setupWebCron() {
        // Alternative 1: External cron services
        return [
            'services' => [
                'cron-job.org' => 'https://cron-job.org - Free web cron service',
                'easycron.com' => 'https://www.easycron.com - Free tier available',
                'webcron.org' => 'https://webcron.org - Simple web cron'
            ],
            'endpoint' => 'https://yourdomain.com/ergon/finance/auto-sync',
            'interval' => '*/30 * * * *' // Every 30 minutes
        ];
    }
    
    public function createAutoSyncEndpoint() {
        // Alternative 2: Web-triggered sync endpoint
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['trigger']) && $_GET['trigger'] === 'auto') {
            
            // Simple security check
            $validTokens = ['sync123', 'auto456']; // Change these
            $token = $_GET['token'] ?? '';
            
            if (!in_array($token, $validTokens)) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }
            
            try {
                require_once __DIR__ . '/DataSyncService.php';
                $syncService = new DataSyncService();
                $results = $syncService->syncAllTables();
                
                echo json_encode([
                    'success' => true,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'results' => $results
                ]);
                
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }
    
    public function createJavaScriptAutoSync() {
        // Alternative 3: Browser-based periodic sync
        return '
        <script>
        // Auto-sync every 30 minutes when page is active
        let syncInterval;
        
        function startAutoSync() {
            syncInterval = setInterval(async () => {
                if (!document.hidden) {
                    try {
                        const response = await fetch("/ergon/finance/sync", {method: "POST"});
                        const result = await response.json();
                        console.log("Auto-sync:", result);
                    } catch (error) {
                        console.error("Auto-sync failed:", error);
                    }
                }
            }, 30 * 60 * 1000); // 30 minutes
        }
        
        // Start auto-sync when page loads
        document.addEventListener("DOMContentLoaded", startAutoSync);
        
        // Pause when page is hidden, resume when visible
        document.addEventListener("visibilitychange", () => {
            if (document.hidden) {
                clearInterval(syncInterval);
            } else {
                startAutoSync();
            }
        });
        </script>';
    }
}