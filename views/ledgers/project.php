<?php
$title = 'Project Ledger';
$active_page = 'ledgers';
$additional_js = '<script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1>📊 Project-wise Ledger</h1>
        <p>Consolidated financial transactions by project</p>
    </div>
    <div class="page-actions" style="display: flex; gap: 8px; align-items: center;">
        <form method="GET" style="display: flex; gap: 8px; align-items: center;">
            <label style="font-size: 0.875rem; font-weight: 500; color: var(--text-primary); white-space: nowrap;">🔍</label>
            <select name="project_id" class="form-control" onchange="this.form.submit()" style="min-width: 200px; padding: 8px 12px; font-size: 0.875rem;">
                <option value="">All Projects</option>
                <?php foreach ($projects ?? [] as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($project_id ?? '') == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['project_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="/ergon/users" class="btn btn--secondary">← Back</a>
    </div>
</div>

<?php if (isset($project_name)): ?>
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Project: <?= htmlspecialchars($project_name ?? 'Unknown') ?></h2>
    </div>
    <div class="card__body">
        <div class="dashboard-grid" style="margin-bottom: 16px; margin-top: 0;">
            <div class="stat-card">
                <div class="stat-card__header">
                    <div class="stat-card__title">
                        <span class="stat-card__icon">💰</span>
                        <span class="stat-card__label">Budget</span>
                    </div>
                </div>
                <div class="stat-card__body">
                    <div class="stat-card__meta">
                        <div>
                            <small>Allocated</small>
                            <div class="stat-card__meta-value">₹<?= number_format($budget ?? 0, 2) ?></div>
                        </div>
                        <div>
                            <small>Utilized</small>
                            <div class="stat-card__meta-value"><?= number_format($utilization ?? 0, 1) ?>%</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card__header">
                    <div class="stat-card__title">
                        <span class="stat-card__icon">➕</span>
                        <span class="stat-card__label">Credits</span>
                    </div>
                </div>
                <div class="stat-card__body">
                    <div class="stat-card__meta">
                        <div>
                            <small>Total</small>
                            <div class="stat-card__meta-value" style="color: #059669;">₹<?= number_format($total_credits ?? 0, 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card__header">
                    <div class="stat-card__title">
                        <span class="stat-card__icon">➖</span>
                        <span class="stat-card__label">Debits</span>
                    </div>
                </div>
                <div class="stat-card__body">
                    <div class="stat-card__meta">
                        <div>
                            <small>Total</small>
                            <div class="stat-card__meta-value" style="color: #dc2626;">₹<?= number_format($total_debits ?? 0, 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card__header">
                    <div class="stat-card__title">
                        <span class="stat-card__icon">⚖️</span>
                        <span class="stat-card__label">Net Balance</span>
                    </div>
                </div>
                <div class="stat-card__body">
                    <div class="stat-card__meta">
                        <div>
                            <small>Credits - Debits</small>
                            <div class="stat-card__meta-value" style="color: <?= ($net_balance_type ?? 'Credit') === 'Credit' ? '#059669' : '#dc2626' ?>;">₹<?= number_format($net_balance_amount ?? 0, 2) ?></div>
                        </div>
                        <div>
                            <small>Type</small>
                            <div class="stat-card__meta-value"><?= $net_balance_type ?? 'Credit' ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card__header">
                    <div class="stat-card__title">
                        <span class="stat-card__icon">💼</span>
                        <span class="stat-card__label">Budget Balance</span>
                    </div>
                </div>
                <div class="stat-card__body">
                    <div class="stat-card__meta">
                        <div>
                            <small>Remaining</small>
                            <div class="stat-card__meta-value" style="color: <?= ($balance_type ?? 'Credit') === 'Credit' ? '#059669' : '#dc2626' ?>;">₹<?= number_format($balance_amount ?? 0, 2) ?></div>
                        </div>
                        <div>
                            <small>Status</small>
                            <div class="stat-card__meta-value"><?= $balance_type ?? 'Credit' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="card__body">
        <div class="dashboard-grid" style="margin-bottom: 24px;">
            <!-- Budget Utilization Chart -->
            <div class="chart-card">
                <div class="chart-card__header">
                    <h3 class="chart-card__title">🍰 Budget Utilization</h3>
                    <p class="chart-card__subtitle">Budget vs Spending</p>
                </div>
                <div class="chart-card__body">
                    <canvas id="budgetChart" width="300" height="200"></canvas>
                </div>
            </div>
            
            <!-- Credits vs Debits Chart -->
            <div class="chart-card">
                <div class="chart-card__header">
                    <h3 class="chart-card__title">📈 Credits vs Debits</h3>
                    <p class="chart-card__subtitle">Inflows vs Outflows</p>
                </div>
                <div class="chart-card__body">
                    <canvas id="creditsDebitsChart" width="300" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <div class="dashboard-grid" style="margin-bottom: 24px;">
            <!-- Project-wise Ledger Trend -->
            <div class="chart-card chart-card--wide">
                <div class="chart-card__header">
                    <h3 class="chart-card__title">📉 Project Ledger Trend</h3>
                    <p class="chart-card__subtitle">Transaction patterns over time</p>
                </div>
                <div class="chart-card__body">
                    <canvas id="trendChart" width="600" height="250"></canvas>
                </div>
            </div>
            
            <!-- Category-wise Spending -->
            <div class="chart-card">
                <div class="chart-card__header">
                    <h3 class="chart-card__title">📊 Category Breakdown</h3>
                    <p class="chart-card__subtitle">Expense vs Advance</p>
                </div>
                <div class="chart-card__body">
                    <canvas id="categoryChart" width="300" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card__body">
        <?php if (empty($entries)): ?>
            <p>No transactions found for this project.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Credit</th>
                            <th>Debit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $e): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($e['created_at'])) ?></td>
                            <td><?= htmlspecialchars($e['user_name']) ?></td>
                            <td>
                                <span class="badge badge--<?= $e['type'] === 'expense' ? 'warning' : 'info' ?>">
                                    <?= $e['type'] === 'expense' ? '💰 Expense' : '💳 Advance' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($e['description']) ?></td>
                            <td><?= $e['entry_type'] === 'credit' ? '₹' . number_format($e['amount'], 2) : '-' ?></td>
                            <td><?= $e['entry_type'] === 'debit' ? '₹' . number_format($e['amount'], 2) : '-' ?></td>
                            <td>
                                <span class="badge badge--<?= match($e['status']) {
                                    'approved' => 'success',
                                    'paid' => 'success',
                                    'rejected' => 'danger',
                                    default => 'warning'
                                } ?>">
                                    <?= ucfirst($e['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<style>
.chart-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #e5e7eb;
}

.chart-card--wide {
    grid-column: span 2;
}

.chart-card__header {
    margin-bottom: 16px;
}

.chart-card__title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.chart-card__subtitle {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
}

.chart-card__body {
    position: relative;
}

@media (max-width: 768px) {
    .chart-card--wide {
        grid-column: span 1;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data from PHP
    const budget = <?= $budget ?? 0 ?>;
    const totalDebits = <?= $total_debits ?? 0 ?>;
    const totalCredits = <?= $total_credits ?? 0 ?>;
    const entries = <?= json_encode($entries ?? []) ?>;
    
    // 1. Budget Utilization Chart (Donut)
    const budgetCtx = document.getElementById('budgetChart');
    if (budgetCtx) {
        const utilized = totalDebits;
        const remaining = Math.max(0, budget - utilized);
        const overSpent = Math.max(0, utilized - budget);
        
        new Chart(budgetCtx, {
            type: 'doughnut',
            data: {
                labels: overSpent > 0 ? ['Utilized', 'Over Budget'] : ['Utilized', 'Remaining'],
                datasets: [{
                    data: overSpent > 0 ? [budget, overSpent] : [utilized, remaining],
                    backgroundColor: overSpent > 0 ? ['#ef4444', '#dc2626'] : ['#3b82f6', '#e5e7eb'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { fontSize: 12 }
                    }
                }
            }
        });
    }
    
    // 2. Credits vs Debits Chart (Bar)
    const creditsDebitsCtx = document.getElementById('creditsDebitsChart');
    if (creditsDebitsCtx) {
        new Chart(creditsDebitsCtx, {
            type: 'bar',
            data: {
                labels: ['Credits', 'Debits'],
                datasets: [{
                    label: 'Amount (₹)',
                    data: [totalCredits, totalDebits],
                    backgroundColor: ['#10b981', '#ef4444'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
    
    // 3. Project-wise Ledger Trend (Line)
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx && entries.length > 0) {
        // Group entries by date
        const dailyData = {};
        entries.forEach(entry => {
            const date = entry.created_at.split(' ')[0];
            if (!dailyData[date]) {
                dailyData[date] = { credits: 0, debits: 0 };
            }
            if (entry.entry_type === 'credit') {
                dailyData[date].credits += parseFloat(entry.amount);
            } else {
                dailyData[date].debits += parseFloat(entry.amount);
            }
        });
        
        const dates = Object.keys(dailyData).sort();
        const creditsData = dates.map(date => dailyData[date].credits);
        const debitsData = dates.map(date => dailyData[date].debits);
        
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: dates.map(date => new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
                datasets: [
                    {
                        label: 'Credits',
                        data: creditsData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Debits',
                        data: debitsData,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
    
    // 4. Category-wise Spending (Pie)
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx && entries.length > 0) {
        const categoryData = {};
        entries.forEach(entry => {
            const type = entry.type === 'expense' ? 'Expenses' : 'Advances';
            categoryData[type] = (categoryData[type] || 0) + parseFloat(entry.amount);
        });
        
        new Chart(categoryCtx, {
            type: 'pie',
            data: {
                labels: Object.keys(categoryData),
                datasets: [{
                    data: Object.values(categoryData),
                    backgroundColor: ['#f59e0b', '#8b5cf6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { fontSize: 12 }
                    }
                }
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
