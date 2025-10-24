<?php
$title = 'My Requests';
$active_page = 'requests';
ob_start();
?>

<div class="header-actions" style="margin-bottom: var(--space-6);">
    <a href="/ergon/leaves/create" class="btn btn--primary">Apply Leave</a>
    <a href="/ergon/expenses/create" class="btn btn--secondary">Submit Expense</a>
    <a href="/ergon/advances/create" class="btn btn--accent">Request Advance</a>
</div>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending_leaves'] ?></div>
        <div class="kpi-card__label">Pending Leaves</div>
        <div class="kpi-card__status kpi-card__status--pending">Awaiting Approval</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💰</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending_expenses'] ?></div>
        <div class="kpi-card__label">Pending Expenses</div>
        <div class="kpi-card__status kpi-card__status--review">Under Review</div>
    </div>
    
    <div class="kpi-card kpi-card--accent">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💸</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending_advances'] ?></div>
        <div class="kpi-card__label">Pending Advances</div>
        <div class="kpi-card__status kpi-card__status--pending">Processing</div>
    </div>
</div>

<div class="reports-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Leave Requests</h2>
        </div>
        <div class="card__body">
            <?php if (empty($data['leaves'])): ?>
            <p>No leave requests found.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Dates</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Applied On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['leaves'] as $leave): ?>
                        <tr>
                            <td><?= htmlspecialchars($leave['type']) ?></td>
                            <td><?= date('M d', strtotime($leave['start_date'])) ?> - <?= date('M d', strtotime($leave['end_date'])) ?></td>
                            <td><?= htmlspecialchars($leave['reason']) ?></td>
                            <td>
                                <span class="badge badge--<?= $leave['status'] === 'approved' ? 'success' : ($leave['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($leave['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($leave['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Expense Claims</h2>
        </div>
        <div class="card__body">
            <?php if (empty($data['expenses'])): ?>
            <p>No expense claims found.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Submitted On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['expenses'] as $expense): ?>
                        <tr>
                            <td><?= htmlspecialchars($expense['category']) ?></td>
                            <td>₹<?= number_format($expense['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($expense['description']) ?></td>
                            <td>
                                <span class="badge badge--<?= $expense['status'] === 'approved' ? 'success' : ($expense['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($expense['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($expense['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Advance Requests</h2>
        </div>
        <div class="card__body">
            <?php if (empty($data['advances'])): ?>
            <p>No advance requests found.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Reason</th>
                            <th>Repayment Date</th>
                            <th>Status</th>
                            <th>Requested On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['advances'] as $advance): ?>
                        <tr>
                            <td><?= htmlspecialchars($advance['type']) ?></td>
                            <td>₹<?= number_format($advance['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($advance['reason']) ?></td>
                            <td><?= date('M d, Y', strtotime($advance['repayment_date'])) ?></td>
                            <td>
                                <span class="badge badge--<?= $advance['status'] === 'approved' ? 'success' : ($advance['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($advance['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($advance['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>