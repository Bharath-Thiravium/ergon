<?php
/**
 * Admin Dashboard - System Admin vs Department Admin
 * ERGON - Employee Tracker & Task Manager
 */

$pageTitle = $is_system_admin ? 'System Admin Dashboard' : 'Department Admin Dashboard';
include __DIR__ . '/../layouts/dashboard.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <?= $is_system_admin ? 'System Admin Dashboard' : 'Department Admin Dashboard' ?>
            </h1>
            <p class="text-muted mb-0">
                <?= $is_system_admin ? 'Complete system management and oversight' : 'Department team management and coordination' ?>
            </p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-plus"></i> Quick Actions
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/ergon/admin/create-task"><i class="fas fa-tasks"></i> Create Task</a></li>
                <?php if ($management_options['create_users']): ?>
                <li><a class="dropdown-item" href="/ergon/admin/create-user"><i class="fas fa-user-plus"></i> Create User</a></li>
                <?php endif; ?>
                <?php if ($management_options['manage_departments']): ?>
                <li><a class="dropdown-item" href="/ergon/admin/manage-departments"><i class="fas fa-building"></i> Manage Departments</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/ergon/admin/reports"><i class="fas fa-chart-bar"></i> View Reports</a></li>
            </ul>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <?php if ($is_system_admin): ?>
            <!-- System Admin Stats -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_users'] ?? 0 ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Departments</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_departments'] ?? 0 ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-building fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Department Admin Stats -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Team Members</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['department_users'] ?? 0 ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Dept Tasks</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['department_tasks'] ?? 0 ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-tasks fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pending Tasks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['pending_tasks'] ?? 0 ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Approvals</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['pending_approvals'] ?? 0 ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Pending Approvals Section -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Pending Approvals (Admin Level)</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_approvals['leaves']) && empty($pending_approvals['expenses']) && empty($pending_approvals['advances'])): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">No pending approvals</p>
                        </div>
                    <?php else: ?>
                        <!-- Tabs for different approval types -->
                        <ul class="nav nav-tabs" id="approvalTabs" role="tablist">
                            <?php if (!empty($pending_approvals['leaves'])): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="leaves-tab" data-bs-toggle="tab" data-bs-target="#leaves" type="button">
                                    Leaves (<?= count($pending_approvals['leaves']) ?>)
                                </button>
                            </li>
                            <?php endif; ?>
                            <?php if (!empty($pending_approvals['expenses'])): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= empty($pending_approvals['leaves']) ? 'active' : '' ?>" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expenses" type="button">
                                    Expenses (<?= count($pending_approvals['expenses']) ?>)
                                </button>
                            </li>
                            <?php endif; ?>
                            <?php if (!empty($pending_approvals['advances'])): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= empty($pending_approvals['leaves']) && empty($pending_approvals['expenses']) ? 'active' : '' ?>" id="advances-tab" data-bs-toggle="tab" data-bs-target="#advances" type="button">
                                    Advances (<?= count($pending_approvals['advances']) ?>)
                                </button>
                            </li>
                            <?php endif; ?>
                        </ul>

                        <div class="tab-content mt-3" id="approvalTabContent">
                            <!-- Leave Approvals -->
                            <?php if (!empty($pending_approvals['leaves'])): ?>
                            <div class="tab-pane fade show active" id="leaves" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-sm">
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
                                                    <button class="btn btn-sm btn-success" onclick="approveRequest('leave', <?= $leave['id'] ?>, 'approved')">Approve</button>
                                                    <button class="btn btn-sm btn-danger" onclick="approveRequest('leave', <?= $leave['id'] ?>, 'rejected')">Reject</button>
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
                            <div class="tab-pane fade <?= empty($pending_approvals['leaves']) ? 'show active' : '' ?>" id="expenses" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-sm">
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
                                                    <button class="btn btn-sm btn-success" onclick="approveRequest('expense', <?= $expense['id'] ?>, 'approved')">Approve</button>
                                                    <button class="btn btn-sm btn-danger" onclick="approveRequest('expense', <?= $expense['id'] ?>, 'rejected')">Reject</button>
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
                            <div class="tab-pane fade <?= empty($pending_approvals['leaves']) && empty($pending_approvals['expenses']) ? 'show active' : '' ?>" id="advances" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-sm">
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
                                                    <button class="btn btn-sm btn-success" onclick="approveRequest('advance', <?= $advance['id'] ?>, 'approved')">Approve</button>
                                                    <button class="btn btn-sm btn-danger" onclick="approveRequest('advance', <?= $advance['id'] ?>, 'rejected')">Reject</button>
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
        </div>

        <!-- Management Panel -->
        <div class="col-lg-4 mb-4">
            <!-- Team Overview -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?= $is_system_admin ? 'System Overview' : 'Team Overview' ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($is_system_admin): ?>
                        <div class="mb-3">
                            <div class="small mb-1">Today's Attendance</div>
                            <div class="h5 mb-0"><?= $stats['today_attendance'] ?? 0 ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="small mb-1">System Alerts</div>
                            <div class="h5 mb-0 text-warning"><?= count($stats['system_alerts'] ?? []) ?></div>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <div class="small mb-1">Department Attendance</div>
                            <div class="h5 mb-0"><?= $stats['department_attendance'] ?? 0 ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($team_data)): ?>
                    <div class="mt-3">
                        <div class="small mb-2">Team Members</div>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($team_data, 0, 5) as $member): ?>
                            <div class="list-group-item px-0 py-1">
                                <div class="d-flex justify-content-between">
                                    <span class="small"><?= htmlspecialchars($member['name'] ?? $member['user_name'] ?? 'Unknown') ?></span>
                                    <span class="badge bg-primary"><?= htmlspecialchars($member['role'] ?? 'N/A') ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Management -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Management</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="/ergon/admin/manage-tasks" class="list-group-item list-group-item-action">
                            <i class="fas fa-tasks text-primary"></i> Manage Tasks
                        </a>
                        <a href="/ergon/admin/manage-users" class="list-group-item list-group-item-action">
                            <i class="fas fa-users text-success"></i> Manage Users
                        </a>
                        <a href="/ergon/admin/attendance-overview" class="list-group-item list-group-item-action">
                            <i class="fas fa-clock text-info"></i> Attendance Overview
                        </a>
                        <a href="/ergon/admin/reports" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-bar text-warning"></i> View Reports
                        </a>
                        <?php if ($management_options['system_settings']): ?>
                        <a href="/ergon/admin/system-settings" class="list-group-item list-group-item-action">
                            <i class="fas fa-cog text-danger"></i> System Settings
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Admin Approval</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="approvalForm">
                    <input type="hidden" id="approvalType" name="type">
                    <input type="hidden" id="approvalId" name="id">
                    <input type="hidden" id="approvalAction" name="action">
                    
                    <div class="mb-3">
                        <label for="comments" class="form-label">Comments (Optional)</label>
                        <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitApproval()">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
function approveRequest(type, id, action) {
    document.getElementById('approvalType').value = type;
    document.getElementById('approvalId').value = id;
    document.getElementById('approvalAction').value = action;
    
    const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
    modal.show();
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