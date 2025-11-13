<?php
$title = 'Edit Advance Request';
$active_page = 'advances';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>💰</span> Edit Advance Request</h1>
        <p>Modify your advance request details</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/advances" class="btn btn--secondary">
            <span>←</span> Back to Advances
        </a>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert--error">
    ❌ <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>✏️</span> Edit Advance Request
        </h2>
    </div>
    <div class="card__body">
        <form method="POST" class="form">
            <div class="form-row">
                <div class="form-group">
                    <label for="type" class="form-label">Advance Type</label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="Salary Advance" <?= ($advance['type'] ?? '') === 'Salary Advance' ? 'selected' : '' ?>>Salary Advance</option>
                        <option value="Travel Advance" <?= ($advance['type'] ?? '') === 'Travel Advance' ? 'selected' : '' ?>>Travel Advance</option>
                        <option value="Medical Advance" <?= ($advance['type'] ?? '') === 'Medical Advance' ? 'selected' : '' ?>>Medical Advance</option>
                        <option value="Emergency Advance" <?= ($advance['type'] ?? '') === 'Emergency Advance' ? 'selected' : '' ?>>Emergency Advance</option>
                        <option value="General Advance" <?= ($advance['type'] ?? '') === 'General Advance' ? 'selected' : '' ?>>General Advance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amount" class="form-label">Amount (₹)</label>
                    <input type="number" id="amount" name="amount" class="form-control" 
                           value="<?= htmlspecialchars($advance['amount'] ?? '') ?>" 
                           min="1" step="0.01" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="reason" class="form-label">Reason for Advance</label>
                <textarea id="reason" name="reason" class="form-control" rows="4" 
                          placeholder="Please provide a detailed reason for the advance request..." required><?= htmlspecialchars($advance['reason'] ?? '') ?></textarea>
            </div>
            
            <div class="card__footer">
                <button type="submit" class="btn btn--primary">
                    <span>💾</span> Update Advance Request
                </button>
                <a href="/ergon/advances" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h3 class="card__title">
            <span>ℹ️</span> Request Information
        </h3>
    </div>
    <div class="card__body">
        <div class="info-grid">
            <div class="info-item">
                <label>Request ID</label>
                <span>#ADV-<?= str_pad($advance['id'] ?? 0, 4, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="info-item">
                <label>Current Status</label>
                <span class="badge badge--warning">Pending</span>
            </div>
            <div class="info-item">
                <label>Requested Date</label>
                <span><?= date('M d, Y', strtotime($advance['created_at'] ?? 'now')) ?></span>
            </div>
            <div class="info-item">
                <label>Current Amount</label>
                <span>₹<?= number_format($advance['amount'] ?? 0, 2) ?></span>
            </div>
        </div>
    </div>
</div>

<style>
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-item label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
}

.info-item span {
    font-weight: 600;
    color: #1f2937;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>