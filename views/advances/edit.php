<?php
$title = 'Edit Advance Request';
$active_page = 'advances';
ob_start();
?>

<div class="header-actions" style="margin-bottom: var(--space-6);">
    <a href="/ergon/advances" class="btn btn--secondary">Back to Advances</a>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Edit Advance Request</h2>
    </div>
    <div class="card__body">
        <form method="POST" class="form">
            <div class="form-group">
                <label class="form-label">Advance Type</label>
                <select name="type" class="form-control" required>
                    <option value="">Select advance type</option>
                    <option value="Salary Advance" <?= ($advance['type'] ?? '') === 'Salary Advance' ? 'selected' : '' ?>>Salary Advance</option>
                    <option value="Travel Advance" <?= ($advance['type'] ?? '') === 'Travel Advance' ? 'selected' : '' ?>>Travel Advance</option>
                    <option value="Emergency Advance" <?= ($advance['type'] ?? '') === 'Emergency Advance' ? 'selected' : '' ?>>Emergency Advance</option>
                    <option value="Project Advance" <?= ($advance['type'] ?? '') === 'Project Advance' ? 'selected' : '' ?>>Project Advance</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Amount (₹)</label>
                <input type="number" name="amount" class="form-control" step="0.01" min="1" 
                       value="<?= htmlspecialchars($advance['amount'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" rows="4" required><?= htmlspecialchars($advance['reason'] ?? '') ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Update Advance Request</button>
                <a href="/ergon/advances" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>