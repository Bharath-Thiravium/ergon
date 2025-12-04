<?php
$active_page = 'contact_followups';
include __DIR__ . '/../shared/modal_component.php';
ob_start();
?>

<div class="page-header">
    <div class="page-title" style="display:block;align-items:center;gap:0.5rem;flex-wrap:nowrap">
        <h1 style="margin:0;font-size:1.25rem;white-space:nowrap"><span>👤</span> <?= htmlspecialchars($contact['name']) ?></h1>
        <div style="display:block;gap:0.5rem;font-size:0.75rem;color:#6b7280;white-space:nowrap">
            <?php if ($contact['phone']): ?><span>📞 <?= htmlspecialchars($contact['phone']) ?></span><?php endif; ?>
            <?php if ($contact['email']): ?><span>✉️ <?= htmlspecialchars($contact['email']) ?></span><?php endif; ?>
            <?php if ($contact['company']): ?><span>🏢 <?= htmlspecialchars($contact['company']) ?></span><?php endif; ?>
        </div>
    </div>
    <div class="page-actions" style="gap:0.25rem">
        <button class="btn btn--secondary view-toggle" data-view="list" title="List View" style="padding:0.5rem 0.75rem">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/></svg>
        </button>
        <button class="btn btn--secondary view-toggle" data-view="grid" title="Grid View" style="padding:0.5rem 0.75rem">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/></svg>
        </button>
        <a href="/ergon/contacts/followups" class="btn btn--secondary" style="padding:0.5rem 0.75rem" title="Back">←</a>
        <a href="/ergon/contacts/followups/create?contact_id=C_<?= $contact['id'] ?>" class="btn btn--primary" style="padding:0.5rem 0.75rem" title="New Follow-up">➕</a>
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
            <div class="followups-modern" id="followupsContainer">
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
                    ?>
                    <div class="followup-card <?= $followup['status'] ?> <?= $isOverdue ? 'overdue' : '' ?>" style="max-width:500px">
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
                            <div class="followup-description" style="display:flex;gap:0.5rem;align-items:flex-start">
                                <div style="flex:1"><?= nl2br(htmlspecialchars($followup['description'])) ?></div>
                                <div class="ab-container" style="flex-shrink:0;display:flex;gap:0.25rem">
                                    <?php if ($followup['status'] !== 'completed' && $followup['status'] !== 'cancelled'): ?>
                                        <button class="ab-btn ab-btn--success" onclick="completeFollowup('F_<?= $followup['id'] ?>')" title="Complete" style="width:20px;height:20px;padding:0;display:flex;align-items:center;justify-content:center">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                        </button>
                                        <button class="ab-btn ab-btn--warning" onclick="rescheduleFollowup('F_<?= $followup['id'] ?>')" title="Reschedule" style="width:20px;height:20px;padding:0;display:flex;align-items:center;justify-content:center">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
                                        </button>
                                        <button class="ab-btn ab-btn--delete" onclick="cancelFollowup('F_<?= $followup['id'] ?>')" title="Cancel" style="width:20px;height:20px;padding:0;display:flex;align-items:center;justify-content:center">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                                        </button>
                                    <?php endif; ?>
                                    <button class="ab-btn ab-btn--edit" onclick="editFollowup('F_<?= $followup['id'] ?>')" title="Edit" style="width:20px;height:20px;padding:0;display:flex;align-items:center;justify-content:center">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"/><path d="M20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                    </button>
                                    <button class="ab-btn ab-btn--delete" onclick="deleteFollowup('F_<?= $followup['id'] ?>')" title="Delete" style="width:20px;height:20px;padding:0;display:flex;align-items:center;justify-content:center">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-9l-1 1H5v2h14V4z"/></svg>
                                    </button>
                                    <button class="ab-btn ab-btn--view" onclick="window.location.href='/ergon/contacts/followups/view/F_<?= $followup['id'] ?>'" title="View Details" style="width:20px;height:20px;padding:0;display:flex;align-items:center;justify-content:center">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </button>
                                </div>
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
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📞</div>
                <h3>No Follow-ups Yet</h3>
                <p>Create the first follow-up for <?= htmlspecialchars($contact['name']) ?></p>
                <a href="/ergon/contacts/followups/create?contact_id=C_<?= $contact['id'] ?>" class="btn btn--primary">
                    Create Follow-up
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php renderModalCSS(); ?>

<?php renderModalJS(); ?>

<script>
function completeFollowup(id) {
    id = id.replace('F_', '');
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
    id = id.replace('F_', '');
    showModal('rescheduleModal');
    document.getElementById('rescheduleFollowupId').value = id;
    const form = document.getElementById('rescheduleForm');
    form.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch(`/ergon/contacts/followups/reschedule/${id}`, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(response => response.json())
        .then(data => { if (data.success) { closeModal('rescheduleModal'); location.reload(); } else { alert('Error: ' + (data.error || 'Failed to reschedule')); } })
        .catch(error => { console.error('Error:', error); alert('Network error occurred'); });
    };
}

