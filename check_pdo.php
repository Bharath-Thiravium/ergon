<?php
// TEMPORARY — delete after checking. Do not leave on production.
if (extension_loaded('pdo_mysql')) {
    echo json_encode(['pdo_mysql' => 'ENABLED', 'php_version' => PHP_VERSION]);
} else {
    $loaded = get_loaded_extensions();
    $pdo = array_filter($loaded, fn($e) => stripos($e, 'pdo') !== false);
    echo json_encode([
        'pdo_mysql'   => 'MISSING',
        'php_version' => PHP_VERSION,
        'pdo_drivers' => PDO::getAvailableDrivers(),
        'loaded_pdo'  => array_values($pdo),
    ]);
}
