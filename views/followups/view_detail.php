<?php
$title = 'Follow-up Details';
$active_page = 'followups';
ob_start();

function getStatusBadge($status) {
    switch ($status) {
        case 'completed': return 'success';
        case 'postponed':
        case 'rescheduled': return 'warning';
        case 'cancelled': return 'danger';
        default: return 'info';
    }
}
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📞</span> Follow-up Details</h1>
        <p>View follow-up information and history</p>
    </div>
    <div class="page-actions">
        <?php if ($followup['status'] !== 'completed'): ?>
            <a href="/ergon/followups/reschedule/<?= $followup['id'] ?>" class="btn btn--warning">
                <span>📅</span> Reschedule
            </a>
            <form method="POST" action="/ergon/followups" style="display: inline;">
                <input type="hidden" name="action" value="complete">
                <input type="hidden" name="id" value="<?= $followup['id'] ?>">
                <button type="submit" class="btn btn--success" onclick="return confirm('Mark as completed?')">
                    <span>✅</span> Complete
                </button>
            </form>
        <?php endif; ?>
        <a href="/ergon/followups" class="btn btn--secondary">
            <span>←</span> Back to Follow-ups
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📞</span> <?= htmlspecialchars($followup['title']) ?>
        </h2>
    </div>
    <div class="card__body">
        <div class="detail-grid">
            <div class="detail-item">
                <label>Title</label>
                <span><?= htmlspecialchars($followup['title']) ?></span>
            </div>
            <div class="detail-item">
                <label>Company</label>
                <span><?= htmlspecialchars($followup['company_name'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Contact Person</label>
                <span><?= htmlspecialchars($followup['contact_person'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Contact Phone</label>
                <span><?= $followup['contact_phone'] ? '<a href="tel:' . $followup['contact_phone'] . '" class="text-primary">' . htmlspecialchars($followup['contact_phone']) . '</a>' : 'N/A' ?></span>
            </div>
            <div class="detail-item">
                <label>Project</label>
                <span><?= htmlspecialchars($followup['project_name'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Follow-up Date</label>
                <span><?= date('M d, Y', strtotime($followup['follow_up_date'])) ?></span>
            </div>
            <div class="detail-item">
                <label>Reminder Time</label>
                <span><?= $followup['reminder_time'] ? date('g:i A', strtotime($followup['reminder_time'])) : 'No reminder set' ?></span>
            </div>
            <div class="detail-item">
                <label>Status</label>
                <span class="badge badge--<?= getStatusBadge($followup['status']) ?>">
                    <?= ucfirst($followup['status']) ?>
                </span>
            </div>
            <div class="detail-item">
                <label>Created Date</label>
                <span><?= date('M d, Y H:i', strtotime($followup['created_at'])) ?></span>
            </div>
            <?php if ($followup['completed_at']): ?>
            <div class="detail-item">
                <label>Completed Date</label>
                <span><?= date('M d, Y H:i', strtotime($followup['completed_at'])) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($followup['updated_at']): ?>
            <div class="detail-item">
                <label>Last Updated</label>
                <span><?= date('M d, Y H:i', strtotime($followup['updated_at'])) ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($followup['description']): ?>
        <div style="margin-top: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 6px; border-left: 3px solid #10b981;">
            <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">Description:</strong>
            <p style="margin: 0; color: #6b7280; line-height: 1.5;"><?= nl2br(htmlspecialchars($followup['description'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>