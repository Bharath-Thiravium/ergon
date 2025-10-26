<?php
$title = 'Approval Center';
$active_page = 'approvals';
ob_start();
?>

<div class="header-actions">
    <a href="/ergon_clean/public/reports" class="btn btn--secondary">View Reports</a>
</div>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏖️</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['approvals'], fn($a) => $a['type'] === 'Leave')) ?></div>
        <div class="kpi-card__label">Leave Requests</div>
        <div class="kpi-card__status kpi-card__status--pending">Pending</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💰</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +5%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['approvals'], fn($a) => $a['type'] === 'Expense')) ?></div>
        <div class="kpi-card__label">Expense Claims</div>
        <div class="kpi-card__status kpi-card__status--review">Review</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Pending Approvals</h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Employee</th>
                        <th>Details</th>
                        <th>Amount/Duration</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['approvals'])): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No pending approvals</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($data['approvals'] as $approval): ?>
                    <tr>
                        <td>
                            <span class="badge badge--<?= $approval['type'] === 'Leave' ? 'warning' : 'info' ?>">
                                <?= $approval['type'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($approval['requested_by_name']) ?></td>
                        <td><?= htmlspecialchars($approval['remarks'] ?? 'N/A') ?></td>
                        <td><?= $approval['count'] ?></td>
                        <td><?= date('M d, Y', strtotime($approval['created_at'] ?? 'now')) ?></td>
                        <td>
                            <a href="/ergon_clean/public/<?= strtolower($approval['type']) ?>s" class="btn btn--primary btn--sm">Review</a>
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