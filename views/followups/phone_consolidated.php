<?php
$active_page = 'followups';
ob_start();

// Group followups by contact person for phone call consolidation
$contactGroups = [];
foreach ($followups as $followup) {
    $contact = $followup['contact_person'] ?: 'Unknown Contact';
    if (!isset($contactGroups[$contact])) {
        $contactGroups[$contact] = [
            'contact_info' => [
                'name' => $contact,
                'phone' => $followup['contact_phone'] ?: '',
                'company' => $followup['company_name'] ?: ''
            ],
            'tasks' => []
        ];
    }
    $contactGroups[$contact]['tasks'][] = $followup;
}
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📞</span> Phone Call Follow-ups</h1>
        <p>Consolidated follow-ups by contact person for efficient phone calls</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--secondary" onclick="toggleFilters()">
            <span>🔍</span> Filters
        </button>
        <a href="/ergon/tasks/create" class="btn btn--primary">
            <span>➕</span> Create Task
        </a>
    </div>
</div>

<!-- Filters -->
<div id="filtersPanel" class="card" style="display: none;">
    <div class="card__header">
        <h3 class="card__title">
            <span>🔍</span> Filters
        </h3>
    </div>
    <div class="card__body">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Contact Person</label>
                <select id="contactFilter" class="form-control">
                    <option value="">All Contacts</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select id="statusFilter" class="form-control">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="postponed">Postponed</option>
                </select>
            </div>
        </div>
        <div class="card__footer">
            <button class="btn btn--secondary" onclick="clearFilters()">Clear</button>
            <button class="btn btn--primary" onclick="applyFilters()">Apply Filters</button>
        </div>
    </div>
</div>

<!-- Phone Call Dashboard -->
<div class="dashboard-grid">
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend">Contacts</div>
        </div>
        <div class="kpi-card__value"><?= count($contactGroups) ?></div>
        <div class="kpi-card__label">People to Call</div>
        <div class="kpi-card__status">Ready</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📞</div>
            <div class="kpi-card__trend">Calls</div>
        </div>
        <div class="kpi-card__value"><?= array_sum(array_map(fn($g) => count($g['tasks']), $contactGroups)) ?></div>
        <div class="kpi-card__label">Total Follow-ups</div>
        <div class="kpi-card__status">Pending</div>
    </div>
    
    <div class="kpi-card kpi-card--danger">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚠️</div>
            <div class="kpi-card__trend">Urgent</div>
        </div>
        <div class="kpi-card__value"><?php
            $urgentCount = 0;
            foreach ($contactGroups as $group) {
                foreach ($group['tasks'] as $task) {
                    if (!empty($task['follow_up_date']) && strtotime($task['follow_up_date']) < time() && $task['status'] !== 'completed') {
                        $urgentCount++;
                    }
                }
            }
            echo $urgentCount;
        ?></div>
        <div class="kpi-card__label">Overdue Tasks</div>
        <div class="kpi-card__status">Action Needed</div>
    </div>
</div>

