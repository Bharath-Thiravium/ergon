<?php
$title = 'Leave Management';
$active_page = 'leaves';
ob_start();
?>

<?php if (isset($data['error'])): ?>
<div class="alert alert--error"><?= htmlspecialchars($data['error']) ?></div>
<?php endif; ?>

<?php if ($data['user_role'] === 'user'): ?>
<div class="header-actions" style="margin-bottom: var(--space-6);">
    <a href="/ergon/leaves/create" class="btn btn--primary">Apply for Leave</a>
</div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success">Leave request submitted successfully!</div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏖️</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">Total</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending'] ?? 0 ?></div>
        <div class="kpi-card__label">Pending Requests</div>
        <div class="kpi-card__status kpi-card__status--pending">Awaiting</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend kpi-card__trend--up">Approved</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['approved'] ?? 0 ?></div>
        <div class="kpi-card__label">Approved Leaves</div>
        <div class="kpi-card__status kpi-card__status--active">Granted</div>
    </div>
    
    <div class="kpi-card kpi-card--error">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">❌</div>
            <div class="kpi-card__trend kpi-card__trend--down">Rejected</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['rejected'] ?? 0 ?></div>
        <div class="kpi-card__label">Rejected Requests</div>
        <div class="kpi-card__status kpi-card__status--urgent">Denied</div>
    </div>
</div>

<div class="card">
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <?php if ($data['user_role'] !== 'user'): ?>
                        <th>Employee</th>
                        <?php endif; ?>
                        <th>Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Applied On</th>
                        <?php if ($data['user_role'] !== 'user'): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['leaves'])): ?>
                    <tr>
                        <td colspan="<?= $data['user_role'] !== 'user' ? '8' : '6' ?>" class="text-center">
                            No leave requests found.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($data['leaves'] as $leave): ?>
                        <tr>
                            <?php if ($data['user_role'] !== 'user'): ?>
                            <td><?= htmlspecialchars($leave['employee_name'] ?? 'N/A') ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($leave['leave_type'] ?? $leave['type'] ?? 'N/A') ?></td>
                            <td><?= isset($leave['start_date']) ? date('M d, Y', strtotime($leave['start_date'])) : 'N/A' ?></td>
                            <td><?= isset($leave['end_date']) ? date('M d, Y', strtotime($leave['end_date'])) : 'N/A' ?></td>
                            <td><?= htmlspecialchars($leave['reason'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge badge--<?= strtolower($leave['status'] ?? 'pending') ?>">
                                    <?= $leave['status'] ?? 'Pending' ?>
                                </span>
                            </td>
                            <td><?= isset($leave['created_at']) ? date('M d, Y', strtotime($leave['created_at'])) : 'N/A' ?></td>
                            <?php if ($data['user_role'] !== 'user' && ($leave['status'] ?? 'Pending') === 'Pending'): ?>
                            <td>
                                <a href="/ergon/leaves/approve/<?= $leave['id'] ?>" class="btn btn--success btn--sm">Approve</a>
                                <a href="/ergon/leaves/reject/<?= $leave['id'] ?>" class="btn btn--danger btn--sm">Reject</a>
                            </td>
                            <?php elseif ($data['user_role'] !== 'user'): ?>
                            <td>-</td>
                            <?php endif; ?>
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