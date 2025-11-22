<?php
$active_page = 'followups';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📞</span> Follow-ups</h1>
        <p>Manage all your follow-up communications</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/followups/create" class="btn btn--primary">
            <span>➕</span> New Follow-up
        </a>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">All Follow-ups</h2>
        <div class="card__actions">
            <span class="badge badge--info"><?= count($followups) ?> follow-ups</span>
        </div>
    </div>
    <div class="card__body">
        <?php if (!empty($followups)): ?>
            <div class="followups-list">
                <?php foreach ($followups as $followup): ?>
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
                    $typeIcon = ($followup['followup_type'] === 'task') ? '🔗' : '📞';
                    $isOverdue = strtotime($followup['follow_up_date']) < strtotime('today') && $followup['status'] !== 'completed';
                    ?>
                    <div class="followup-card <?= $followup['status'] ?> <?= $isOverdue ? 'overdue' : '' ?>">
                        <div class="followup-card__header">
                            <div class="followup-icon <?= $followup['followup_type'] ?>">
                                <?= $typeIcon ?>
                            </div>
                            <div class="followup-title-section">
                                <h4 class="followup-title"><?= htmlspecialchars($followup['title']) ?></h4>
                                <div class="followup-meta">
                                    <span class="followup-date <?= $isOverdue ? 'overdue-date' : '' ?>">
                                        📅 <?= date('M d, Y', strtotime($followup['follow_up_date'])) ?>
                                        <?php if ($isOverdue): ?>
                                            <span class="overdue-label">OVERDUE</span>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($followup['contact_name']): ?>
                                        <span class="contact-info">
                                            👤 <?= htmlspecialchars($followup['contact_name']) ?>
                                            <?php if ($followup['contact_company']): ?>
                                                - <?= htmlspecialchars($followup['contact_company']) ?>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($followup['user_name']): ?>
                                        <span class="assigned-to">
                                            👨‍💼 <?= htmlspecialchars($followup['user_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="followup-badges">
                                <span class="badge badge--<?= $statusClass ?> badge--modern">
                                    <?= $statusIcon ?> <?= ucfirst(str_replace('_', ' ', $followup['status'])) ?>
                                </span>
                                <span class="badge badge--<?= ($followup['followup_type'] === 'task') ? 'info' : 'secondary' ?> badge--outline">
                                    <?= ($followup['followup_type'] === 'task') ? 'Task-linked' : 'Standalone' ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($followup['description']): ?>
                            <div class="followup-description">
                                <?= nl2br(htmlspecialchars($followup['description'])) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="followup-actions">
                            <?php if ($followup['status'] !== 'completed' && $followup['status'] !== 'cancelled'): ?>
                                <button class="btn btn--success btn--small" onclick="completeFollowup(<?= $followup['id'] ?>)">
                                    ✅ Complete
                                </button>
                                <button class="btn btn--warning btn--small" onclick="rescheduleFollowup(<?= $followup['id'] ?>)">
                                    📅 Reschedule
                                </button>
                                <button class="btn btn--danger btn--small" onclick="cancelFollowup(<?= $followup['id'] ?>)">
                                    ❌ Cancel
                                </button>
                            <?php endif; ?>
                            <button class="btn btn--danger btn--small btn--outline" onclick="deleteFollowup(<?= $followup['id'] ?>)">
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📞</div>
                <h3>No Follow-ups Yet</h3>
                <p>Create your first follow-up to get started</p>
                <a href="/ergon/followups/create" class="btn btn--primary">
                    Create Follow-up
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.followups-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.followup-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
    background: white;
    transition: all 0.2s ease;
}

.followup-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #d1d5db;
}

.followup-card.overdue {
    border-left: 4px solid #ef4444;
    background: #fef2f2;
}

.followup-card__header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.followup-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    background: #f3f4f6;
    flex-shrink: 0;
}

.followup-icon.task {
    background: #dbeafe;
    color: #3b82f6;
}

.followup-icon.standalone {
    background: #f0fdf4;
    color: #16a34a;
}

.followup-title-section {
    flex: 1;
    min-width: 0;
}

.followup-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
    line-height: 1.3;
}

.followup-meta {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.followup-date.overdue-date {
    color: #dc2626;
    font-weight: 600;
}

.overdue-label {
    background: #dc2626;
    color: white;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

.followup-badges {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-end;
    flex-shrink: 0;
}

.followup-description {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    line-height: 1.5;
    color: #4b5563;
}

.followup-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn--small {
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
}

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

.alert-success {
    background-color: #f0fdf4;
    border-color: #bbf7d0;
    color: #16a34a;
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

.badge--outline {
    background: transparent;
    border: 1px solid currentColor;
}

@media (max-width: 768px) {
    .followup-card__header {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .followup-badges {
        flex-direction: row;
        align-items: flex-start;
    }
    
    .followup-actions {
        justify-content: center;
    }
    
    .followup-meta {
        font-size: 0.8rem;
    }
}
</style>

<script>
function completeFollowup(id) {
    if (confirm('Mark this follow-up as completed?')) {
        fetch(`/ergon/contacts/followups/complete/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to complete follow-up'));
            }
        })
        .catch(error => {
            console.error('Complete error:', error);
            alert('An error occurred while completing the follow-up.');
        });
    }
}

function rescheduleFollowup(id) {
    const newDate = prompt('Enter new date (YYYY-MM-DD):');
    if (newDate && /^\d{4}-\d{2}-\d{2}$/.test(newDate)) {
        const reason = prompt('Reason for rescheduling (optional):') || 'Rescheduled';
        
        const formData = new FormData();
        formData.append('new_date', newDate);
        formData.append('reason', reason);
        
        fetch(`/ergon/contacts/followups/reschedule/${id}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to reschedule'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
        });
    } else if (newDate) {
        alert('Please enter a valid date in YYYY-MM-DD format');
    }
}

function cancelFollowup(id) {
    const reason = prompt('Reason for cancellation:');
    if (reason && reason.trim()) {
        const formData = new FormData();
        formData.append('reason', reason.trim());
        
        fetch(`/ergon/contacts/followups/cancel/${id}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Follow-up cancelled successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to cancel follow-up'));
            }
        })
        .catch(error => {
            console.error('Cancel error:', error);
            alert('Network error occurred');
        });
    }
}

function deleteFollowup(id) {
    if (confirm('Are you sure you want to delete this follow-up? This action cannot be undone.')) {
        fetch(`/ergon/followups/delete/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to delete follow-up'));
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            alert('An error occurred while deleting the follow-up.');
        });
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>