<!-- Follow-ups List -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Follow-ups List</h2>
        <div class="card__actions">
            <label class="checkbox-label">
                <input type="checkbox" id="consolidatedView" checked> 📞 Consolidated by Contact
            </label>
            <button class="btn btn--sm btn--secondary" onclick="toggleView()">
                <span id="viewToggle">📋</span> List View
            </button>
        </div>
    </div>
    <div class="card__body">
        <?php if (!empty($followups)): ?>
            <!-- List View -->
            <div id="listView" class="table-responsive grid--hidden">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Title</th>
                            <th>Contact & Priority</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($followups as $followup): ?>
                            <tr data-contact="<?= htmlspecialchars($followup['contact_person'] ?? '') ?>" data-status="<?= $followup['status'] ?>">
                                <td>
                                    <strong><?= htmlspecialchars($followup['title']) ?></strong>
                                    <?php if (!empty($followup['description'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars(substr($followup['description'], 0, 80)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="assignment-info">
                                        <div class="assigned-user"><?= htmlspecialchars($followup['contact_person'] ?? 'Unknown Contact') ?></div>
                                        <div class="priority-badge">
                                            <span class="badge badge--<?= match($followup['priority'] ?? 'medium') { 'high' => 'danger', 'medium' => 'warning', default => 'info' } ?>"><?= ucfirst($followup['priority'] ?? 'medium') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-meta">
                                        <div class="cell-primary"><?= !empty($followup['follow_up_date']) ? date('M d, Y', strtotime($followup['follow_up_date'])) : 'No due date' ?></div>
                                        <?php if (isset($followup['created_at']) && $followup['created_at']): ?>
                                            <div class="cell-secondary">Created <?= date('M d, Y', strtotime($followup['created_at'])) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge--<?= $followup['status'] ?>"><?= ucfirst($followup['status']) ?></span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="/ergon/tasks/view/<?= $followup['id'] ?>?from=followups" class="btn-icon btn-icon--view" title="View Task Details">👁️</a>
                                        <?php if ($followup['status'] !== 'completed'): ?>
                                            <button onclick="completeTask(<?= $followup['id'] ?>)" class="btn-icon btn-icon--status" title="Update Progress & Status">📊</button>
                                        <?php endif; ?>
                                        <a href="/ergon/tasks/edit/<?= $followup['id'] ?>" class="btn-icon btn-icon--edit" title="Edit Task Details">✏️</a>
                                        <button onclick="deleteRecord('tasks', <?= $followup['id'] ?>, '<?= htmlspecialchars($followup['title'], ENT_QUOTES) ?>')" class="btn-icon btn-icon--delete" title="Delete Task Permanently">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            

            
            <!-- Consolidated View -->
            <div id="consolidatedView">
                <?php if (empty($contactGroups)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📞</div>
                        <h3>No Follow-up Calls Needed</h3>
                        <p>All follow-ups are completed or no phone follow-ups are scheduled</p>
                        <a href="/ergon/tasks/create" class="btn btn--primary">Create Follow-up Task</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($contactGroups as $contact => $group): ?>
                        <div class="card" style="margin-bottom: 1.5rem;">
                            <div class="card__header">
                                <div class="card__title">
                                    <h3><span>👤</span> <?= htmlspecialchars($group['contact_info']['name']) ?></h3>
                                    <div style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                        <?php if (!empty($group['contact_info']['phone'])): ?>
                                            <a href="tel:<?= $group['contact_info']['phone'] ?>" style="color: var(--success); text-decoration: none; margin-right: 1rem;">
                                                📞 <?= htmlspecialchars($group['contact_info']['phone']) ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($group['contact_info']['company'])): ?>
                                            <span>🏢 <?= htmlspecialchars($group['contact_info']['company']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card__actions">
                                    <?php if (!empty($group['contact_info']['phone'])): ?>
                                        <a href="tel:<?= $group['contact_info']['phone'] ?>" class="btn btn--success">
                                            📞 Call Now
                                        </a>
                                    <?php endif; ?>
                                    <button onclick="markAllComplete('<?= htmlspecialchars($contact) ?>')" class="btn btn--primary btn--sm">
                                        ✅ Mark All Done
                                    </button>
                                </div>
                            </div>
                            <div class="card__body">
                                <h4 style="margin: 0 0 1rem 0; font-size: 1rem;">Follow-up Items (<?= count($group['tasks']) ?>)</h4>
                                
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th style="width: 35%;">Title</th>
                                                <th>Priority</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($group['tasks'] as $task): ?>
                                                <?php
                                                $isOverdue = !empty($task['follow_up_date']) && strtotime($task['follow_up_date']) < time() && $task['status'] !== 'completed';
                                                $priorityClass = match($task['priority'] ?? 'medium') {
                                                    'high' => 'danger',
                                                    'medium' => 'warning',
                                                    default => 'info'
                                                };
                                                ?>
                                                <tr data-task-id="<?= $task['id'] ?>" <?= $isOverdue ? 'style="background: rgba(239, 68, 68, 0.1);"' : '' ?>>
                                                    <td>
                                                        <strong><?= htmlspecialchars($task['title']) ?></strong>
                                                        <?php if (!empty($task['description'])): ?>
                                                            <br><small class="text-muted"><?= htmlspecialchars(substr($task['description'], 0, 80)) ?>...</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge--<?= $priorityClass ?>"><?= ucfirst($task['priority'] ?? 'medium') ?></span>
                                                        <?php if ($isOverdue): ?>
                                                            <br><span class="badge badge--danger">Overdue</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="cell-meta">
                                                            <div class="cell-primary"><?= !empty($task['follow_up_date']) ? date('M d, Y', strtotime($task['follow_up_date'])) : 'No due date' ?></div>
                                                            <?php if (isset($task['created_at']) && $task['created_at']): ?>
                                                                <div class="cell-secondary">Created <?= date('M d, Y', strtotime($task['created_at'])) ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge--<?= $task['status'] ?>"><?= ucfirst($task['status']) ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="/ergon/tasks/view/<?= $task['id'] ?>?from=followups" class="btn-icon btn-icon--view" title="View Task Details">👁️</a>
                                                            <?php if ($task['status'] !== 'completed'): ?>
                                                                <button onclick="completeTask(<?= $task['id'] ?>)" class="btn-icon btn-icon--status" title="Update Progress & Status">📊</button>
                                                            <?php endif; ?>
                                                            <a href="/ergon/tasks/edit/<?= $task['id'] ?>" class="btn-icon btn-icon--edit" title="Edit Task Details">✏️</a>
                                                            <button onclick="deleteRecord('tasks', <?= $task['id'] ?>, '<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>')" class="btn-icon btn-icon--delete" title="Delete Task Permanently">🗑️</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📞</div>
                <h3>No Follow-ups Yet</h3>
                <p>Create your first follow-up to get started</p>
                <a href="/ergon/tasks/create" class="btn btn--primary">Add Follow-up</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Call Notes Modal -->
<div id="callNotesModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📞 Call Notes</h3>
            <button class="modal-close" onclick="closeCallNotesModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="callNotesForm">
                <input type="hidden" id="contactName" name="contact_name">
                <div class="form-group">
                    <label class="form-label">Call Outcome</label>
                    <select id="callOutcome" name="outcome" class="form-control">
                        <option value="completed">Call Completed Successfully</option>
                        <option value="no_answer">No Answer - Left Voicemail</option>
                        <option value="busy">Line Busy - Try Again Later</option>
                        <option value="rescheduled">Rescheduled Call</option>
                        <option value="partial">Partial Discussion - Follow-up Needed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Call Notes</label>
                    <textarea id="callNotes" name="notes" class="form-control" rows="4" placeholder="What was discussed? Any action items or next steps?"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Next Follow-up Date (if needed)</label>
                    <input type="date" id="nextFollowupDate" name="next_date" class="form-control">
                </div>
                <div class="card__footer">
                    <button type="submit" class="btn btn--primary">Save Call Notes</button>
                    <button type="button" class="btn btn--secondary" onclick="closeCallNotesModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentView = 'consolidated';
let isConsolidated = true;

function toggleFilters() {
    const panel = document.getElementById('filtersPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function toggleConsolidatedView() {
    isConsolidated = !isConsolidated;
    
    const listView = document.getElementById('listView');
    const consolidatedView = document.getElementById('consolidatedView');
    
    if (isConsolidated) {
        listView.classList.add('grid--hidden');
        consolidatedView.classList.remove('grid--hidden');
    } else {
        listView.classList.remove('grid--hidden');
        consolidatedView.classList.add('grid--hidden');
    }
}

function toggleView() {
    const listView = document.getElementById('listView');
    const consolidatedView = document.getElementById('consolidatedView');
    const toggleBtn = document.getElementById('viewToggle');
    
    if (currentView === 'consolidated') {
        consolidatedView.classList.add('grid--hidden');
        listView.classList.remove('grid--hidden');
        toggleBtn.nextSibling.textContent = ' Consolidated View';
        currentView = 'list';
    } else {
        listView.classList.add('grid--hidden');
        consolidatedView.classList.remove('grid--hidden');
        toggleBtn.nextSibling.textContent = ' List View';
        currentView = 'consolidated';
    }
}



function completeTask(taskId) {
    if (confirm('Mark this follow-up task as completed?')) {
        fetch('/ergon/tasks/update-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ task_id: taskId, progress: 100, status: 'completed' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating task: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating task');
        });
    }
}

function postponeTask(taskId) {
    const newDate = prompt('Enter new follow-up date (YYYY-MM-DD):');
    if (newDate && /^\d{4}-\d{2}-\d{2}$/.test(newDate)) {
        fetch('/ergon/workflow/postpone-task', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ task_id: taskId, new_date: newDate })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error postponing task: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error postponing task');
        });
    }
}

function markAllComplete(contactName) {
    if (confirm(`Mark all follow-up tasks for ${contactName} as completed?`)) {
        const contactCard = document.querySelector(`[data-contact="${contactName}"]`);
        if (contactCard) {
            const taskIds = Array.from(contactCard.querySelectorAll('[data-task-id]')).map(el => el.dataset.taskId);
            
            Promise.all(taskIds.map(taskId => 
                fetch('/ergon/tasks/update-status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ task_id: taskId, progress: 100, status: 'completed' })
                })
            ))
            .then(() => {
                openCallNotesModal(contactName);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating tasks');
            });
        }
    }
}

