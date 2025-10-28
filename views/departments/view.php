<?php
$title = 'Department Details';
$active_page = 'departments';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🏢</span> Department Details</h1>
        <p>View department information and employees</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/departments" class="btn btn--secondary">
            <span>←</span> Back to Departments
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>🏢</span> <?= htmlspecialchars($department['name'] ?? 'Department') ?>
        </h2>
    </div>
    <div class="card__body">
        <div class="detail-grid">
            <div class="detail-item">
                <label>Name</label>
                <span><?= htmlspecialchars($department['name'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Description</label>
                <span><?= htmlspecialchars($department['description'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Department Head</label>
                <span><?= htmlspecialchars($department['head_name'] ?? 'Not Assigned') ?></span>
            </div>
            <div class="detail-item">
                <label>Employee Count</label>
                <span><?= $department['employee_count'] ?? 0 ?></span>
            </div>
            <div class="detail-item">
                <label>Status</label>
                <span class="badge badge--<?= $department['status'] === 'active' ? 'success' : 'warning' ?>"><?= ucfirst($department['status'] ?? 'active') ?></span>
            </div>
            <div class="detail-item">
                <label>Created</label>
                <span><?= date('M d, Y', strtotime($department['created_at'] ?? 'now')) ?></span>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>