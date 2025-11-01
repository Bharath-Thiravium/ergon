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
        <div class="kpi-card__value">₹<?= number_format(array_sum(array_map(fn($e) => $e['amount'] ?? 0, array_filter($expenses ?? [], fn($e) => ($e['status'] ?? 'pending') === 'approved'))), 2) ?></div>
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
                        <td>
                            <?php 
                            $role = ucfirst($expense['user_role'] ?? 'user');
                            if ($role === 'User') $role = 'Employee';
                            
                            if (($expense['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0)) {
                                echo 'My Self (' . htmlspecialchars($expense['user_name'] ?? 'Unknown') . ') - ' . $role;
                            } else {
                                echo htmlspecialchars($expense['user_name'] ?? 'Unknown') . ' - ' . $role;
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($expense['description'] ?? '') ?></td>
                        <td>₹<?= number_format($expense['amount'] ?? 0, 2) ?></td>
                        <td><?= htmlspecialchars($expense['category'] ?? 'General') ?></td>
                        <td><?= date('M d, Y', strtotime($expense['expense_date'])) ?></td>
                        <td>
                            <?php 
                            $status = $expense['status'] ?? 'pending';
                            $badgeClass = 'badge--warning';
                            if ($status === 'approved') $badgeClass = 'badge--success';
                            elseif ($status === 'rejected') $badgeClass = 'badge--danger';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="/ergon/expenses/view/<?= $expense['id'] ?>" class="btn btn--sm btn--primary" title="View Details">
                                    <span>👁️</span> View
                                </a>
                                <?php if (($expense['status'] ?? 'pending') === 'pending' && ($expense['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0)): ?>
                                <a href="/ergon/expenses/edit/<?= $expense['id'] ?>" class="btn btn--sm btn--info" title="Edit Expense">
                                    <span>✏️</span> Edit
                                </a>
                                <?php endif; ?>
                                <?php 
                                // Show approve/reject buttons only if:
                                // 1. Owner can approve/reject any expense
                                // 2. Admin can approve/reject only user expenses (not their own or other admin/owner expenses)
                                $canApprove = false;
                                if (($user_role ?? '') === 'owner' && ($expense['status'] ?? 'pending') === 'pending') {
                                    $canApprove = true;
                                } elseif (($user_role ?? '') === 'admin' && ($expense['status'] ?? 'pending') === 'pending' && ($expense['user_id'] ?? 0) != ($_SESSION['user_id'] ?? 0)) {
                                    // Admin can only approve user expenses, not their own
                                    $canApprove = true;
                                }
                                ?>
                                <?php if ($canApprove): ?>
                                <a href="/ergon/expenses/approve/<?= $expense['id'] ?>" class="btn btn--sm btn--success" title="Approve Expense" onclick="return confirm('Are you sure you want to approve this expense?')">
                                    <span>✅</span> Approve
                                </a>
                                <button onclick="showRejectModal(<?= $expense['id'] ?>)" class="btn btn--sm btn--warning" title="Reject Expense">
                                    <span>❌</span> Reject
                                </button>
                                <?php endif; ?>
                                <?php if (in_array($user_role ?? '', ['admin', 'owner']) || (($user_role ?? '') === 'user' && ($expense['status'] ?? 'pending') === 'pending')): ?>
                                <button onclick="deleteRecord('expenses', <?= $expense['id'] ?>, 'Expense Claim')" class="btn btn--sm btn--danger" title="Delete Claim">
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
            <h3>Reject Expense Claim</h3>
            <span class="close" onclick="closeRejectModal()">&times;</span>
        </div>
        <form id="rejectForm" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="rejection_reason">Reason for Rejection:</label>
                    <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="4" placeholder="Please provide a reason for rejecting this expense claim..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn--danger">Reject Expense</button>
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
function showRejectModal(expenseId) {
    document.getElementById('rejectForm').action = '/ergon/expenses/reject/' + expenseId;
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
