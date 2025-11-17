<?php
$title = 'Reschedule Follow-up';
$active_page = 'followups';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📅</span> Reschedule Follow-up</h1>
        <p>Change the follow-up date and time</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/followups/view/<?= $followup['id'] ?>" class="btn btn--primary">
            <span>👁️</span> View Details
        </a>
        <a href="/ergon/followups" class="btn btn--secondary">
            <span>←</span> Back to Follow-ups
        </a>
    </div>
</div>

<!-- Current Details Card -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📅</span> Current Details: <?= htmlspecialchars($followup['title']) ?>
        </h2>
    </div>
    <div class="card__body">
        <div class="detail-grid">
            <div class="detail-item">
                <label>Current Date</label>
                <span><?= date('M d, Y', strtotime($followup['follow_up_date'])) ?></span>
            </div>
            <div class="detail-item">
                <label>Current Time</label>
                <span><?= $followup['reminder_time'] ? date('g:i A', strtotime($followup['reminder_time'])) : 'No time set' ?></span>
            </div>
            <div class="detail-item">
                <label>Company</label>
                <span><?= htmlspecialchars($followup['company_name'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Contact</label>
                <span><?= htmlspecialchars($followup['contact_person'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Project</label>
                <span><?= htmlspecialchars($followup['project_name'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Status</label>
                <span class="badge badge--info"><?= ucfirst($followup['status']) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Reschedule Form Card -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>✏️</span> Reschedule Information
        </h2>
    </div>
    <div class="card__body">
        <form method="POST" action="/ergon/followups/reschedule">
            <input type="hidden" name="followup_id" value="<?= $followup['id'] ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="new_date">New Date *</label>
                    <input type="date" name="new_date" id="new_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Time (Optional)</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="number" name="hour" min="1" max="12" placeholder="Hour" class="form-control" style="flex: 1;">
                        <span>:</span>
                        <input type="number" name="minute" min="0" max="59" placeholder="Min" class="form-control" style="flex: 1;">
                        <select name="ampm" class="form-control" style="flex: 1;">
                            <option value="">--</option>
                            <option value="AM">AM</option>
                            <option value="PM">PM</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="reason">Reason for Rescheduling</label>
                <textarea name="reason" id="reason" class="form-control" rows="4" placeholder="Please provide a reason for rescheduling this follow-up..."></textarea>
            </div>
            
            <div class="form-actions">
                <a href="/ergon/followups" class="btn btn--secondary">Cancel</a>
                <button type="submit" class="btn btn--warning">
                    <span>📅</span> Reschedule Follow-up
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>