@echo off
echo Setting up Finance Module Sync...

echo 1. Installing Python dependencies...
python -m pip install -r requirements.txt

echo 2. Creating finance_data table...
php -r "
require_once 'app/config/database.php';
try {
    $db = Database::connect();
    $sql = 'CREATE TABLE IF NOT EXISTS finance_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        source_table VARCHAR(100) NOT NULL,
        data JSON NOT NULL,
        synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(source_table),
        INDEX(synced_at)
    )';
    $db->exec($sql);
    echo 'Finance table created successfully\n';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . '\n';
}
"

echo 3. Testing PostgreSQL connection...
python -c "
import psycopg2
try:
    conn = psycopg2.connect(
        host='72.60.218.167',
        port=5432,
        database='modernsap',
        user='postgres',
        password='mango'
    )
    print('PostgreSQL connection successful!')
    conn.close()
except Exception as e:
    print(f'PostgreSQL connection failed: {e}')
"

echo Setup complete! 
echo.
echo To start the finance module:
echo 1. Run: start_finance_api.bat
echo 2. Visit: http://localhost/ergon/finance
echo 3. For auto-sync, schedule: php cron/finance_sync.php

pause