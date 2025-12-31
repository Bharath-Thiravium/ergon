<?php
$active_page = 'followups';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>👁️</span> View Follow-up</h1>
        <p>Follow-up details and history</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/followups" class="btn btn--secondary">
            <span>←</span> Back to Follow-ups
        </a>
        <?php if (!empty($followup)): ?>
            <a href="/ergon/followups/edit/<?= $followup['id'] ?>" class="btn btn--primary">
                <span>✏️</span> Edit
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($followup)): ?>
    <div class="card">
        <div class="card__header">
            <h2 class="card__title"><?= htmlspecialchars($followup['title']) ?></h2>
            <div class="card__actions">
                <?php 
                $statusClass = match($followup['status']) {
                    'completed' => 'success',
                    'in_progress' => 'info',
                    'postponed' => 'warning',
                    'cancelled' => 'danger',
                    default => 'secondary'
                };
                $statusIcon = match($followup['status']) {
                    'completed' => '✅',
                    'in_progress' => '⚡',
                    'postponed' => '🔄',
                    'cancelled' => '❌',
                    default => '⏳'
                };
                ?>
                <span class="badge badge--<?= $statusClass ?>">
                    <?= $statusIcon ?> <?= ucfirst(str_replace('_', ' ', $followup['status'])) ?>
                </span>
            </div>
        </div>
        <div class="card__body">
            <div class="followup-details">
                <div class="detail-row">
                    <label>Type:</label>
                    <span class="badge badge--<?= ($followup['followup_type'] === 'task') ? 'info' : 'secondary' ?>">
                        <?= ($followup['followup_type'] === 'task') ? '🔗 Task-linked' : '📞 Standalone' ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <label>Follow-up Date:</label>
                    <span><?= date('M d, Y', strtotime($followup['follow_up_date'])) ?></span>
                </div>
                
                <?php if ($followup['contact_name']): ?>
                    <div class="detail-row">
                        <label>Contact:</label>
                        <span>
                            👤 <?= htmlspecialchars($followup['contact_name']) ?>
                            <?php if ($followup['contact_company']): ?>
                                - <?= htmlspecialchars($followup['contact_company']) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if ($followup['task_title']): ?>
                    <div class="detail-row">
                        <label>Linked Task:</label>
                        <span>🔗 <?= htmlspecialchars($followup['task_title']) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($followup['user_name']): ?>
                    <div class="detail-row">
                        <label>Assigned To:</label>
                        <span>👨💼 <?= htmlspecialchars($followup['user_name']) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($followup['description']): ?>
                    <div class="detail-row">
                        <label>Description:</label>
                        <div class="description-content">
                            <?= nl2br(htmlspecialchars($followup['description'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <label>Created:</label>
                    <span><?= date('M d, Y H:i', strtotime($followup['created_at'])) ?></span>
                </div>
                
                <div class="detail-row">
                    <label>Last Updated:</label>
                    <span><?= date('M d, Y H:i', strtotime($followup['updated_at'])) ?></span>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card__body">
            <div class="empty-state">
                <div class="empty-icon">❌</div>
                <h3>Follow-up Not Found</h3>
                <p>The requested follow-up could not be found.</p>
                <a href="/ergon/followups" class="btn btn--primary">
                    Back to Follow-ups
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.followup-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.detail-row {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-row label {
    font-weight: 600;
    color: #374151;
    min-width: 120px;
    flex-shrink: 0;
}

.detail-row span {
    color: #6b7280;
    flex: 1;
}

.description-content {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 6px;
    border-left: 4px solid #3b82f6;
    line-height: 1.6;
    color: #4b5563;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge--success { background: #dcfce7; color: #16a34a; }
.badge--info { background: #dbeafe; color: #3b82f6; }
.badge--warning { background: #fef3c7; color: #d97706; }
.badge--danger { background: #fee2e2; color: #dc2626; }
.badge--secondary { background: #f3f4f6; color: #6b7280; }

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    color: #374151;
}

.empty-state p {
    margin: 0 0 1.5rem 0;
}

.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 6px;
    border: 1px solid;
}

.alert-danger {
    background-color: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
}

@media (max-width: 768px) {
    .detail-row {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .detail-row label {
        min-width: auto;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
