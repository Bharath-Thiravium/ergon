<?php
$title = 'Expense Claims';
$active_page = 'expenses';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>💰</span> Expense Management</h1>
        <p>Track and manage employee expense claims</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/expenses/create" class="btn btn--primary">
            <span>💰</span> Submit Expense
        </a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💰</div>
            <div class="kpi-card__trend">↗ +15%</div>
        </div>
        <div class="kpi-card__value"><?= count($expenses ?? []) ?></div>
        <div class="kpi-card__label">Total Claims</div>
        <div class="kpi-card__status">Submitted</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏳</div>
            <div class="kpi-card__trend kpi-card__trend--down">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($expenses ?? [], fn($e) => ($e['status'] ?? 'pending') === 'pending')) ?></div>
        <div class="kpi-card__label">Pending Review</div>
        <div class="kpi-card__status kpi-card__status--pending">Under Review</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ +22%</div>
        </div>
        <div class="kpi-card__value">$<?= number_format(array_sum(array_map(fn($e) => $e['amount'] ?? 0, array_filter($expenses ?? [], fn($e) => ($e['status'] ?? 'pending') === 'approved'))), 2) ?></div>
        <div class="kpi-card__label">Approved Amount</div>
        <div class="kpi-card__status">Processed</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>💰</span> Expense Claims
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses ?? [] as $expense): ?>
                    <tr>
                        <td><?= htmlspecialchars($expense['user_name'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($expense['description'] ?? '') ?></td>
                        <td>$<?= number_format($expense['amount'] ?? 0, 2) ?></td>
                        <td><?= htmlspecialchars($expense['category'] ?? 'General') ?></td>
                        <td><?= date('M d, Y', strtotime($expense['expense_date'])) ?></td>
                        <td><span class="badge badge--warning"><?= ucfirst($expense['status'] ?? 'pending') ?></span></td>
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
