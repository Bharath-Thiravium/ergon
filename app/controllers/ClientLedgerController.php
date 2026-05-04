<?php
require_once __DIR__ . '/../core/Controller.php';

class ClientLedgerController extends Controller {

    private function getDb(): PDO {
        require_once __DIR__ . '/../config/database.php';
        return Database::connect();
    }

    private function ensureTables(PDO $db): void {
        // clients table
        $db->exec("
            CREATE TABLE IF NOT EXISTS clients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                company_name VARCHAR(255),
                email VARCHAR(255),
                phone VARCHAR(50),
                status ENUM('active','inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // client_ledgers — create with VARCHAR so it never needs an ALTER for new types
        $db->exec("
            CREATE TABLE IF NOT EXISTS client_ledgers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                client_id INT NOT NULL,
                entry_type VARCHAR(50) NOT NULL,
                direction ENUM('debit','credit') NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                balance_after DECIMAL(12,2) NOT NULL,
                description TEXT,
                reference_no VARCHAR(100),
                attachment VARCHAR(500),
                transaction_date DATE NOT NULL,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES clients(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // If the table already existed with the old ENUM column, migrate it to VARCHAR
        $col = $db->query("
            SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'client_ledgers'
              AND COLUMN_NAME  = 'entry_type'
        ")->fetchColumn();
        if ($col && stripos($col, 'enum') !== false) {
            $db->exec("ALTER TABLE client_ledgers MODIFY COLUMN entry_type VARCHAR(50) NOT NULL");
        }

        // Add attachment column if missing (tables created before that feature)
        $hasAttachment = $db->query("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'client_ledgers'
              AND COLUMN_NAME  = 'attachment'
        ")->fetchColumn();
        if (!$hasAttachment) {
            $db->exec("ALTER TABLE client_ledgers ADD COLUMN attachment VARCHAR(500) NULL AFTER reference_no");
        }
    }

    private function getUploadDir(): string {
        $dir = __DIR__ . '/../../public/uploads/client_ledger';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private function handleAttachmentUpload(?array $file, int $clientId): ?string {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf'
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            return null;
        }

        $extension = $mimeType === 'application/pdf' ? '.pdf' : '.' . explode('/', $mimeType)[1];
        if ($extension === '.jpeg') $extension = '.jpg';

        $fileName = 'ledger_' . $clientId . '_' . time() . '_' . uniqid() . $extension;
        $targetPath = $this->getUploadDir() . '/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $fileName;
        }

        return null;
    }

    private function deleteAttachmentFile(?string $filename): void {
        if (empty($filename)) return;
        
        $filePath = $this->getUploadDir() . '/' . $filename;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    public function viewAttachment(string $filename): void {
        $filePath = $this->getUploadDir() . '/' . $filename;
        
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            echo 'File not found';
            return;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    private function requireAdmin(): void {
        $this->requireAuth();
        if (!in_array($_SESSION['role'] ?? '', ['owner', 'company_owner', 'admin'])) {
            $this->redirect('/dashboard');
        }
    }

    public function index(): void {
        $this->requireAdmin();
        $db = $this->getDb();
        $this->ensureTables($db);

        $clients = $db->query("
            SELECT c.id, c.name, c.company_name, c.email, c.phone, c.status,
                   COALESCE(SUM(CASE WHEN cl.direction='credit' THEN cl.amount ELSE 0 END), 0) AS total_credits,
                   COALESCE(SUM(CASE WHEN cl.direction='debit'  THEN cl.amount ELSE 0 END), 0) AS total_debits,
                   COALESCE((SELECT cl2.balance_after FROM client_ledgers cl2
                              WHERE cl2.client_id = c.id
                              ORDER BY cl2.transaction_date DESC, cl2.id DESC LIMIT 1), 0) AS current_balance
            FROM clients c
            LEFT JOIN client_ledgers cl ON cl.client_id = c.id
            GROUP BY c.id
            ORDER BY c.name
        ")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('client_ledger/index', [
            'title'       => 'Customer Ledger',
            'active_page' => 'client_ledger',
            'clients'     => $clients,
        ]);
    }

    public function ledger(int $clientId): void {
        $this->requireAdmin();
        $db = $this->getDb();
        $this->ensureTables($db);

        $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$client) {
            $this->redirect('/client-ledger');
        }

        $entries = $this->fetchLedgerEntries($db, $clientId);

        // Totals and current balance — compute from all entries
        $totalCredits = 0.0;
        $totalDebits  = 0.0;
        foreach ($entries as $e) {
            if ($e['direction'] === 'credit') $totalCredits += floatval($e['amount']);
            else                               $totalDebits  += floatval($e['amount']);
        }
        // entries are DESC; the chronologically last entry is the last element
        // but balance_after is stored correctly per row, so the first element
        // (most recent date) holds the final running balance
        $currentBalance = empty($entries) ? 0.0 : floatval($entries[0]['balance_after']);

        $this->view('client_ledger/ledger', [
            'title'          => 'Ledger: ' . htmlspecialchars($client['name']),
            'active_page'    => 'client_ledger',
            'client'         => $client,
            'entries'        => $entries,
            'totalCredits'   => $totalCredits,
            'totalDebits'    => $totalDebits,
            'currentBalance' => $currentBalance,
        ]);
    }

private function fetchLedgerEntries(PDO $db, int $clientId): array {
        $stmt = $db->prepare("
            SELECT id, transaction_date, entry_type, description, reference_no, attachment,
                   amount, direction, balance_after
            FROM client_ledgers
            WHERE client_id = :client_id
            ORDER BY transaction_date DESC, id DESC
        ");
        $stmt->execute([':client_id' => $clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** AJAX: check if a reference_no already exists for this client (excluding a given entry id) */
    public function checkReference(): void {
        $this->requireAdmin();
        $clientId    = (int)($_GET['client_id'] ?? 0);
        $referenceNo = trim($_GET['reference_no'] ?? '');
        $excludeId   = (int)($_GET['exclude_id'] ?? 0);

        if (!$clientId || $referenceNo === '') {
            $this->json(['duplicate' => false]);
        }

        $db   = $this->getDb();
        $sql  = 'SELECT COUNT(*) FROM client_ledgers WHERE client_id = ? AND reference_no = ?';
        $args = [$clientId, $referenceNo];
        if ($excludeId > 0) {
            $sql  .= ' AND id != ?';
            $args[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($args);
        $this->json(['duplicate' => (int)$stmt->fetchColumn() > 0]);
    }

    /** POST: delete an entry and recalculate all remaining balance_after values */
    public function deleteEntry(int $entryId): void {
        $this->requireAdmin();
        if (!$this->isPost()) {
            $this->redirect('/client-ledger');
        }

$db   = $this->getDb();
        $stmt = $db->prepare('SELECT client_id, attachment FROM client_ledgers WHERE id = ?');
        $stmt->execute([$entryId]);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $this->json(['success' => false, 'error' => 'Entry not found'], 404);
        }
        $clientId = (int)$row['client_id'];
        $oldAttachment = $row['attachment'] ?? null;

        $db->beginTransaction();
        try {
            $db->prepare('DELETE FROM client_ledgers WHERE id = ?')->execute([$entryId]);
            
            // Delete the attachment file if exists
            if ($oldAttachment) {
                $this->deleteAttachmentFile($oldAttachment);
            }

            // Recalculate balance_after for all remaining entries of this client
            $all = $db->prepare('
                SELECT id, direction, amount FROM client_ledgers
                WHERE client_id = ?
                ORDER BY transaction_date ASC, id ASC
                FOR UPDATE
            ');
            $all->execute([$clientId]);
            $rows = $all->fetchAll(PDO::FETCH_ASSOC);

            $running = 0.0;
            $updBal  = $db->prepare('UPDATE client_ledgers SET balance_after = ? WHERE id = ?');
            foreach ($rows as $r) {
                $running += $r['direction'] === 'credit' ? floatval($r['amount']) : -floatval($r['amount']);
                $updBal->execute([$running, $r['id']]);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            error_log('ClientLedger deleteEntry error: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'delete_failed']);
        }

        $this->json(['success' => true, 'client_id' => $clientId]);
    }

/** AJAX GET: return a single entry as JSON for the edit modal */
    public function editEntry(int $entryId): void {
        $this->requireAdmin();
        $db   = $this->getDb();
        $stmt = $db->prepare("
            SELECT id, client_id, entry_type, direction, amount, description, reference_no, attachment, transaction_date, balance_after
            FROM client_ledgers WHERE id = ?
        ");
        $stmt->execute([$entryId]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$entry) {
            $this->json(['success' => false, 'error' => 'Entry not found'], 404);
        }
        $this->json(['success' => true, 'entry' => $entry]);
    }

    /** POST: update an entry and recalculate all subsequent balance_after values */
    public function updateEntry(int $entryId): void {
        $this->requireAdmin();
        if (!$this->isPost()) {
            $this->redirect('/client-ledger');
        }

        $entryType   = $_POST['entry_type'] ?? '';
        $amount      = floatval($_POST['amount'] ?? 0);
        $txDate      = $_POST['transaction_date'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $referenceNo = trim($_POST['reference_no'] ?? '');

        $validTypes = ['payment_received', 'payment_sent', 'adjustment', 'invoice_raised', 'invoice_received', 'purchase', 'sale', 'expense', 'income', 'opening_balance', 'closing_balance', 'fees_paid', 'penalties_paid'];
        if (!in_array($entryType, $validTypes) || $amount <= 0 || !$txDate) {
            $this->json(['success' => false, 'error' => 'Invalid input'], 422);
        }

        $directionMap = [
            'payment_received' => 'credit',
            'payment_sent'     => 'debit',
            'adjustment'       => $_POST['adjustment_direction'] ?? 'credit',
            'invoice_raised'   => 'debit',
            'invoice_received' => 'credit',
            'purchase'         => 'debit',
            'sale'            => 'debit',
            'expense'         => 'debit',
            'income'          => 'credit',
            'opening_balance' => $_POST['adjustment_direction'] ?? 'credit',
            'closing_balance' => $_POST['adjustment_direction'] ?? 'credit',
            'fees_paid'       => 'debit',
            'penalties_paid'  => 'debit',
        ];
        $direction = $directionMap[$entryType];
        if (!in_array($direction, ['debit', 'credit'])) $direction = 'credit';

$db = $this->getDb();

        // Verify entry exists and get client_id
        $stmt = $db->prepare('SELECT client_id, attachment FROM client_ledgers WHERE id = ?');
        $stmt->execute([$entryId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $this->json(['success' => false, 'error' => 'Entry not found'], 404);
        }
        $clientId = (int)$row['client_id'];
        $oldAttachment = $row['attachment'] ?? null;

        // Handle new attachment upload
        $newAttachment = null;
        if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $newAttachment = $this->handleAttachmentUpload($_FILES['attachment'], $clientId);
        }

        // Duplicate reference check (exclude self)
        if ($referenceNo !== '') {
            $chk = $db->prepare('SELECT COUNT(*) FROM client_ledgers WHERE client_id = ? AND reference_no = ? AND id != ?');
            $chk->execute([$clientId, $referenceNo, $entryId]);
            if ((int)$chk->fetchColumn() > 0) {
                // Delete uploaded file if we failed due to duplicate reference
                if ($newAttachment) {
                    $this->deleteAttachmentFile($newAttachment);
                }
                $this->json(['success' => false, 'error' => 'duplicate_reference']);
            }
        }

        $db->beginTransaction();
        try {
            // Use new attachment if uploaded, otherwise keep old one
            $attachment_to_save = $newAttachment ?: $oldAttachment;
            
            // Update the target row (balance_after will be recalculated below)
            $upd = $db->prepare("
                UPDATE client_ledgers
                SET entry_type = ?, direction = ?, amount = ?, description = ?,
                    reference_no = ?, attachment = ?, transaction_date = ?
                WHERE id = ?
            ");
            $upd->execute([
                $entryType, $direction, $amount,
                $description ?: null, $referenceNo ?: null, $attachment_to_save, $txDate,
                $entryId,
            ]);
            
            // If new file uploaded and old file exists, delete old file
            if ($newAttachment && $oldAttachment) {
                $this->deleteAttachmentFile($oldAttachment);
            }

            // Recalculate balance_after for ALL entries of this client in chronological order
            $all = $db->prepare("
                SELECT id, direction, amount FROM client_ledgers
                WHERE client_id = ?
                ORDER BY transaction_date ASC, id ASC
                FOR UPDATE
            ");
            $all->execute([$clientId]);
            $rows = $all->fetchAll(PDO::FETCH_ASSOC);

            $running = 0.0;
            $updBal  = $db->prepare('UPDATE client_ledgers SET balance_after = ? WHERE id = ?');
            foreach ($rows as $r) {
                $running += $r['direction'] === 'credit' ? floatval($r['amount']) : -floatval($r['amount']);
                $updBal->execute([$running, $r['id']]);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            error_log('ClientLedger updateEntry error: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'save_failed']);
        }

        $this->json(['success' => true]);
    }

    public function store(): void {
        $this->requireAdmin();
        if (!$this->isPost()) {
            $this->redirect('/client-ledger');
        }

        $clientId      = (int)($_POST['client_id'] ?? 0);
        $entryType     = $_POST['entry_type'] ?? '';
        $amount        = floatval($_POST['amount'] ?? 0);
        $txDate        = $_POST['transaction_date'] ?? '';
        $description   = trim($_POST['description'] ?? '');
        $referenceNo   = trim($_POST['reference_no'] ?? '');

        $validTypes = ['payment_received','payment_sent','adjustment','invoice_raised','invoice_received','purchase','sale','expense','income','opening_balance','closing_balance','fees_paid','penalties_paid'];
        if (!$clientId || !in_array($entryType, $validTypes) || $amount <= 0 || !$txDate) {
            $this->redirect('/client-ledger?error=invalid_input');
        }

        $directionMap = [
            'payment_received' => 'credit',
            'payment_sent'     => 'debit',
            'adjustment'       => $_POST['adjustment_direction'] ?? 'credit',
            'invoice_raised'   => 'debit',
            'invoice_received' => 'credit',
            'purchase'         => 'debit',
            'sale'            => 'debit',
            'expense'         => 'debit',
            'income'          => 'credit',
            'opening_balance' => $_POST['adjustment_direction'] ?? 'credit',
            'closing_balance' => $_POST['adjustment_direction'] ?? 'credit',
            'fees_paid'       => 'debit',
            'penalties_paid'  => 'debit',
        ];
        $direction = $directionMap[$entryType];
        if (!in_array($direction, ['debit', 'credit'])) $direction = 'credit';

        $db = $this->getDb();
        $this->ensureTables($db);

        // Handle attachment upload
        $attachment = null;
        if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $attachment = $this->handleAttachmentUpload($_FILES['attachment'], $clientId);
        }

// Duplicate reference check before opening transaction
        if ($referenceNo !== '') {
            $chk = $db->prepare('SELECT COUNT(*) FROM client_ledgers WHERE client_id = ? AND reference_no = ?');
            $chk->execute([$clientId, $referenceNo]);
            if ((int)$chk->fetchColumn() > 0) {
                // Delete uploaded file if we failed due to duplicate reference
                if ($attachment) {
                    $this->deleteAttachmentFile($attachment);
                }
                $this->redirect('/client-ledger/' . $clientId . '?error=duplicate_reference');
            }
        }

        $db->beginTransaction();
        try {
            $ins = $db->prepare("
                INSERT INTO client_ledgers
                    (client_id, entry_type, direction, amount, balance_after, description, reference_no, attachment, transaction_date, created_by)
                VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?)
            ");
            $ins->execute([
                $clientId, $entryType, $direction, $amount,
                $description ?: null, $referenceNo ?: null, $attachment, $txDate,
                $_SESSION['user_id'] ?? null,
            ]);

            // Recalculate balance_after for ALL entries in chronological order
            // so inserting a back-dated entry keeps every subsequent balance correct
            $all = $db->prepare("
                SELECT id, direction, amount FROM client_ledgers
                WHERE client_id = ?
                ORDER BY transaction_date ASC, id ASC
                FOR UPDATE
            ");
            $all->execute([$clientId]);
            $running = 0.0;
            $updBal  = $db->prepare('UPDATE client_ledgers SET balance_after = ? WHERE id = ?');
            foreach ($all->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $running += $r['direction'] === 'credit' ? floatval($r['amount']) : -floatval($r['amount']);
                $updBal->execute([$running, $r['id']]);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            error_log('ClientLedger store error: ' . $e->getMessage());
            if ($attachment) $this->deleteAttachmentFile($attachment);
            $this->redirect('/client-ledger/' . $clientId . '?error=save_failed');
        }

        $this->redirect('/client-ledger/' . $clientId . '?success=1');
    }

public function createClient(): void {
        $this->requireAdmin();
        if (!$this->isPost()) {
            $this->redirect('/client-ledger');
        }

        $name        = trim($_POST['name'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');

        if (!$name) {
            $this->redirect('/client-ledger?error=name_required');
        }

        $db = $this->getDb();
        $this->ensureTables($db);

        $stmt = $db->prepare("INSERT INTO clients (name, company_name, email, phone) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $companyName ?: null, $email ?: null, $phone ?: null]);

        $this->redirect('/client-ledger?success=client_created');
    }

    public function updateClient(): void {
        $this->requireAdmin();
        if (!$this->isPost()) {
            $this->json(['success' => false, 'error' => 'Invalid request'], 400);
        }

        $clientId   = (int)($_POST['client_id'] ?? 0);
        $name       = trim($_POST['name'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');

        if (!$clientId || !$name) {
            $this->json(['success' => false, 'error' => 'Invalid input'], 400);
        }

        $db = $this->getDb();
        $this->ensureTables($db);

        // Check if client exists
        $stmt = $db->prepare('SELECT id FROM clients WHERE id = ?');
        $stmt->execute([$clientId]);
        if (!$stmt->fetch()) {
            $this->json(['success' => false, 'error' => 'Client not found'], 404);
        }

        // Update client
        $upd = $db->prepare("UPDATE clients SET name = ?, company_name = ?, email = ?, phone = ? WHERE id = ?");
        $upd->execute([$name, $companyName ?: null, $email ?: null, $phone ?: null, $clientId]);

        $this->json(['success' => true]);
    }
}
?>
