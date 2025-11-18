<?php
$active_page = 'contact_followups';
include __DIR__ . '/../shared/modal_component.php';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>👤</span> <?= htmlspecialchars($contact['name']) ?> - Follow-ups</h1>
        <p>All follow-up history and communications for this contact</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/contacts/followups" class="btn btn--secondary">
            <span>←</span> Back to Contacts
        </a>
        <a href="/ergon/contacts/followups/create?contact_id=<?= $contact['id'] ?>" class="btn btn--primary">
            <span>➕</span> New Follow-up
        </a>
    </div>
</div>

<!-- Contact Info Card -->
<div class="contact-compact">
    <div class="card">
        <div class="card__header">
            <div class="contact-title-row">
                <h2 class="contact-title">👤 <?= htmlspecialchars($contact['name']) ?></h2>
                <div class="contact-badges">
                    <?php if ($contact['phone']): ?>
                        <a href="tel:<?= $contact['phone'] ?>" class="btn btn--success">
                            📞 Call Now
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card__body">
            <div class="details-compact">
                <div class="detail-group">
                    <h4>👤 Contact Information</h4>
                    <div class="detail-items">
                        <span><strong>Name:</strong> 👤 <?= htmlspecialchars($contact['name']) ?></span>
                        <?php if ($contact['phone']): ?>
                        <span><strong>Phone:</strong> 📞 <a href="tel:<?= $contact['phone'] ?>" class="phone-link"><?= htmlspecialchars($contact['phone']) ?></a></span>
                        <?php endif; ?>
                        <?php if ($contact['email']): ?>
                        <span><strong>Email:</strong> ✉️ <a href="mailto:<?= $contact['email'] ?>"><?= htmlspecialchars($contact['email']) ?></a></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($contact['company']): ?>
                <div class="detail-group">
                    <h4>🏢 Company</h4>
                    <div class="detail-items">
                        <span><strong>Company:</strong> 🏢 <?= htmlspecialchars($contact['company']) ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Follow-ups Timeline -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Follow-up History</h2>
        <div class="card__actions">
            <span class="badge badge--info"><?= count($followups) ?> follow-ups</span>
        </div>
    </div>
    <div class="card__body followups-timeline">
        <?php if (!empty($followups)): ?>
            <div class="followups-modern">
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
                    $typeIcon = $followup['followup_type'] === 'task-linked' ? '🔗' : '📞';
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
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M9 11H7v6h2v-6zm4 0h-2v6h2v-6zm4 0h-2v6h2v-6zm2-7h-3V2h-2v2H8V2H6v2H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H3V9h14v11z"/>
                                        </svg>
                                        <?= date('M d, Y', strtotime($followup['follow_up_date'])) ?>
                                        <?php if ($isOverdue): ?>
                                            <span class="overdue-label">OVERDUE</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="followup-badges">
                                <span class="badge badge--<?= $statusClass ?> badge--modern">
                                    <?= $statusIcon ?> <?= ucfirst(str_replace('_', ' ', $followup['status'])) ?>
                                </span>
                                <span class="badge badge--<?= $followup['followup_type'] === 'task-linked' ? 'info' : 'secondary' ?> badge--outline">
                                    <?= $followup['followup_type'] === 'task-linked' ? 'Task-linked' : 'Standalone' ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($followup['description']): ?>
                            <div class="followup-description">
                                <?= nl2br(htmlspecialchars($followup['description'])) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($followup['task_title']): ?>
                            <div class="linked-task">
                                <div class="linked-task__icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/>
                                    </svg>
                                </div>
                                <span>Linked to task: <strong><?= htmlspecialchars($followup['task_title']) ?></strong></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="followup-actions">
                            <?php if ($followup['status'] !== 'completed' && $followup['status'] !== 'cancelled'): ?>
                                <button class="btn btn--success btn--modern" onclick="completeFollowup(<?= $followup['id'] ?>)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                    </svg>
                                    Complete
                                </button>
                                <button class="btn btn--warning btn--modern" onclick="rescheduleFollowup(<?= $followup['id'] ?>)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
                                    </svg>
                                    Reschedule
                                </button>
                                <button class="btn btn--danger btn--modern" onclick="cancelFollowup(<?= $followup['id'] ?>)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                    </svg>
                                    Cancel
                                </button>
                            <?php endif; ?>
                            <button class="btn btn--info btn--modern btn--outline" onclick="showHistory(<?= $followup['id'] ?>)">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
                                </svg>
                                History
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📞</div>
                <h3>No Follow-ups Yet</h3>
                <p>Create the first follow-up for <?= htmlspecialchars($contact['name']) ?></p>
                <a href="/ergon/contacts/followups/create?contact_id=<?= $contact['id'] ?>" class="btn btn--primary">
                    Create Follow-up
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php renderModalCSS(); ?>

