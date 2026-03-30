<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class ProjectSubcategoryController extends Controller {

    private function ensureTable($db) {
        $db->exec("CREATE TABLE IF NOT EXISTS project_subcategories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            name VARCHAR(200) NOT NULL,
            description TEXT NULL,
            budget DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            opening_utilised DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status ENUM('active','completed','on_hold') DEFAULT 'active',
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_project_id (project_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        try { $db->exec("ALTER TABLE project_subcategories ADD COLUMN opening_utilised DECIMAL(12,2) NOT NULL DEFAULT 0.00"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE expenses ADD COLUMN subcategory_id INT NULL"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE advances ADD COLUMN subcategory_id INT NULL"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE projects ADD COLUMN budget DECIMAL(12,2) NOT NULL DEFAULT 0.00"); } catch (Exception $e) {}
    }

    public function index($projectId = null) {
        $this->requireAuth();
        if (!in_array($_SESSION['role'] ?? '', ['owner', 'company_owner', 'admin'])) {
            header('Location: /ergon/dashboard'); exit;
        }

        $db = Database::connect();
        $this->ensureTable($db);

        $projects = $db->query("SELECT id, name, budget FROM projects WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

        $selectedProject = null;
        $subcategories = [];

        if ($projectId) {
            $stmt = $db->prepare("SELECT id, name, budget FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $selectedProject = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($selectedProject) {
                $stmt = $db->prepare("
                    SELECT
                        ps.*,
                        COALESCE(SUM(CASE WHEN e.status = 'paid' THEN e.amount ELSE 0 END), 0) as total_expenses,
                        COALESCE(SUM(CASE WHEN a.status = 'paid' THEN COALESCE(a.approved_amount, a.amount) ELSE 0 END), 0) as total_advances,
                        COUNT(DISTINCT e.id) as expense_count,
                        COUNT(DISTINCT a.id) as advance_count
                    FROM project_subcategories ps
                    LEFT JOIN expenses e ON e.subcategory_id = ps.id AND e.status = 'paid'
                    LEFT JOIN advances a ON a.subcategory_id = ps.id AND a.status = 'paid'
                    WHERE ps.project_id = ?
                    GROUP BY ps.id
                    ORDER BY ps.created_at ASC
                ");
                $stmt->execute([$projectId]);
                $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        $this->view('project_subcategories/index', [
            'projects' => $projects,
            'selectedProject' => $selectedProject,
            'subcategories' => $subcategories,
            'active_page' => 'projects',
        ]);
    }

    public function store() {
        $this->requireAuth();
        header('Content-Type: application/json');

        if (!in_array($_SESSION['role'] ?? '', ['owner', 'company_owner', 'admin'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit;
        }

        $projectId = intval($_POST['project_id'] ?? 0);
        $name      = trim($_POST['name'] ?? '');
        $budget    = floatval($_POST['budget'] ?? 0);
        $opening   = floatval($_POST['opening_utilised'] ?? 0);
        $desc      = trim($_POST['description'] ?? '');

        if (!$projectId || !$name) {
            echo json_encode(['success' => false, 'error' => 'Project and name are required']); exit;
        }

        try {
            $db = Database::connect();
            $this->ensureTable($db);
            $stmt = $db->prepare("INSERT INTO project_subcategories (project_id, name, description, budget, opening_utilised, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$projectId, $name, $desc, $budget, $opening, $_SESSION['user_id']]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function update($id = null) {
        $this->requireAuth();
        header('Content-Type: application/json');

        if (!in_array($_SESSION['role'] ?? '', ['owner', 'company_owner', 'admin'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit;
        }

        $name     = trim($_POST['name'] ?? '');
        $budget   = floatval($_POST['budget'] ?? 0);
        $opening  = floatval($_POST['opening_utilised'] ?? 0);
        $desc     = trim($_POST['description'] ?? '');
        $status   = $_POST['status'] ?? 'active';

        try {
            $db = Database::connect();
            $stmt = $db->prepare("UPDATE project_subcategories SET name = ?, description = ?, budget = ?, opening_utilised = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $desc, $budget, $opening, $status, $id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function delete($id = null) {
        $this->requireAuth();
        header('Content-Type: application/json');

        if (!in_array($_SESSION['role'] ?? '', ['owner', 'company_owner', 'admin'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit;
        }

        try {
            $db = Database::connect();
            // Unlink expenses and advances before deleting
            $db->prepare("UPDATE expenses SET subcategory_id = NULL WHERE subcategory_id = ?")->execute([$id]);
            $db->prepare("UPDATE advances SET subcategory_id = NULL WHERE subcategory_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM project_subcategories WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // API: return subcategories for a project (used by expense/advance forms)
    public function byProject($projectId = null) {
        header('Content-Type: application/json');
        if (!$projectId) { echo json_encode([]); exit; }

        try {
            $db = Database::connect();
            $this->ensureTable($db);
            $stmt = $db->prepare("SELECT id, name, budget FROM project_subcategories WHERE project_id = ? AND status = 'active' ORDER BY name");
            $stmt->execute([$projectId]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit;
    }
}
?>
