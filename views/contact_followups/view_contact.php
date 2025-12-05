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
        <a href="/ergon/contacts/followups/create?contact_id=C_<?= $contact['id'] ?>" class="btn btn--primary">
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
                    <button class="btn btn--info" onclick="editContact(<?= $contact['id'] ?>)">
                        ✏️ Edit Contact
                    </button>
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
                                <button class="btn btn--success btn--modern" onclick="completeFollowup('F_<?= $followup['id'] ?>')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                    </svg>
                                    Complete
                                </button>
                                <button class="btn btn--warning btn--modern" onclick="rescheduleFollowup('F_<?= $followup['id'] ?>')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
                                    </svg>
                                    Reschedule
                                </button>
                                <button class="btn btn--danger btn--modern" onclick="cancelFollowup('F_<?= $followup['id'] ?>')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                    </svg>
                                    Cancel
                                </button>
                            <?php endif; ?>
                            <button class="btn btn--info btn--modern btn--outline" onclick="window.location.href='/ergon/contacts/followups/view?id=F_<?= $followup['id'] ?>'">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                                View Details
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
                <a href="/ergon/contacts/followups/create?contact_id=C_<?= $contact['id'] ?>" class="btn btn--primary">
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

<script>
function completeFollowup(id) {
    id = id.replace('F_', '');
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
    id = id.replace('F_', '');
    showModal('rescheduleModal');
    document.getElementById('rescheduleFollowupId').value = id;
    document.getElementById('rescheduleForm').action = `/ergon/contacts/followups/reschedule/${id}`;
    
    const form = document.getElementById('rescheduleForm');
    form.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.ok) {
                closeModal('rescheduleModal');
                location.reload();
            } else {
                alert('Error: Failed to reschedule follow-up');
            }
        })
        .catch(error => {
            console.error('Reschedule error:', error);
            alert('An error occurred while rescheduling the follow-up.');
        });
    };
}

function cancelFollowup(id) {
    id = id.replace('F_', '');
    showModal('cancelModal');
    document.getElementById('cancelFollowupId').value = id;
    document.getElementById('cancelForm').action = `/ergon/contacts/followups/cancel/${id}`;
    
    const form = document.getElementById('cancelForm');
    form.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal('cancelModal');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to cancel follow-up'));
            }
        })
        .catch(error => {
            console.error('Cancel error:', error);
            alert('An error occurred while cancelling the follow-up.');
        });
    };
}

function editContact(contactId) {
    fetch(`/ergon/api/contacts/${contactId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.contact) {
                const contact = data.contact;
                document.getElementById('editContactId').value = contact.id;
                document.getElementById('editContactName').value = contact.name || '';
                document.getElementById('editContactPhone').value = contact.phone || '';
                document.getElementById('editContactEmail').value = contact.email || '';
                document.getElementById('editContactCompany').value = contact.company || '';
                showModal('editContactModal');
            } else {
                alert('Error: ' + (data.error || 'Failed to load contact'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error loading contact');
        });
}

function saveContactChanges() {
    const form = document.getElementById('editContactForm');
    const formData = new FormData(form);
    const contactId = document.getElementById('editContactId').value;
    
    fetch(`/ergon/api/contacts/${contactId}/update`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('editContactModal');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update contact'));
        }
    })
    .catch(error => {
        console.error('Error updating contact:', error);
        alert('Error updating contact');
    });
}
</script>

<?php
$cancelContent = '
<form method="POST" id="cancelForm" action="">
    <input type="hidden" name="followup_id" id="cancelFollowupId">
    <div class="form-group">
        <label class="form-label">Reason for Cancellation *</label>
        <textarea name="reason" class="form-control" rows="3" placeholder="Please provide a reason for cancelling this follow-up..." required></textarea>
    </div>
</form>';

$cancelFooter = createFormModalFooter('Cancel', '❌ Cancel Follow-up', 'cancelModal', 'danger');

$rescheduleContent = '
<form method="POST" id="rescheduleForm" action="">
    <input type="hidden" name="followup_id" id="rescheduleFollowupId">
    <div class="form-group">
        <label class="form-label">New Date *</label>
        <input type="date" name="new_date" class="form-control" required>
    </div>
    <div class="form-group">
        <label class="form-label">Reason for Rescheduling</label>
        <textarea name="reason" class="form-control" rows="3" placeholder="Why is this being rescheduled?"></textarea>
    </div>
</form>';

$rescheduleFooter = createFormModalFooter('Cancel', '📅 Reschedule', 'rescheduleModal', 'warning');

$editContactContent = '
<form id="editContactForm">
    <input type="hidden" id="editContactId" name="contact_id">
    <div class="form-group">
        <label class="form-label">Name *</label>
        <input type="text" id="editContactName" name="name" class="form-control" required>
    </div>
    <div class="form-group">
        <label class="form-label">Phone</label>
        <input type="tel" id="editContactPhone" name="phone" class="form-control">
    </div>
    <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" id="editContactEmail" name="email" class="form-control">
    </div>
    <div class="form-group">
        <label class="form-label">Company</label>
        <input type="text" id="editContactCompany" name="company" class="form-control">
    </div>
</form>';

$editContactFooter = '
<button type="button" class="btn btn--secondary" onclick="closeModal(\'editContactModal\')">Cancel</button>
<button type="button" class="btn btn--primary" onclick="saveContactChanges()">💾 Save Changes</button>';

renderModal('cancelModal', 'Cancel Follow-up', $cancelContent, $cancelFooter, ['icon' => '❌']);
renderModal('rescheduleModal', 'Reschedule Follow-up', $rescheduleContent, $rescheduleFooter, ['icon' => '📅']);
renderModal('editContactModal', 'Edit Contact Details', $editContactContent, $editContactFooter, ['icon' => '✏️']);
?>

<?php renderModalJS(); ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
