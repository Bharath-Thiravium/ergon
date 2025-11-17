<?php
$active_page = 'contact_followups';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📋</span> Follow-up History</h1>
        <p>Audit trail for follow-up: <?= htmlspecialchars($followup['title']) ?></p>
    </div>
    <div class="page-actions">
        <a href="/ergon/contacts/followups" class="btn btn--secondary">
            <span>←</span> Back to Contacts
        </a>
    </div>
</div>

<!-- Follow-up Info Card -->
<div class="followup-info-card">
    <div class="info-header">
        <h3><?= htmlspecialchars($followup['title']) ?></h3>
        <span class="badge badge--<?= $this->getStatusBadgeClass($followup['status']) ?>">
            <?= ucfirst($followup['status']) ?>
        </span>
    </div>
    <div class="info-details">
        <div class="detail-item">
            <span class="label">Due Date:</span>
            <span class="value"><?= date('M d, Y', strtotime($followup['follow_up_date'])) ?></span>
        </div>
        <?php if ($followup['description']): ?>
            <div class="detail-item">
                <span class="label">Description:</span>
                <span class="value"><?= htmlspecialchars($followup['description']) ?></span>
            </div>
        <?php endif; ?>
        <div class="detail-item">
            <span class="label">Created:</span>
            <span class="value"><?= date('M d, Y H:i', strtotime($followup['created_at'])) ?></span>
        </div>
    </div>
</div>

<!-- History Timeline -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Activity History</h2>
        <div class="card__actions">
            <span class="badge badge--info"><?= count($history) ?> entries</span>
        </div>
    </div>
    <div class="card__body">
        <?php if (!empty($history)): ?>
            <div class="history-timeline">
                <?php foreach ($history as $entry): ?>
                    <div class="history-entry">
                        <div class="history-marker">
                            <div class="marker-dot <?= $entry['action'] ?>"></div>
                        </div>
                        <div class="history-content">
                            <div class="history-header">
                                <h4 class="history-action"><?= ucfirst($entry['action']) ?></h4>
                                <span class="history-timestamp">
                                    <?= date('M d, Y \a\t H:i', strtotime($entry['created_at'])) ?>
                                </span>
                            </div>
                            
                            <?php if ($entry['notes']): ?>
                                <div class="history-notes">
                                    <?= nl2br(htmlspecialchars($entry['notes'])) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($entry['old_value'] && $entry['new_value']): ?>
                                <div class="history-changes">
                                    <div class="change-item">
                                        <span class="change-label">From:</span>
                                        <span class="change-old"><?= htmlspecialchars($entry['old_value']) ?></span>
                                    </div>
                                    <div class="change-item">
                                        <span class="change-label">To:</span>
                                        <span class="change-new"><?= htmlspecialchars($entry['new_value']) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="history-footer">
                                <span class="history-user">
                                    <span class="icon">👤</span>
                                    <?= htmlspecialchars($entry['user_name'] ?? 'System') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h3>No History Available</h3>
                <p>No activity has been recorded for this follow-up yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.followup-info-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.info-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.info-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 1.25rem;
}

.info-details {
    display: grid;
    gap: 0.75rem;
}

.detail-item {
    display: flex;
    gap: 1rem;
}

.detail-item .label {
    font-weight: 500;
    color: #6b7280;
    min-width: 100px;
}

.detail-item .value {
    color: #1f2937;
    flex: 1;
}

.history-timeline {
    position: relative;
    padding-left: 2rem;
}

.history-timeline::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}

.history-entry {
    position: relative;
    margin-bottom: 2rem;
}

.history-marker {
    position: absolute;
    left: -2rem;
    top: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.marker-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 0 2px #e5e7eb;
}

.marker-dot.created {
    background: #10b981;
    box-shadow: 0 0 0 2px #10b981;
}

.marker-dot.completed {
    background: #059669;
    box-shadow: 0 0 0 2px #059669;
}

.marker-dot.rescheduled,
.marker-dot.postponed {
    background: #f59e0b;
    box-shadow: 0 0 0 2px #f59e0b;
}

.marker-dot.cancelled {
    background: #ef4444;
    box-shadow: 0 0 0 2px #ef4444;
}

.history-content {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
    margin-left: 1rem;
}

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.history-action {
    margin: 0;
    color: #1f2937;
    font-size: 1rem;
}

.history-timestamp {
    color: #6b7280;
    font-size: 0.875rem;
}

.history-notes {
    color: #4b5563;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.history-changes {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.change-item {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.change-item:last-child {
    margin-bottom: 0;
}

.change-label {
    font-weight: 500;
    color: #6b7280;
    min-width: 50px;
}

.change-old {
    color: #dc2626;
    text-decoration: line-through;
}

.change-new {
    color: #059669;
    font-weight: 500;
}

.history-footer {
    display: flex;
    justify-content: flex-end;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
}

.history-user {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.history-user .icon {
    width: 16px;
    text-align: center;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #6b7280;
}

@media (max-width: 768px) {
    .info-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .history-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .detail-item {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .detail-item .label {
        min-width: auto;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>