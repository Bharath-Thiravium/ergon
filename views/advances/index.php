<?php
$title = 'Advance Requests';
$active_page = 'advances';
include __DIR__ . '/../shared/modal_component.php';
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
<div class="alert alert--success">
    ✅ <?= htmlspecialchars($_GET['success']) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert--error">
    ❌ <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<?php renderModalCSS(); ?>

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
                        <td colspan="7" class="text-center">
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
                                $employeeRole = ucfirst($advance['user_role'] ?? 'user');
                                if ($employeeRole === 'User') $employeeRole = 'Employee';
                                
                                $employeeName = htmlspecialchars($advance['user_name'] ?? 'Unknown');
                                $isCurrentUser = ($advance['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0);
                                $displayName = $isCurrentUser ? "Myself ({$employeeName})" : $employeeName;
                                echo $displayName . ' - ' . $employeeRole;
                                ?>
                            </td>
                            <td><?= htmlspecialchars(!empty($advance['type']) ? $advance['type'] : 'General Advance') ?></td>
                            <td>₹<?= number_format($advance['amount'] ?? 0, 2) ?></td>
                            <td><?= htmlspecialchars($advance['reason'] ?? '') ?></td>
                            <td>
                                <?php 
                                $advanceStatus = $advance['status'] ?? 'pending';
                                $statusBadgeClass = match($advanceStatus) {
                                    'approved' => 'badge--success',
                                    'rejected' => 'badge--danger',
                                    default => 'badge--warning'
                                };
                                ?>
                                <span class="badge <?= $statusBadgeClass ?>"><?= ucfirst($advanceStatus) ?></span>
                            </td>
                            <td><?= date('M d, Y', strtotime($advance['created_at'] ?? 'now')) ?></td>
                            <td>
                                <div class="ab-container">
                                    <a class="ab-btn ab-btn--view" data-action="view" data-module="advances" data-id="<?= $advance['id'] ?>" title="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                        </svg>
                                    </a>
                                    <?php if (($advance['status'] ?? 'pending') === 'pending' && ($advance['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0)): ?>
                                    <a class="ab-btn ab-btn--edit" data-action="edit" data-module="advances" data-id="<?= $advance['id'] ?>" title="Edit Advance">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="M15 5l4 4"/>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                    <?php 
                                    $currentUserRole = $user_role ?? '';
                                    $isPending = ($advance['status'] ?? 'pending') === 'pending';
                                    $isNotOwnAdvance = ($advance['user_id'] ?? 0) != ($_SESSION['user_id'] ?? 0);
                                    $canApprove = $isPending && (($currentUserRole === 'owner') || ($currentUserRole === 'admin' && $isNotOwnAdvance));
                                    ?>
                                    <?php if ($canApprove): ?>
                                    <button class="ab-btn ab-btn--approve" data-action="approve" data-module="advances" data-id="<?= $advance['id'] ?>" data-name="Advance Request" title="Approve Advance">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <polyline points="20,6 9,17 4,12"/>
                                        </svg>
                                    </button>
                                    <button class="ab-btn ab-btn--reject" onclick="showRejectModal(<?= $advance['id'] ?>)" title="Reject Advance">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <line x1="18" y1="6" x2="6" y2="18"/>
                                            <line x1="6" y1="6" x2="18" y2="18"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (in_array($user_role ?? '', ['admin', 'owner']) || (($user_role ?? '') === 'user' && ($advance['status'] ?? 'pending') === 'pending')): ?>
                                    <button class="ab-btn ab-btn--delete" data-action="delete" data-module="advances" data-id="<?= $advance['id'] ?>" data-name="Advance Request" title="Delete Request">
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
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Rejection Modal Content
$rejectContent = '
<form id="rejectForm" method="POST">
    <div class="form-group">
        <label for="rejection_reason">Reason for Rejection:</label>
        <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="4" placeholder="Please provide a reason for rejecting this advance request..." required></textarea>
    </div>
</form>';

$rejectFooter = createFormModalFooter('Cancel', 'Reject Advance', 'rejectModal', 'danger');

// Render Modal
renderModal('rejectModal', 'Reject Advance Request', $rejectContent, $rejectFooter, ['icon' => '❌']);
?>



<script>
function showRejectModal(advanceId) {
    document.getElementById('rejectForm').action = '/ergon/advances/reject/' + advanceId;
    showModal('rejectModal');
}
</script>

<script src="/ergon/assets/js/table-utils.js"></script>

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

<?php renderModalJS(); ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
