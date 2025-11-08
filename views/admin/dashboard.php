<?php
/**
 * Admin Dashboard - System Admin vs Department Admin
 * ERGON - Employee Tracker & Task Manager
 */

$title = ($data['is_system_admin'] ?? false) ? 'System Admin Dashboard' : 'Department Admin Dashboard';
$is_system_admin = $data['is_system_admin'] ?? false;
$stats = $data['stats'] ?? [];
$pending_approvals = $data['pending_approvals'] ?? [];
$team_data = $data['team_data'] ?? [];
$management_options = $data['management_options'] ?? [];
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><?= $is_system_admin ? 'System Admin Dashboard' : 'Department Admin Dashboard' ?></h1>
        <p><?= $is_system_admin ? 'Complete system management and oversight' : 'Department team management and coordination' ?></p>
    </div>
    <div class="page-actions">
        <a href="/ergon/admin/create-task" class="btn btn--primary">✅ Create Task</a>
        <?php if (($management_options['create_users'] ?? false)): ?>
        <a href="/ergon/admin/create-user" class="btn btn--secondary">👤 Create User</a>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-grid">
    <?php if ($is_system_admin): ?>
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend">↗ +5%</div>
        </div>
        <div class="kpi-card__value"><?= $stats['total_users'] ?? 0 ?></div>
        <div class="kpi-card__label">Total Users</div>
        <div class="kpi-card__status">Active</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏢</div>
            <div class="kpi-card__trend">→ 0%</div>
        </div>
        <div class="kpi-card__value"><?= $stats['total_departments'] ?? 0 ?></div>
        <div class="kpi-card__label">Departments</div>
        <div class="kpi-card__status">Active</div>
    </div>
    <?php else: ?>
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend">↗ +3%</div>
        </div>
        <div class="kpi-card__value"><?= $stats['department_users'] ?? 0 ?></div>
        <div class="kpi-card__label">Team Members</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ +8%</div>
        </div>
        <div class="kpi-card__value"><?= $stats['department_tasks'] ?? 0 ?></div>
        <div class="kpi-card__label">Dept Tasks</div>
        <div class="kpi-card__status">Pending</div>
    </div>
    <?php endif; ?>

    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏰</div>
            <div class="kpi-card__trend kpi-card__trend--down">↘ -2%</div>
        </div>
        <div class="kpi-card__value"><?= $stats['pending_tasks'] ?? 0 ?></div>
        <div class="kpi-card__label">Pending Tasks</div>
        <div class="kpi-card__status kpi-card__status--pending">Pending</div>
    </div>
    
    <div class="kpi-card kpi-card--danger">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📋</div>
            <div class="kpi-card__trend">→ 0%</div>
        </div>
        <div class="kpi-card__value"><?= $stats['pending_approvals'] ?? 0 ?></div>
        <div class="kpi-card__label">Pending Approvals</div>
        <div class="kpi-card__status kpi-card__status--urgent">Urgent</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h3>📋 Pending Approvals (Admin Level)</h3>
        </div>
        <div class="card__body">
            <?php if (empty($pending_approvals['leaves']) && empty($pending_approvals['expenses']) && empty($pending_approvals['advances'])): ?>
                <div class="empty-state">
                    <div class="empty-icon">✅</div>
                    <h3>No pending approvals</h3>
                    <p>All requests have been processed</p>
                </div>
                    <?php else: ?>
                        <!-- Tabs for different approval types -->
                        <div class="tab-nav">
                            <?php if (!empty($pending_approvals['leaves'])): ?>
                            <button class="tab-btn tab-btn--active" onclick="showTab('leaves')">
                                Leaves (<?= count($pending_approvals['leaves']) ?>)
                            </button>
                            <?php endif; ?>
                            <?php if (!empty($pending_approvals['expenses'])): ?>
                            <button class="tab-btn <?= empty($pending_approvals['leaves']) ? 'tab-btn--active' : '' ?>" onclick="showTab('expenses')">
                                Expenses (<?= count($pending_approvals['expenses']) ?>)
                            </button>
                            <?php endif; ?>
                            <?php if (!empty($pending_approvals['advances'])): ?>
                            <button class="tab-btn <?= empty($pending_approvals['leaves']) && empty($pending_approvals['expenses']) ? 'tab-btn--active' : '' ?>" onclick="showTab('advances')">
                                Advances (<?= count($pending_approvals['advances']) ?>)
                            </button>
                            <?php endif; ?>
                        </div>

                        <div class="tab-content">
                            <!-- Leave Approvals -->
                            <?php if (!empty($pending_approvals['leaves'])): ?>
                            <div class="tab-panel tab-panel--active" id="leaves">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Type</th>
                                                <th>Duration</th>
                                                <th>Days</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_approvals['leaves'] as $leave): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($leave['user_name']) ?></td>
                                                <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                                                <td><?= htmlspecialchars($leave['start_date']) ?> to <?= htmlspecialchars($leave['end_date']) ?></td>
                                                <td><?= $leave['days'] ?? 1 ?></td>
                                                <td>
                                                    <button class="btn btn--sm btn--primary" onclick="approveRequest('leave', <?= $leave['id'] ?>, 'approved')">Approve</button>
                                                    <button class="btn btn--sm btn--danger" onclick="approveRequest('leave', <?= $leave['id'] ?>, 'rejected')">Reject</button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Expense Approvals -->
                            <?php if (!empty($pending_approvals['expenses'])): ?>
                            <div class="tab-panel <?= empty($pending_approvals['leaves']) ? 'tab-panel--active' : '' ?>" id="expenses">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Category</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_approvals['expenses'] as $expense): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($expense['user_name']) ?></td>
                                                <td><?= htmlspecialchars($expense['category']) ?></td>
                                                <td>₹<?= number_format($expense['amount'], 2) ?></td>
                                                <td><?= htmlspecialchars($expense['expense_date']) ?></td>
                                                <td>
                                                    <button class="btn btn--sm btn--primary" onclick="approveRequest('expense', <?= $expense['id'] ?>, 'approved')">Approve</button>
                                                    <button class="btn btn--sm btn--danger" onclick="approveRequest('expense', <?= $expense['id'] ?>, 'rejected')">Reject</button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Advance Approvals -->
                            <?php if (!empty($pending_approvals['advances'])): ?>
                            <div class="tab-panel <?= empty($pending_approvals['leaves']) && empty($pending_approvals['expenses']) ? 'tab-panel--active' : '' ?>" id="advances">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Amount</th>
                                                <th>Reason</th>
                                                <th>Repayment</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_approvals['advances'] as $advance): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($advance['user_name']) ?></td>
                                                <td>₹<?= number_format($advance['amount'], 2) ?></td>
                                                <td><?= htmlspecialchars(substr($advance['reason'], 0, 30)) ?>...</td>
                                                <td><?= $advance['repayment_months'] ?? 1 ?> months</td>
                                                <td>
                                                    <button class="btn btn--sm btn--primary" onclick="approveRequest('advance', <?= $advance['id'] ?>, 'approved')">Approve</button>
                                                    <button class="btn btn--sm btn--danger" onclick="approveRequest('advance', <?= $advance['id'] ?>, 'rejected')">Reject</button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h3>👥 <?= $is_system_admin ? 'System Overview' : 'Team Overview' ?></h3>
        </div>
        <div class="card__body">
            <?php if ($is_system_admin): ?>
                <div class="stat-item">
                    <div class="stat-label">Today's Attendance</div>
                    <div class="stat-value"><?= $stats['today_attendance'] ?? 0 ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">System Alerts</div>
                    <div class="stat-value stat-value--warning"><?= count($stats['system_alerts'] ?? []) ?></div>
                </div>
            <?php else: ?>
                <div class="stat-item">
                    <div class="stat-label">Department Attendance</div>
                    <div class="stat-value"><?= $stats['department_attendance'] ?? 0 ?></div>
                </div>
            <?php endif; ?>
                    
            <?php if (!empty($team_data)): ?>
            <div class="team-section">
                <div class="section-label">Team Members</div>
                <div class="team-list">
                    <?php foreach (array_slice($team_data, 0, 5) as $member): ?>
                    <div class="team-member">
                        <span class="member-name"><?= htmlspecialchars($member['name'] ?? $member['user_name'] ?? 'Unknown') ?></span>
                        <span class="member-role"><?= htmlspecialchars($member['role'] ?? 'N/A') ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
                </div>
            </div>

    </div>
    

</div>

<div class="modal" id="approvalModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Admin Approval</h3>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="approvalForm">
                <input type="hidden" id="approvalType" name="type">
                <input type="hidden" id="approvalId" name="id">
                <input type="hidden" id="approvalAction" name="action">
                
                <div class="form-group">
                    <label for="comments" class="form-label">Comments (Optional)</label>
                    <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn--secondary" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn--primary" onclick="submitApproval()">Confirm</button>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-panel').forEach(panel => {
        panel.classList.remove('tab-panel--active');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('tab-btn--active');
    });
    document.getElementById(tabName).classList.add('tab-panel--active');
    event.target.classList.add('tab-btn--active');
}

function approveRequest(type, id, action) {
    document.getElementById('approvalType').value = type;
    document.getElementById('approvalId').value = id;
    document.getElementById('approvalAction').value = action;
    document.getElementById('approvalModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('approvalModal').style.display = 'none';
}

function submitApproval() {
    const formData = new FormData(document.getElementById('approvalForm'));
    
    fetch('/ergon/admin/approve-request', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>