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
                    $typeIcon = ($followup['followup_type'] === 'task' || $followup['followup_type'] === 'task-linked') ? '🔗' : '📞';
                    $isOverdue = strtotime($followup['follow_up_date']) < strtotime('today') && $followup['status'] !== 'completed';
                    $canEdit = $followup['status'] !== 'completed' && $followup['status'] !== 'postponed' && $followup['status'] !== 'cancelled';
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
                                <span class="badge badge--<?= ($followup['followup_type'] === 'task' || $followup['followup_type'] === 'task-linked') ? 'info' : 'secondary' ?> badge--outline">
                                    <?= ($followup['followup_type'] === 'task' || $followup['followup_type'] === 'task-linked') ? 'Task-linked' : 'Standalone' ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($followup['description']): ?>
                            <div class="followup-description">
                                <?= nl2br(htmlspecialchars($followup['description'])) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($followup['task_title']) && $followup['task_title']): ?>
                            <div class="linked-task">
                                <div class="linked-task__icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/>
                                    </svg>
                                </div>
                                <span>Linked to task: <strong><?= htmlspecialchars($followup['task_title']) ?></strong></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="ab-container">
                            <a href="/ergon/contacts/followups/view/<?= $followup['id'] ?>" class="ab-btn ab-btn--view" title="View Details">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14,2 14,8 20,8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </a>
                            <?php if ($canEdit): ?>
                                <button class="ab-btn ab-btn--progress" onclick="completeFollowup(<?= $followup['id'] ?>)" title="Mark Complete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                </button>
                                <button class="ab-btn ab-btn--warning" onclick="rescheduleFollowup(<?= $followup['id'] ?>)" title="Reschedule">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                </button>
                                <button class="ab-btn ab-btn--cancel" onclick="cancelFollowup(<?= $followup['id'] ?>)" title="Cancel">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <line x1="18" y1="6" x2="6" y2="18"/>
                                        <line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                </button>
                                <button class="ab-btn ab-btn--edit" onclick="editFollowup(<?= $followup['id'] ?>)" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                        <path d="M15 5l4 4"/>
                                    </svg>
                                </button>
                                <button class="ab-btn ab-btn--delete" onclick="deleteFollowup(<?= $followup['id'] ?>)" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M3 6h18"/>
                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                </button>
                            <?php endif; ?>
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
.contact-compact { max-width: 1000px; margin: 0 auto 2rem auto; }
.contact-title-row { display: flex; justify-content: space-between; align-items: flex-start; width: 100%; gap: 1.5rem; min-height: 2rem; }
.contact-title { font-size: 1.25rem; font-weight: 600; color: var(--text-primary); margin: 0; flex: 1 1 auto; min-width: 200px; max-width: calc(100% - 200px); overflow-wrap: break-word; word-break: break-word; line-height: 1.3; }
.contact-badges { display: flex; align-items: center; gap: 1rem; flex: 0 0 auto; min-width: 120px; justify-content: flex-end; }
.phone-link { color: #059669; text-decoration: none; font-weight: 500; }
.phone-link:hover { text-decoration: underline; }
.details-compact { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
.detail-group { background: var(--bg-secondary); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); }
.detail-group h4 { margin: 0 0 0.75rem 0; font-size: 0.9rem; color: var(--primary); font-weight: 600; }
.detail-items { display: flex; flex-direction: column; gap: 0.5rem; }
.detail-items span { font-size: 0.85rem; color: var(--text-secondary); display: flex; align-items: center; gap: 0.5rem; }
.detail-items strong { color: var(--text-primary); min-width: 60px; font-size: 0.8rem; }

.ab-container { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
.ab-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-secondary); border-radius: 4px; cursor: pointer; transition: all 0.2s ease; padding: 0; }
.ab-btn svg { width: 16px; height: 16px; stroke-width: 2; }
.ab-btn:hover { background: var(--bg-tertiary); color: var(--text-primary); border-color: var(--primary); }
.ab-btn--view:hover { background: #3b82f6; color: white; border-color: #3b82f6; }
.ab-btn--edit:hover { background: #f59e0b; color: white; border-color: #f59e0b; }
.ab-btn--delete:hover { background: #ef4444; color: white; border-color: #ef4444; }
.ab-btn--progress:hover { background: #10b981; color: white; border-color: #10b981; }
.ab-btn--warning:hover { background: #f59e0b; color: white; border-color: #f59e0b; }
.ab-btn--cancel:hover { background: #ef4444; color: white; border-color: #ef4444; }

@media (max-width: 768px) { .contact-title-row { flex-direction: column; align-items: flex-start; gap: 1rem; min-height: auto; } .contact-title { max-width: 100%; min-width: auto; } .contact-badges { width: 100%; min-width: auto; justify-content: flex-start; flex-wrap: wrap; } .details-compact { grid-template-columns: 1fr; } }
</style>

<?php renderModalJS(); ?>

<script>
function completeFollowup(id) {
    if (confirm('Mark this follow-up as completed?')) {
        fetch(`/ergon/contacts/followups/complete/${id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => { if (data.success) { location.reload(); } else { alert('Error: ' + (data.error || 'Failed to complete follow-up')); } })
        .catch(error => { console.error('Complete error:', error); alert('An error occurred while completing the follow-up.'); });
    }
}

function rescheduleFollowup(id) {
    showModal('rescheduleModal');
    document.getElementById('rescheduleFollowupId').value = id;
    const dateInput = document.querySelector('#rescheduleForm input[name="new_date"]');
    if (dateInput) { const today = new Date().toISOString().split('T')[0]; dateInput.min = today; dateInput.value = today; }
    const form = document.getElementById('rescheduleForm');
    form.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const submitBtn = document.querySelector('button[form="rescheduleForm"]');
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Rescheduling...'; }
        fetch(`/ergon/contacts/followups/reschedule/${id}`, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(response => response.json())
        .then(data => { if (data.success) { closeModal('rescheduleModal'); location.reload(); } else { alert('Error: ' + (data.error || 'Failed to reschedule')); if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Reschedule'; } } })
        .catch(error => { console.error('Error:', error); alert('Network error occurred'); if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Reschedule'; } });
    };
}

function cancelFollowup(id) {
    if (!id || isNaN(id)) { alert('Invalid follow-up ID'); return; }
    showModal('cancelModal');
    document.getElementById('cancelFollowupId').value = id;
    document.getElementById('cancelForm').action = `/ergon/contacts/followups/cancel/${id}`;
    const form = document.getElementById('cancelForm');
    form.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const reason = formData.get('reason');
        const submitBtn = document.querySelector('button[form="cancelForm"]');
        if (!reason || reason.trim().length === 0) { alert('Please provide a reason for cancellation'); return; }
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Cancelling...'; }
        fetch(form.action, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(response => response.json())
        .then(data => { if (data.success) { closeModal('cancelModal'); alert('Follow-up cancelled successfully!'); location.reload(); } else { alert('Error: ' + (data.error || 'Failed to cancel follow-up')); } })
        .catch(error => { console.error('Cancel network error:', error); alert('Network error occurred. Please try again.'); })
        .finally(() => { if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Cancel Follow-up'; } });
    };
}

function editFollowup(id) {
    showModal('editModal');
    document.getElementById('editFollowupId').value = id;
    const form = document.getElementById('editForm');
    form.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const submitBtn = document.querySelector('button[form="editForm"]');
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Saving...'; }
        fetch(`/ergon/contacts/followups/edit/${id}`, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(response => response.json())
        .then(data => { if (data.success) { closeModal('editModal'); location.reload(); } else { alert('Error: ' + (data.error || 'Failed to update follow-up')); } })
        .catch(error => { console.error('Edit error:', error); alert('Network error occurred'); })
        .finally(() => { if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Update'; } });
    };
}

function deleteFollowup(id) {
    if (confirm('Are you sure you want to delete this follow-up? This action cannot be undone.')) {
        const formData = new FormData();
        fetch(`/ergon/contacts/followups/delete/${id}`, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(response => response.json())
        .then(data => { if (data.success) { alert('Follow-up deleted successfully!'); location.reload(); } else { alert('Error: ' + (data.error || 'Failed to delete follow-up')); } })
        .catch(error => { console.error('Delete error:', error); alert('Network error occurred'); });
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

<?php
$cancelContent = '<form method="POST" id="cancelForm" action=""><input type="hidden" name="followup_id" id="cancelFollowupId"><div class="form-group"><label class="form-label">Reason for Cancellation *</label><textarea name="reason" class="form-control" rows="3" placeholder="Please provide a reason for cancelling this follow-up..." required></textarea></div></form>';
$cancelFooter = '<button type="button" onclick="closeModal(\'cancelModal\')" class="btn btn--secondary">Cancel</button><button type="submit" form="cancelForm" class="btn btn--danger">❌ Cancel Follow-up</button>';
$rescheduleContent = '<form method="POST" id="rescheduleForm" action=""><input type="hidden" name="followup_id" id="rescheduleFollowupId"><div style="margin-bottom: 15px;"><label style="display: block; margin-bottom: 5px; font-weight: bold;">New Date *</label><input type="date" name="new_date" required min="' . date('Y-m-d') . '" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"></div><div style="margin-bottom: 15px;"><label style="display: block; margin-bottom: 5px; font-weight: bold;">Reason for Rescheduling</label><textarea name="reason" rows="3" placeholder="Why is this being rescheduled?" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"></textarea></div></form>';
$rescheduleFooter = '<button type="button" onclick="closeModal(\'rescheduleModal\')" class="btn btn--secondary">Cancel</button><button type="submit" form="rescheduleForm" class="btn btn--warning">📅 Reschedule</button>';
$editContent = '<form method="POST" id="editForm" action=""><input type="hidden" name="followup_id" id="editFollowupId"><div class="form-group"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" placeholder="Follow-up title" required></div><div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" placeholder="Add details about this follow-up..."></textarea></div><div class="form-group"><label class="form-label">Follow-up Date *</label><input type="date" name="follow_up_date" class="form-control" required></div></form>';
$editFooter = '<button type="button" onclick="closeModal(\'editModal\')" class="btn btn--secondary">Cancel</button><button type="submit" form="editForm" class="btn btn--primary">✏️ Update</button>';

renderModal('cancelModal', 'Cancel Follow-up', $cancelContent, $cancelFooter, ['icon' => '❌']);
renderModal('rescheduleModal', 'Reschedule Follow-up', $rescheduleContent, $rescheduleFooter, ['icon' => '📅']);
renderModal('editModal', 'Edit Follow-up', $editContent, $editFooter, ['icon' => '✏️']);
?>
