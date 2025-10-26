<?php
$title = 'Advance Requests';
$active_page = 'advances';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>💳</span> Advance Requests</h1>
        <p>Manage employee salary advance requests and approvals</p>
    </div>
    <div class="page-actions">
        <a href="/ergon_clean/public/advances/create" class="btn btn--primary">
            <span>➕</span> Request Advance
        </a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💳</div>
            <div class="kpi-card__trend">↗ +10%</div>
        </div>
        <div class="kpi-card__value"><?= count($advances ?? []) ?></div>
        <div class="kpi-card__label">Total Requests</div>
        <div class="kpi-card__status">Submitted</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏳</div>
            <div class="kpi-card__trend kpi-card__trend--down">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($advances ?? [], fn($a) => ($a['status'] ?? 'pending') === 'pending')) ?></div>
        <div class="kpi-card__label">Pending Review</div>
        <div class="kpi-card__status kpi-card__status--pending">Under Review</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ +18%</div>
        </div>
        <div class="kpi-card__value">$<?= number_format(array_sum(array_map(fn($a) => $a['amount'] ?? 0, array_filter($advances ?? [], fn($a) => ($a['status'] ?? 'pending') === 'approved'))), 2) ?></div>
        <div class="kpi-card__label">Approved Amount</div>
        <div class="kpi-card__status">Processed</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>💳</span> Advance Requests
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($advances ?? [])): ?>
                    <tr>
                        <td colspan="6" class="text-center" style="color: var(--text-muted); padding: 2rem;">
                            <div class="empty-state">
                                <div class="empty-icon">💳</div>
                                <h3>No Advance Requests</h3>
                                <p>No advance requests have been submitted yet.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($advances as $advance): ?>
                        <tr>
                            <td><?= htmlspecialchars($advance['user_name'] ?? 'Unknown') ?></td>
                            <td>$<?= number_format($advance['amount'] ?? 0, 2) ?></td>
                            <td><?= htmlspecialchars($advance['reason'] ?? '') ?></td>
                            <td><span class="badge badge--warning"><?= ucfirst($advance['status'] ?? 'pending') ?></span></td>
                            <td><?= date('M d, Y', strtotime($advance['created_at'] ?? 'now')) ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn--sm btn--primary">Approve</button>
                                    <button class="btn btn--sm btn--secondary">Reject</button>
                                </div>
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