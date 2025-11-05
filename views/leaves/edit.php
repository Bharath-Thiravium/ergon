<?php
$title = 'Edit Leave Request';
$active_page = 'leaves';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>✏️</span> Edit Leave Request</h1>
        <p>Modify your leave request details</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/leaves" class="btn btn--secondary">
            <span>←</span> Back to Leaves
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Leave Request Details</h2>
    </div>
    <div class="card__body">
        <form method="POST" action="/ergon/leaves/edit/<?= $leave['id'] ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Leave Type</label>
                    <select name="type" class="form-control" required>
                        <option value="sick" <?= $leave['type'] === 'sick' ? 'selected' : '' ?>>Sick Leave</option>
                        <option value="casual" <?= $leave['type'] === 'casual' ? 'selected' : '' ?>>Casual Leave</option>
                        <option value="annual" <?= $leave['type'] === 'annual' ? 'selected' : '' ?>>Annual Leave</option>
                        <option value="emergency" <?= $leave['type'] === 'emergency' ? 'selected' : '' ?>>Emergency Leave</option>
                        <option value="maternity" <?= $leave['type'] === 'maternity' ? 'selected' : '' ?>>Maternity Leave</option>
                        <option value="paternity" <?= $leave['type'] === 'paternity' ? 'selected' : '' ?>>Paternity Leave</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($leave['start_date']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($leave['end_date']) ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" rows="4" placeholder="Please provide reason for leave" required><?= htmlspecialchars($leave['reason']) ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <span>💾</span> Update Leave Request
                </button>
                <a href="/ergon/leaves" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>