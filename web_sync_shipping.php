<?php
// Web-accessible shipping sync for production
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_key']) && $_POST['sync_key'] === 'ergon_sync_2025') {
    require_once __DIR__ . '/src/services/PostgreSQLSyncService.php';
    
    try {
        $syncService = new PostgreSQLSyncService();
        $result = $syncService->syncCustomerShippingAddress();
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => "Synced: {$result['synced']} addresses, Skipped: {$result['skipped']} duplicates"
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error']]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Sync Shipping Addresses</title></head>
<body>
<h2>Sync Production Shipping Addresses</h2>
<form method="POST">
    <input type="password" name="sync_key" placeholder="Sync Key" required>
    <button type="submit">Sync Now</button>
</form>
</body>
</html>