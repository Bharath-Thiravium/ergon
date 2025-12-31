<?php
$requestUri = $_SERVER['REQUEST_URI'];
$filename = basename(parse_url($requestUri, PHP_URL_PATH));

if (empty($filename) || $filename === 'index.php') {
    http_response_code(404);
    exit('File not found');
}

$filePath = __DIR__ . '/' . $filename;

if (file_exists($filePath)) {
    $mimeType = mime_content_type($filePath);
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

if (preg_match('/^(\d+)_(.+)$/', $filename, $matches)) {
    $originalName = $matches[2];
    $pattern = '*_' . $originalName;
    $files = glob(__DIR__ . '/' . $pattern);
    
    if (!empty($files)) {
        $file = $files[0];
        $mimeType = mime_content_type($file);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

http_response_code(404);
exit('File not found');
