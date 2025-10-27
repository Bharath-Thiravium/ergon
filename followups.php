<?php
session_start();
require_once 'app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = Database::connect();
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

// Handle form submissions
if ($_POST) {
    if ($action === 'create') {
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
        header('Location: followups.php');
        exit;
    }
    
    if ($action === 'complete') {
        $stmt = $pdo->prepare("UPDATE followups SET status = 'completed', completed_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['id'], $user_id]);
        header('Location: followups.php');
        exit;
    }
}

// Get follow-ups
$stmt = $pdo->prepare("SELECT * FROM followups WHERE user_id = ? ORDER BY follow_up_date ASC");
$stmt->execute([$user_id]);
$followups = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Follow-ups</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Follow-ups Management</h2>
    
    <!-- Add New Follow-up -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Add New Follow-up</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="?action=create">
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" name="title" class="form-control mb-2" placeholder="Follow-up Title" required>
                        <input type="text" name="company_name" class="form-control mb-2" placeholder="Company Name">
                        <input type="text" name="contact_person" class="form-control mb-2" placeholder="Contact Person">
                    </div>
                    <div class="col-md-6">
                        <input type="tel" name="contact_phone" class="form-control mb-2" placeholder="Phone Number">
                        <input type="text" name="project_name" class="form-control mb-2" placeholder="Project Name">
                        <input type="date" name="follow_up_date" class="form-control mb-2" required>
                    </div>
                </div>
                <textarea name="description" class="form-control mb-2" placeholder="Description"></textarea>
                <button type="submit" class="btn btn-primary">Create Follow-up</button>
            </form>
        </div>
    </div>

    <!-- Follow-ups List -->
    <div class="row">
        <?php foreach ($followups as $followup): ?>
        <div class="col-md-6 mb-3">
            <div class="card <?= $followup['status'] === 'completed' ? 'border-success' : (strtotime($followup['follow_up_date']) < time() ? 'border-danger' : 'border-warning') ?>">
                <div class="card-header d-flex justify-content-between">
                    <strong><?= htmlspecialchars($followup['title']) ?></strong>
                    <span class="badge bg-<?= $followup['status'] === 'completed' ? 'success' : (strtotime($followup['follow_up_date']) < time() ? 'danger' : 'warning') ?>">
                        <?= ucfirst($followup['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <p><strong>Company:</strong> <?= htmlspecialchars($followup['company_name']) ?></p>
                    <p><strong>Contact:</strong> <?= htmlspecialchars($followup['contact_person']) ?>
                        <?php if ($followup['contact_phone']): ?>
                            <a href="tel:<?= $followup['contact_phone'] ?>" class="btn btn-sm btn-outline-primary ms-2">📞</a>
                        <?php endif; ?>
                    </p>
                    <p><strong>Project:</strong> <?= htmlspecialchars($followup['project_name']) ?></p>
                    <p><strong>Date:</strong> <?= date('M d, Y', strtotime($followup['follow_up_date'])) ?></p>
                    <p><?= htmlspecialchars($followup['description']) ?></p>
                    
                    <?php if ($followup['status'] !== 'completed'): ?>
                    <form method="POST" action="?action=complete" class="d-inline">
                        <input type="hidden" name="id" value="<?= $followup['id'] ?>">
                        <button type="submit" class="btn btn-success btn-sm">Mark Complete</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($followups)): ?>
    <div class="alert alert-info">
        No follow-ups found. Create your first follow-up above.
    </div>
    <?php endif; ?>
</div>
</body>
</html>