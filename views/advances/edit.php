<?php
$title = 'Edit Advance Request';
$active_page = 'advances';
ob_start();
?>

<div class="compact-header">
    <h1>💰 Edit Advance Request</h1>
    <div class="header-actions">
        <a href="/ergon/advances" class="btn-back">← Back</a>
    </div>
</div>

<div class="compact-form">
    <form method="POST">
        <div class="form-section">
            <div class="form-grid">
                <div class="form-group">
                    <label for="type">💰 Advance Type</label>
                    <select id="type" name="type" required>
                        <option value="">Select Type</option>
                        <option value="Salary Advance" <?= ($advance['type'] ?? '') === 'Salary Advance' ? 'selected' : '' ?>>Salary Advance</option>
                        <option value="Travel Advance" <?= ($advance['type'] ?? '') === 'Travel Advance' ? 'selected' : '' ?>>Travel Advance</option>
                        <option value="Medical Advance" <?= ($advance['type'] ?? '') === 'Medical Advance' ? 'selected' : '' ?>>Medical Advance</option>
                        <option value="Emergency Advance" <?= ($advance['type'] ?? '') === 'Emergency Advance' ? 'selected' : '' ?>>Emergency Advance</option>
                        <option value="General Advance" <?= ($advance['type'] ?? '') === 'General Advance' ? 'selected' : '' ?>>General Advance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amount">💵 Amount (₹)</label>
                    <input type="number" id="amount" name="amount" value="<?= htmlspecialchars($advance['amount'] ?? '') ?>" min="1" step="0.01" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="reason">📝 Reason for Advance</label>
                <textarea id="reason" name="reason" rows="4" placeholder="Please provide a detailed reason for the advance request..." required><?= htmlspecialchars($advance['reason'] ?? '') ?></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                ✨ Update Advance Request
            </button>
            <a href="/ergon/advances" class="btn-secondary">❌ Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>