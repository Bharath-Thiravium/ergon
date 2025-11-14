<?php
$title = 'Edit Advance Request';
$active_page = 'advances';
ob_start();
?>

<div class="page-header">
    <h1>Edit Advance Request</h1>
    <a href="/ergon/advances" class="btn btn--secondary">Back to Advances</a>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Advance Request Details</h2>
    </div>
    <div class="card__body">
        <form method="POST" class="form">
            <div class="form-group">
                <label class="form-label">Advance Type *</label>
                <select name="type" class="form-control" required>
                    <option value="">Select advance type</option>
                    <option value="Salary Advance" <?= ($advance['type'] ?? '') === 'Salary Advance' ? 'selected' : '' ?>>Salary Advance</option>
                    <option value="Travel Advance" <?= ($advance['type'] ?? '') === 'Travel Advance' ? 'selected' : '' ?>>Travel Advance</option>
                    <option value="Emergency Advance" <?= ($advance['type'] ?? '') === 'Emergency Advance' ? 'selected' : '' ?>>Emergency Advance</option>
                    <option value="Project Advance" <?= ($advance['type'] ?? '') === 'Project Advance' ? 'selected' : '' ?>>Project Advance</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Amount (₹) *</label>
                <input type="number" name="amount" class="form-control" step="0.01" min="1" 
                       value="<?= htmlspecialchars($advance['amount'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Reason *</label>
                <textarea name="reason" class="form-control" rows="4" 
                         placeholder="Please provide reason for advance..." required><?= htmlspecialchars($advance['reason'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Expected Repayment Date</label>
                <input type="date" name="repayment_date" class="form-control" 
                       value="<?= htmlspecialchars($advance['repayment_date'] ?? '') ?>">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Update Advance Request</button>
                <a href="/ergon/advances" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
        
        <?php if ((($_SESSION['role'] ?? '') === 'owner' || ($_SESSION['role'] ?? '') === 'admin') && ($advance['user_id'] ?? 0) != ($_SESSION['user_id'] ?? 0)): ?>
        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
            <h3 style="margin-bottom: 1rem; color: var(--text-primary);">Approval Actions</h3>
            <div style="display: flex; gap: 1rem;">
                <?php if (($advance['status'] ?? '') === 'pending'): ?>
                <form method="POST" action="/ergon/advances/approve/<?= $advance['id'] ?>" style="display: inline;">
                    <button type="submit" class="btn btn--success" 
                            onclick="return confirm('Are you sure you want to approve this advance request?')">
                        ✅ Approve Request
                    </button>
                </form>
                <form method="POST" action="/ergon/advances/reject/<?= $advance['id'] ?>" style="display: inline;">
                    <button type="submit" class="btn btn--danger" 
                            onclick="return confirm('Are you sure you want to reject this advance request?')">
                        ❌ Reject Request
                    </button>
                </form>
                <?php else: ?>
                <div class="alert alert--info">
                    <strong>Status:</strong> This advance request has been <?= ucfirst($advance['status'] ?? 'pending') ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.btn--success {
    background: var(--success);
    color: white;
    border-color: var(--success);
}

.btn--success:hover {
    background: #047857;
    border-color: #047857;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>