<?php
$title = 'Leave Management';
$active_page = 'leaves';
ob_start();
?>

<div class="page-header">
    <h1>Leave Management</h1>
    <?php if ($_SESSION['role'] === 'User'): ?>
    <a href="/leaves/create" class="btn btn--primary">Apply for Leave</a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success">Leave request submitted successfully!</div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏖️</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['leaves'], fn($l) => $l['status'] === 'Pending')) ?></div>
        <div class="kpi-card__label">Pending Requests</div>
        <div class="kpi-card__status kpi-card__status--pending">Awaiting</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +5%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['leaves'], fn($l) => $l['status'] === 'Approved')) ?></div>
        <div class="kpi-card__label">Approved Leaves</div>
        <div class="kpi-card__status kpi-card__status--active">Granted</div>
    </div>
    
    <div class="kpi-card kpi-card--error">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">❌</div>
            <div class="kpi-card__trend kpi-card__trend--down">↘ -2%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['leaves'], fn($l) => $l['status'] === 'Rejected')) ?></div>
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
                        <?php if ($_SESSION['role'] !== 'User'): ?>
                        <th>Employee</th>
                        <?php endif; ?>
                        <th>Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Applied On</th>
                        <?php if ($_SESSION['role'] !== 'User'): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['leaves'] as $leave): ?>
                    <tr>
                        <?php if ($_SESSION['role'] !== 'User'): ?>
                        <td><?= htmlspecialchars($leave['employee_name']) ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($leave['type']) ?></td>
                        <td><?= date('M d, Y', strtotime($leave['start_date'])) ?></td>
                        <td><?= date('M d, Y', strtotime($leave['end_date'])) ?></td>
                        <td><?= htmlspecialchars($leave['reason']) ?></td>
                        <td>
                            <span class="badge badge--<?= strtolower($leave['status']) ?>">
                                <?= $leave['status'] ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($leave['created_at'])) ?></td>
                        <?php if ($_SESSION['role'] !== 'User' && $leave['status'] === 'Pending'): ?>
                        <td>
                            <a href="/ergon/leaves/approve/<?= $leave['id'] ?>" class="btn btn--success btn--sm">Approve</a>
                            <a href="/ergon/leaves/reject/<?= $leave['id'] ?>" class="btn btn--danger btn--sm">Reject</a>
                        </td>
                        <?php elseif ($_SESSION['role'] !== 'User'): ?>
                        <td>-</td>
                        <?php endif; ?>
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