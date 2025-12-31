<?php
require_once __DIR__ . '/../core/Controller.php';

class ApprovedExpensesController extends Controller {
    public function index() {
        $this->requireAuth();
        // only admin/owner
        if (!in_array($_SESSION['role'] ?? '', ['admin','owner'])) {
            header('Location: /ergon/expenses?error=Unauthorized');
            exit;
        }

        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();

            $where = [];
            $params = [];

            // Filter: status=paid|unpaid
            if (!empty($_GET['status'])) {
                if ($_GET['status'] === 'paid') {
                    $where[] = 'paid_at IS NOT NULL';
                } elseif ($_GET['status'] === 'unpaid') {
                    $where[] = 'paid_at IS NULL';
                }
            }

            // Filter: deducted_from_advance
            if (!empty($_GET['deducted']) && $_GET['deducted'] === '1') {
                $where[] = "LOWER(category) = 'deduct_from_advance' OR LOWER(category) = 'deduct from advance'";
            }

              $sql = 'SELECT ae.*, u.name as user_name, u2.name as approved_by_name'
                  . ' FROM approved_expenses ae'
                  . ' JOIN users u ON ae.user_id = u.id'
                  . ' LEFT JOIN users u2 ON ae.approved_by = u2.id';
            if (!empty($where)) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY ae.created_at DESC';

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('approved_expenses/index', ['rows' => $rows, 'active_page' => 'approved_expenses']);
        } catch (Exception $e) {
            error_log('ApprovedExpenses index error: ' . $e->getMessage());
            header('Location: /ergon/expenses?error=Unable to load approved expenses');
            exit;
        }
    }
}

?>
