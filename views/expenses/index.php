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
            <span>📋</span> Expense Legends
        </h2>
    </div>
    <div class="card__body">
        <div class="legends-grid">
            <div class="legend-section">
                <h4>Status Colors</h4>
                <div class="legend-items">
                    <div class="legend-item">
                        <span class="badge badge--warning">Pending</span>
                        <span>Awaiting review</span>
                    </div>
                    <div class="legend-item">
                        <span class="badge badge--success">Approved</span>
                        <span>Ready for payment</span>
                    </div>
                    <div class="legend-item">
                        <span class="badge badge--danger">Rejected</span>
                        <span>Needs revision</span>
                    </div>
                </div>
            </div>
            <div class="legend-section">
                <h4>Common Categories</h4>
                <div class="legend-items">
                    <div class="legend-item">
                        <span class="category-tag">🚗 Travel</span>
                        <span>Transportation costs</span>
                    </div>
                    <div class="legend-item">
                        <span class="category-tag">🍽️ Meals</span>
                        <span>Food & dining expenses</span>
                    </div>
                    <div class="legend-item">
                        <span class="category-tag">🏨 Accommodation</span>
                        <span>Hotel & lodging</span>
                    </div>
                    <div class="legend-item">
                        <span class="category-tag">📱 Communication</span>
                        <span>Phone & internet</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.legends-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}
.legend-section h4 {
    margin-bottom: 1rem;
    color: #333;
    font-weight: 600;
}
.legend-items {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.legend-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
}
.category-tag {
    background: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    min-width: 120px;
    text-align: center;
}
</style>

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
                                <a href="/ergon/expenses/view/<?= $expense['id'] ?>" class="btn btn--sm btn--primary" title="View Details">
                                    <span>👁️</span> View
                                </a>
                                <button onclick="deleteRecord('expenses', <?= $expense['id'] ?>, 'Expense Claim')" class="btn btn--sm btn--danger" title="Delete Claim">
                                    <span>🗑️</span> Delete
                                </button>
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
