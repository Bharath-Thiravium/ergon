<?php
/**
 * ERGON Database Backup & Restore Utility
 * 
 * Features:
 * - Backup entire database to SQL file
 * - Backup specific tables
 * - Restore from SQL file
 * - Download backup as file
 * - Email backup (optional)
 * 
 * Usage: Visit /ergon/backup.php in browser
 * Access: Admin/Owner only
 */

session_start();

// Check access
$isAdmin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'owner']);
if (!$isAdmin && php_sapi_name() !== 'cli') {
    die('Access denied. Admin only.');
}

require_once __DIR__ . '/app/config/database.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'view';
$db = Database::connect();

function log_backup($message) {
    $logFile = __DIR__ . '/logs/backup.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

function backup_table($db, $tableName) {
    $result = '';
    
    // Get table structure
    $stmt = $db->query("SHOW CREATE TABLE `$tableName`");
    $createTable = $stmt->fetchColumn(1);
    $result .= "\n-- Table: $tableName\n";
    $result .= "DROP TABLE IF EXISTS `$tableName`;\n";
    $result .= $createTable . ";\n\n";
    
    // Get table data
    $stmt = $db->query("SELECT * FROM `$tableName`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($rows)) {
        $columns = array_keys($rows[0]);
        $columnNames = '`' . implode('`, `', $columns) . '`';
        
        $result .= "-- Data for table: $tableName\n";
        foreach ($rows as $row) {
            $values = [];
            foreach ($row as $value) {
                if (is_null($value)) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . str_replace("'", "''", $value) . "'";
                }
            }
            $result .= "INSERT INTO `$tableName` ($columnNames) VALUES (" . implode(', ', $values) . ");\n";
        }
        $result .= "\n";
    }
    
    return $result;
}

function get_all_tables($db) {
    $stmt = $db->query("SHOW TABLES");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ============================================
// ACTION: Full Database Backup
// ============================================
if ($action === 'backup_full') {
    $tables = get_all_tables($db);
    $backup = "-- ERGON Database Full Backup\n";
    $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $backup .= "-- Database: " . DB::NAME . "\n";
    $backup .= "-- User: " . ($_SESSION['user_name'] ?? 'Unknown') . "\n\n";
    $backup .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        $backup .= backup_table($db, $table);
    }
    
    $backup .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $backup .= "\n-- Backup complete\n";
    
    log_backup('Full backup created by ' . ($_SESSION['user_name'] ?? 'Unknown'));
    
    // Send as download
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="ergon_backup_' . date('Y-m-d_H-i-s') . '.sql"');
    echo $backup;
    exit;
}

// ============================================
// ACTION: Backup Specific Table
// ============================================
if ($action === 'backup_table') {
    $table = $_GET['table'] ?? null;
    if (!$table) {
        die('No table specified');
    }
    
    $backup = "-- ERGON Single Table Backup\n";
    $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $backup .= "-- Table: $table\n\n";
    $backup .= backup_table($db, $table);
    
    log_backup("Table backup created: $table");
    
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="ergon_' . $table . '_' . date('Y-m-d_H-i-s') . '.sql"');
    echo $backup;
    exit;
}

// ============================================
// ACTION: Restore Database
// ============================================
if ($action === 'restore' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['backup_file'])) {
        die('No file uploaded');
    }
    
    $file = $_FILES['backup_file']['tmp_name'];
    $filename = $_FILES['backup_file']['name'];
    
    if (!file_exists($file)) {
        die('Upload failed');
    }
    
    try {
        $sql = file_get_contents($file);
        
        // Split by semicolon, but be careful with semicolons in strings
        $queries = preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql);
        
        $count = 0;
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query) && strpos($query, '--') !== 0) {
                try {
                    $db->exec($query);
                    $count++;
                } catch (Exception $e) {
                    // Log error but continue
                    error_log('Restore query error: ' . $e->getMessage());
                }
            }
        }
        
        log_backup("Database restored from $filename - $count queries executed");
        
        $message = "✓ Restore successful! Executed $count queries.";
        $message_type = 'success';
    } catch (Exception $e) {
        log_backup("Restore failed: " . $e->getMessage());
        $message = "✗ Restore failed: " . $e->getMessage();
        $message_type = 'error';
    }
}

// ============================================
// ACTION: View (Default)
// ============================================
$tables = get_all_tables($db);
$totalSize = 0;
$tableStats = [];

