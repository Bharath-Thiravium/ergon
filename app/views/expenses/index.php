<?php
$title = 'Expense Management';
$active_page = 'expenses';
ob_start();
?>

<div class="page-header">
    <h1>Expense Management</h1>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
    <a href="/expenses/create" class="btn btn--primary">Submit Expense</a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success">Expense submitted successfully!</div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💰</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +8%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['expenses'], fn($e) => $e['status'] === 'pending')) ?></div>
        <div class="kpi-card__label">Pending Claims</div>
        <div class="kpi-card__status kpi-card__status--pending">Review</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +12%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['expenses'], fn($e) => $e['status'] === 'approved')) ?></div>
        <div class="kpi-card__label">Approved Claims</div>
        <div class="kpi-card__status kpi-card__status--active">Processed</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📈</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">— 0%</div>
        </div>
        <div class="kpi-card__value">₹<?= number_format(array_sum(array_column(array_filter($data['expenses'], fn($e) => $e['status'] === 'approved'), 'amount')), 0) ?></div>
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
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user'): ?>
                        <th>Employee</th>
                        <?php endif; ?>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Receipt</th>
                        <th>Status</th>
                        <th>Submitted On</th>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user'): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['expenses'] as $expense): ?>
                    <tr>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user'): ?>
                        <td><?= htmlspecialchars($expense['user_name']) ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($expense['category']) ?></td>
                        <td>₹<?= number_format($expense['amount'], 2) ?></td>
                        <td><?= date('M d, Y', strtotime($expense['created_at'])) ?></td>
                        <td><?= htmlspecialchars($expense['description']) ?></td>
                        <td>
                            <?php if (isset($expense['receipt_path']) && $expense['receipt_path']): ?>
                            <a href="/storage/receipts/<?= $expense['receipt_path'] ?>" target="_blank" class="btn btn--sm btn--secondary">View</a>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge--<?= strtolower($expense['status']) ?>">
                                <?= $expense['status'] ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($expense['created_at'])) ?></td>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user' && $expense['status'] === 'pending'): ?>
                        <td>
                            <a href="/ergon/expenses/approve/<?= $expense['id'] ?>" class="btn btn--success btn--sm">Approve</a>
                            <a href="/ergon/expenses/reject/<?= $expense['id'] ?>" class="btn btn--danger btn--sm">Reject</a>
                        </td>
                        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] !== 'user'): ?>
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