<style>
.contact-compact {
    max-width: 1000px;
    margin: 0 auto 2rem auto;
}

.contact-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    width: 100%;
    gap: 1.5rem;
    min-height: 2rem;
}

.contact-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    flex: 1 1 auto;
    min-width: 200px;
    max-width: calc(100% - 200px);
    overflow-wrap: break-word;
    word-break: break-word;
    line-height: 1.3;
}

.contact-badges {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 0 0 auto;
    min-width: 120px;
    justify-content: flex-end;
}

.phone-link {
    color: #059669;
    text-decoration: none;
    font-weight: 500;
}

.phone-link:hover {
    text-decoration: underline;
}

.details-compact {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.detail-group {
    background: var(--bg-secondary);
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.detail-group h4 {
    margin: 0 0 0.75rem 0;
    font-size: 0.9rem;
    color: var(--primary);
    font-weight: 600;
}

.detail-items {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-items span {
    font-size: 0.85rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-items strong {
    color: var(--text-primary);
    min-width: 60px;
    font-size: 0.8rem;
}

/* Enhanced button styles for better visibility */
.btn--danger {
    background: #ef4444 !important;
    color: white !important;
    border: 1px solid #dc2626 !important;
}

.btn--danger:hover {
    background: #dc2626 !important;
    color: white !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3) !important;
}

.btn--info {
    background: #3b82f6 !important;
    color: white !important;
    border: 1px solid #2563eb !important;
}

.btn--info:hover {
    background: #2563eb !important;
    color: white !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3) !important;
}

.btn--info.btn--outline {
    background: transparent !important;
    color: #3b82f6 !important;
    border: 2px solid #3b82f6 !important;
}

.btn--info.btn--outline:hover {
    background: #3b82f6 !important;
    color: white !important;
    border-color: #3b82f6 !important;
}

/* Form validation styles */
.form-text {
    font-size: 0.75rem;
    color: var(--text-muted, #6b7280);
    margin-top: 0.25rem;
}

.text-muted {
    color: var(--text-muted, #6b7280) !important;
}

.form-control:invalid {
    border-color: var(--danger, #ef4444);
}

.form-control:valid {
    border-color: var(--success, #10b981);
}

/* Loading state for buttons */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Better modal form spacing */
.ergon-modal .form-group:last-child {
    margin-bottom: 0;
}

/* Modern History Timeline Styles */
.modern-history-timeline {
    position: relative;
    padding: 0;
    margin: 0;
}

.history-entry {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #f1f5f9;
    position: relative;
}

.history-entry:last-child {
    border-bottom: none;
}

.history-entry:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 1.25rem;
    top: 3rem;
    bottom: -1rem;
    width: 2px;
    background: linear-gradient(to bottom, #e2e8f0, transparent);
    z-index: 1;
}

.history-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: white;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    position: relative;
    z-index: 2;
    flex-shrink: 0;
}

.history-content {
    flex: 1;
    min-width: 0;
}

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    gap: 1rem;
}

.history-action {
    font-weight: 600;
    font-size: 0.95rem;
    color: #1e293b;
    text-transform: capitalize;
}

.history-date {
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 500;
    white-space: nowrap;
}

.history-notes {
    font-size: 0.875rem;
    color: #475569;
    line-height: 1.5;
    margin-bottom: 0.5rem;
    background: #f8fafc;
    padding: 0.75rem;
    border-radius: 6px;
    border-left: 3px solid #e2e8f0;
}

.history-user {
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* Empty state for history */
.history-empty {
    text-align: center;
    padding: 2rem;
    color: #64748b;
}

.history-empty-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Mobile responsive */
@media (max-width: 640px) {
    .history-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .history-entry {
        gap: 0.75rem;
    }
    
    .history-icon {
        width: 2rem;
        height: 2rem;
        font-size: 0.875rem;
    }
    
    .history-entry:not(:last-child)::after {
        left: 1rem;
    }
}

@media (max-width: 768px) {
    .contact-title-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        min-height: auto;
    }
    
    .contact-title {
        max-width: 100%;
        min-width: auto;
    }
    
    .contact-badges {
        width: 100%;
        min-width: auto;
        justify-content: flex-start;
        flex-wrap: wrap;
    }
    
    .details-compact {
        grid-template-columns: 1fr;
    }
}
</style>

<?php renderModalJS(); ?>

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
    showModal('rescheduleModal');
    document.getElementById('rescheduleFollowupId').value = id;
    
    const dateInput = document.querySelector('#rescheduleForm input[name="new_date"]');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
        dateInput.value = today;
    }
    
    const form = document.getElementById('rescheduleForm');
    form.onsubmit = function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = document.querySelector('button[form="rescheduleForm"]');
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Rescheduling...';
        }
        
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
                closeModal('rescheduleModal');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to reschedule'));
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = '📅 Reschedule';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = '📅 Reschedule';
            }
        });
    };
}

