<?php
$title = 'Follow-up History';
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
        <h1><span>📋</span> Follow-up History</h1>
        <p>Track all changes and updates</p>
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

<!-- Follow-up Summary Card -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📋</span> <?= htmlspecialchars($followup['title']) ?>
        </h2>
    </div>
    <div class="card__body">
        <div class="detail-grid">
            <div class="detail-item">
                <label>Company</label>
                <span><?= htmlspecialchars($followup['company_name'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Contact Person</label>
                <span><?= htmlspecialchars($followup['contact_person'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Project</label>
                <span><?= htmlspecialchars($followup['project_name'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Current Status</label>
                <span class="badge badge--<?= getStatusBadge($followup['status']) ?>">
                    <?= ucfirst($followup['status']) ?>
                </span>
            </div>
            <div class="detail-item">
                <label>Current Date</label>
                <span><?= date('M d, Y', strtotime($followup['follow_up_date'])) ?></span>
            </div>
            <div class="detail-item">
                <label>Created Date</label>
                <span><?= date('M d, Y H:i', strtotime($followup['created_at'])) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- History Timeline Card -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>🕰️</span> Change History
        </h2>
    </div>
    <div class="card__body">
        <?php if (!empty($history)): ?>
            <div style="position: relative; padding-left: 2rem;">
                <!-- Timeline line -->
                <div style="position: absolute; left: 0.75rem; top: 0; bottom: 0; width: 2px; background: #e5e7eb;"></div>
                
                <?php foreach ($history as $entry): ?>
                    <div style="position: relative; margin-bottom: 2rem; padding-bottom: 1rem;">
                        <!-- Timeline marker -->
                        <div style="position: absolute; left: -2rem; top: 0.5rem; width: 12px; height: 12px; background: #3b82f6; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px #e5e7eb;"></div>
                        
                        <!-- History content -->
                        <div style="background: #f9fafb; border-radius: 8px; padding: 1rem; border-left: 3px solid #3b82f6;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <h4 style="margin: 0; color: #1f2937; font-size: 1rem;"><?= ucfirst($entry['action']) ?></h4>
                                <span style="color: #6b7280; font-size: 0.875rem;"><?= date('M d, Y H:i', strtotime($entry['created_at'])) ?></span>
                            </div>
                            
                            <div>
                                <?php if ($entry['notes']): ?>
                                    <p style="margin: 0 0 0.5rem 0; color: #374151; line-height: 1.5;"><?= htmlspecialchars($entry['notes']) ?></p>
                                <?php endif; ?>
                                
                                <?php if ($entry['old_value'] && $entry['new_value']): ?>
                                    <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 4px; padding: 0.5rem; margin: 0.5rem 0; font-size: 0.875rem; color: #92400e;">
                                        <strong>Changed from:</strong> <?= htmlspecialchars($entry['old_value']) ?><br>
                                        <strong>Changed to:</strong> <?= htmlspecialchars($entry['new_value']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="margin-top: 0.5rem; color: #9ca3af; font-size: 0.875rem;">
                                    <small>By: <?= htmlspecialchars($entry['user_name'] ?? 'Unknown') ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h3>No History Available</h3>
                <p>No changes have been recorded for this follow-up yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>