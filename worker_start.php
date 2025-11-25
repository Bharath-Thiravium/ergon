#!/usr/bin/env php
<?php
/**
 * Notification Worker Starter Script
 * Usage: php worker_start.php
 */

require_once __DIR__ . '/app/services/NotificationWorker.php';

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

// Set up error handling
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Handle shutdown gracefully
$shutdown = false;
pcntl_signal(SIGTERM, function() use (&$shutdown) {
    echo "Received SIGTERM, shutting down gracefully...\n";
    $shutdown = true;
});
pcntl_signal(SIGINT, function() use (&$shutdown) {
    echo "Received SIGINT, shutting down gracefully...\n";
    $shutdown = true;
});

echo "Starting Notification Worker...\n";
echo "Process ID: " . getmypid() . "\n";
echo "Press Ctrl+C to stop\n\n";

try {
    $worker = new NotificationWorker();
    
    while (!$shutdown) {
        pcntl_signal_dispatch();
        
        try {
            $event = $worker->processNext();
            if (!$event) {
                sleep(5); // No events, wait 5 seconds
            }
        } catch (Exception $e) {
            error_log("Worker error: " . $e->getMessage());
            sleep(10); // Wait longer on error
        }
    }
    
} catch (Exception $e) {
    error_log("Fatal worker error: " . $e->getMessage());
    exit(1);
}

echo "Worker stopped.\n";
?>