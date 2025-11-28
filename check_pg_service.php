<?php
// Check if PostgreSQL service is running and get connection info
echo "PostgreSQL Service Check:\n";

// Check if PostgreSQL is running on common ports
$ports = [5432, 5433, 5434];
$hosts = ['localhost', '127.0.0.1'];

foreach ($hosts as $host) {
    foreach ($ports as $port) {
        $connection = @fsockopen($host, $port, $errno, $errstr, 1);
        if ($connection) {
            echo "✅ PostgreSQL service found at $host:$port\n";
            fclose($connection);
        } else {
            echo "❌ No service at $host:$port\n";
        }
    }
}

// Check environment variables for PostgreSQL
echo "\nEnvironment Variables:\n";
$pgVars = ['PGHOST', 'PGPORT', 'PGDATABASE', 'PGUSER', 'PGPASSWORD'];
foreach ($pgVars as $var) {
    $value = getenv($var);
    echo "$var: " . ($value ? $value : 'not set') . "\n";
}

// Try to find PostgreSQL config
$configPaths = [
    '/etc/postgresql/',
    '/var/lib/postgresql/',
    '/usr/local/pgsql/',
    'C:\\Program Files\\PostgreSQL\\'
];

echo "\nLooking for PostgreSQL installation:\n";
foreach ($configPaths as $path) {
    if (is_dir($path)) {
        echo "✅ Found PostgreSQL directory: $path\n";
    }
}
?>