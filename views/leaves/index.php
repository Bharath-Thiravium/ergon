<?php
$title = 'Leave Requests';
$active_page = 'leaves';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📅</span> Leave Management</h1>
        <p>Manage employee leave requests and approvals</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/leaves/create" class="btn btn--primary">
            <span>➕</span> Request Leave
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
    ✅ <?= htmlspecialchars($_GET['success']) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-error" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
    ❌ <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend">↗ +12%</div>
        </div>
        <div class="kpi-card__value"><?= count($leaves ?? []) ?></div>
        <div class="kpi-card__label">Total Requests</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏳</div>
            <div class="kpi-card__trend kpi-card__trend--down">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($leaves ?? [], fn($l) => strtolower($l['status'] ?? 'pending') === 'pending')) ?></div>
        <div class="kpi-card__label">Pending Approval</div>
        <div class="kpi-card__status kpi-card__status--pending">Needs Review</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ +8%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($leaves ?? [], fn($l) => strtolower($l['status'] ?? 'pending') === 'approved')) ?></div>
        <div class="kpi-card__label">Approved</div>
        <div class="kpi-card__status">Granted</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📅</span> Leave Requests
        </h2>
        <div class="card__filters">
            <form method="GET" class="filter-form">
                <select name="employee" class="form-control">
                    <option value="">All Employees</option>
                    <?php foreach ($employees ?? [] as $employee): ?>
                        <option value="<?= $employee['id'] ?>" <?= ($filters['employee'] ?? '') == $employee['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($employee['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="leave_type" class="form-control">
                    <option value="">All Leave Types</option>
                    <option value="sick" <?= ($filters['leave_type'] ?? '') == 'sick' ? 'selected' : '' ?>>Sick Leave</option>
                    <option value="casual" <?= ($filters['leave_type'] ?? '') == 'casual' ? 'selected' : '' ?>>Casual Leave</option>
                    <option value="annual" <?= ($filters['leave_type'] ?? '') == 'annual' ? 'selected' : '' ?>>Annual Leave</option>
                    <option value="emergency" <?= ($filters['leave_type'] ?? '') == 'emergency' ? 'selected' : '' ?>>Emergency Leave</option>
                    <option value="maternity" <?= ($filters['leave_type'] ?? '') == 'maternity' ? 'selected' : '' ?>>Maternity Leave</option>
                    <option value="paternity" <?= ($filters['leave_type'] ?? '') == 'paternity' ? 'selected' : '' ?>>Paternity Leave</option>
                </select>
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="pending" <?= ($filters['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= ($filters['status'] ?? '') == 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= ($filters['status'] ?? '') == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
                <button type="submit" class="btn btn--primary">Filter</button>
                <a href="/ergon/leaves" class="btn btn--secondary">Clear</a>
            </form>
        </div>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaves ?? [] as $leave): ?>
                    <tr>
                        <td>
                            <?php 
                            $role = ucfirst($leave['user_role'] ?? 'user');
                            if ($role === 'User') $role = 'Employee';
                            
                            if (($leave['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0)) {
                                echo 'My Self (' . htmlspecialchars($leave['user_name'] ?? 'Unknown') . ') - ' . $role;
                            } else {
                                echo htmlspecialchars($leave['user_name'] ?? 'Unknown') . ' - ' . $role;
                            }
                            ?>
                        </td>
                        <td><?= ucfirst(htmlspecialchars($leave['type'] ?? 'annual')) ?></td>
                        <td><?= date('M d, Y', strtotime($leave['start_date'])) ?></td>
                        <td><?= date('M d, Y', strtotime($leave['end_date'])) ?></td>
                        <td><?php 
                            // Always calculate from dates to ensure accuracy after edits
                            $start = new DateTime($leave['start_date']);
                            $end = new DateTime($leave['end_date']);
                            $days = $end->diff($start)->days + 1;
                            echo $days;
                        ?></td>
                        <td>
                            <?php 
                            $status = strtolower($leave['status'] ?? 'pending');
                            $badgeClass = 'badge--warning';
                            if ($status === 'approved') $badgeClass = 'badge--success';
                            elseif ($status === 'rejected') $badgeClass = 'badge--danger';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($leave['status'] ?? 'pending') ?></span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="/ergon/leaves/view/<?= $leave['id'] ?>" class="btn btn--sm btn--primary" title="View Details">
                                    <span>👁️</span> View
                                </a>
                                <?php if (strtolower($leave['status'] ?? 'pending') === 'pending' && ($leave['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0)): ?>
                                <a href="/ergon/leaves/edit/<?= $leave['id'] ?>" class="btn btn--sm btn--info" title="Edit Leave">
                                    <span>✏️</span> Edit
                                </a>
                                <?php endif; ?>
                                <?php 
                                // Debug: Show current values
                                // echo "<!-- DEBUG: user_role=" . ($user_role ?? 'NULL') . ", leave_status=" . ($leave['status'] ?? 'NULL') . ", leave_user_id=" . ($leave['user_id'] ?? 'NULL') . ", session_user_id=" . ($_SESSION['user_id'] ?? 'NULL') . " -->";
                                
                                $canApprove = false;
                                $leaveStatus = strtolower($leave['status'] ?? 'pending');
                                if (($user_role ?? '') === 'owner' && $leaveStatus === 'pending') {
                                    $canApprove = true;
                                } elseif (($user_role ?? '') === 'admin' && $leaveStatus === 'pending' && ($leave['user_id'] ?? 0) != ($_SESSION['user_id'] ?? 0)) {
                                    $canApprove = true;
                                }
                                ?>
                                <?php if ($canApprove): ?>
                                <a href="/ergon/leaves/approve/<?= $leave['id'] ?>" class="btn btn--sm btn--success" title="Approve Leave" onclick="return confirm('Are you sure you want to approve this leave?')">
                                    <span>✅</span> Approve
                                </a>
                                <button onclick="showRejectModal(<?= $leave['id'] ?>)" class="btn btn--sm btn--danger" title="Reject Leave">
                                    <span>❌</span> Reject
                                </button>
                                <?php endif; ?>
                                <?php if (strtolower($leave['status'] ?? 'pending') === 'pending' && (in_array($user_role ?? '', ['admin', 'owner']) || ($leave['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0))): ?>
                                <button onclick="deleteRecord('leaves', <?= $leave['id'] ?>, 'Leave Request')" class="btn btn--sm btn--danger" title="Delete Request">
                                    <span>🗑️</span> Delete
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reject Leave Request</h3>
            <span class="close" onclick="closeRejectModal()">&times;</span>
        </div>
        <form id="rejectForm" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="rejection_reason">Reason for Rejection:</label>
                    <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="4" placeholder="Please provide a reason for rejecting this leave request..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn--danger">Reject Leave</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}
.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 0;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h3 {
    margin: 0;
    color: #333;
}
.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}
.close:hover {
    color: #000;
}
.modal-body {
    padding: 20px;
}
.modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.card__filters {
    margin-top: 15px;
}
.filter-form {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: nowrap;
}
.filter-form .form-control {
    flex: 1;
    min-width: 140px;
    max-width: 180px;
}
.filter-form .btn {
    white-space: nowrap;
    flex-shrink: 0;
}
</style>

<script>
function showRejectModal(leaveId) {
    document.getElementById('rejectForm').action = '/ergon/leaves/reject/' + leaveId;
    document.getElementById('rejectModal').style.display = 'block';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    document.getElementById('rejection_reason').value = '';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('rejectModal');
    if (event.target === modal) {
        closeRejectModal();
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
