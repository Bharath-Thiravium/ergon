<?php
$title = 'Follow-ups';
$active_page = 'followups';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📋</span> Follow-ups</h1>
        <p>Manage and track all follow-up activities</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/followups/create" class="btn btn--primary">
            <span>➕</span> New Follow-up
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert--success">
        Follow-up <?= htmlspecialchars($_GET['success']) ?> successfully!
    </div>
<?php endif; ?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📋</span> Follow-ups List
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Contact</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($followups ?? [])): ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="empty-state">
                                <div class="empty-icon">📋</div>
                                <h3>No Follow-ups Found</h3>
                                <p>No follow-ups have been created yet.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($followups as $followup): ?>
                    <tr>
                        <td><?= htmlspecialchars($followup['title']) ?></td>
                        <td><?= ucfirst($followup['followup_type'] ?? ($followup['task_id'] ? 'Task-linked' : 'Standalone')) ?></td>
                        <td><?= htmlspecialchars($followup['contact_name'] ?? $followup['contact_person'] ?? ($followup['contact_company'] ? $followup['contact_company'] : 'Unknown')) ?></td>
                        <td><?= date('M d, Y', strtotime($followup['follow_up_date'])) ?></td>
                        <td><span class="badge badge--<?= $followup['status'] === 'completed' ? 'success' : 'warning' ?>"><?= ucfirst($followup['status']) ?></span></td>
                        <td>
                            <a href="/ergon/followups/view/<?= $followup['id'] ?>" class="btn btn--sm btn--secondary">View</a>
                        </td>
                    </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>