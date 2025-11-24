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
<div class="alert alert--success">
    ✅ <?= htmlspecialchars($_GET['success']) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert--error">
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
        <div class="card__actions">
            <button class="btn btn--secondary" onclick="toggleLeaveFilters()">
                <span>🔍</span> Filters
            </button>
        </div>
    </div>
    <div id="leaveFiltersPanel" class="card" style="display: none; margin-bottom: 1rem;">
        <div class="card__body">
            <form method="GET" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Employee</label>
                        <select name="employee" class="form-control">
                            <option value="">All Employees</option>
                            <?php foreach ($employees ?? [] as $employee): ?>
                                <option value="<?= $employee['id'] ?>" <?= ($filters['employee'] ?? '') == $employee['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($employee['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Leave Type</label>
                        <select name="leave_type" class="form-control">
                            <option value="">All Leave Types</option>
                            <option value="sick" <?= ($filters['leave_type'] ?? '') == 'sick' ? 'selected' : '' ?>>Sick Leave</option>
                            <option value="casual" <?= ($filters['leave_type'] ?? '') == 'casual' ? 'selected' : '' ?>>Casual Leave</option>
                            <option value="annual" <?= ($filters['leave_type'] ?? '') == 'annual' ? 'selected' : '' ?>>Annual Leave</option>
                            <option value="emergency" <?= ($filters['leave_type'] ?? '') == 'emergency' ? 'selected' : '' ?>>Emergency Leave</option>
                            <option value="maternity" <?= ($filters['leave_type'] ?? '') == 'maternity' ? 'selected' : '' ?>>Maternity Leave</option>
                            <option value="paternity" <?= ($filters['leave_type'] ?? '') == 'paternity' ? 'selected' : '' ?>>Paternity Leave</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="pending" <?= ($filters['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= ($filters['status'] ?? '') == 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= ($filters['status'] ?? '') == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="card__footer">
                    <button type="submit" class="btn btn--primary">Apply Filters</button>
                    <a href="/ergon/leaves" class="btn btn--secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="col-employee">Employee</th>
                        <th class="col-type">Leave Type</th>
                        <th class="col-start-date">Start Date</th>
                        <th class="col-end-date">End Date</th>
                        <th class="col-days">Days</th>
                        <th class="col-status">Status</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaves ?? [] as $leave): ?>
                    <tr>
                        <td>
                            <?php 
                            $userRole = ucfirst($leave['user_role'] ?? 'user');
                            if ($userRole === 'User') $userRole = 'Employee';
                            
                            $employeeName = htmlspecialchars($leave['user_name'] ?? 'Unknown');
                            $isCurrentUser = ($leave['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0);
                            $displayName = $isCurrentUser ? "Myself ({$employeeName})" : $employeeName;
                            ?>
                            <strong><?= $displayName ?></strong>
                            <br><small class="text-muted"><?= $userRole ?></small>
                        </td>
                        <td data-sort-value="<?= $leave['type'] ?? 'annual' ?>"><span class="badge badge--info"><?= ucfirst(htmlspecialchars($leave['type'] ?? 'annual')) ?></span></td>
                        <td data-sort-value="<?= $leave['start_date'] ?>">
                            <div class="cell-meta">
                                <div class="cell-primary"><?= date('M d, Y', strtotime($leave['start_date'])) ?></div>
                                <div class="cell-secondary">Start Date</div>
                            </div>
                        </td>
                        <td data-sort-value="<?= $leave['end_date'] ?>">
                            <div class="cell-meta">
                                <div class="cell-primary"><?= date('M d, Y', strtotime($leave['end_date'])) ?></div>
                                <div class="cell-secondary">End Date</div>
                            </div>
                        </td>
                        <td data-sort-value="<?= $days ?>">
                            <?php 
                            $days = 1;
                            if (!empty($leave['start_date']) && !empty($leave['end_date'])) {
                                try {
                                    $startDate = new DateTime($leave['start_date']);
                                    $endDate = new DateTime($leave['end_date']);
                                    $dateDiff = $endDate->diff($startDate);
                                    $days = max(1, $dateDiff->days + 1);
                                } catch (Exception $e) {
                                    error_log('Date calculation error: ' . $e->getMessage());
                                    $days = 1;
                                }
                            } else {
                                error_log('Missing date values for leave calculation');
                            }
                            ?>
                            <strong><?= $days ?></strong> <?= $days == 1 ? 'day' : 'days' ?>
                        </td>
                        <td data-sort-value="<?= $leave['status'] ?? 'pending' ?>">
                            <?php 
                            $leaveStatus = strtolower($leave['status'] ?? 'pending');
                            $statusBadgeClass = match($leaveStatus) {
                                'approved' => 'badge--success',
                                'rejected' => 'badge--danger',
                                default => 'badge--warning'
                            };
                            ?>
                            <span class="badge <?= $statusBadgeClass ?>"><?= ucfirst($leave['status'] ?? 'pending') ?></span>
                        </td>
                        <td>
                            <div class="ab-container">
                                <a class="ab-btn ab-btn--view" data-action="view" data-module="leaves" data-id="<?= $leave['id'] ?>" title="View Details">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14,2 14,8 20,8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                    </svg>
                                </a>
                                <?php if (strtolower($leave['status'] ?? 'pending') === 'pending' && ($leave['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0)): ?>
                                <a class="ab-btn ab-btn--edit" data-action="edit" data-module="leaves" data-id="<?= $leave['id'] ?>" title="Edit Leave">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                        <path d="M15 5l4 4"/>
                                    </svg>
                                </a>
                                <?php endif; ?>
                                <?php 
                                $userRole = $user_role ?? '';
                                $leaveStatus = strtolower($leave['status'] ?? 'pending');
                                $isNotOwnLeave = ($leave['user_id'] ?? 0) != ($_SESSION['user_id'] ?? 0);
                                $canApprove = $leaveStatus === 'pending' && (($userRole === 'owner') || ($userRole === 'admin' && $isNotOwnLeave));
                                ?>
                                <?php if ($canApprove): ?>
                                <button class="ab-btn ab-btn--approve" data-action="approve" data-module="leaves" data-id="<?= $leave['id'] ?>" data-name="Leave Request" title="Approve Leave">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <polyline points="20,6 9,17 4,12"/>
                                    </svg>
                                </button>
                                <button class="ab-btn ab-btn--reject" onclick="showRejectModal(<?= $leave['id'] ?>)" title="Reject Leave">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <line x1="18" y1="6" x2="6" y2="18"/>
                                        <line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                <?php if (strtolower($leave['status'] ?? 'pending') === 'pending' && ($leave['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0)): ?>
                                <button class="ab-btn ab-btn--delete" data-action="delete" data-module="leaves" data-id="<?= $leave['id'] ?>" data-name="Leave Request" title="Delete Request">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M3 6h18"/>
                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
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
<div id="rejectModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 99999; display: flex; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
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



<script>
function toggleLeaveFilters() {
    const panel = document.getElementById('leaveFiltersPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function showRejectModal(leaveId) {
    document.getElementById('rejectForm').action = '/ergon/leaves/reject/' + leaveId;
    document.getElementById('rejectModal').style.display = 'flex';
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

<script>
// Global action button handler
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.ab-btn');
    if (!btn) return;
    
    const action = btn.dataset.action;
    const module = btn.dataset.module;
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    
    if (action === 'view' && module && id) {
        window.location.href = `/ergon/${module}/view/${id}`;
    } else if (action === 'edit' && module && id) {
        window.location.href = `/ergon/${module}/edit/${id}`;
    } else if (action === 'delete' && module && id && name) {
        deleteRecord(module, id, name);
    } else if (action === 'approve' && module && id) {
        window.location.href = `/ergon/${module}/approve/${id}`;
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>