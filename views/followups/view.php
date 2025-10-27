<div class="page-header">
    <div class="page-title">
        <h1><span>📞</span> <?= htmlspecialchars($data['followup']['title']) ?></h1>
        <p>Follow-up Details</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/followups" class="btn btn--secondary">← Back to Follow-ups</a>
        <?php if ($data['followup']['status'] !== 'completed'): ?>
            <button class="btn btn--warning" onclick="rescheduleFollowup(<?= $data['followup']['id'] ?>)">📅 Reschedule</button>
            <button class="btn btn--success" onclick="completeFollowup(<?= $data['followup']['id'] ?>)">✅ Complete</button>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>📋</span> Follow-up Information
            </h2>
        </div>
        <div class="card__body">
            <div class="info-grid">
                <div class="info-item">
                    <label>Status</label>
                    <span class="badge badge--<?= $data['followup']['status'] === 'completed' ? 'success' : ($data['followup']['status'] === 'overdue' ? 'danger' : 'warning') ?>">
                        <?= ucfirst($data['followup']['status']) ?>
                    </span>
                </div>
                
                <div class="info-item">
                    <label>Priority</label>
                    <span class="badge badge--<?= $data['followup']['priority'] === 'urgent' ? 'danger' : ($data['followup']['priority'] === 'high' ? 'warning' : 'success') ?>">
                        <?= ucfirst($data['followup']['priority']) ?>
                    </span>
                </div>
                
                <div class="info-item">
                    <label>Follow-up Date</label>
                    <span><?= date('M j, Y', strtotime($data['followup']['follow_up_date'])) ?></span>
                </div>
                
                <div class="info-item">
                    <label>Original Date</label>
                    <span><?= date('M j, Y', strtotime($data['followup']['original_date'])) ?></span>
                </div>
                
                <?php if ($data['followup']['reschedule_count'] > 0): ?>
                <div class="info-item">
                    <label>Reschedule Count</label>
                    <span class="badge badge--warning"><?= $data['followup']['reschedule_count'] ?>x</span>
                </div>
                <?php endif; ?>
                
                <?php if ($data['followup']['department_name']): ?>
                <div class="info-item">
                    <label>Department</label>
                    <span><?= htmlspecialchars($data['followup']['department_name']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($data['followup']['description']): ?>
            <div class="info-section">
                <label>Description</label>
                <p><?= nl2br(htmlspecialchars($data['followup']['description'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>🏢</span> Contact Information
            </h2>
        </div>
        <div class="card__body">
            <div class="info-grid">
                <?php if ($data['followup']['company_name']): ?>
                <div class="info-item">
                    <label>Company</label>
                    <span><?= htmlspecialchars($data['followup']['company_name']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($data['followup']['contact_person']): ?>
                <div class="info-item">
                    <label>Contact Person</label>
                    <span><?= htmlspecialchars($data['followup']['contact_person']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($data['followup']['contact_phone']): ?>
                <div class="info-item">
                    <label>Phone</label>
                    <span><a href="tel:<?= htmlspecialchars($data['followup']['contact_phone']) ?>"><?= htmlspecialchars($data['followup']['contact_phone']) ?></a></span>
                </div>
                <?php endif; ?>
                
                <?php if ($data['followup']['contact_email']): ?>
                <div class="info-item">
                    <label>Email</label>
                    <span><a href="mailto:<?= htmlspecialchars($data['followup']['contact_email']) ?>"><?= htmlspecialchars($data['followup']['contact_email']) ?></a></span>
                </div>
                <?php endif; ?>
                
                <?php if ($data['followup']['project_name']): ?>
                <div class="info-item">
                    <label>Project</label>
                    <span><?= htmlspecialchars($data['followup']['project_name']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($data['items'])): ?>
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>✅</span> Action Items / Checklist
        </h2>
    </div>
    <div class="card__body">
        <div class="checklist">
            <?php foreach ($data['items'] as $item): ?>
                <div class="checklist-item <?= $item['is_completed'] ? 'completed' : '' ?>">
                    <label class="checkbox-label">
                        <input type="checkbox" 
                               <?= $item['is_completed'] ? 'checked' : '' ?>
                               <?= $data['followup']['status'] === 'completed' ? 'disabled' : '' ?>
                               onchange="updateItem(<?= $item['id'] ?>, this.checked)">
                        <span class="checkmark"></span>
                        <span class="item-text"><?= htmlspecialchars($item['item_text']) ?></span>
                    </label>
                    <?php if ($item['completed_at']): ?>
                        <small class="completion-time">Completed: <?= date('M j, Y g:i A', strtotime($item['completed_at'])) ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="progress-summary">
            <?php 
            $completedCount = count(array_filter($data['items'], function($item) { return $item['is_completed']; }));
            $totalCount = count($data['items']);
            $progressPercent = $totalCount > 0 ? ($completedCount / $totalCount) * 100 : 0;
            ?>
            <div class="progress-info">
                <span>Progress: <?= $completedCount ?>/<?= $totalCount ?> items completed (<?= round($progressPercent) ?>%)</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $progressPercent ?>%"></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📝</span> History & Activity
        </h2>
    </div>
    <div class="card__body">
        <?php if (empty($data['history'])): ?>
            <div class="empty-state">
                <div class="empty-icon">📝</div>
                <h3>No History Available</h3>
                <p>Activity history will appear here as actions are taken.</p>
            </div>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($data['history'] as $entry): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker <?= $entry['action_type'] ?>"></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <span class="timeline-action">
                                    <?php
                                    $actionIcons = [
                                        'created' => '➕',
                                        'updated' => '✏️',
                                        'rescheduled' => '📅',
                                        'completed' => '✅',
                                        'postponed' => '⏸️',
                                        'cancelled' => '❌'
                                    ];
                                    echo $actionIcons[$entry['action_type']] ?? '📝';
                                    ?>
                                    <?= ucfirst(str_replace('_', ' ', $entry['action_type'])) ?>
                                </span>
                                <span class="timeline-time"><?= date('M j, Y g:i A', strtotime($entry['created_at'])) ?></span>
                            </div>
                            <div class="timeline-details">
                                <span class="timeline-user">by <?= htmlspecialchars($entry['user_name']) ?></span>
                                <?php if ($entry['old_date'] && $entry['new_date']): ?>
                                    <div class="timeline-change">
                                        From: <?= date('M j, Y', strtotime($entry['old_date'])) ?> → 
                                        To: <?= date('M j, Y', strtotime($entry['new_date'])) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($entry['notes']): ?>
                                    <div class="timeline-notes"><?= htmlspecialchars($entry['notes']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Reschedule Modal -->
<div class="modal" id="rescheduleModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><span>📅</span> Reschedule Follow-up</h3>
            <button class="modal-close" onclick="closeRescheduleModal()">&times;</button>
        </div>
        <form id="rescheduleForm">
            <div class="modal-body">
                <input type="hidden" id="rescheduleFollowupId" name="followup_id" value="<?= $data['followup']['id'] ?>">
                
                <div class="form-group">
                    <label class="form-label">Current Date</label>
                    <input type="text" class="form-control" value="<?= date('M j, Y', strtotime($data['followup']['follow_up_date'])) ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Date *</label>
                    <input type="date" id="newDate" name="new_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reason for Rescheduling</label>
                    <textarea id="rescheduleReason" name="reason" class="form-control" rows="3" placeholder="Why is this being rescheduled?"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeRescheduleModal()">Cancel</button>
                <button type="submit" class="btn btn--warning">Reschedule</button>
            </div>
        </form>
    </div>
</div>

<style>
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.info-item label {
    display: block;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.info-item span {
    display: block;
    color: var(--text-primary);
}

.info-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.info-section label {
    display: block;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.checklist {
    margin-bottom: 1.5rem;
}

.checklist-item {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: 0.5rem;
    background: white;
}

.checklist-item.completed {
    background: rgba(34, 197, 94, 0.05);
    opacity: 0.8;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    margin: 0;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 0.75rem;
}

.item-text {
    flex: 1;
}

.checklist-item.completed .item-text {
    text-decoration: line-through;
    color: var(--text-secondary);
}

.completion-time {
    display: block;
    margin-top: 0.25rem;
    color: var(--text-secondary);
    font-size: 0.75rem;
}

.progress-summary {
    padding: 1rem;
    background: var(--background-secondary);
    border-radius: var(--border-radius);
}

.progress-info {
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: var(--border-color);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: var(--success-color);
    transition: width 0.3s ease;
}

.timeline {
    position: relative;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--border-color);
}

.timeline-item {
    position: relative;
    padding-left: 3rem;
    margin-bottom: 1.5rem;
}

.timeline-marker {
    position: absolute;
    left: 0.5rem;
    top: 0.25rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: var(--primary-color);
    border: 2px solid white;
    box-shadow: 0 0 0 2px var(--border-color);
}

.timeline-marker.completed {
    background: var(--success-color);
}

.timeline-marker.rescheduled {
    background: var(--warning-color);
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.timeline-action {
    font-weight: 600;
    color: var(--text-primary);
}

.timeline-time {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.timeline-user {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.timeline-change {
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: var(--text-primary);
}

.timeline-notes {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: var(--background-secondary);
    border-radius: var(--border-radius);
    font-size: 0.875rem;
}
</style>

<script>
function updateItem(itemId, isCompleted) {
    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('is_completed', isCompleted ? '1' : '0');
    
    fetch('/ergon/followups/update-item', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update item: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update item');
    });
}

function rescheduleFollowup(id) {
    document.getElementById('newDate').value = '';
    document.getElementById('rescheduleReason').value = '';
    document.getElementById('rescheduleModal').style.display = 'block';
}

function closeRescheduleModal() {
    document.getElementById('rescheduleModal').style.display = 'none';
}

function completeFollowup(id) {
    if (confirm('Mark this follow-up as completed?')) {
        const formData = new FormData();
        formData.append('followup_id', id);
        formData.append('completion_notes', 'Follow-up completed');
        
        fetch('/ergon/followups/complete', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to complete follow-up: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to complete follow-up');
        });
    }
}

// Handle reschedule form submission
document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/ergon/followups/reschedule', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeRescheduleModal();
            location.reload();
        } else {
            alert('Failed to reschedule follow-up: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to reschedule follow-up');
    });
});
</script>