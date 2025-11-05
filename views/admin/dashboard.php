<?php
$title = 'Admin Dashboard';
$active_page = 'dashboard';

if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
    header('Location: /ergon/login');
    exit;
}

ob_start();
?>

<div class="header-actions">
    <a href="/ergon/tasks/create" class="btn btn--primary">Assign Task</a>
    <a href="/ergon/leaves" class="btn btn--secondary">Review Leaves</a>
    <a href="/ergon/expenses" class="btn btn--secondary">Review Expenses</a>
</div>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +8%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['total_users'] ?? 0 ?></div>
        <div class="kpi-card__label">Team Members</div>
        <div class="kpi-card__status kpi-card__status--active">Active</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📋</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +15%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['active_tasks'] ?? 0 ?></div>
        <div class="kpi-card__label">Active Tasks</div>
        <div class="kpi-card__status kpi-card__status--active">Assigned</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏖️</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending_leaves'] ?? 0 ?></div>
        <div class="kpi-card__label">Leave Requests</div>
        <div class="kpi-card__status kpi-card__status--pending">Pending</div>
    </div>
    
    <div class="kpi-card kpi-card--error">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚠️</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +25%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['overdue_tasks'] ?? 0 ?></div>
        <div class="kpi-card__label">Overdue Tasks</div>
        <div class="kpi-card__status kpi-card__status--urgent">Urgent</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💰</div>
            <div class="kpi-card__trend kpi-card__trend--down">↘ -12%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending_expenses'] ?? 0 ?></div>
        <div class="kpi-card__label">Expense Claims</div>
        <div class="kpi-card__status kpi-card__status--review">Review</div>
    </div>
</div>

<div class="reports-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Recent Tasks</h2>
        </div>
        <div class="card__body">
            <?php if (empty($data['recent_tasks'])): ?>
            <p>No recent tasks.</p>
            <?php else: ?>
            <?php foreach ($data['recent_tasks'] as $task): ?>
            <div class="timeline-item">
                <div class="timeline-date"><?= date('M d', strtotime($task['created_at'])) ?></div>
                <div class="timeline-content">
                    <h4><?= htmlspecialchars($task['title']) ?></h4>
                    <p>Assigned to: <?= htmlspecialchars($task['assigned_to_name']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Pending Approvals</h2>
        </div>
        <div class="card__body">
            <div class="approvals-container">
                <?php if (!empty($data['pending_approvals']['leaves'])): ?>
                <div class="approval-section">
                    <div class="approval-header">
                        <span class="approval-icon">🏖️</span>
                        <h4>Leave Requests</h4>
                        <span class="approval-count"><?= count($data['pending_approvals']['leaves']) ?></span>
                    </div>
                    <div class="approval-list">
                        <?php foreach ($data['pending_approvals']['leaves'] as $leave): ?>
                        <div class="approval-item">
                            <div class="approval-user"><?= htmlspecialchars($leave['user_name']) ?></div>
                            <div class="approval-details">
                                <span class="approval-type"><?= htmlspecialchars($leave['leave_type'] ?? 'Leave') ?></span>
                                <span class="approval-period"><?= date('M d', strtotime($leave['start_date'])) ?> - <?= date('M d, Y', strtotime($leave['end_date'])) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($data['pending_approvals']['expenses'])): ?>
                <div class="approval-section">
                    <div class="approval-header">
                        <span class="approval-icon">💰</span>
                        <h4>Expense Claims</h4>
                        <span class="approval-count"><?= count($data['pending_approvals']['expenses']) ?></span>
                    </div>
                    <div class="approval-list">
                        <?php foreach ($data['pending_approvals']['expenses'] as $expense): ?>
                        <div class="approval-item">
                            <div class="approval-user"><?= htmlspecialchars($expense['user_name']) ?></div>
                            <div class="approval-details">
                                <span class="approval-amount">₹<?= number_format($expense['amount'], 2) ?></span>
                                <span class="approval-desc"><?= htmlspecialchars($expense['description'] ?? 'Expense claim') ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($data['pending_approvals']['advances'])): ?>
                <div class="approval-section">
                    <div class="approval-header">
                        <span class="approval-icon">💸</span>
                        <h4>Advance Requests</h4>
                        <span class="approval-count"><?= count($data['pending_approvals']['advances']) ?></span>
                    </div>
                    <div class="approval-list">
                        <?php foreach ($data['pending_approvals']['advances'] as $advance): ?>
                        <div class="approval-item">
                            <div class="approval-user"><?= htmlspecialchars($advance['user_name']) ?></div>
                            <div class="approval-details">
                                <span class="approval-amount">₹<?= number_format($advance['amount'], 2) ?></span>
                                <span class="approval-desc"><?= htmlspecialchars($advance['reason'] ?? 'Advance request') ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (empty($data['pending_approvals']['leaves']) && empty($data['pending_approvals']['expenses']) && empty($data['pending_approvals']['advances'])): ?>
                <div class="no-approvals">
                    <span class="no-approvals-icon">✅</span>
                    <p>No pending approvals</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.approvals-container {
    max-height: 400px;
    overflow-y: auto;
}
.approval-section {
    margin-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
}
.approval-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}
.approval-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}
.approval-icon {
    font-size: 1.2rem;
}
.approval-header h4 {
    margin: 0;
    flex: 1;
    font-size: 0.9rem;
    color: #333;
}
.approval-count {
    background: #007bff;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}
.approval-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.approval-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 3px solid #007bff;
}
.approval-user {
    font-weight: 600;
    color: #333;
    font-size: 0.85rem;
}
.approval-details {
    text-align: right;
    font-size: 0.8rem;
}
.approval-type, .approval-amount {
    display: block;
    font-weight: 600;
    color: #007bff;
}
.approval-period, .approval-desc {
    display: block;
    color: #666;
    margin-top: 0.2rem;
}
.no-approvals {
    text-align: center;
    padding: 2rem;
    color: #666;
}
.no-approvals-icon {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.5rem;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