function cancelFollowup(id) {
    console.log('Cancel function called with ID:', id);
    
    if (!id || isNaN(id)) {
        alert('Invalid follow-up ID');
        return;
    }
    
    showModal('cancelModal');
    document.getElementById('cancelFollowupId').value = id;
    document.getElementById('cancelForm').action = `/ergon/contacts/followups/cancel/${id}`;
    
    // Add form submit handler
    const form = document.getElementById('cancelForm');
    form.onsubmit = function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const reason = formData.get('reason');
        const submitBtn = document.querySelector('button[form="cancelForm"]');
        
        // Validate reason
        if (!reason || reason.trim().length === 0) {
            alert('Please provide a reason for cancellation');
            return;
        }
        
        console.log('Submitting cancel request for ID:', id, 'Reason:', reason);
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Cancelling...';
        }
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('Cancel response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Cancel response data:', data);
            
            if (data.success) {
                closeModal('cancelModal');
                alert('Follow-up cancelled successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to cancel follow-up'));
                console.error('Cancel error:', data);
            }
        })
        .catch(error => {
            console.error('Cancel network error:', error);
            alert('Network error occurred. Please try again.');
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = '❌ Cancel Follow-up';
            }
        });
    };
}

function showHistory(id) {
    showModal('historyModal');
    document.getElementById('historyContent').innerHTML = 'Loading...';
    
    fetch(`/ergon/contacts/followups/history/${id}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const content = data.html || '<div class="history-empty"><div class="history-empty-icon">📋</div><h4>No History Available</h4><p>This follow-up has no recorded history yet.</p></div>';
            document.getElementById('historyContent').innerHTML = content;
        } else {
            document.getElementById('historyContent').innerHTML = '<div class="history-empty"><div class="history-empty-icon">⚠️</div><h4>Error Loading History</h4><p>' + (data.error || 'Failed to load history') + '</p></div>';
        }
    })
    .catch(error => {
        console.error('Error loading history:', error);
        document.getElementById('historyContent').innerHTML = 'Error loading history';
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

<?php
// Cancel Modal Content
$cancelContent = '
<form method="POST" id="cancelForm" action="">
    <input type="hidden" name="followup_id" id="cancelFollowupId">
    <div class="form-group">
        <label class="form-label">Reason for Cancellation *</label>
        <textarea name="reason" class="form-control" rows="3" placeholder="Please provide a reason for cancelling this follow-up..." required></textarea>
    </div>
</form>';

$cancelFooter = '
<button type="button" onclick="closeModal(\'cancelModal\')" class="btn btn--secondary">
    Cancel
</button>
<button type="submit" form="cancelForm" class="btn btn--danger">
    ❌ Cancel Follow-up
</button>';

// Reschedule Modal Content
$rescheduleContent = '
<form method="POST" id="rescheduleForm" action="">
    <input type="hidden" name="followup_id" id="rescheduleFollowupId">
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">New Date *</label>
        <input type="date" name="new_date" required min="' . date('Y-m-d') . '" 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Reason for Rescheduling</label>
        <textarea name="reason" rows="3" placeholder="Why is this being rescheduled?"
                  style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"></textarea>
    </div>
</form>';

$rescheduleFooter = '
<button type="button" onclick="closeModal(\'rescheduleModal\')" class="btn btn--secondary">
    Cancel
</button>
<button type="submit" form="rescheduleForm" class="btn btn--warning">
    📅 Reschedule
</button>';

// History Modal Content
$historyContent = '<div id="historyContent" style="min-height: 200px; display: flex; align-items: center; justify-content: center; color: #64748b;">📋 Loading history...</div>';

// Render Modals
renderModal('cancelModal', 'Cancel Follow-up', $cancelContent, $cancelFooter, ['icon' => '❌']);
renderModal('rescheduleModal', 'Reschedule Follow-up', $rescheduleContent, $rescheduleFooter, ['icon' => '📅']);
renderModal('historyModal', 'Follow-up History', $historyContent, '', ['icon' => '📋', 'size' => 'large']);
?>