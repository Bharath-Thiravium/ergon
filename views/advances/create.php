<?php
$title = 'Request Advance';
$active_page = 'requests';
ob_start();
?>

<div class="header-actions" style="margin-bottom: var(--space-6);">
    <a href="/ergon_clean/public/advances" class="btn btn--secondary">Back to Advances</a>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Request Advance</h2>
    </div>
    <div class="card__body">
        <form method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
            <div class="form-group">
                <label class="form-label">Advance Type</label>
                <select name="type" class="form-control" required>
                    <option value="">Select advance type</option>
                    <option value="Salary Advance">Salary Advance</option>
                    <option value="Travel Advance">Travel Advance</option>
                    <option value="Emergency Advance">Emergency Advance</option>
                    <option value="Project Advance">Project Advance</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Amount (₹)</label>
                <input type="number" name="amount" class="form-control" step="0.01" min="1" placeholder="Enter amount" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" rows="4" placeholder="Please provide reason for advance..." required></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Expected Repayment Date</label>
                <input type="date" name="repayment_date" class="form-control" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Submit Advance Request</button>
                <a href="/ergon_clean/public/advances" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>