function openCallNotesModal(contactName) {
    document.getElementById('contactName').value = contactName;
    document.getElementById('callNotesModal').style.display = 'flex';
}

function closeCallNotesModal() {
    document.getElementById('callNotesModal').style.display = 'none';
    document.getElementById('callNotesForm').reset();
}

function applyFilters() {
    const contact = document.getElementById('contactFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    const rows = document.querySelectorAll('#listView tr, .task-card');
    
    rows.forEach(row => {
        let show = true;
        
        if (contact && row.dataset.contact !== contact) show = false;
        if (status && row.dataset.status !== status) show = false;
        
        if (show) {
            row.classList.remove('grid--hidden');
        } else {
            row.classList.add('grid--hidden');
        }
    });
}

function clearFilters() {
    document.getElementById('contactFilter').value = '';
    document.getElementById('statusFilter').value = '';
    applyFilters();
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('consolidatedView').addEventListener('change', toggleConsolidatedView);
    
    // Populate filter options
    const contacts = [...new Set(Array.from(document.querySelectorAll('[data-contact]')).map(el => el.dataset.contact).filter(Boolean))];
    const contactSelect = document.getElementById('contactFilter');
    
    contacts.forEach(contact => {
        const option = document.createElement('option');
        option.value = contact;
        option.textContent = contact;
        contactSelect.appendChild(option);
    });
});

document.getElementById('callNotesForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Call notes saved successfully!');
    closeCallNotesModal();
    location.reload();
});
</script>



<style>
.grid--hidden {
    display: none !important;
}

.assignment-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.assigned-user {
    font-weight: 500;
    color: var(--text-primary);
    font-size: 0.875rem;
}

.priority-badge {
    display: flex;
    align-items: center;
}

.priority-badge .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>

<script src="/ergon/assets/js/table-utils.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>