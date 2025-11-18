<?php
$active_page = 'followups';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📞</span> Follow-ups Management</h1>
        <p>Track and manage client follow-ups and communications</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--secondary" onclick="toggleFilters()">
            <span>🔍</span> Filters
        </button>
        <a href="/ergon/followups/create" class="btn btn--primary">
            <span>➕</span> Add Follow-up
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="dashboard-grid">
    <div class="kpi-card kpi-card--danger">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚠️</div>
            <div class="kpi-card__trend">Overdue</div>
        </div>
        <div class="kpi-card__value"><?= $overdue ?? 0 ?></div>
        <div class="kpi-card__label">Overdue Follow-ups</div>
        <div class="kpi-card__status">Needs Attention</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend">Today</div>
        </div>
        <div class="kpi-card__value"><?= $today_count ?? 0 ?></div>
        <div class="kpi-card__label">Due Today</div>
        <div class="kpi-card__status">Scheduled</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">Completed</div>
        </div>
        <div class="kpi-card__value"><?= $completed ?? 0 ?></div>
        <div class="kpi-card__label">Completed</div>
        <div class="kpi-card__status">Done</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📊</div>
            <div class="kpi-card__trend">Total</div>
        </div>
        <div class="kpi-card__value"><?= count($followups ?? []) ?></div>
        <div class="kpi-card__label">All Follow-ups</div>
        <div class="kpi-card__status">Active</div>
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
                <label class="form-label">Company</label>
                <select id="companyFilter" class="form-control">
                    <option value="">All Companies</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Project</label>
                <select id="projectFilter" class="form-control">
                    <option value="">All Projects</option>
                </select>
            </div>
        </div>
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
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Date From</label>
                <input type="date" id="dateFrom" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Date To</label>
                <input type="date" id="dateTo" class="form-control">
            </div>
        </div>
        <div class="card__footer">
            <button class="btn btn--secondary" onclick="clearFilters()">Clear</button>
            <button class="btn btn--primary" onclick="applyFilters()">Apply Filters</button>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Follow-ups List -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Follow-ups List</h2>
        <div class="card__actions">
            <a href="/ergon/followups/phone_consolidated" class="btn btn--sm btn--primary">
                <i class="bi bi-telephone-fill"></i> Phone Consolidated
            </a>
            <button class="btn btn--sm btn--secondary" onclick="toggleView()">
                <span id="viewToggle">📋</span> Grid View
            </button>
        </div>
    </div>
    <div class="card__body">
        <?php if (!empty($followups)): ?>
            <div id="listView" class="table-responsive">
                <table class="table">
                    <thead>
                        <tr id="normalHeader">
                            <th>Title</th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Project</th>
                            <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'owner'])): ?>
                                <th>Assigned To</th>
                            <?php endif; ?>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>

                    </thead>
                    <tbody id="followupsTable">
                        <?php foreach ($followups as $followup): ?>
                            <tr class="normal-row" data-company="<?= htmlspecialchars($followup['company_name'] ?? '') ?>" 
                                data-project="<?= htmlspecialchars($followup['project_name'] ?? '') ?>"
                                data-contact="<?= htmlspecialchars($followup['contact_person'] ?? '') ?>"
                                data-status="<?= $followup['status'] ?>"
                                data-date="<?= $followup['follow_up_date'] ?>"
                                data-id="<?= $followup['id'] ?>"
                                data-title="<?= htmlspecialchars($followup['title']) ?>"
                                data-description="<?= htmlspecialchars($followup['description'] ?? '') ?>">
                                <td>
                                    <strong><?= htmlspecialchars($followup['title']) ?></strong>
                                    <?php if ($followup['description']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars(substr($followup['description'], 0, 50)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($followup['company_name'] ?? '-') ?></td>
                                <td>
                                    <?= htmlspecialchars($followup['contact_person'] ?? '-') ?>
                                    <?php if ($followup['contact_phone']): ?>
                                        <br><a href="tel:<?= $followup['contact_phone'] ?>" class="text-primary">📞 <?= $followup['contact_phone'] ?></a>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($followup['project_name'] ?? '-') ?></td>
                                <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'owner'])): ?>
                                    <td><?= htmlspecialchars($followup['assigned_user'] ?? 'User ID: ' . $followup['user_id']) ?></td>
                                <?php endif; ?>
                                <td data-sort-value="<?= $followup['follow_up_date'] ?>">
                                    <div class="cell-meta">
                                        <?php if (!empty($followup['follow_up_date'])): ?>
                                            <div class="cell-primary"><?= date('M d, Y', strtotime($followup['follow_up_date'])) ?></div>
                                            <div class="cell-secondary"><?php
                                                $followupTime = strtotime($followup['follow_up_date']);
                                                if ($followupTime !== false) {
                                                    $todayTime = strtotime('today');
                                                    $currentTime = time();
                                                    
                                                    if ($followupTime < $todayTime) {
                                                        $daysAgo = abs(floor(($currentTime - $followupTime) / 86400));
                                                        echo $daysAgo . ' days ago';
                                                    } elseif ($followupTime == $todayTime) {
                                                        echo 'Today';
                                                    } else {
                                                        $daysLeft = ceil(($followupTime - $currentTime) / 86400);
                                                        echo $daysLeft . ' days left';
                                                    }
                                                } else {
                                                    echo 'Invalid date';
                                                }
                                            ?></div>
                                        <?php else: ?>
                                            <div class="cell-primary">No date set</div>
                                        <?php endif; ?>
                                        <?php if (strtotime($followup['follow_up_date']) < time() && $followup['status'] !== 'completed'): ?>
                                            <span class="badge badge--danger">⚠️ Overdue</span>
                                        <?php endif; ?>
                                        <?php 
                                        $hasReminder = !empty($followup['reminder_time']) && !($followup['reminder_sent'] ?? false);
                                        if ($hasReminder && !empty($followup['follow_up_date'])) {
                                            try {
                                                $reminderDateTime = $followup['follow_up_date'] . ' ' . $followup['reminder_time'];
                                                $isReminderDue = strtotime($reminderDateTime) <= time();
                                                if ($isReminderDue) {
                                                    echo '<span class="badge badge--warning">🔔 Time</span>';
                                                }
                                            } catch (Exception $e) {
                                                error_log('Reminder time calculation error: ' . $e->getMessage());
                                            }
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td data-sort-value="<?= $followup['status'] ?>">
                                    <?php 
                                    $status = $followup['status'];
                                    $isOverdue = strtotime($followup['follow_up_date']) < time() && $status !== 'completed';
                                    
                                    if ($isOverdue) {
                                        $badgeClass = 'danger';
                                    } else {
                                        $badgeClass = match($status) {
                                            'completed' => 'success',
                                            'pending' => 'pending',
                                            'in_progress' => 'info',
                                            'postponed', 'rescheduled' => 'warning',
                                            'cancelled' => 'cancelled',
                                            default => 'info'
                                        };
                                    }
                                    ?>
                                    <span class="badge badge--<?= $badgeClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $followup['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="ab-container">
                                        <button class="ab-btn ab-btn--view" onclick="viewFollowup(<?= $followup['id'] ?>)" data-tooltip="View Details">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <polyline points="14,2 14,8 20,8"/>
                                                <line x1="16" y1="13" x2="8" y2="13"/>
                                                <line x1="16" y1="17" x2="8" y2="17"/>
                                            </svg>
                                        </button>
                                        <?php if ($followup['status'] !== 'completed'): ?>
                                            <button class="ab-btn ab-btn--approve" onclick="completeFollowup(<?= $followup['id'] ?>)" data-tooltip="Mark Complete">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <polyline points="20,6 9,17 4,12"/>
                                                </svg>
                                            </button>
                                            <button class="ab-btn ab-btn--edit" onclick="rescheduleFollowup(<?= $followup['id'] ?>)" data-tooltip="Reschedule">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                                    <path d="M15 5l4 4"/>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                        <button class="ab-btn ab-btn--history" onclick="showHistory(<?= $followup['id'] ?>)" data-tooltip="View History">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12,6 12,12 16,14"/>
                                            </svg>
                                        </button>
                                        <?php 
                                        $currentUserId = $_SESSION['user_id'] ?? 0;
                                        $currentUserRole = $_SESSION['role'] ?? '';
                                        $isOwner = ($followup['user_id'] ?? 0) == $currentUserId;
                                        $isAdmin = in_array($currentUserRole, ['admin', 'owner']);
                                        $canDelete = $isOwner || $isAdmin;
                                        if ($canDelete): 
                                        ?>
                                            <button class="ab-btn ab-btn--delete" onclick="deleteFollowup(<?= $followup['id'] ?>, '<?= htmlspecialchars($followup['title']) ?>')" data-tooltip="Delete">
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
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
                </table>
            </div>
            
            <div id="gridView" class="followups-grid grid--hidden">
                <?php foreach ($followups as $followup): ?>
                    <div class="followup-card normal-card" data-company="<?= htmlspecialchars($followup['company_name'] ?? '') ?>" 
                         data-project="<?= htmlspecialchars($followup['project_name'] ?? '') ?>"
                         data-contact="<?= htmlspecialchars($followup['contact_person'] ?? '') ?>"
                         data-status="<?= $followup['status'] ?>"
                         data-date="<?= $followup['follow_up_date'] ?>"
                         data-id="<?= $followup['id'] ?>"
                         data-title="<?= htmlspecialchars($followup['title']) ?>"
                         data-description="<?= htmlspecialchars($followup['description'] ?? '') ?>">
                        <div class="followup-card__header">
                            <h4><?= htmlspecialchars($followup['title']) ?></h4>
                            <?php 
                            $badgeClass = match($followup['status']) {
                                'completed' => 'success',
                                'pending' => 'pending',
                                'in_progress' => 'info',
                                'postponed', 'rescheduled' => 'warning',
                                'cancelled' => 'cancelled',
                                default => 'info'
                            };
                            ?>
                            <span class="badge badge--<?= $badgeClass ?>">
                                <?= ucfirst($followup['status']) ?>
                            </span>
                        </div>
                        <div class="followup-card__body">
                            <div class="followup-info">
                                <div><strong>Company:</strong> <?= htmlspecialchars($followup['company_name'] ?? '-') ?></div>
                                <div><strong>Project:</strong> <?= htmlspecialchars($followup['project_name'] ?? '-') ?></div>
                                <div><strong>Contact:</strong> <?= htmlspecialchars($followup['contact_person'] ?? '-') ?></div>
                                <div><strong>Due:</strong> <?= date('M d, Y', strtotime($followup['follow_up_date'])) ?></div>
                                <?php if ($followup['reminder_time']): ?>
                                    <div><strong>Time:</strong> <?= date('g:i A', strtotime($followup['reminder_time'])) ?>
                                        <?php 
                                        if (!($followup['reminder_sent'] ?? false) && !empty($followup['follow_up_date']) && !empty($followup['reminder_time'])) {
                                            try {
                                                $reminderDateTime = $followup['follow_up_date'] . ' ' . $followup['reminder_time'];
                                                if (strtotime($reminderDateTime) <= time()) {
                                                    echo ' <span class="badge badge--warning">🔔</span>';
                                                }
                                            } catch (Exception $e) {
                                                error_log('Reminder display error: ' . $e->getMessage());
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($followup['description']): ?>
                                <p class="followup-desc"><?= htmlspecialchars($followup['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="followup-card__actions">
                            <button class="btn btn--sm btn--primary" onclick="viewFollowup(<?= $followup['id'] ?>)">View</button>
                            <?php if ($followup['status'] !== 'completed'): ?>
                                <button class="btn btn--sm btn--success" onclick="completeFollowup(<?= $followup['id'] ?>)">Complete</button>
                                <button class="btn btn--sm btn--warning" onclick="rescheduleFollowup(<?= $followup['id'] ?>)">Reschedule</button>
                            <?php endif; ?>
                            <button class="btn btn--sm btn--info" onclick="showHistory(<?= $followup['id'] ?>)">History</button>
                            <?php 
                            $currentUserId = $_SESSION['user_id'] ?? 0;
                            $currentUserRole = $_SESSION['role'] ?? '';
                            $isOwner = ($followup['user_id'] ?? 0) == $currentUserId;
                            $isAdmin = in_array($currentUserRole, ['admin', 'owner']);
                            $canDelete = $isOwner || $isAdmin;
                            if ($canDelete): 
                            ?>
                                <button class="btn btn--sm btn--danger" onclick="deleteFollowup(<?= $followup['id'] ?>, '<?= htmlspecialchars($followup['title']) ?>')">Delete</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📞</div>
                <h3>No Follow-ups Yet</h3>
                <p>Create your first follow-up to get started</p>
                <a href="/ergon/followups/create" class="btn btn--primary">Add Follow-up</a>
            </div>
        <?php endif; ?>
    </div>
</div>



<!-- Reschedule Modal -->
<div id="rescheduleModal" class="modal modal--hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reschedule Follow-up</h3>
            <button class="modal-close" onclick="closeRescheduleModal()">&times;</button>
        </div>
        <form method="POST" action="/ergon/followups/reschedule">
            <input type="hidden" name="followup_id" id="rescheduleFollowupId">
            <div class="modal-body">
                <div class="form-group">
                    <label>New Date *</label>
                    <input type="date" name="new_date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Time</label>
                    <div class="time-input">
                        <input type="number" name="hour" min="1" max="12" placeholder="Hour" class="form-input">
                        <input type="number" name="minute" min="0" max="59" placeholder="Min" class="form-input">
                        <select name="ampm" class="form-input">
                            <option value="AM">AM</option>
                            <option value="PM">PM</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Reason for Rescheduling</label>
                    <textarea name="reason" class="form-input" rows="3" placeholder="Why is this being rescheduled?"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeRescheduleModal()">Cancel</button>
                <button type="submit" class="btn btn--warning">Reschedule</button>
            </div>
        </form>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal modal--hidden">
    <div class="modal-content modal-content--large">
        <div class="modal-header">
            <h3>Follow-up Details</h3>
            <button class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="viewContent">Loading...</div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div id="historyModal" class="modal modal--hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Follow-up History</h3>
            <button class="modal-close" onclick="closeHistoryModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="historyContent">Loading...</div>
        </div>
    </div>
</div>

<script>
let currentView = 'list';

function toggleFilters() {
    const panel = document.getElementById('filtersPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

// Remove backup system check entries
function removeBackupEntries() {
    const rows = document.querySelectorAll('tr[data-title*="Backup System Check"], .followup-card[data-title*="Backup System Check"]');
    rows.forEach(row => row.remove());
}



function toggleView() {
    const listView = document.getElementById('listView');
    const gridView = document.getElementById('gridView');
    const toggleBtn = document.getElementById('viewToggle');
    
    if (currentView === 'list') {
        listView.classList.add('grid--hidden');
        gridView.classList.remove('grid--hidden');
        toggleBtn.nextSibling.textContent = ' List View';
        currentView = 'grid';
    } else {
        listView.classList.remove('grid--hidden');
        gridView.classList.add('grid--hidden');
        toggleBtn.nextSibling.textContent = ' Grid View';
        currentView = 'list';
    }
}

function viewFollowup(id) {
    fetch(`/ergon/followups/view/${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('viewContent').innerHTML = html;
            document.getElementById('viewModal').classList.remove('modal--hidden');
        })
        .catch(error => {
            console.error('Error loading followup details:', error);
            alert('Error loading followup details');
        });
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('modal--hidden');
}

function completeFollowup(id) {
    if (confirm('Mark this follow-up as completed?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="complete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function rescheduleFollowup(id) {
    document.getElementById('rescheduleModal').classList.remove('modal--hidden');
    document.getElementById('rescheduleFollowupId').value = id;
}

function closeRescheduleModal() {
    document.getElementById('rescheduleModal').classList.add('modal--hidden');
}

function showHistory(id) {
    document.getElementById('historyModal').classList.remove('modal--hidden');
    fetch(`/ergon/followups/history/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('historyContent').innerHTML = data.html || 'No history available';
        })
        .catch(error => {
            console.error('Error loading history:', error);
            document.getElementById('historyContent').innerHTML = 'Error loading history';
        });
}

function closeHistoryModal() {
    document.getElementById('historyModal').classList.add('modal--hidden');
}

function deleteFollowup(id, title) {
    if (confirm(`Delete follow-up "${title}"?\n\nThis action cannot be undone.`)) {
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
                alert('Error: ' + (data.message || 'Failed to delete follow-up'));
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            alert('An error occurred while deleting the follow-up.');
        });
    }
}

function applyFilters() {
    const company = document.getElementById('companyFilter').value;
    const project = document.getElementById('projectFilter').value;
    const contact = document.getElementById('contactFilter').value;
    const status = document.getElementById('statusFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    const rows = document.querySelectorAll('#followupsTable tr, .followup-card');
    
    rows.forEach(row => {
        let show = true;
        
        if (company && row.dataset.company !== company) show = false;
        if (project && row.dataset.project !== project) show = false;
        if (contact && row.dataset.contact !== contact) show = false;
        if (status && row.dataset.status !== status) show = false;
        if (dateFrom && row.dataset.date < dateFrom) show = false;
        if (dateTo && row.dataset.date > dateTo) show = false;
        
        if (show) {
            row.classList.remove('grid--hidden');
        } else {
            row.classList.add('grid--hidden');
        }
    });
    

}

function clearFilters() {
    document.getElementById('companyFilter').value = '';
    document.getElementById('projectFilter').value = '';
    document.getElementById('contactFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    applyFilters();
}

// Check for reminders
function checkReminders() {
    fetch('/ergon/check_reminders.php')
        .then(response => {
            if (!response.ok) return { reminders: [] };
            return response.json();
        })
        .then(data => {
            if (data.reminders && data.reminders.length > 0) {
                showReminderPopup(data.reminders);
            }
        })
        .catch(error => {
            console.error('Reminder check failed:', error);
            // Fail silently for reminders to not disrupt user experience
        });
}

function showReminderPopup(reminders) {
    let html = '<div class="reminder-popup"><h3>🔔 Follow-up Reminders</h3>';
    reminders.forEach(reminder => {
        const time = reminder.reminder_time ? new Date('2000-01-01 ' + reminder.reminder_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '';
        html += `<div class="reminder-item">`;
        html += `<strong>${reminder.title}</strong><br>`;
        html += `<small>${reminder.company_name || ''} - ${reminder.contact_person || ''}</small><br>`;
        html += `<small>Follow-up: ${new Date(reminder.follow_up_date).toLocaleDateString()} ${time}</small>`;
        html += `</div>`;
    });
    html += '<button onclick="closeReminderPopup()" class="btn btn--primary">Got it!</button></div>';
    html += '<div class="reminder-overlay" onclick="closeReminderPopup()"></div>';
    
    document.body.insertAdjacentHTML('beforeend', html);
}

function closeReminderPopup() {
    const popup = document.querySelector('.reminder-popup');
    const overlay = document.querySelector('.reminder-overlay');
    if (popup) popup.remove();
    if (overlay) overlay.remove();
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Check reminders on page load
    checkReminders();
    
    // Check reminders every 5 minutes
    setInterval(checkReminders, 300000);
    
    // Remove backup system check entries on page load
    removeBackupEntries();
    const companies = [...new Set(Array.from(document.querySelectorAll('[data-company]')).map(el => el.dataset.company).filter(Boolean))];
    const projects = [...new Set(Array.from(document.querySelectorAll('[data-project]')).map(el => el.dataset.project).filter(Boolean))];
    const contacts = [...new Set(Array.from(document.querySelectorAll('[data-contact]')).map(el => el.dataset.contact).filter(Boolean))];
    
    const companySelect = document.getElementById('companyFilter');
    const projectSelect = document.getElementById('projectFilter');
    const contactSelect = document.getElementById('contactFilter');
    
    companies.forEach(company => {
        const option = document.createElement('option');
        option.value = company;
        option.textContent = company;
        companySelect.appendChild(option);
    });
    
    projects.forEach(project => {
        const option = document.createElement('option');
        option.value = project;
        option.textContent = project;
        projectSelect.appendChild(option);
    });
    
    contacts.forEach(contact => {
        const option = document.createElement('option');
        option.value = contact;
        option.textContent = contact;
        contactSelect.appendChild(option);
    });
});
</script>

<script src="/ergon/assets/js/table-utils.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>