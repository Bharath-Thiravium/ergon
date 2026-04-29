<?php
require_once __DIR__ . '/../core/Controller.php';

class ClientLedgerController extends Controller {

    private function getDb(): PDO {
        require_once __DIR__ . '/../config/database.php';
        return Database::connect();
    }

    private function ensureTables(PDO $db): void {
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
        $db->exec("
            CREATE TABLE IF NOT EXISTS client_ledgers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                client_id INT NOT NULL,
                entry_type ENUM('payment_received','payment_sent','adjustment') NOT NULL,
                direction ENUM('debit','credit') NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                balance_after DECIMAL(12,2) NOT NULL,
                description TEXT,
                reference_no VARCHAR(100),
                transaction_date DATE NOT NULL,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES clients(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
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

        $totalCredits = 0.0;
        $totalDebits  = 0.0;
        foreach ($entries as $e) {
            if ($e['direction'] === 'credit') $totalCredits += $e['amount'];
            else                               $totalDebits  += $e['amount'];
        }
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
            SELECT transaction_date, entry_type, description, reference_no,
                   amount, direction, balance_after
            FROM client_ledgers
            WHERE client_id = :client_id
            ORDER BY transaction_date DESC, id DESC
        ");
        $stmt->execute([':client_id' => $clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        $validTypes = ['payment_received', 'payment_sent', 'adjustment'];
        if (!$clientId || !in_array($entryType, $validTypes) || $amount <= 0 || !$txDate) {
            $this->redirect('/client-ledger?error=invalid_input');
        }

        // direction: payment_received = credit (money IN), payment_sent = debit (money OUT)
        // adjustment direction comes from entry_type mapping; for adjustment we use a sub-field
        $directionMap = [
            'payment_received' => 'credit',
            'payment_sent'     => 'debit',
            'adjustment'       => $_POST['adjustment_direction'] ?? 'credit',
        ];
        $direction = $directionMap[$entryType];
        if (!in_array($direction, ['debit', 'credit'])) $direction = 'credit';

        $db = $this->getDb();
        $this->ensureTables($db);

        $db->beginTransaction();
        try {
            // Lock last row for this client to get previous balance
            $stmt = $db->prepare("
                SELECT balance_after FROM client_ledgers
                WHERE client_id = ?
                ORDER BY transaction_date DESC, id DESC
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([$clientId]);
            $prevBalance = floatval($stmt->fetchColumn() ?: 0);

            $newBalance = $direction === 'credit'
                ? $prevBalance + $amount
                : $prevBalance - $amount;

            $ins = $db->prepare("
                INSERT INTO client_ledgers
                    (client_id, entry_type, direction, amount, balance_after, description, reference_no, transaction_date, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([
                $clientId, $entryType, $direction, $amount, $newBalance,
                $description ?: null, $referenceNo ?: null, $txDate,
                $_SESSION['user_id'] ?? null,
            ]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            error_log('ClientLedger store error: ' . $e->getMessage());
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
}
?>
