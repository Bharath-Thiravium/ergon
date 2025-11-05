<?php
$title = 'Advance Requests';
$active_page = 'advances';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>💳</span> Advance Requests</h1>
        <p>Manage employee salary advance requests and approvals</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/advances/create" class="btn btn--primary">
            <span>➕</span> Request Advance
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
            <div class="kpi-card__icon">💳</div>
            <div class="kpi-card__trend">↗ +10%</div>
        </div>
        <div class="kpi-card__value"><?= count($advances ?? []) ?></div>
        <div class="kpi-card__label">Total Requests</div>
        <div class="kpi-card__status">Submitted</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏳</div>
            <div class="kpi-card__trend kpi-card__trend--down">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($advances ?? [], fn($a) => ($a['status'] ?? 'pending') === 'pending')) ?></div>
        <div class="kpi-card__label">Pending Review</div>
        <div class="kpi-card__status kpi-card__status--pending">Under Review</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ +18%</div>
        </div>
        <div class="kpi-card__value">₹<?= number_format(array_sum(array_map(fn($a) => $a['amount'] ?? 0, array_filter($advances ?? [], fn($a) => ($a['status'] ?? 'pending') === 'approved'))), 2) ?></div>
        <div class="kpi-card__label">Approved Amount</div>
        <div class="kpi-card__status">Processed</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>💳</span> Advance Requests
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($advances ?? [])): ?>
                    <tr>
                        <td colspan="7" class="text-center" style="color: var(--text-muted); padding: 2rem;">
                            <div class="empty-state">
                                <div class="empty-icon">💳</div>
                                <h3>No Advance Requests</h3>
                                <p>No advance requests have been submitted yet.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($advances as $advance): ?>
                        <tr>
                            <td>
                                <?php 
                                $role = ucfirst($advance['user_role'] ?? 'user');
                                if ($role === 'User') $role = 'Employee';
                                
                                if (($advance['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0)) {
                                    echo 'My Self (' . htmlspecialchars($advance['user_name'] ?? 'Unknown') . ') - ' . $role;
                                } else {
                                    echo htmlspecialchars($advance['user_name'] ?? 'Unknown') . ' - ' . $role;
                                }
                                ?>
                            </td>
                            <td><?= htmlspecialchars(!empty($advance['type']) ? $advance['type'] : 'General Advance') ?></td>
                            <td>₹<?= number_format($advance['amount'] ?? 0, 2) ?></td>
                            <td><?= htmlspecialchars($advance['reason'] ?? '') ?></td>
                            <td>
                                <?php 
                                $status = $advance['status'] ?? 'pending';
                                $badgeClass = 'badge--warning';
                                if ($status === 'approved') $badgeClass = 'badge--success';
                                elseif ($status === 'rejected') $badgeClass = 'badge--danger';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                            </td>
                            <td><?= date('M d, Y', strtotime($advance['created_at'] ?? 'now')) ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="/ergon/advances/view/<?= $advance['id'] ?>" class="btn btn--sm btn--primary" title="View Details">
                                        <span>👁️</span> View
                                    </a>
                                    <?php if (($advance['status'] ?? 'pending') === 'pending' && ($advance['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0)): ?>
                                    <a href="/ergon/advances/edit/<?= $advance['id'] ?>" class="btn btn--sm btn--info" title="Edit Advance">
                                        <span>✏️</span> Edit
                                    </a>
                                    <?php endif; ?>
                                    <?php 
                                    $canApprove = false;
                                    if (($user_role ?? '') === 'owner' && ($advance['status'] ?? 'pending') === 'pending') {
                                        $canApprove = true;
                                    } elseif (($user_role ?? '') === 'admin' && ($advance['status'] ?? 'pending') === 'pending' && ($advance['user_id'] ?? 0) != ($_SESSION['user_id'] ?? 0)) {
                                        $canApprove = true;
                                    }
                                    ?>
                                    <?php if ($canApprove): ?>
                                    <a href="/ergon/advances/approve/<?= $advance['id'] ?>" class="btn btn--sm btn--success" title="Approve Advance" onclick="return confirm('Are you sure you want to approve this advance?')">
                                        <span>✅</span> Approve
                                    </a>
                                    <button onclick="showRejectModal(<?= $advance['id'] ?>)" class="btn btn--sm btn--warning" title="Reject Advance">
                                        <span>❌</span> Reject
                                    </button>
                                    <?php endif; ?>
                                    <?php if (in_array($user_role ?? '', ['admin', 'owner']) || (($user_role ?? '') === 'user' && ($advance['status'] ?? 'pending') === 'pending')): ?>
                                    <button onclick="deleteRecord('advances', <?= $advance['id'] ?>, 'Advance Request')" class="btn btn--sm btn--danger" title="Delete Request">
                                        <span>🗑️</span> Delete
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reject Advance Request</h3>
            <span class="close" onclick="closeRejectModal()">&times;</span>
        </div>
        <form id="rejectForm" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="rejection_reason">Reason for Rejection:</label>
                    <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="4" placeholder="Please provide a reason for rejecting this advance request..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn--danger">Reject Advance</button>
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
</style>

<script>
function showRejectModal(advanceId) {
    document.getElementById('rejectForm').action = '/ergon/advances/reject/' + advanceId;
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