function cancelFollowup(id) {
    id = id.replace('F_', '');
    showModal('cancelModal');
    document.getElementById('cancelFollowupId').value = id;
    const form = document.getElementById('cancelForm');
    form.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch(`/ergon/contacts/followups/cancel/${id}`, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(response => response.json())
        .then(data => { if (data.success) { closeModal('cancelModal'); location.reload(); } else { alert('Error: ' + (data.error || 'Failed to cancel follow-up')); } })
        .catch(error => { console.error('Cancel error:', error); alert('Network error occurred'); });
    };
}

function editFollowup(id) {
    id = id.replace('F_', '');
    showModal('editModal');
    document.getElementById('editFollowupId').value = id;
    const form = document.getElementById('editForm');
    form.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch(`/ergon/contacts/followups/edit/${id}`, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(response => response.json())
        .then(data => { if (data.success) { closeModal('editModal'); location.reload(); } else { alert('Error: ' + (data.error || 'Failed to update follow-up')); } })
        .catch(error => { console.error('Edit error:', error); alert('Network error occurred'); });
    };
}

function deleteFollowup(id) {
    id = id.replace('F_', '');
    if (confirm('Are you sure you want to delete this follow-up? This action cannot be undone.')) {
        fetch(`/ergon/contacts/followups/delete/${id}`, { method: 'POST', body: new FormData(), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(response => response.json())
        .then(data => { if (data.success) { alert('Follow-up deleted successfully!'); location.reload(); } else { alert('Error: ' + (data.error || 'Failed to delete follow-up')); } })
        .catch(error => { console.error('Delete error:', error); alert('Network error occurred'); });
    }
}

document.querySelectorAll('.view-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        const view = this.dataset.view;
        const container = document.getElementById('followupsContainer');
        document.querySelectorAll('.view-toggle').forEach(b => b.classList.remove('btn--primary'));
        this.classList.add('btn--primary');
        localStorage.setItem('followupView', view);
        container.className = 'followups-' + view;
    });
});
const savedView = localStorage.getItem('followupView') || 'list';
document.querySelector('[data-view="' + savedView + '"]').click();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

<?php
$cancelContent = '<form method="POST" id="cancelForm" action=""><input type="hidden" name="followup_id" id="cancelFollowupId"><div class="form-group"><label class="form-label">Reason for Cancellation *</label><textarea name="reason" class="form-control" rows="3" placeholder="Please provide a reason for cancelling this follow-up..." required></textarea></div></form>';
$cancelFooter = '<button type="button" onclick="closeModal(\'cancelModal\')" class="btn btn--secondary">Cancel</button><button type="submit" form="cancelForm" class="btn btn--danger">❌ Cancel Follow-up</button>';
$rescheduleContent = '<form method="POST" id="rescheduleForm" action=""><input type="hidden" name="followup_id" id="rescheduleFollowupId"><div class="form-group"><label class="form-label">New Date *</label><input type="date" name="new_date" class="form-control" required></div><div class="form-group"><label class="form-label">Reason for Rescheduling</label><textarea name="reason" class="form-control" rows="3" placeholder="Why is this being rescheduled?"></textarea></div></form>';
$rescheduleFooter = '<button type="button" onclick="closeModal(\'rescheduleModal\')" class="btn btn--secondary">Cancel</button><button type="submit" form="rescheduleForm" class="btn btn--warning">📅 Reschedule</button>';
$editContent = '<form method="POST" id="editForm" action=""><input type="hidden" name="followup_id" id="editFollowupId"><div class="form-group"><label class="form-label">Follow-up Type *</label><select name="followup_type" id="editFollowupType" class="form-control" disabled><option value="standalone">Standalone Follow-up</option><option value="task">Task-linked Follow-up</option></select></div><div class="form-group"><label class="form-label">Contact *</label><select name="contact_id" id="editContactId" class="form-control" disabled><option value="">Select a contact</option></select></div><div class="form-group"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" placeholder="e.g., Follow up on proposal discussion" required></div><div class="form-group"><label class="form-label">Follow-up Date *</label><input type="date" name="follow_up_date" class="form-control" required></div><div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4" placeholder="Additional details about this follow-up..."></textarea></div></form>';
$editFooter = '<button type="button" onclick="closeModal(\'editModal\')" class="btn btn--secondary">Cancel</button><button type="submit" form="editForm" class="btn btn--primary">✏️ Update</button>';

renderModal('cancelModal', 'Cancel Follow-up', $cancelContent, $cancelFooter, ['icon' => '❌']);
renderModal('rescheduleModal', 'Reschedule Follow-up', $rescheduleContent, $rescheduleFooter, ['icon' => '📅']);
renderModal('editModal', 'Edit Follow-up', $editContent, $editFooter, ['icon' => '✏️']);
?>

<style>
.followups-list { display: flex; flex-direction: column; gap: 0.75rem; }
.followups-list .followup-card { max-width: 100% !important; }
.followups-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1rem; }
.followups-grid .followup-card { max-width: 100% !important; }
.followups-card { display: flex; flex-direction: column; gap: 0.75rem; }
.followups-card .followup-card { max-width: 500px; }
</style>
