<?php
$title = 'Edit Leave Request';
$active_page = 'leaves';
ob_start();
?>

<div class="compact-header">
    <h1>✏️ Edit Leave Request</h1>
    <div class="header-actions">
        <a href="/ergon/leaves" class="btn-back">← Back</a>
    </div>
</div>

<div class="compact-form">
    <form method="POST" action="/ergon/leaves/edit/<?= $leave['id'] ?>">
        <div class="form-section">
            <div class="form-grid">
                <div class="form-group">
                    <label for="type">📅 Leave Type</label>
                    <select name="type" id="type" required>
                        <?php $currentType = $leave['leave_type'] ?? $leave['type'] ?? ''; ?>
                        <option value="sick" <?= $currentType === 'sick' ? 'selected' : '' ?>>Sick Leave</option>
                        <option value="casual" <?= $currentType === 'casual' ? 'selected' : '' ?>>Casual Leave</option>
                        <option value="annual" <?= $currentType === 'annual' ? 'selected' : '' ?>>Annual Leave</option>
                        <option value="emergency" <?= $currentType === 'emergency' ? 'selected' : '' ?>>Emergency Leave</option>
                        <option value="maternity" <?= $currentType === 'maternity' ? 'selected' : '' ?>>Maternity Leave</option>
                        <option value="paternity" <?= $currentType === 'paternity' ? 'selected' : '' ?>>Paternity Leave</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="start_date">📅 Start Date</label>
                    <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($leave['start_date']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date">📅 End Date</label>
                    <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($leave['end_date']) ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="reason">📝 Reason</label>
                <textarea name="reason" id="reason" rows="4" placeholder="Please provide reason for leave" required><?= htmlspecialchars($leave['reason']) ?></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                ✨ Update Leave Request
            </button>
            <a href="/ergon/leaves" class="btn-secondary">❌ Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>