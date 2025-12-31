<?php
$title = 'Approve Advance Request';
$active_page = 'advances';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>✅</span> Approve Advance Request</h1>
        <p>Review and approve advance request</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/advances" class="btn btn--secondary">
            <span>←</span> Back to Advances
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success">
    ✅ Advance approved successfully!
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert--error">
    ❌ <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<div class="approval-container">
    <div class="card">
        <div class="card__header">
            <h2>💳 Advance Request Details</h2>
        </div>
        <div class="card__body">
            <div class="advance-details">
                <div class="detail-row">
                    <span class="detail-label">👤 Employee:</span>
                    <span class="detail-value"><?= htmlspecialchars($advance['user_name']) ?> (<?= htmlspecialchars($advance['user_email']) ?>)</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">🏷️ Type:</span>
                    <span class="detail-value"><?= htmlspecialchars($advance['type'] ?? 'General Advance') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">💰 Requested Amount:</span>
                    <span class="detail-value">₹<?= number_format($advance['amount'], 2) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">📝 Reason:</span>
                    <span class="detail-value"><?= nl2br(htmlspecialchars($advance['reason'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">📅 Requested Date:</span>
                    <span class="detail-value"><?= date('d M Y', strtotime($advance['requested_date'] ?? $advance['created_at'])) ?></span>
                </div>
                <?php if ($advance['repayment_date']): ?>
                <div class="detail-row">
                    <span class="detail-label">📅 Expected Repayment:</span>
                    <span class="detail-value"><?= date('d M Y', strtotime($advance['repayment_date'])) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="card" style="margin-top: 1.5rem;">
        <div class="card__header">
            <h2>✅ Approval Decision</h2>
        </div>
        <div class="card__body">
            <form method="POST" action="/ergon/advances/approve/<?= $advance['id'] ?>">
                <div class="form-group">
                    <label class="form-label" for="approved_amount">💵 Approved Amount (₹)</label>
                    <input type="number" 
                           id="approved_amount" 
                           name="approved_amount" 
                           class="form-control" 
                           value="<?= $advance['amount'] ?>" 
                           step="0.01" 
                           min="0" 
                           max="<?= $advance['amount'] ?>" 
                           required>
                    <small class="text-muted">Maximum: ₹<?= number_format($advance['amount'], 2) ?></small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="approval_remarks">📝 Approval Remarks (Optional)</label>
                    <textarea id="approval_remarks" 
                              name="approval_remarks" 
                              class="form-control" 
                              rows="3" 
                              placeholder="Add any comments or conditions for this approval..."></textarea>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn--success">
                        <span>✅</span> Approve Advance
                    </button>
                </div>
            </form>
            
            <hr style="margin: 2rem 0; border: 1px solid var(--border-color);">
            
            <form method="POST" action="/ergon/advances/reject/<?= $advance['id'] ?>">
                <div class="form-group">
                    <label class="form-label" for="rejection_reason">❌ Rejection Reason</label>
                    <textarea id="rejection_reason" 
                              name="rejection_reason" 
                              class="form-control" 
                              rows="3" 
                              placeholder="Please provide a reason for rejecting this advance request..." 
                              required></textarea>
                </div>
                
                <button type="submit" class="btn btn--danger" onclick="return confirm('Are you sure you want to reject this advance request?')">
                    <span>❌</span> Reject Advance
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.approval-container {
    max-width: 800px;
    margin: 0 auto;
}

.advance-details {
    background: var(--bg-secondary);
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 1rem;
}

.detail-row:last-child {
    margin-bottom: 0;
}

.detail-label {
    font-weight: 600;
    color: var(--text-primary);
    min-width: 150px;
    flex-shrink: 0;
}

.detail-value {
    color: var(--text-secondary);
    text-align: right;
    flex: 1;
    word-break: break-word;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-primary);
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 1rem;
    background: var(--bg-primary);
    color: var(--text-primary);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.1);
}

.btn-group {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.text-muted {
    color: var(--text-muted);
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .detail-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .detail-label {
        min-width: auto;
    }
    
    .detail-value {
        text-align: left;
    }
    
    .btn-group {
        flex-direction: column;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
