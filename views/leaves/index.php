<?php
$title = 'Leave Requests';
$active_page = 'leaves';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📅</span> Leave Management</h1>
        <p>Manage employee leave requests and approvals</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/public/leaves/create" class="btn btn--primary">
            <span>➕</span> Request Leave
        </a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend">↗ +12%</div>
        </div>
        <div class="kpi-card__value"><?= count($leaves ?? []) ?></div>
        <div class="kpi-card__label">Total Requests</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏳</div>
            <div class="kpi-card__trend kpi-card__trend--down">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($leaves ?? [], fn($l) => ($l['status'] ?? 'pending') === 'pending')) ?></div>
        <div class="kpi-card__label">Pending Approval</div>
        <div class="kpi-card__status kpi-card__status--pending">Needs Review</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ +8%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($leaves ?? [], fn($l) => ($l['status'] ?? 'pending') === 'approved')) ?></div>
        <div class="kpi-card__label">Approved</div>
        <div class="kpi-card__status">Granted</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📅</span> Leave Requests
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaves ?? [] as $leave): ?>
                    <tr>
                        <td><?= htmlspecialchars($leave['user_name'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($leave['leave_type'] ?? 'Annual') ?></td>
                        <td><?= date('M d, Y', strtotime($leave['start_date'])) ?></td>
                        <td><?= date('M d, Y', strtotime($leave['end_date'])) ?></td>
                        <td><?= $leave['days'] ?? 1 ?></td>
                        <td><span class="badge badge--warning"><?= ucfirst($leave['status'] ?? 'pending') ?></span></td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn--sm btn--primary">Approve</button>
                                <button class="btn btn--sm btn--secondary">Reject</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
