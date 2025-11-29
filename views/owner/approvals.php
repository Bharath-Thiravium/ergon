<?php
$title = 'Pending Approvals';
$active_page = 'approvals';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🔍</span> Pending Approvals</h1>
        <p>Review and manage pending requests across all modules</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--secondary" onclick="refreshData()">
            <span>🔄</span> Refresh
        </button>
        <button class="btn btn--primary" onclick="exportApprovals()">
            <span>📊</span> Export Report
        </button>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend">↗ New</div>
        </div>
        <div class="kpi-card__value"><?= count($leaves ?? []) ?></div>
        <div class="kpi-card__label">Leave Requests</div>
        <div class="kpi-card__status">Pending</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💰</div>
            <div class="kpi-card__trend">↗ New</div>
        </div>
        <div class="kpi-card__value"><?= count($expenses ?? []) ?></div>
        <div class="kpi-card__label">Expense Claims</div>
        <div class="kpi-card__status">Pending</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💳</div>
            <div class="kpi-card__trend">↗ New</div>
        </div>
        <div class="kpi-card__value"><?= count($advances ?? []) ?></div>
        <div class="kpi-card__label">Advance Requests</div>
        <div class="kpi-card__status">Pending</div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Leave Requests -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📅</span> Leave Requests
        </h2>
    </div>
    <div class="card__body">
        <?php if (!empty($leaves)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaves as $leave): ?>
                            <tr>
                                <td><?= htmlspecialchars($leave['user_name'] ?? 'Unknown') ?></td>
                                <td><span class="badge badge--info"><?= htmlspecialchars($leave['type'] ?? '') ?></span></td>
                                <td><?= htmlspecialchars($leave['start_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($leave['end_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($leave['reason'] ?? '') ?></td>
                                <td>
                                    <div class="ab-container">
                                        <a class="ab-btn ab-btn--view" onclick="viewItem('leave', <?= $leave['id'] ?>)" data-tooltip="View Details">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14,2 14,8 20,8"></polyline>
                                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                            </svg>
                                        </a>
                                        <button class="ab-btn ab-btn--approve" onclick="approveItem('leave', <?= $leave['id'] ?>)" data-tooltip="Approve Leave">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <polyline points="20,6 9,17 4,12"></polyline>
                                            </svg>
                                        </button>
                                        <button class="ab-btn ab-btn--reject" onclick="rejectItem('leave', <?= $leave['id'] ?>)" data-tooltip="Reject Leave">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📅</div>
                <h3>No Pending Leave Requests</h3>
                <p>All leave requests have been processed.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Expense Claims -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>💰</span> Expense Claims
        </h2>
    </div>
    <div class="card__body">
        <?php if (!empty($expenses)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Amount</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?= htmlspecialchars($expense['user_name'] ?? 'Unknown') ?></td>
                                <td><strong>₹<?= number_format($expense['amount'] ?? 0, 2) ?></strong></td>
                                <td><span class="badge badge--warning"><?= htmlspecialchars($expense['category'] ?? '') ?></span></td>
                                <td><?= htmlspecialchars($expense['description'] ?? '') ?></td>
                                <td><?= htmlspecialchars($expense['expense_date'] ?? $expense['created_at'] ?? '') ?></td>
                                <td>
                                    <div class="ab-container">
                                        <a class="ab-btn ab-btn--view" onclick="viewItem('expense', <?= $expense['id'] ?>)" data-tooltip="View Details">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14,2 14,8 20,8"></polyline>
                                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                            </svg>
                                        </a>
                                        <button class="ab-btn ab-btn--approve" onclick="approveItem('expense', <?= $expense['id'] ?>)" data-tooltip="Approve Expense">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <polyline points="20,6 9,17 4,12"></polyline>
                                            </svg>
                                        </button>
                                        <button class="ab-btn ab-btn--reject" onclick="rejectItem('expense', <?= $expense['id'] ?>)" data-tooltip="Reject Expense">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">💰</div>
                <h3>No Pending Expense Claims</h3>
                <p>All expense claims have been processed.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Advance Requests -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>💳</span> Advance Requests
        </h2>
    </div>
    <div class="card__body">
        <?php if (!empty($advances)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Amount</th>
                            <th>Reason</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($advances as $advance): ?>
                            <tr>
                                <td><?= htmlspecialchars($advance['user_name'] ?? 'Unknown') ?></td>
                                <td><strong>₹<?= number_format($advance['amount'] ?? 0, 2) ?></strong></td>
                                <td><?= htmlspecialchars($advance['reason'] ?? '') ?></td>
                                <td><?= htmlspecialchars($advance['requested_date'] ?? $advance['created_at'] ?? '') ?></td>
                                <td>
                                    <div class="ab-container">
                                        <a class="ab-btn ab-btn--view" onclick="viewItem('advance', <?= $advance['id'] ?>)" data-tooltip="View Details">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14,2 14,8 20,8"></polyline>
                                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                            </svg>
                                        </a>
                                        <button class="ab-btn ab-btn--approve" onclick="approveItem('advance', <?= $advance['id'] ?>)" data-tooltip="Approve Advance">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <polyline points="20,6 9,17 4,12"></polyline>
                                            </svg>
                                        </button>
                                        <button class="ab-btn ab-btn--reject" onclick="rejectItem('advance', <?= $advance['id'] ?>)" data-tooltip="Reject Advance">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">💳</div>
                <h3>No Pending Advance Requests</h3>
                <p>All advance requests have been processed.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function approveItem(type, id) {
    if (confirm('Approve this ' + type + '?')) {
        const formData = new FormData();
        formData.append('type', type);
        formData.append('id', id);
        formData.append('remarks', '');
        
        fetch('/ergon/owner/approve-request', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Request approved successfully!');
                location.reload();
            } else {
                alert('Failed to approve request: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to approve request');
        });
    }
}

function rejectItem(type, id) {
    const reason = prompt('Please provide a reason for rejection:');
    if (reason !== null && reason.trim() !== '') {
        const formData = new FormData();
        formData.append('type', type);
        formData.append('id', id);
        formData.append('remarks', reason);
        
        fetch('/ergon/owner/reject-request', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Request rejected successfully!');
                location.reload();
            } else {
                alert('Failed to reject request: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to reject request');
        });
    }
}

function viewItem(type, id) {
    window.location.href = `/ergon/owner/approvals/view/${type}/${id}`;
}

function refreshData() {
    location.reload();
}

function exportApprovals() {
    window.open('/ergon/reports/approvals-export', '_blank');
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>