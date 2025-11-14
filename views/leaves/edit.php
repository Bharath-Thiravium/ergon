<?php
$title = 'Edit Leave Request';
$active_page = 'leaves';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🏦️</span> Edit Leave Request</h1>
        <p>Update your leave application details</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/leaves" class="btn btn--secondary">
            <span>←</span> Back to Leaves
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📝</span> Leave Application Form
        </h2>
    </div>
    <div class="card__body">
        <form method="POST" action="/ergon/leaves/edit/<?= $leave['id'] ?>" class="form">
            <div class="form-row">
                <div class="form-group">
                    <label for="type" class="form-label">Leave Type *</label>
                    <select class="form-control" id="type" name="type" required>
                        <?php $currentType = $leave['leave_type'] ?? $leave['type'] ?? ''; ?>
                        <option value="casual" <?= $currentType === 'casual' ? 'selected' : '' ?>>Casual Leave</option>
                        <option value="sick" <?= $currentType === 'sick' ? 'selected' : '' ?>>Sick Leave</option>
                        <option value="annual" <?= $currentType === 'annual' ? 'selected' : '' ?>>Annual Leave</option>
                        <option value="emergency" <?= $currentType === 'emergency' ? 'selected' : '' ?>>Emergency Leave</option>
                        <option value="maternity" <?= $currentType === 'maternity' ? 'selected' : '' ?>>Maternity Leave</option>
                        <option value="paternity" <?= $currentType === 'paternity' ? 'selected' : '' ?>>Paternity Leave</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date" class="form-label">Start Date *</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($leave['start_date']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date" class="form-label">End Date *</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($leave['end_date']) ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="reason" class="form-label">Reason for Leave *</label>
                <textarea class="form-control" id="reason" name="reason" rows="4" 
                         placeholder="Please provide a detailed reason for your leave request..." required><?= htmlspecialchars($leave['reason']) ?></textarea>
                <small class="form-text">Minimum 10 characters required</small>
            </div>
            
            <div class="form-group">
                <label for="contact_during_leave" class="form-label">Emergency Contact During Leave</label>
                <input type="tel" class="form-control" id="contact_during_leave" name="contact_during_leave" 
                       value="<?= htmlspecialchars($leave['contact_during_leave'] ?? '') ?>"
                       placeholder="Phone number for emergency contact">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    📤 Update Leave Request
                </button>
                <a href="/ergon/leaves" class="btn btn--secondary">❌ Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>