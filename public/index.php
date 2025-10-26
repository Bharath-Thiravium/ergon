<?php
// Redirect /Ergon/public/* to /Ergon/*
$requestUri = $_SERVER["REQUEST_URI"];
$newUri = str_replace("/Ergon/public/", "/Ergon/", $requestUri);
if ($newUri !== $requestUri) {
    header("Location: $newUri", true, 301);
    exit;
}
// If direct access to /public/, redirect to root
header("Location: /Ergon/", true, 301);
exit;
?>