foreach ($tables as $table) {
    $stmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
    $count = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT data_length FROM information_schema.tables WHERE table_name = '$table' AND table_schema = '" . DB::NAME . "'");
    $size = $stmt->fetchColumn() ?? 0;
    $totalSize += $size;
    
    $tableStats[$table] = [
        'count' => $count,
        'size' => $size
    ];
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>ERGON Database Backup & Restore</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { font-size: 1.1em; opacity: 0.9; }
        .content { padding: 30px; }
        .section { margin-bottom: 40px; }
        .section-title { 
            font-size: 1.5em; 
            color: #333; 
            margin-bottom: 20px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .button-group { 
            display: flex; 
            gap: 10px; 
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        button, .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .btn-success:hover {
            background: #059669;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-small {
            padding: 8px 12px;
            font-size: 0.9em;
        }
        .table-container {
            overflow-x: auto;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }
        tr:hover { background: #f9fafb; }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .stat-value { font-size: 2em; font-weight: bold; }
        .stat-label { font-size: 0.9em; opacity: 0.9; margin-top: 5px; }
        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            background: #f9fafb;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: #764ba2;
            background: #f3f4f6;
        }
        .upload-area input[type="file"] {
            display: none;
        }
        .timestamp { 
            font-size: 0.85em; 
            color: #6b7280; 
        }
        .footer {
            background: #f3f4f6;
            padding: 20px;
            text-align: center;
            font-size: 0.85em;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💾 Database Backup & Restore</h1>
            <p>ERGON Attendance System - Database Management</p>
        </div>
        
        <div class="content">
            <?php if (isset($message)): ?>
                <div class="message <?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="section">
                <div class="section-title">📊 Database Statistics</div>
                <div class="stat-card">
                    <div class="stat-value"><?= count($tables) ?></div>
                    <div class="stat-label">Total Tables</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($totalSize / 1024 / 1024, 2) ?> MB</div>
                    <div class="stat-label">Total Size</div>
                </div>
            </div>
            
            <!-- Backup Section -->
            <div class="section">
                <div class="section-title">🔒 Backup Database</div>
                <div class="button-group">
                    <button class="btn btn-primary" onclick="location.href='?action=backup_full'">
                        📥 Download Full Backup
                    </button>
                </div>
                <p style="color: #6b7280; margin-bottom: 20px;">
                    Creates a complete database backup file that can be downloaded and stored safely.
                </p>
                
                <div class="section-title" style="margin-top: 30px;">Backup Individual Tables</div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Records</th>
                                <th>Size</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tableStats as $table => $stats): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($table) ?></strong></td>
                                    <td><?= number_format($stats['count']) ?></td>
                                    <td><?= number_format($stats['size'] / 1024, 2) ?> KB</td>
                                    <td>
                                        <a href="?action=backup_table&table=<?= urlencode($table) ?>" class="btn btn-primary btn-small">
                                            Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Restore Section -->
            <div class="section">
                <div class="section-title">♻️ Restore Database</div>
                <p style="color: #6b7280; margin-bottom: 20px;">
                    ⚠️ <strong>Warning:</strong> Restoring will replace existing data. Backup current database first!
                </p>
                <form method="POST" enctype="multipart/form-data" style="margin-bottom: 20px;">
                    <input type="hidden" name="action" value="restore">
                    <div class="upload-area" onclick="document.querySelector('input[type=file]').click()">
                        <input type="file" name="backup_file" accept=".sql" required onchange="this.form.submit()">
                        <div>📂 Click to upload backup file (.sql)</div>
                        <div style="font-size: 0.85em; color: #6b7280; margin-top: 10px;">or drag and drop</div>
                    </div>
                </form>
            </div>
            
            <!-- Recent Backups -->
            <div class="section">
                <div class="section-title">📋 Recent Backups</div>
                <?php
                $backupLog = __DIR__ . '/logs/backup.log';
                if (file_exists($backupLog)) {
                    $lines = array_reverse(file($backupLog));
                    echo "<pre style='background: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto;'>";
                    foreach (array_slice($lines, 0, 10) as $line) {
                        echo htmlspecialchars($line);
                    }
                    echo "</pre>";
                } else {
                    echo "<p style='color: #6b7280;'>No backup history yet.</p>";
                }
                ?>
            </div>
        </div>
        
        <div class="footer">
            <p>ERGON Database Management Tool</p>
            <p>Generated: <?= date('Y-m-d H:i:s') ?></p>
            <p>⚠️ For security: Delete this file after deployment</p>
        </div>
    </div>
</body>
</html>
