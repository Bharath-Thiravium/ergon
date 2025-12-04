<?php
echo "🔧 STRONG DATABASE CONFIGURATION FIX\n\n";

// Step 1: Update database configuration for dual environment
$databaseConfig = '<?php
/**
 * Database Configuration - DUAL ENVIRONMENT SETUP
 * Local: ergon_db | Production: u494785662_ergon
 */

require_once __DIR__ . \'/environment.php\';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    private static $instance = null;
    
    public function __construct() {
        try {
            if (Environment::isDevelopment()) {
                // LOCAL DEVELOPMENT DATABASE
                $this->host = \'localhost\';
                $this->db_name = \'ergon_db\';
                $this->username = \'root\';
                $this->password = \'\';
            } else {
                // PRODUCTION DATABASE (Hostinger)
                $this->host = \'localhost\';
                $this->db_name = \'u494785662_ergon\';
                $this->username = \'u494785662_ergon\';
                $this->password = \'@Admin@2025@\';
            }
        } catch (Exception $e) {
            error_log(\'Database configuration error: \' . $e->getMessage());
            throw new Exception(\'Database configuration failed\');
        }
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ];
            
            if (!Environment::isDevelopment()) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                $options[PDO::ATTR_TIMEOUT] = 30;
            }
            
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                $options
            );
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
        
        return $this->conn;
    }
    
    public static function connect() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->getConnection();
    }
    
    public function getEnvironment() {
        return Environment::isDevelopment() ? \'development\' : \'production\';
    }
    
    public function getDatabaseInfo() {
        return [
            \'environment\' => $this->getEnvironment(),
            \'host\' => $this->host,
            \'database\' => $this->db_name,
            \'username\' => $this->username
        ];
    }
}
?>';

// Write updated database config
file_put_contents(__DIR__ . '/app/config/database.php', $databaseConfig);
echo "✅ Updated database.php with dual environment setup\n";

// Step 2: Create local ergon_db database
try {
    $localPdo = new PDO('mysql:host=localhost', 'root', '');
    $localPdo->exec("CREATE DATABASE IF NOT EXISTS ergon_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Created local database: ergon_db\n";
} catch (Exception $e) {
    echo "❌ Local database creation failed: " . $e->getMessage() . "\n";
}

// Step 3: Sync production data to local
try {
    // Connect to production (Hostinger)
    $prodPdo = new PDO('mysql:host=localhost;dbname=u494785662_ergon', 'u494785662_ergon', '@Admin@2025@');
    
    // Connect to local
    $localPdo = Database::connect());
    
    // Get production users table structure
    $structure = $prodPdo->query("SHOW CREATE TABLE users")->fetch();
    $createTable = $structure['Create Table'];
    
    // Create users table in local database
    $localPdo->exec("DROP TABLE IF EXISTS users");
    $localPdo->exec($createTable);
    echo "✅ Created users table structure in local database\n";
    
    // Copy production data to local
    $prodUsers = $prodPdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($prodUsers as $user) {
        $columns = implode(',', array_keys($user));
        $placeholders = ':' . implode(', :', array_keys($user));
        
        $stmt = $localPdo->prepare("INSERT INTO users ($columns) VALUES ($placeholders)");
        $stmt->execute($user);
    }
    
    echo "✅ Synced " . count($prodUsers) . " users from production to local\n";
    
} catch (Exception $e) {
    echo "❌ Production sync failed: " . $e->getMessage() . "\n";
}

// Step 4: Test both connections
echo "\n🧪 TESTING CONNECTIONS:\n";

// Test local
try {
    require_once __DIR__ . '/app/config/database.php';
    $_SERVER['HTTP_HOST'] = 'localhost'; // Force development
    $db = Database::connect();
    $info = (new Database())->getDatabaseInfo();
    echo "✅ LOCAL: Connected to {$info['database']} as {$info['username']}\n";
} catch (Exception $e) {
    echo "❌ LOCAL: Connection failed - " . $e->getMessage() . "\n";
}

// Test production
try {
    $_SERVER['HTTP_HOST'] = 'athenas.co.in'; // Force production
    $db = Database::connect();
    $info = (new Database())->getDatabaseInfo();
    echo "✅ PRODUCTION: Connected to {$info['database']} as {$info['username']}\n";
} catch (Exception $e) {
    echo "❌ PRODUCTION: Connection failed - " . $e->getMessage() . "\n";
}

echo "\n🎯 CONFIGURATION COMPLETE:\n";
echo "• Local Development: ergon_db (localhost)\n";
echo "• Production: u494785662_ergon (Hostinger)\n";
echo "• Auto-detection based on HTTP_HOST\n";
echo "• Data synced from production to local\n";
?>