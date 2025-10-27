<?php
$title = 'Follow-ups Management';
$active_page = 'followups';

// Get follow-ups data
require_once __DIR__ . '/../../app/config/database.php';
$pdo = Database::connect();

$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $stmt = $pdo->prepare("INSERT INTO followups (user_id, title, company_name, contact_person, contact_phone, project_name, follow_up_date, original_date, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $_POST['title'],
            $_POST['company_name'],
            $_POST['contact_person'],
            $_POST['contact_phone'],
            $_POST['project_name'],
            $_POST['follow_up_date'],
            $_POST['follow_up_date'],
            $_POST['description']
        ]);
        header('Location: /ergon/followups');
        exit;
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'complete') {
        $stmt = $pdo->prepare("UPDATE followups SET status = 'completed', completed_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['id'], $user_id]);
        header('Location: /ergon/followups');
        exit;
    }
}

// Get follow-ups
$stmt = $pdo->prepare("SELECT * FROM followups WHERE user_id = ? ORDER BY follow_up_date ASC");
$stmt->execute([$user_id]);
$followups = $stmt->fetchAll();

// Get counts for KPIs
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM followups WHERE user_id = ? AND follow_up_date < ? AND status != 'completed'");
$stmt->execute([$user_id, $today]);
$overdue = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM followups WHERE user_id = ? AND follow_up_date = ? AND status != 'completed'");
$stmt->execute([$user_id, $today]);
$today_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM followups WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed = $stmt->fetchColumn();

ob_start();
?>

<div class="header-actions">
    <button class="btn btn--primary" onclick="document.getElementById('addForm').style.display='block'">Add Follow-up</button>
</div>

<!-- KPI Cards -->
<div class="dashboard-grid">
    <div class="kpi-card kpi-card--danger">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚠️</div>
            <div class="kpi-card__trend">Overdue</div>
        </div>
        <div class="kpi-card__value"><?= $overdue ?></div>
        <div class="kpi-card__label">Overdue Follow-ups</div>
        <div class="kpi-card__status">Needs Attention</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend">Today</div>
        </div>
        <div class="kpi-card__value"><?= $today_count ?></div>
        <div class="kpi-card__label">Due Today</div>
        <div class="kpi-card__status">Scheduled</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">Completed</div>
        </div>
        <div class="kpi-card__value"><?= $completed ?></div>
        <div class="kpi-card__label">Completed</div>
        <div class="kpi-card__status">Done</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📊</div>
            <div class="kpi-card__trend">Total</div>
        </div>
        <div class="kpi-card__value"><?= count($followups) ?></div>
        <div class="kpi-card__label">All Follow-ups</div>
        <div class="kpi-card__status">Active</div>
    </div>
</div>

<!-- Add Form Modal -->
<div id="addForm" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:2rem; border-radius:8px; width:90%; max-width:500px;">
        <h3>Add New Follow-up</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div style="margin-bottom:1rem;">
                <label>Title</label>
                <input type="text" name="title" class="form-input" required style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="margin-bottom:1rem;">
                <label>Company</label>
                <input type="text" name="company_name" class="form-input" style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="margin-bottom:1rem;">
                <label>Contact Person</label>
                <input type="text" name="contact_person" class="form-input" style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="margin-bottom:1rem;">
                <label>Phone</label>
                <input type="tel" name="contact_phone" class="form-input" style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="margin-bottom:1rem;">
                <label>Project</label>
                <input type="text" name="project_name" class="form-input" style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="margin-bottom:1rem;">
                <label>Follow-up Date</label>
                <input type="date" name="follow_up_date" class="form-input" required style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="margin-bottom:1rem;">
                <label>Description</label>
                <textarea name="description" class="form-input" rows="3" style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:4px;"></textarea>
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="document.getElementById('addForm').style.display='none'" class="btn btn--secondary" style="margin-right:1rem;">Cancel</button>
                <button type="submit" class="btn btn--primary">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Follow-ups List -->
<div class="reports-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">My Follow-ups</h2>
        </div>
        <div class="card__body">
            <?php if (empty($followups)): ?>
            <div style="text-align:center; padding:2rem; color:#666;">
                <div style="font-size:3rem; margin-bottom:1rem;">📞</div>
                <h3>No Follow-ups Yet</h3>
                <p>Create your first follow-up to get started</p>
            </div>
            <?php else: ?>
            <?php foreach ($followups as $followup): ?>
            <div class="timeline-item" style="border-left: 4px solid <?= $followup['status'] === 'completed' ? '#10b981' : (strtotime($followup['follow_up_date']) < time() ? '#ef4444' : '#f59e0b') ?>; margin-bottom:1rem;">
                <div class="timeline-date">
                    <?= date('M d, Y', strtotime($followup['follow_up_date'])) ?>
                    <span style="background:<?= $followup['status'] === 'completed' ? '#10b981' : (strtotime($followup['follow_up_date']) < time() ? '#ef4444' : '#f59e0b') ?>; color:white; padding:2px 8px; border-radius:12px; font-size:0.8rem; margin-left:8px;">
                        <?= ucfirst($followup['status']) ?>
                    </span>
                </div>
                <div class="timeline-content">
                    <h4><?= htmlspecialchars($followup['title']) ?></h4>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin:0.5rem 0; font-size:0.9rem; color:#666;">
                        <div><strong>Company:</strong> <?= htmlspecialchars($followup['company_name']) ?></div>
                        <div><strong>Project:</strong> <?= htmlspecialchars($followup['project_name']) ?></div>
                        <div>
                            <strong>Contact:</strong> <?= htmlspecialchars($followup['contact_person']) ?>
                            <?php if ($followup['contact_phone']): ?>
                                <a href="tel:<?= $followup['contact_phone'] ?>" style="margin-left:8px; color:#3b82f6; text-decoration:none;">📞</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($followup['description']): ?>
                    <p style="margin:0.5rem 0; color:#666;"><?= htmlspecialchars($followup['description']) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($followup['status'] !== 'completed'): ?>
                    <div style="margin-top:1rem;">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="complete">
                            <input type="hidden" name="id" value="<?= $followup['id'] ?>">
                            <button type="submit" class="btn btn--success btn--sm">Mark Complete</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>