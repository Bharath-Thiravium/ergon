<?php
/**
 * PHP Built-in Server Router
 * Usage: php -S localhost:8000 router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Strip /ergon prefix to get real filesystem path
$filePath = preg_replace('#^/ergon#', '', $uri);
$fullPath = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $filePath);

// Serve static files directly with correct MIME type
if ($filePath !== '' && is_file($fullPath)) {
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'eot'   => 'application/vnd.ms-fontobject',
        'json'  => 'application/json',
        'map'   => 'application/json',
        'html'  => 'text/html',
        'txt'   => 'text/plain',
    ];
    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    readfile($fullPath);
    exit;
}

// Everything else goes through the application
require_once __DIR__ . '/index.php';
