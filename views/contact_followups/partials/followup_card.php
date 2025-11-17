<?php
/**
 * Reusable Follow-up Card Component
 * Usage: include with $followup array containing followup data
 */
?>

<div class="followup-card" data-id="<?= $followup['id'] ?>" data-status="<?= $followup['status'] ?>">
    <div class="followup-card__header">
        <div class="followup-title">
            <h4><?= htmlspecialchars($followup['title']) ?></h4>
            <div class="followup-badges">
                <span class="badge badge--<?= $this->getStatusBadgeClass($followup['status']) ?>">
                    <?= ucfirst($followup['status']) ?>
                </span>
                <?php if (isset($followup['followup_type'])): ?>
                    <span class="badge badge--<?= $followup['followup_type'] === 'task-linked' ? 'info' : 'secondary' ?>">
                        <?= $followup['followup_type'] === 'task-linked' ? '🔗 Task-linked' : '📞 Standalone' ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="followup-date">
            <div class="date-primary"><?= date('M d', strtotime($followup['follow_up_date'])) ?></div>
            <div class="date-year"><?= date('Y', strtotime($followup['follow_up_date'])) ?></div>
        </div>
    </div>
    
    <div class="followup-card__body">
        <?php if ($followup['description']): ?>
            <div class="followup-description">
                <?= nl2br(htmlspecialchars(substr($followup['description'], 0, 150))) ?>
                <?php if (strlen($followup['description']) > 150): ?>
                    <span class="text-muted">...</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($followup['contact_name'])): ?>
            <div class="followup-contact">
                <span class="icon">👤</span>
                <span><?= htmlspecialchars($followup['contact_name']) ?></span>
                <?php if (isset($followup['contact_phone']) && $followup['contact_phone']): ?>
                    <a href="tel:<?= $followup['contact_phone'] ?>" class="phone-quick">
                        📞 <?= htmlspecialchars($followup['contact_phone']) ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($followup['task_title']) && $followup['task_title']): ?>
            <div class="linked-task">
                <span class="icon">🔗</span>
                <span>Task: <?= htmlspecialchars($followup['task_title']) ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Priority indicator -->
        <?php if (strtotime($followup['follow_up_date']) < time() && $followup['status'] !== 'completed'): ?>
            <div class="priority-indicator overdue">
                <span class="icon">⚠️</span>
                <span>Overdue</span>
            </div>
        <?php elseif (date('Y-m-d', strtotime($followup['follow_up_date'])) === date('Y-m-d')): ?>
            <div class="priority-indicator today">
                <span class="icon">📅</span>
                <span>Due Today</span>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="followup-card__actions">
        <?php if ($followup['status'] !== 'completed' && $followup['status'] !== 'cancelled'): ?>
            <button class="btn btn--sm btn--success" onclick="completeFollowup(<?= $followup['id'] ?>)" 
                    data-tooltip="Mark as completed">
                ✅
            </button>
            <button class="btn btn--sm btn--warning" onclick="rescheduleFollowup(<?= $followup['id'] ?>)" 
                    data-tooltip="Reschedule">
                📅
            </button>
            <button class="btn btn--sm btn--danger" onclick="cancelFollowup(<?= $followup['id'] ?>)" 
                    data-tooltip="Cancel follow-up">
                ❌
            </button>
        <?php endif; ?>
        
        <button class="btn btn--sm btn--info" onclick="showFollowupDetails(<?= $followup['id'] ?>)" 
                data-tooltip="View details">
            👁️
        </button>
        
        <button class="btn btn--sm btn--secondary" onclick="showHistory(<?= $followup['id'] ?>)" 
                data-tooltip="View history">
            📋
        </button>
        
        <?php if (isset($followup['contact_phone']) && $followup['contact_phone']): ?>
            <a href="tel:<?= $followup['contact_phone'] ?>" class="btn btn--sm btn--primary" 
               data-tooltip="Call contact">
                📞
            </a>
        <?php endif; ?>
    </div>
</div>

<style>
.followup-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
    position: relative;
}

.followup-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
}

.followup-card[data-status="completed"] {
    opacity: 0.8;
    background: #f9fafb;
}

.followup-card[data-status="completed"]::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: #10b981;
    border-radius: 4px 0 0 4px;
}

.followup-card__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.followup-title h4 {
    margin: 0 0 0.5rem 0;
    color: #1f2937;
    font-size: 1rem;
    line-height: 1.4;
}

.followup-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.followup-date {
    text-align: center;
    min-width: 60px;
}

.date-primary {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.9rem;
}

.date-year {
    font-size: 0.8rem;
    color: #6b7280;
}

.followup-card__body {
    margin-bottom: 1rem;
}

.followup-description {
    color: #4b5563;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 0.75rem;
}

.followup-contact,
.linked-task {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
    color: #6b7280;
}

.followup-contact .icon,
.linked-task .icon {
    width: 16px;
    text-align: center;
}

.phone-quick {
    color: #059669;
    text-decoration: none;
    margin-left: auto;
}

.phone-quick:hover {
    text-decoration: underline;
}

.priority-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.priority-indicator.overdue {
    background: #fef2f2;
    color: #dc2626;
}

.priority-indicator.today {
    background: #fffbeb;
    color: #d97706;
}

.followup-card__actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn--sm {
    padding: 0.375rem 0.5rem;
    font-size: 0.8rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn--success {
    background: #10b981;
    color: white;
}

.btn--success:hover {
    background: #059669;
}

.btn--warning {
    background: #f59e0b;
    color: white;
}

.btn--warning:hover {
    background: #d97706;
}

.btn--info {
    background: #3b82f6;
    color: white;
}

.btn--info:hover {
    background: #2563eb;
}

.btn--secondary {
    background: #6b7280;
    color: white;
}

.btn--secondary:hover {
    background: #4b5563;
}

.btn--primary {
    background: #059669;
    color: white;
}

.btn--primary:hover {
    background: #047857;
}

.btn--danger {
    background: #dc2626;
    color: white;
}

.btn--danger:hover {
    background: #b91c1c;
}

/* Tooltip styles */
[data-tooltip] {
    position: relative;
}

[data-tooltip]:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #1f2937;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    white-space: nowrap;
    z-index: 1000;
    margin-bottom: 0.25rem;
}

[data-tooltip]:hover::before {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: #1f2937;
    z-index: 1000;
}

@media (max-width: 768px) {
    .followup-card__header {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .followup-date {
        align-self: flex-end;
    }
    
    .followup-card__actions {
        justify-content: center;
    }
}
</style>