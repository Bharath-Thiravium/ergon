<?php
$title = 'Request Advance';
$active_page = 'requests';
ob_start();
?>

<div class="page-header">
    <h1>Request Advance</h1>
    <a href="/ergon/user/requests" class="btn btn--secondary">Back to My Requests</a>
</div>

<div class="card">
    <div class="card__body">
        <form method="POST" class="form">
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
                <a href="/ergon/user/requests" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>