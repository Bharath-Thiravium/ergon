<?php
/**
 * Secure File Download Handler
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/SessionManager.php';
require_once __DIR__ . '/../app/helpers/Security.php';

SessionManager::start();
SessionManager::requireLogin();

$file = Security::sanitizeString($_GET['file'] ?? '');
$type = Security::sanitizeString($_GET['type'] ?? '');

if (empty($file) || empty($type)) {
    http_response_code(400);
    die('Invalid request');
}

// Validate file type
$allowedTypes = ['receipts', 'documents'];
if (!in_array($type, $allowedTypes)) {
    http_response_code(400);
    die('Invalid file type');
}

// Build secure file path
$filePath = __DIR__ . '/../storage/secure_uploads/' . $type . '/' . $file;

// Security checks
if (!file_exists($filePath) || !is_file($filePath)) {
    http_response_code(404);
    die('File not found');
}

// Prevent directory traversal
$realPath = realpath($filePath);
$allowedPath = realpath(__DIR__ . '/../storage/secure_uploads/' . $type . '/');

if (strpos($realPath, $allowedPath) !== 0) {
    http_response_code(403);
    die('Access denied');
}

// Get file info
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

// Set secure headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline; filename="' . basename($file) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');

// Output file
readfile($filePath);
?>