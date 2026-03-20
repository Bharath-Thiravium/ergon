<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/services/DataSyncService.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $sync = new DataSyncService();

    if (!$sync->isPostgreSQLAvailable()) {
        echo json_encode(['success' => false, 'message' => 'PostgreSQL not available']);
        exit;
    }

    $results = $sync->syncAllTables();

    $summary = [];
    foreach ($results as $table => $r) {
        $summary[] = "{$r['records']} $table";
    }

    echo json_encode([
        'success' => true,
        'message' => 'Synced: ' . implode(', ', $summary),
        'details' => $results
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
