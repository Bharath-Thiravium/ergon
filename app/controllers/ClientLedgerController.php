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

        $outstanding = $totalCredits - $totalDebits;

        $this->view('client_ledger/ledger', [
            'title'          => 'Ledger: ' . htmlspecialchars($client['name']),
            'active_page'    => 'client_ledger',
            'client'         => $client,
            'entries'        => $entries,
            'totalCredits'   => $totalCredits,
            'totalDebits'    => $totalDebits,
            'outstanding'    => $outstanding,
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
            'sale'            => 'credit',
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
            'sale'            => 'credit',
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

    public function downloadCsv(int $clientId): void {
        $this->requireAdmin();
        $db = $this->getDb();
        $this->ensureTables($db);

        $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$client) { http_response_code(404); exit('Client not found'); }

        $entries = $this->fetchLedgerEntries($db, $clientId);

        // Compute totals
        $totalCredits = 0.0;
        $totalDebits  = 0.0;
        foreach ($entries as $e) {
            if ($e['direction'] === 'credit') $totalCredits += floatval($e['amount']);
            else                               $totalDebits  += floatval($e['amount']);
        }
        $outstanding    = $totalCredits - $totalDebits;
        $currentBalance = empty($entries) ? 0.0 : floatval($entries[0]['balance_after']);

        $clientSlug = preg_replace('/[^a-z0-9]+/', '_', strtolower($client['name']));
        $filename   = 'ledger_' . $clientSlug . '_' . date('Ymd') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // ── Header block (financial ledger standard) ──────────────────────
        fputcsv($out, ['CUSTOMER LEDGER STATEMENT']);
        fputcsv($out, []);
        fputcsv($out, ['Client Name',   $client['name']]);
        if (!empty($client['company_name'])) {
            fputcsv($out, ['Company',   $client['company_name']]);
        }
        if (!empty($client['email']))   fputcsv($out, ['Email',   $client['email']]);
        if (!empty($client['phone']))   fputcsv($out, ['Phone',   $client['phone']]);
        fputcsv($out, ['Generated On',  date('d M Y, h:i A')]);
        fputcsv($out, ['Total Entries', count($entries)]);
        fputcsv($out, []);

        // ── Summary block ─────────────────────────────────────────────────
        fputcsv($out, ['ACCOUNT SUMMARY']);
        fputcsv($out, ['Total Credits (Dr)',  number_format($totalCredits, 2)]);
        fputcsv($out, ['Total Debits (Cr)',   number_format($totalDebits,  2)]);
        fputcsv($out, ['Outstanding Balance', number_format(abs($outstanding), 2) . ' ' . ($outstanding >= 0 ? 'Cr' : 'Dr')]);
        fputcsv($out, ['Closing Balance',     number_format(abs($currentBalance), 2) . ' ' . ($currentBalance >= 0 ? 'Cr' : 'Dr')]);
        fputcsv($out, []);

        // ── Column headers ────────────────────────────────────────────────
        fputcsv($out, [
            'Date',
            'Particulars',
            'Reference No.',
            'Vch Type',
            'Debit (Dr)',
            'Credit (Cr)',
            'Balance',
            'Dr/Cr',
        ]);

        // ── Entries (chronological ASC for ledger standard) ───────────────
        $chronological = array_reverse($entries);
        $typeLabels = [
            'payment_received' => 'Payment Received',
            'payment_sent'     => 'Payment Sent',
            'adjustment'       => 'Adjustment',
            'invoice_raised'   => 'Invoice Raised',
            'invoice_received' => 'Invoice Received',
            'purchase'         => 'Purchase',
            'sale'             => 'Sale',
            'expense'          => 'Expense',
            'income'           => 'Income',
            'opening_balance'  => 'Opening Balance',
            'closing_balance'  => 'Closing Balance',
            'fees_paid'        => 'Fees Paid',
            'penalties_paid'   => 'Penalties Paid',
        ];
        foreach ($chronological as $e) {
            $bal    = floatval($e['balance_after']);
            $drCr   = $bal >= 0 ? 'Cr' : 'Dr';
            $debit  = $e['direction'] === 'debit'  ? number_format($e['amount'], 2) : '';
            $credit = $e['direction'] === 'credit' ? number_format($e['amount'], 2) : '';
            fputcsv($out, [
                date('d-M-Y', strtotime($e['transaction_date'])),
                $e['description'] ?? '',
                $e['reference_no'] ?? '',
                $typeLabels[$e['entry_type']] ?? $e['entry_type'],
                $debit,
                $credit,
                number_format(abs($bal), 2),
                $drCr,
            ]);
        }

        // ── Footer totals ─────────────────────────────────────────────────
        fputcsv($out, []);
        fputcsv($out, [
            'TOTALS', '', '', '',
            number_format($totalDebits,  2),
            number_format($totalCredits, 2),
            number_format(abs($currentBalance), 2),
            $currentBalance >= 0 ? 'Cr' : 'Dr',
        ]);

        fclose($out);
        exit;
    }

    public function downloadPdf(int $clientId): void {
        $this->requireAdmin();
        $db = $this->getDb();
        $this->ensureTables($db);

        $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$client) { http_response_code(404); exit('Client not found'); }

        $entries = $this->fetchLedgerEntries($db, $clientId);

        $totalCredits = 0.0;
        $totalDebits  = 0.0;
        foreach ($entries as $e) {
            if ($e['direction'] === 'credit') $totalCredits += floatval($e['amount']);
            else                               $totalDebits  += floatval($e['amount']);
        }
        $outstanding    = $totalCredits - $totalDebits;
        $currentBalance = empty($entries) ? 0.0 : floatval($entries[0]['balance_after']);
        $chronological  = array_reverse($entries);

        $typeLabels = [
            'payment_received' => 'Payment Received',
            'payment_sent'     => 'Payment Sent',
            'adjustment'       => 'Adjustment',
            'invoice_raised'   => 'Invoice Raised',
            'invoice_received' => 'Invoice Received',
            'purchase'         => 'Purchase',
            'sale'             => 'Sale',
            'expense'          => 'Expense',
            'income'           => 'Income',
            'opening_balance'  => 'Opening Balance',
            'closing_balance'  => 'Closing Balance',
            'fees_paid'        => 'Fees Paid',
            'penalties_paid'   => 'Penalties Paid',
        ];

        $clientSlug = preg_replace('/[^a-z0-9]+/', '_', strtolower($client['name']));
        $filename   = 'ledger_' . $clientSlug . '_' . date('Ymd') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store');
        header('Pragma: no-cache');

        // ── Pure-PHP PDF writer (no external library) ─────────────────────
        // Uses PDF 1.4 spec: manual object construction.
        $pdf = new class($client, $entries, $chronological, $typeLabels,
                         $totalCredits, $totalDebits, $outstanding, $currentBalance) {

            private array  $objects  = [];
            private array  $offsets  = [];
            private string $buf      = '';
            private int    $objCount = 0;

            // page geometry (A4 portrait, points)
            private float $pw = 595.28;
            private float $ph = 841.89;
            private float $ml = 36.0;   // margin left
            private float $mr = 36.0;   // margin right
            private float $mt = 36.0;   // margin top
            private float $mb = 36.0;   // margin bottom

            // column widths — 7 cols, total = pw(595.28) - ml(36) - mr(36) = 523.28
            // Validated against real Helvetica AFM glyph widths at fs=7.0
            // Date=50 | Particulars=155 | Reference=62 | VchType=72 | Debit=58 | Credit=58 | Balance=68.28
            private array $cols = [50.0, 155.0, 62.0, 72.0, 58.0, 58.0, 68.28];

            private array  $client;
            private array  $entries;
            private array  $chrono;
            private array  $typeLabels;
            private float  $totalCredits;
            private float  $totalDebits;
            private float  $outstanding;
            private float  $currentBalance;

            public function __construct($client, $entries, $chrono, $typeLabels,
                                        $tc, $td, $out, $cb) {
                $this->client         = $client;
                $this->entries        = $entries;
                $this->chrono         = $chrono;
                $this->typeLabels     = $typeLabels;
                $this->totalCredits   = $tc;
                $this->totalDebits    = $td;
                $this->outstanding    = $out;
                $this->currentBalance = $cb;
            }

            // ── helpers ──────────────────────────────────────────────────
            private function esc(string $s): string {
                return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
            }
            private function fmt(float $n): string {
                return number_format(abs($n), 2);
            }
            private function drCr(float $n): string {
                return $n >= 0 ? 'Cr' : 'Dr';
            }
            private function truncate(string $s, int $max): string {
                return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 1) . '...' : $s;
            }

            // ── wrap text to fit within colWidth, return array of lines ──
            private function wrapText(string $s, float $colWidth, float $fs, float $pad): array {
                $avail = $colWidth - $pad * 2;
                $words = explode(' ', $s);
                $lines = [];
                $line  = '';
                foreach ($words as $word) {
                    $test = $line === '' ? $word : $line . ' ' . $word;
                    if ($this->strW($test, $fs) <= $avail) {
                        $line = $test;
                    } else {
                        if ($line !== '') $lines[] = $line;
                        // if single word is too wide, hard-break it
                        while ($this->strW($word, $fs) > $avail) {
                            $cut = '';
                            for ($i = 0; $i < mb_strlen($word); $i++) {
                                $ch = mb_substr($word, $i, 1);
                                if ($this->strW($cut . $ch, $fs) > $avail) break;
                                $cut .= $ch;
                            }
                            $lines[] = $cut;
                            $word = mb_substr($word, mb_strlen($cut));
                        }
                        $line = $word;
                    }
                }
                if ($line !== '') $lines[] = $line;
                return $lines ?: [''];
            }

            // ── PDF object helpers ────────────────────────────────────────
            private function addObj(string $content): int {
                $this->objCount++;
                $this->objects[$this->objCount] = $content;
                return $this->objCount;
            }

            // ── stream a page's content ───────────────────────────────────
            private function buildStream(string $ops): int {
                $len = strlen($ops);
                return $this->addObj("<< /Length $len >>\nstream\n" . $ops . "\nendstream");
            }

            // ── Helvetica glyph-width table (1/1000 em units, covers ASCII 32-126)
            // Source: Adobe Helvetica AFM. Used for accurate right-alignment.
            private array $hw = [
                278,278,355,556,556,889,667,222,333,333,389,584,278,333,
                278,278,556,556,556,556,556,556,556,556,556,556,278,278,
                584,584,584,556,1015,667,667,722,722,667,611,778,722,278,
                500,667,556,833,722,778,667,778,722,667,611,722,667,944,
                667,667,611,278,278,278,469,556,222,556,556,500,556,556,
                278,556,556,222,222,500,222,833,556,556,556,556,333,500,
                278,556,500,722,500,500,500,334,260,334,584
            ];

            // Return string width in points at given font size
            private function strW(string $s, float $fs): float {
                $w = 0.0;
                $len = strlen($s);
                for ($i = 0; $i < $len; $i++) {
                    $c = ord($s[$i]);
                    $idx = $c - 32;
                    $w += ($idx >= 0 && $idx < count($this->hw)) ? $this->hw[$idx] : 556;
                }
                return $w * $fs / 1000.0;
            }

            // ── draw one ledger row with wrapping support, return new y ──
            private function drawRow(
                string &$ops, float $y, float $rowH,
                string $date, string $particulars, string $ref,
                string $vch, string $debit, string $credit,
                string $balance, string $drCr,
                bool $isHeader = false, bool $isTotal = false
            ): float {
                $x       = $this->ml;
                $tableW  = $this->pw - $this->ml - $this->mr;
                $fs      = $isHeader ? 7.5 : 7.0;
                $fontCmd = $isHeader || $isTotal ? '/F2' : '/F1';
                $pad     = 4.0;
                $lineH   = $fs * 1.4; // line height for wrapped lines

                // wrap Particulars (col 1) only for data rows
                $partLines = ($isHeader || $isTotal)
                    ? [$particulars]
                    : $this->wrapText($particulars, $this->cols[1], $fs, $pad);
                $numLines = count($partLines);
                $actualRowH = max($rowH, $pad * 2 + $numLines * $lineH);

                // ── background fill ───────────────────────────────────────
                if ($isHeader) {
                    $ops .= "0.18 0.36 0.60 rg\n";
                    $ops .= "$x " . ($y - $actualRowH) . " $tableW $actualRowH re f\n";
                    $ops .= "0 0 0 rg\n";
                } elseif ($isTotal) {
                    $ops .= "0.91 0.91 0.91 rg\n";
                    $ops .= "$x " . ($y - $actualRowH) . " $tableW $actualRowH re f\n";
                    $ops .= "0 0 0 rg\n";
                }

                // ── outer row border ──────────────────────────────────────
                $ops .= "0.75 0.75 0.75 RG 0.3 w\n";
                $ops .= "$x " . ($y - $actualRowH) . " $tableW $actualRowH re S\n";

                // ── vertical column separators ────────────────────────────
                $sepColor = $isHeader ? '0.40 0.55 0.75' : '0.82 0.82 0.82';
                $ops .= "$sepColor RG 0.3 w\n";
                $cx = $x;
                foreach ($this->cols as $ci => $cw) {
                    if ($ci === count($this->cols) - 1) break;
                    $cx += $cw;
                    $ops .= "$cx " . ($y - $actualRowH) . " m $cx $y l S\n";
                }
                $ops .= "0 0 0 RG\n";

                // ── single-line cells (all except Particulars) ────────────
                $balColor = (strpos($drCr, 'Dr') !== false) ? '0.72 0.08 0.08' : '0.02 0.50 0.28';
                // vertically centre single-line text in the row
                $textY = $y - $actualRowH / 2 - $fs * 0.35;

                $singleCells  = [$date, null, $ref, $vch, $debit, $credit, $balance . ($balance !== '' ? ' ' . $drCr : '')];
                $aligns       = ['L', 'L', 'L', 'L', 'R', 'R', 'R'];
                $cx = $x;
                foreach ($singleCells as $i => $cell) {
                    $cw = $this->cols[$i];
                    if ($cell === null || $cell === '') { $cx += $cw; continue; }

                    if ($isHeader)       $fg = '1 1 1';
                    elseif ($isTotal)    $fg = '0.15 0.15 0.15';
                    elseif ($i === 4)    $fg = '0.72 0.08 0.08';
                    elseif ($i === 5)    $fg = '0.02 0.50 0.28';
                    elseif ($i === 6)    $fg = $balColor;
                    else                 $fg = '0.10 0.10 0.10';
                    $ops .= "$fg rg\n";

                    if ($aligns[$i] === 'R') {
                        $tw = $this->strW($cell, $fs);
                        $tx = $cx + $cw - $pad - $tw;
                        if ($tx < $cx + 1.0) $tx = $cx + 1.0;
                    } else {
                        $tx = $cx + $pad;
                    }
                    $ops .= "BT $fontCmd $fs Tf $tx $textY Td (" . $this->esc($cell) . ") Tj ET\n";
                    $cx += $cw;
                }

                // ── Particulars: multi-line wrapped ───────────────────────
                $fg = $isHeader ? '1 1 1' : ($isTotal ? '0.15 0.15 0.15' : '0.10 0.10 0.10');
                $ops .= "$fg rg\n";
                $partX  = $x + $this->cols[0] + $pad;
                // top of text block, vertically centred
                $topY   = $y - ($actualRowH - $numLines * $lineH) / 2 - $lineH * 0.75;
                foreach ($partLines as $li => $line) {
                    $lineY = $topY - $li * $lineH;
                    $ops .= "BT $fontCmd $fs Tf $partX $lineY Td (" . $this->esc($line) . ") Tj ET\n";
                }

                return $y - $actualRowH;
            }

            // ── build full PDF ────────────────────────────────────────────
            public function render(): string {
                $f1 = $this->addObj("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>");
                $f2 = $this->addObj("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>");

                $tableW   = $this->pw - $this->ml - $this->mr; // 523.28
                $pages    = [];
                $rowH     = 15.0;
                $fs       = 7.0;
                $pad      = 4.0;
                $lineH    = $fs * 1.4;
                $firstPageHeaderH = 160.0;
                $contPageHeaderH  = 22.0;
                $colHdrH  = $rowH;
                $usableH  = $this->ph - $this->mt - $this->mb;

                // pre-compute actual height of each data row
                $rowHeights = [];
                foreach ($this->chrono as $e) {
                    $desc  = $e['description'] ?? '';
                    $lines = $this->wrapText($desc, $this->cols[1], $fs, $pad);
                    $rowHeights[] = max($rowH, $pad * 2 + count($lines) * $lineH);
                }

                // paginate by actual heights
                $chunks   = [];
                $chunkH   = [];
                $pageRows = [];
                $pageUsed = $firstPageHeaderH + $colHdrH;
                $avail    = $usableH;
                $cur      = [];
                foreach ($this->chrono as $idx => $e) {
                    $rh = $rowHeights[$idx];
                    $headerH = count($chunks) === 0 ? $firstPageHeaderH : $contPageHeaderH;
                    $used    = $headerH + $colHdrH + array_sum(array_map(fn($i) => $rowHeights[$i], array_keys($cur)));
                    if (!empty($cur) && $used + $rh > $avail) {
                        $chunks[] = $cur;
                        $cur = [];
                    }
                    $cur[$idx] = $e;
                }
                $chunks[] = $cur; // last page (may be empty)
                if (empty($this->chrono)) $chunks = [[]];
                $totalPages = count($chunks);

                foreach ($chunks as $pageIdx => $chunk) {
                    $ops = '';
                    $y   = $this->ph - $this->mt;

                    // ── Page header ─────────────────────────────────────────
                    if ($pageIdx === 0) {
                        // title bar
                        $ops .= "0.18 0.36 0.60 rg\n";
                        $ops .= $this->ml . " " . ($y - 24) . " $tableW 24 re f\n";
                        $ops .= "1 1 1 rg\n";
                        $titleText = 'CUSTOMER LEDGER STATEMENT';
                        $titleW    = $this->strW($titleText, 13);
                        $titleX    = $this->ml + ($tableW - $titleW) / 2;
                        $ops .= "BT /F2 13 Tf $titleX " . ($y - 16) . " Td ($titleText) Tj ET\n";
                        $ops .= "0 0 0 rg\n";
                        $y -= 38;

                        // client info (2-column layout)
                        $lx = $this->ml;
                        $vx = $this->ml + 52;
                        $ops .= "BT /F2 8.5 Tf $lx $y Td (Client:) Tj ET\n";
                        $ops .= "BT /F1 8.5 Tf $vx $y Td (" . $this->esc($this->client['name']) . ") Tj ET\n";
                        $y -= 13;
                        if (!empty($this->client['company_name'])) {
                            $ops .= "BT /F2 8 Tf $lx $y Td (Company:) Tj ET\n";
                            $ops .= "BT /F1 8 Tf $vx $y Td (" . $this->esc($this->client['company_name']) . ") Tj ET\n";
                            $y -= 12;
                        }
                        if (!empty($this->client['email'])) {
                            $ops .= "BT /F2 8 Tf $lx $y Td (Email:) Tj ET\n";
                            $ops .= "BT /F1 8 Tf $vx $y Td (" . $this->esc($this->client['email']) . ") Tj ET\n";
                            $y -= 12;
                        }
                        $ops .= "BT /F2 8 Tf $lx $y Td (Generated:) Tj ET\n";
                        $ops .= "BT /F1 8 Tf $vx $y Td (" . date('d M Y, h:i A') . ") Tj ET\n";
                        $y -= 10;

                        // Summary box
                        $boxH = 50.0;
                        $ops .= "0.94 0.96 1.00 rg\n";
                        $ops .= $this->ml . " " . ($y - $boxH) . " $tableW $boxH re f\n";
                        $ops .= "0.18 0.36 0.60 RG 0.6 w\n";
                        $ops .= $this->ml . " " . ($y - $boxH) . " $tableW $boxH re S\n";
                        // vertical divider at midpoint
                        $midX = $this->ml + $tableW / 2;
                        $ops .= "0.70 0.78 0.88 RG 0.4 w\n";
                        $ops .= "$midX " . ($y - $boxH) . " m $midX $y l S\n";
                        // horizontal divider between row1 and row2
                        $hDivY = $y - $boxH / 2;
                        $ops .= $this->ml . " $hDivY m " . ($this->ml + $tableW) . " $hDivY l S\n";
                        $ops .= "0 0 0 RG\n";

                        // fixed X positions
                        $lx1 = $this->ml + 8;               // left-half label start
                        $vx1 = $midX - 8;                   // left-half value right-edge
                        $lx2 = $midX + 8;                   // right-half label start
                        $vx2 = $this->ml + $tableW - 8;     // right-half value right-edge

                        // row 1 baseline (upper half centre)
                        $sy1 = $y - $boxH * 0.28;
                        // row 2 baseline (lower half centre)
                        $sy2 = $y - $boxH * 0.72;

                        // row 1 — Total Credits | Total Debits
                        $ops .= "0.18 0.36 0.60 rg\n";
                        $ops .= "BT /F2 7.5 Tf $lx1 $sy1 Td (Total Credits:) Tj ET\n";
                        $v = 'Rs.' . $this->fmt($this->totalCredits) . ' Cr';
                        $tw = $this->strW($v, 7.5);
                        $ops .= "0.02 0.50 0.28 rg\n";
                        $ops .= "BT /F2 7.5 Tf " . ($vx1 - $tw) . " $sy1 Td ($v) Tj ET\n";

                        $ops .= "0.18 0.36 0.60 rg\n";
                        $ops .= "BT /F2 7.5 Tf $lx2 $sy1 Td (Total Debits:) Tj ET\n";
                        $v = 'Rs.' . $this->fmt($this->totalDebits) . ' Dr';
                        $tw = $this->strW($v, 7.5);
                        $ops .= "0.72 0.08 0.08 rg\n";
                        $ops .= "BT /F2 7.5 Tf " . ($vx2 - $tw) . " $sy1 Td ($v) Tj ET\n";

                        // row 2 — Outstanding | Closing Balance
                        $ops .= "0.18 0.36 0.60 rg\n";
                        $ops .= "BT /F2 7.5 Tf $lx1 $sy2 Td (Outstanding:) Tj ET\n";
                        $v = 'Rs.' . $this->fmt($this->outstanding) . ' ' . $this->drCr($this->outstanding);
                        $tw = $this->strW($v, 7.5);
                        $oc = $this->outstanding >= 0 ? '0.02 0.50 0.28' : '0.72 0.08 0.08';
                        $ops .= "$oc rg\n";
                        $ops .= "BT /F2 7.5 Tf " . ($vx1 - $tw) . " $sy2 Td ($v) Tj ET\n";

                        $ops .= "0.18 0.36 0.60 rg\n";
                        $ops .= "BT /F2 7.5 Tf $lx2 $sy2 Td (Closing Balance:) Tj ET\n";
                        $v = 'Rs.' . $this->fmt($this->currentBalance) . ' ' . $this->drCr($this->currentBalance);
                        $tw = $this->strW($v, 7.5);
                        $bc = $this->currentBalance >= 0 ? '0.02 0.50 0.28' : '0.72 0.08 0.08';
                        $ops .= "$bc rg\n";
                        $ops .= "BT /F2 7.5 Tf " . ($vx2 - $tw) . " $sy2 Td ($v) Tj ET\n";
                        $ops .= "0 0 0 rg\n";

                        $y -= ($boxH + 8);
                    } else {
                        // continuation banner
                        $ops .= "0.18 0.36 0.60 rg\n";
                        $ops .= $this->ml . " " . ($y - 18) . " $tableW 18 re f\n";
                        $ops .= "1 1 1 rg\n";
                        $ops .= "BT /F2 8.5 Tf " . ($this->ml + 5) . " " . ($y - 12) . " Td (CUSTOMER LEDGER  -  " . $this->esc($this->client['name']) . "  -  Continued) Tj ET\n";
                        $ops .= "0 0 0 rg\n";
                        $y -= 22;
                    }

                    // ── Column header row ──────────────────────────────────
                    $y = $this->drawRow($ops, $y, $rowH,
                        'Date', 'Particulars', 'Reference No.', 'Vch Type',
                        'Debit (Dr)', 'Credit (Cr)', 'Balance', 'Dr/Cr', true);

                    // ── Data rows ──────────────────────────────────────────
                    foreach ($chunk as $e) {
                        $bal    = floatval($e['balance_after']);
                        $debit  = $e['direction'] === 'debit'  ? 'Rs.' . $this->fmt($e['amount']) : '';
                        $credit = $e['direction'] === 'credit' ? 'Rs.' . $this->fmt($e['amount']) : '';
                        $vch    = $this->typeLabels[$e['entry_type']] ?? $e['entry_type'];
                        $y = $this->drawRow($ops, $y, $rowH,
                            date('d-M-Y', strtotime($e['transaction_date'])),
                            $e['description'] ?? '',
                            $this->truncate($e['reference_no'] ?? '', 16),
                            $this->truncate($vch, 20),
                            $debit, $credit,
                            'Rs.' . $this->fmt($bal), $this->drCr($bal));
                    }

                    // ── Totals row (last page only) ────────────────────────
                    if ($pageIdx === $totalPages - 1) {
                        $y -= 4;
                        $y = $this->drawRow($ops, $y, $rowH,
                            'TOTALS', '', '', '',
                            'Rs.' . $this->fmt($this->totalDebits),
                            'Rs.' . $this->fmt($this->totalCredits),
                            'Rs.' . $this->fmt($this->currentBalance),
                            $this->drCr($this->currentBalance),
                            false, true);
                    }

                    // ── Page number ────────────────────────────────────────
                    $pgText = 'Page ' . ($pageIdx + 1) . ' of ' . $totalPages;
                    $pgW    = $this->strW($pgText, 7.0);
                    $pgX    = $this->ml + ($tableW - $pgW) / 2;
                    $ops .= "0.50 0.50 0.50 rg\n";
                    $ops .= "BT /F1 7 Tf $pgX " . ($this->mb - 6) . " Td ($pgText) Tj ET\n";
                    $ops .= "0 0 0 rg\n";

                    $streamId = $this->buildStream($ops);
                    $pageId   = $this->addObj(
                        "<< /Type /Page /Parent 3 0 R\n"
                        . "   /MediaBox [0 0 " . $this->pw . " " . $this->ph . "]\n"
                        . "   /Contents $streamId 0 R\n"
                        . "   /Resources << /Font << /F1 $f1 0 R /F2 $f2 0 R >> >>\n"
                        . ">>"
                    );
                    $pages[] = $pageId;
                }

                // Pages dict — obj number is whatever addObj assigns next
                $kidsStr = implode(' 0 R ', $pages) . ' 0 R';
                $pagesId = $this->addObj(
                    "<< /Type /Pages /Kids [$kidsStr] /Count " . count($pages) . " >>"
                );
                // Catalog
                $catId = $this->addObj("<< /Type /Catalog /Pages $pagesId 0 R >>");

                // Patch each Page object's /Parent to point at the real pagesId
                foreach ($pages as $pid) {
                    $this->objects[$pid] = str_replace(
                        '/Parent 3 0 R',
                        "/Parent $pagesId 0 R",
                        $this->objects[$pid]
                    );
                }

                // ── Serialise ──────────────────────────────────────────────
                $out  = "%PDF-1.4\n";
                $xref = [];
                foreach ($this->objects as $id => $body) {
                    $xref[$id] = strlen($out);
                    $out .= "$id 0 obj\n$body\nendobj\n";
                }

                $xrefOffset = strlen($out);
                $out .= "xref\n0 " . ($this->objCount + 1) . "\n";
                $out .= "0000000000 65535 f \n";
                for ($i = 1; $i <= $this->objCount; $i++) {
                    $out .= str_pad($xref[$i] ?? 0, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
                }
                $out .= "trailer\n<< /Size " . ($this->objCount + 1) . " /Root $catId 0 R >>\n";
                $out .= "startxref\n$xrefOffset\n%%EOF";
                return $out;
            }
        };

        echo $pdf->render();
        exit;
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
