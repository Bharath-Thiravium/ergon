<?php
$title = 'Expense Management';
$active_page = 'expenses';
ob_start();
?>

<?php if (isset($data['error'])): ?>
<div class="alert alert--error"><?= htmlspecialchars($data['error']) ?></div>
<?php endif; ?>

<?php if ($data['user_role'] === 'user'): ?>
<div class="header-actions" style="margin-bottom: var(--space-6);">
    <a href="/ergon/expenses/create" class="btn btn--primary">Submit Expense</a>
</div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success">Expense submitted successfully!</div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💰</div>
            <div class="kpi-card__trend kpi-card__trend--up">Pending</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending'] ?? 0 ?></div>
        <div class="kpi-card__label">Pending Claims</div>
        <div class="kpi-card__status kpi-card__status--pending">Review</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend kpi-card__trend--up">Approved</div>
        </div>
        <div class="kpi-card__value"><?= ($data['stats']['total'] ?? 0) - ($data['stats']['pending'] ?? 0) - ($data['stats']['rejected'] ?? 0) ?></div>
        <div class="kpi-card__label">Approved Claims</div>
        <div class="kpi-card__status kpi-card__status--active">Processed</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📈</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">Total</div>
        </div>
        <div class="kpi-card__value">₹<?= number_format($data['stats']['approved_amount'] ?? 0, 0) ?></div>
        <div class="kpi-card__label">Total Approved</div>
        <div class="kpi-card__status kpi-card__status--info">Amount</div>
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
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Receipt</th>
                        <th>Status</th>
                        <th>Submitted On</th>
                        <?php if ($data['user_role'] !== 'user'): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['expenses'])): ?>
                    <tr>
                        <td colspan="<?= $data['user_role'] !== 'user' ? '9' : '7' ?>" class="text-center">
                            No expense claims found.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($data['expenses'] as $expense): ?>
                        <tr>
                            <?php if ($data['user_role'] !== 'user'): ?>
                            <td><?= htmlspecialchars($expense['user_name'] ?? 'N/A') ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($expense['category'] ?? 'N/A') ?></td>
                            <td>₹<?= number_format($expense['amount'] ?? 0, 0) ?></td>
                            <td><?= isset($expense['created_at']) ? date('M d, Y', strtotime($expense['created_at'])) : 'N/A' ?></td>
                            <td><?= htmlspecialchars($expense['description'] ?? 'N/A') ?></td>
                            <td>
                                <?php if (isset($expense['receipt_path']) && $expense['receipt_path']): ?>
                                <a href="/ergon/storage/receipts/<?= $expense['receipt_path'] ?>" target="_blank" class="btn btn--sm btn--secondary">View</a>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge--<?= strtolower($expense['status'] ?? 'pending') ?>">
                                    <?= $expense['status'] ?? 'Pending' ?>
                                </span>
                            </td>
                            <td><?= isset($expense['created_at']) ? date('M d, Y', strtotime($expense['created_at'])) : 'N/A' ?></td>
                            <?php if ($data['user_role'] !== 'user' && ($expense['status'] ?? 'pending') === 'pending'): ?>
                            <td>
                                <a href="/ergon/expenses/approve/<?= $expense['id'] ?>" class="btn btn--success btn--sm">Approve</a>
                                <a href="/ergon/expenses/reject/<?= $expense['id'] ?>" class="btn btn--danger btn--sm">Reject</a>
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