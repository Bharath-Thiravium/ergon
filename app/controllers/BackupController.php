<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class BackupController extends Controller {

    private $backupDir;
    private $retentionDays = 45;

    public function __construct() {
        $this->backupDir = realpath(__DIR__ . '/../../storage') . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0755, true);
        }
        $htaccess = $this->backupDir . '.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "Deny from all\nOptions -Indexes\n");
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
            'backups'        => $backups,
            'active_page'    => 'settings',
            'retention_days' => $this->retentionDays,
            'backup_dir_ok'  => is_writable($this->backupDir),
        ]);
    }

    public function create() {
        $this->requireOwnerOrAdmin();

        // Clear any prior output so JSON is clean
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            // Check backup dir is writable
            if (!is_dir($this->backupDir) || !is_writable($this->backupDir)) {
                echo json_encode(['success' => false, 'error' => 'Backup directory is not writable: ' . $this->backupDir]);
                exit;
            }

            require_once __DIR__ . '/../config/database.php';
            $cfg    = Database::getPostgreSQLConfig()['mysql'];
            $dbName = $cfg['database'];

            if (empty($dbName)) {
                echo json_encode(['success' => false, 'error' => 'Database name not configured']);
                exit;
            }

            $label    = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['label'] ?? '');
            $filename = 'backup_' . date('Y-m-d_H-i-s') . ($label ? '_' . $label : '') . '.sql';
            $filepath = $this->backupDir . $filename;

            // Try mysqldump if exec is available, fall back to PHP dump
            $dumped = false;
            if ($this->execEnabled()) {
                $dumped = $this->mysqldumpToFile($filepath, $cfg);
            }
            if (!$dumped) {
                $this->phpDumpToFile($filepath, $dbName);
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
                'method'   => $dumped ? 'mysqldump' : 'php',
            ]);

        } catch (Exception $e) {
            error_log('Backup create error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function restore($filename) {
        $this->requireOwnerOrAdmin();
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            $filename = basename($filename);
            if (!preg_match('/^(backup_|pre_restore_)[\w\-]+\.sql$/', $filename)) {
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

            // Safety backup before restore
            $safetyFile = $this->backupDir . 'pre_restore_' . date('Y-m-d_H-i-s') . '.sql';
            if ($this->execEnabled()) {
                $this->mysqldumpToFile($safetyFile, $cfg);
            }
            if (!file_exists($safetyFile) || filesize($safetyFile) < 10) {
                $this->phpDumpToFile($safetyFile, $dbName);
            }

            // Restore
            $restored = false;
            if ($this->execEnabled()) {
                $restored = $this->mysqlRestoreFromFile($filepath, $cfg);
            }
            if (!$restored) {
                $this->phpRestoreFromFile($filepath);
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
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache');
        readfile($filepath);
        exit;
    }

    public function delete($filename) {
        $this->requireOwnerOrAdmin();
        if (ob_get_level()) ob_end_clean();
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

    // ── Private helpers ───────────────────────────────────────────────────────

    private function execEnabled(): bool {
        if (!function_exists('exec')) return false;
        $disabled = array_map('trim', explode(',', ini_get('disable_functions') ?? ''));
        return !in_array('exec', $disabled);
    }

    private function mysqldumpToFile(string $filepath, array $cfg): bool {
        $dump = $this->findBinary('mysqldump');
        if (!$dump) return false;

        $passArg = $cfg['password'] !== '' ? '-p' . escapeshellarg($cfg['password']) : '';
        $cmd = sprintf(
            '%s --no-tablespaces --single-transaction --quick -h %s -u %s %s %s > %s 2>/dev/null',
            escapeshellcmd($dump),
            escapeshellarg($cfg['host']),
            escapeshellarg($cfg['username']),
            $passArg,
            escapeshellarg($cfg['database']),
            escapeshellarg($filepath)
        );
        exec($cmd, $out, $rc);
        return $rc === 0 && file_exists($filepath) && filesize($filepath) > 100;
    }

    private function mysqlRestoreFromFile(string $filepath, array $cfg): bool {
        $mysql = $this->findBinary('mysql');
        if (!$mysql) return false;

        $passArg = $cfg['password'] !== '' ? '-p' . escapeshellarg($cfg['password']) : '';
        $cmd = sprintf(
            '%s -h %s -u %s %s %s < %s 2>/dev/null',
            escapeshellcmd($mysql),
            escapeshellarg($cfg['host']),
            escapeshellarg($cfg['username']),
            $passArg,
            escapeshellarg($cfg['database']),
            escapeshellarg($filepath)
        );
        exec($cmd, $out, $rc);
        return $rc === 0;
    }

    private function findBinary(string $name): ?string {
        // Linux/Mac paths
        $candidates = [
            '/usr/bin/' . $name,
            '/usr/local/bin/' . $name,
            '/opt/homebrew/bin/' . $name,
            '/usr/local/mysql/bin/' . $name,
        ];
        // Windows paths (Laragon / XAMPP)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $laragon = glob('C:/laragon/bin/mysql/*/bin/' . $name . '.exe') ?: [];
            $candidates = array_merge($laragon, [
                'C:/xampp/mysql/bin/' . $name . '.exe',
            ]);
        }
        foreach ($candidates as $path) {
            if (file_exists($path) && is_executable($path)) return $path;
        }
        // Last resort: check PATH
        if ($this->execEnabled()) {
            $which = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'which';
            exec($which . ' ' . escapeshellarg($name) . ' 2>/dev/null', $out, $rc);
            if ($rc === 0 && !empty($out[0])) return trim($out[0]);
        }
        return null;
    }

    /**
     * Pure-PHP streaming dump — writes directly to file handle, no memory buildup.
     * Safe for large databases and shared hosting.
     */
    private function phpDumpToFile(string $filepath, string $dbName): void {
        // Raise limits for large databases
        @set_time_limit(300);
        @ini_set('memory_limit', '256M');

        require_once __DIR__ . '/../config/database.php';
        $db = Database::connect();

        $fh = fopen($filepath, 'w');
        if (!$fh) throw new Exception('Cannot open backup file for writing: ' . $filepath);

        fwrite($fh, "-- ERGON Database Backup\n");
        fwrite($fh, "-- Generated : " . date('Y-m-d H:i:s') . "\n");
        fwrite($fh, "-- Database  : {$dbName}\n");
        fwrite($fh, "-- Method    : PHP PDO\n\n");
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

        $tables = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Table structure
            $create = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
            fwrite($fh, "-- Table: `{$table}`\n");
            fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($fh, $create[1] . ";\n\n");

            // Row count check
            $count = (int)$db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
            if ($count === 0) continue;

            // Stream rows in chunks of 200
            $cols = null;
            $stmt = $db->query("SELECT * FROM `{$table}`");
            $chunk = [];
            $chunkSize = 200;

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($cols === null) {
                    $cols = '`' . implode('`, `', array_keys($row)) . '`';
                }
                $escaped = array_map(
                    fn($v) => $v === null ? 'NULL' : $db->quote((string)$v),
                    $row
                );
                $chunk[] = '(' . implode(', ', $escaped) . ')';

                if (count($chunk) >= $chunkSize) {
                    fwrite($fh, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $chunk) . ";\n");
                    $chunk = [];
                }
            }
            if ($chunk) {
                fwrite($fh, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $chunk) . ";\n");
            }
            fwrite($fh, "\n");
        }

        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
    }

    private function phpRestoreFromFile(string $filepath): void {
        @set_time_limit(300);
        require_once __DIR__ . '/../config/database.php';
        $db = Database::connect();

        $db->exec("SET FOREIGN_KEY_CHECKS=0");
        $db->exec("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'");

        $sql = file_get_contents($filepath);
        // Remove comments, split on statement boundaries
        $sql = preg_replace('/^--.*$/m', '', $sql);
        $statements = array_filter(array_map('trim', explode(";\n", $sql)));

        foreach ($statements as $stmt) {
            if ($stmt !== '') {
                try { $db->exec($stmt); } catch (Exception $e) {
                    error_log('Restore stmt error: ' . $e->getMessage());
                }
            }
        }
        $db->exec("SET FOREIGN_KEY_CHECKS=1");
    }

    private function listBackups(): array {
        $files = glob($this->backupDir . '*.sql') ?: [];
        $backups = [];
        foreach ($files as $f) {
            $name  = basename($f);
            $mtime = filemtime($f);
            $age   = (int)floor((time() - $mtime) / 86400);
            $backups[] = [
                'filename'   => $name,
                'size'       => $this->formatSize(filesize($f)),
                'created'    => date('d M Y, h:i A', $mtime),
                'age_days'   => $age,
                'is_auto'    => strpos($name, 'pre_restore_') === 0,
                'expires_in' => max(0, $this->retentionDays - $age),
            ];
        }
        usort($backups, fn($a, $b) => strcmp($b['filename'], $a['filename']));
        return $backups;
    }

    private function pruneOldBackups(): void {
        $files  = glob($this->backupDir . '*.sql') ?: [];
        $cutoff = time() - ($this->retentionDays * 86400);
        foreach ($files as $f) {
            if (filemtime($f) < $cutoff) @unlink($f);
        }
    }

    private function formatSize(int $bytes): string {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
?>
