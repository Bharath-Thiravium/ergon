<?php
// Test PostgreSQL connection with different parameters
$connections = [
    ['host' => 'localhost', 'port' => '5432', 'dbname' => 'u494785662_ergon_finance', 'user' => 'u494785662_ergon', 'password' => 'ErgonFinance2024!'],
    ['host' => '127.0.0.1', 'port' => '5432', 'dbname' => 'u494785662_ergon_finance', 'user' => 'u494785662_ergon', 'password' => 'ErgonFinance2024!'],
    ['host' => 'localhost', 'port' => '5432', 'dbname' => 'postgres', 'user' => 'postgres', 'password' => 'ErgonFinance2024!'],
    ['host' => 'localhost', 'port' => '5432', 'dbname' => 'postgres', 'user' => 'u494785662_ergon', 'password' => 'ErgonFinance2024!']
];

foreach ($connections as $i => $config) {
    echo "Test " . ($i + 1) . ": ";
    $connString = "host={$config['host']} port={$config['port']} dbname={$config['dbname']} user={$config['user']} password={$config['password']}";
    
    $conn = @pg_connect($connString);
    if ($conn) {
        echo "✅ Connected to {$config['dbname']} as {$config['user']}\n";
        
        // List available databases
        $result = pg_query($conn, "SELECT datname FROM pg_database WHERE datistemplate = false");
        if ($result) {
            echo "Available databases: ";
            while ($row = pg_fetch_row($result)) {
                echo $row[0] . " ";
            }
            echo "\n";
        }
        
        pg_close($conn);
        break;
    } else {
        echo "❌ Failed\n";
    }
}
?>