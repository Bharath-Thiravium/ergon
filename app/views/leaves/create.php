<?php
$title = 'Apply for Leave';
$active_page = 'leaves';
ob_start();
?>

<div class="page-header">
    <h1>Apply for Leave</h1>
    <a href="/ergon/user/requests" class="btn btn--secondary">Back to My Requests</a>
</div>

<div class="card">
    <div class="card__body">
        <form method="POST" class="form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
            <div class="form-group">
                <label class="form-label">Leave Type</label>
                <select name="type" class="form-control" required>
                    <option value="">Select leave type</option>
                    <option value="Sick Leave">Sick Leave</option>
                    <option value="Casual Leave">Casual Leave</option>
                    <option value="Annual Leave">Annual Leave</option>
                    <option value="Emergency Leave">Emergency Leave</option>
                    <option value="Maternity Leave">Maternity Leave</option>
                    <option value="Paternity Leave">Paternity Leave</option>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" rows="4" placeholder="Please provide reason for leave..." required></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Submit Leave Request</button>
                <a href="/ergon/user/requests" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>