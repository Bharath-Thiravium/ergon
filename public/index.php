<?php
// Redirect /ergon/public/* to /ergon/*
$requestUri = $_SERVER["REQUEST_URI"];
$newUri = str_replace("/ergon/public/", "/ergon/", $requestUri);
if ($newUri !== $requestUri) {
    header("Location: $newUri", true, 301);
    exit;
}
// If direct access to /public/, redirect to root
header("Location: /ergon/", true, 301);
exit;
?>