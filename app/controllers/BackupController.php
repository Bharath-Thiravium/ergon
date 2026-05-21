<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class BackupController extends Controller {

    private $backupDir;
    private $retentionDays = 45;

    public function __construct() {
        $this->backupDir = __DIR__ . '/../../storage/backups/';
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        // Write .htaccess to block direct web access
        $htaccess = $this->backupDir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }

    private function requireOwnerOrAdmin() {
        AuthMiddleware::requireAuth();
        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, ['owner', 'company_owner', 'admin'])) {
            header('Location: ' . Environment::getBaseUrl() . '/settings?error=Access+denied');
            exit;
        }
    }

    public function index() {
        $this->requireOwnerOrAdmin();
        $this->pruneOldBackups();
        $backups = $this->listBackups();
        $this->view('settings/backup', [
            'backups'       => $backups,
            'active_page'   => 'settings',
            'retention_days'=> $this->retentionDays,
        ]);
    }

    public function create() {
        $this->requireOwnerOrAdmin();
        header('Content-Type: application/json');

        try {
            require_once __DIR__ . '/../config/database.php';
            $cfg = Database::getPostgreSQLConfig()['mysql'];
            $dbName = $cfg['database'];
            $dbUser = $cfg['username'];
            $dbPass = $cfg['password'];
            $dbHost = $cfg['host'];

            if (empty($dbName)) {
                echo json_encode(['success' => false, 'error' => 'Database name not configured']);
                exit;
            }

            $timestamp  = date('Y-m-d_H-i-s');
            $label      = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['label'] ?? '');
            $filename   = 'backup_' . $timestamp . ($label ? '_' . $label : '') . '.sql';
            $filepath   = $this->backupDir . $filename;

            // Try mysqldump first
            $mysqldump = $this->findMysqldump();
            if ($mysqldump) {
                $passArg = $dbPass !== '' ? '-p' . escapeshellarg($dbPass) : '';
                // Use --no-tablespaces to avoid PROCESS privilege requirement
                $cmd = sprintf(
                    '%s --no-tablespaces -h %s -u %s %s %s > %s 2>&1',
                    escapeshellcmd($mysqldump),
                    escapeshellarg($dbHost),
                    escapeshellarg($dbUser),
                    $passArg,
                    escapeshellarg($dbName),
                    escapeshellarg($filepath)
                );
                exec($cmd, $output, $returnCode);
                if ($returnCode !== 0 || !file_exists($filepath) || filesize($filepath) < 100) {
                    // Fall back to PHP-based dump
                    $this->phpDump($filepath, $dbName);
                }
            } else {
                $this->phpDump($filepath, $dbName);
            }

            if (!file_exists($filepath) || filesize($filepath) < 10) {
                echo json_encode(['success' => false, 'error' => 'Backup file was not created or is empty']);
                exit;
            }

            $this->pruneOldBackups();

            echo json_encode([
                'success'  => true,
                'message'  => 'Backup created successfully',
                'filename' => $filename,
                'size'     => $this->formatSize(filesize($filepath)),
            ]);
        } catch (Exception $e) {
            error_log('Backup create error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function restore($filename) {
        $this->requireOwnerOrAdmin();
        header('Content-Type: application/json');

        try {
            $filename = basename($filename); // sanitize
            if (!preg_match('/^backup_[\w\-]+\.sql$/', $filename)) {
                echo json_encode(['success' => false, 'error' => 'Invalid filename']);
                exit;
            }

            $filepath = $this->backupDir . $filename;
            if (!file_exists($filepath)) {
                echo json_encode(['success' => false, 'error' => 'Backup file not found']);
                exit;
            }

            require_once __DIR__ . '/../config/database.php';
            $cfg    = Database::getPostgreSQLConfig()['mysql'];
            $dbName = $cfg['database'];
            $dbUser = $cfg['username'];
            $dbPass = $cfg['password'];
            $dbHost = $cfg['host'];

            // Create a safety backup before restoring
            $safetyFile = $this->backupDir . 'pre_restore_' . date('Y-m-d_H-i-s') . '.sql';
            $mysqldump  = $this->findMysqldump();
            if ($mysqldump) {
                $passArg = $dbPass !== '' ? '-p' . escapeshellarg($dbPass) : '';
                $cmd = sprintf(
                    '%s --no-tablespaces -h %s -u %s %s %s > %s 2>&1',
                    escapeshellcmd($mysqldump),
                    escapeshellarg($dbHost),
                    escapeshellarg($dbUser),
                    $passArg,
                    escapeshellarg($dbName),
                    escapeshellarg($safetyFile)
                );
                exec($cmd);
            }

            // Restore using mysql client
            $mysql = $this->findMysql();
            if ($mysql) {
                $passArg = $dbPass !== '' ? '-p' . escapeshellarg($dbPass) : '';
                $cmd = sprintf(
                    '%s -h %s -u %s %s %s < %s 2>&1',
                    escapeshellcmd($mysql),
                    escapeshellarg($dbHost),
                    escapeshellarg($dbUser),
                    $passArg,
                    escapeshellarg($dbName),
                    escapeshellarg($filepath)
                );
                exec($cmd, $output, $returnCode);
                if ($returnCode !== 0) {
                    echo json_encode(['success' => false, 'error' => 'Restore failed: ' . implode(' ', $output)]);
                    exit;
                }
            } else {
                // PHP-based restore
                $this->phpRestore($filepath);
            }

            echo json_encode(['success' => true, 'message' => 'Database restored successfully from ' . $filename]);
        } catch (Exception $e) {
            error_log('Backup restore error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function download($filename) {
        $this->requireOwnerOrAdmin();
        $filename = basename($filename);
        if (!preg_match('/^(backup_|pre_restore_)[\w\-]+\.sql$/', $filename)) {
            header('Location: ' . Environment::getBaseUrl() . '/settings/backup?error=Invalid+file');
            exit;
        }
        $filepath = $this->backupDir . $filename;
        if (!file_exists($filepath)) {
            header('Location: ' . Environment::getBaseUrl() . '/settings/backup?error=File+not+found');
            exit;
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }

    public function delete($filename) {
        $this->requireOwnerOrAdmin();
        header('Content-Type: application/json');
        $filename = basename($filename);
        if (!preg_match('/^(backup_|pre_restore_)[\w\-]+\.sql$/', $filename)) {
            echo json_encode(['success' => false, 'error' => 'Invalid filename']);
            exit;
        }
        $filepath = $this->backupDir . $filename;
        if (file_exists($filepath) && unlink($filepath)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Could not delete file']);
        }
        exit;
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function listBackups() {
        $files = glob($this->backupDir . '*.sql') ?: [];
        $backups = [];
        foreach ($files as $f) {
            $name = basename($f);
            $mtime = filemtime($f);
            $age   = floor((time() - $mtime) / 86400);
            $backups[] = [
                'filename'  => $name,
                'size'      => $this->formatSize(filesize($f)),
                'created'   => date('d M Y, h:i A', $mtime),
                'age_days'  => $age,
                'is_auto'   => strpos($name, 'pre_restore_') === 0,
                'expires_in'=> max(0, $this->retentionDays - $age),
            ];
        }
        usort($backups, fn($a, $b) => strcmp($b['filename'], $a['filename']));
        return $backups;
    }

    private function pruneOldBackups() {
        $files = glob($this->backupDir . '*.sql') ?: [];
        $cutoff = time() - ($this->retentionDays * 86400);
        foreach ($files as $f) {
            if (filemtime($f) < $cutoff) {
                @unlink($f);
            }
        }
    }

    private function findMysqldump() {
        $candidates = [
            'mysqldump',
            'C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqldump.exe',
            'C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysqldump.exe',
            'C:/xampp/mysql/bin/mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];
        // Auto-detect Laragon MySQL
        $laragonMysql = glob('C:/laragon/bin/mysql/*/bin/mysqldump.exe');
        if ($laragonMysql) $candidates = array_merge($laragonMysql, $candidates);

        foreach ($candidates as $c) {
            if (strpos($c, '/') === false && strpos($c, '\\') === false) {
                exec('where ' . escapeshellarg($c) . ' 2>nul', $out, $rc);
                if ($rc === 0 && !empty($out)) return $c;
            } elseif (file_exists($c)) {
                return $c;
            }
        }
        return null;
    }

    private function findMysql() {
        $candidates = [
            'mysql',
            'C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql.exe',
            'C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe',
            'C:/xampp/mysql/bin/mysql.exe',
            '/usr/bin/mysql',
            '/usr/local/bin/mysql',
        ];
        $laragonMysql = glob('C:/laragon/bin/mysql/*/bin/mysql.exe');
        if ($laragonMysql) $candidates = array_merge($laragonMysql, $candidates);

        foreach ($candidates as $c) {
            if (strpos($c, '/') === false && strpos($c, '\\') === false) {
                exec('where ' . escapeshellarg($c) . ' 2>nul', $out, $rc);
                if ($rc === 0 && !empty($out)) return $c;
            } elseif (file_exists($c)) {
                return $c;
            }
        }
        return null;
    }

    private function phpDump($filepath, $dbName) {
        require_once __DIR__ . '/../config/database.php';
        $db = Database::connect();

        $out = "-- ERGON Database Backup\n";
        $out .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $out .= "-- Database: {$dbName}\n\n";
        $out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            // Structure
            $create = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
            $out .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $out .= $create[1] . ";\n\n";

            // Data in chunks
            $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) {
                $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
                foreach (array_chunk($rows, 100) as $chunk) {
                    $values = [];
                    foreach ($chunk as $row) {
                        $escaped = array_map(fn($v) => $v === null ? 'NULL' : $db->quote($v), $row);
                        $values[] = '(' . implode(', ', $escaped) . ')';
                    }
                    $out .= "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $values) . ";\n";
                }
                $out .= "\n";
            }
        }
        $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($filepath, $out);
    }

    private function phpRestore($filepath) {
        require_once __DIR__ . '/../config/database.php';
        $db  = Database::connect();
        $sql = file_get_contents($filepath);

        // Split on semicolons not inside strings (simple approach)
        $db->exec("SET FOREIGN_KEY_CHECKS=0");
        $statements = array_filter(array_map('trim', explode(";\n", $sql)));
        foreach ($statements as $stmt) {
            if ($stmt && !preg_match('/^--/', $stmt)) {
                try { $db->exec($stmt); } catch (Exception $e) {
                    error_log('Restore stmt error: ' . $e->getMessage());
                }
            }
        }
        $db->exec("SET FOREIGN_KEY_CHECKS=1");
    }

    private function formatSize($bytes) {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
?>
