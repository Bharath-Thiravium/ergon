#!/usr/bin/env php
<?php

// Auto sync script for finance data (runs every 30 minutes)

require_once __DIR__ . '/../app/services/DataSyncService.php';

try {
    echo "[" . date('Y-m-d H:i:s') . "] Starting automatic data sync...\n";
    
    $syncService = new DataSyncService();
    $results = $syncService->syncAllTables();
    
    $totalRecords = 0;
    $errors = [];
    
    foreach ($results as $result) {
        $totalRecords += $result['records'];
        echo "[" . date('Y-m-d H:i:s') . "] {$result['table']}: {$result['records']} records ({$result['status']})\n";
        
        if ($result['status'] === 'error') {
            $errors[] = $result['table'] . ': ' . $result['error'];
            echo "[ERROR] " . $result['error'] . "\n";
        }
    }
    
    if (empty($errors)) {
        echo "[" . date('Y-m-d H:i:s') . "] Sync completed successfully. Total records: {$totalRecords}\n";
        exit(0);
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Sync completed with errors. Total records: {$totalRecords}\n";
        foreach ($errors as $error) {
            echo "[ERROR] {$error}\n";
        }
        exit(1);
    }
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Sync failed: " . $e->getMessage() . "\n";
    exit(2);
}
