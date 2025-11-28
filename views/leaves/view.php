<?php
$title = 'Leave Request Details';
$active_page = 'leaves';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📅</span> Leave Request Details</h1>
        <p>View leave request information</p>
    </div>
    <div class="page-actions">
        <?php if (($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'owner') && strtolower($leave['status']) === 'pending'): ?>
        <a href="/ergon/leaves/approve/<?= $leave['id'] ?>" class="btn btn--success" onclick="return confirm('Approve this leave request?')">
            ✅ Approve
        </a>
        <button class="btn btn--danger" onclick="showRejectModal(<?= $leave['id'] ?>)">
            ❌ Reject
        </button>
        <?php endif; ?>
        <a href="/ergon/leaves" class="btn btn--secondary">
            <span>←</span> Back to Leaves
        </a>
    </div>
</div>

<div class="leave-compact">
    <div class="card">
        <div class="card__header">
            <div class="leave-title-row">
                <h2 class="leave-title">📅 <?= htmlspecialchars($leave['leave_type'] ?? 'Leave Request') ?></h2>
                <div class="leave-badges">
                    <?php 
                    $status = $leave['status'] ?? 'pending';
                    $statusClass = match(strtolower($status)) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning'
                    };
                    $statusIcon = match(strtolower($status)) {
                        'approved' => '✅',
                        'rejected' => '❌',
                        default => '⏳'
                    };
                    $days = 0;
                    if (isset($leave['days_requested']) && $leave['days_requested'] > 0) {
                        $days = $leave['days_requested'];
                    } else {
                        $start = new DateTime($leave['start_date']);
                        $end = new DateTime($leave['end_date']);
                        $days = $end->diff($start)->days + 1;
                    }
                    ?>
                    <span class="badge badge--<?= $statusClass ?>"><?= $statusIcon ?> <?= ucfirst($status) ?></span>
                    <div class="days-display">
                        <span class="days-text"><?= $days ?> day<?= $days != 1 ? 's' : '' ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card__body">
            <?php if ($leave['reason']): ?>
            <div class="description-compact">
                <strong>Reason:</strong> <?= nl2br(htmlspecialchars($leave['reason'])) ?>
            </div>
            <?php endif; ?>
            
            <div class="details-compact">
                <div class="detail-group">
                    <h4>👤 Employee Details</h4>
                    <div class="detail-items">
                        <span><strong>Name:</strong> 👤 <?= htmlspecialchars($leave['user_name'] ?? 'Unknown') ?></span>
                        <span><strong>Leave Type:</strong> 🏷️ <?= htmlspecialchars($leave['leave_type'] ?? 'Annual') ?></span>
                        <span><strong>Duration:</strong> 📅 <?= $days ?> day<?= $days != 1 ? 's' : '' ?></span>
                    </div>
                </div>
                
                <div class="detail-group">
                    <h4>📅 Leave Period</h4>
                    <div class="detail-items">
                        <span><strong>Start Date:</strong> 📅 <?= date('M d, Y', strtotime($leave['start_date'] ?? 'now')) ?></span>
                        <span><strong>End Date:</strong> 📅 <?= date('M d, Y', strtotime($leave['end_date'] ?? 'now')) ?></span>
                        <span><strong>Requested:</strong> 📅 <?= date('M d, Y', strtotime($leave['created_at'] ?? 'now')) ?></span>
                    </div>
                </div>
                
                <div class="detail-group">
                    <h4>📋 Status</h4>
                    <div class="detail-items">
                        <span><strong>Current Status:</strong> 
                            <span class="badge badge--<?= $statusClass ?>"><?= $statusIcon ?> <?= ucfirst($status) ?></span>
                        </span>
                        <?php if (isset($leave['approved_at']) && $leave['approved_at']): ?>
                        <span><strong>Processed:</strong> 📅 <?= date('M d, Y', strtotime($leave['approved_at'])) ?></span>
                        <?php endif; ?>
                        <?php if (isset($leave['rejection_reason']) && $leave['rejection_reason']): ?>
                        <span><strong>Rejection Reason:</strong> <?= htmlspecialchars($leave['rejection_reason']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Reject Leave Request</h3>
        <form method="POST" action="" id="rejectForm">
            <div class="form-group">
                <label for="rejection_reason">Rejection Reason:</label>
                <textarea name="rejection_reason" id="rejection_reason" required rows="3" placeholder="Please provide a reason for rejection..."></textarea>
            </div>
            <div class="form-actions">
                <button type="button" onclick="closeRejectModal()" class="btn btn--secondary">Cancel</button>
                <button type="submit" class="btn btn--danger">Reject Leave</button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal(leaveId) {
    document.getElementById('rejectForm').action = '/ergon/leaves/reject/' + leaveId;
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: vertical;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

<style>
.leave-compact {
    max-width: 1000px;
    margin: 0 auto;
}

.leave-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    width: 100%;
    gap: 1.5rem;
    min-height: 2rem;
}

.leave-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    flex: 1 1 auto;
    min-width: 200px;
    max-width: calc(100% - 200px);
    overflow-wrap: break-word;
    word-break: break-word;
    line-height: 1.3;
}

.leave-badges {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 0 0 auto;
    min-width: 180px;
    justify-content: flex-end;
}

.days-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.days-text {
    font-size: 1rem;
    font-weight: 600;
    color: var(--primary);
    background: var(--bg-secondary);
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    border: 1px solid var(--border-color);
}

.description-compact {
    background: var(--bg-secondary);
    padding: 0.75rem;
    border-radius: 6px;
    border-left: 3px solid var(--primary);
    margin-bottom: 1rem;
    font-size: 0.9rem;
    line-height: 1.4;
}

.details-compact {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.detail-group {
    background: var(--bg-secondary);
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.detail-group h4 {
    margin: 0 0 0.75rem 0;
    font-size: 0.9rem;
    color: var(--primary);
    font-weight: 600;
}

.detail-items {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-items span {
    font-size: 0.85rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-items strong {
    color: var(--text-primary);
    min-width: 80px;
    font-size: 0.8rem;
}

@media (max-width: 768px) {
    .leave-title-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        min-height: auto;
    }
    
    .leave-title {
        max-width: 100%;
        min-width: auto;
    }
    
    .leave-badges {
        width: 100%;
        min-width: auto;
        justify-content: flex-start;
        flex-wrap: wrap;
    }
    
    .details-compact {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>