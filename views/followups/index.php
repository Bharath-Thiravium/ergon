<?php
$title = 'Follow-ups Management';
$active_page = 'followups';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📞</span> Follow-ups Management</h1>
        <p>Track and manage client follow-ups with history and reminders</p>
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
<div id="filtersPanel" class="card" style="display:none;">
    <div class="card__header">
        <h3 class="card__title">
            <span>🔍</span> Filters
        </h3>
    </div>
    <div class="card__body">
        <div class="filter-grid">
            <div class="filter-item">
                <label>Company</label>
                <select id="companyFilter" class="form-input">
                    <option value="">All Companies</option>
                </select>
            </div>
            <div class="filter-item">
                <label>Project</label>
                <select id="projectFilter" class="form-input">
                    <option value="">All Projects</option>
                </select>
            </div>
            <div class="filter-item">
                <label>Contact Person</label>
                <select id="contactFilter" class="form-input">
                    <option value="">All Contacts</option>
                </select>
            </div>
            <div class="filter-item">
                <label>Status</label>
                <select id="statusFilter" class="form-input">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="postponed">Postponed</option>
                </select>
            </div>
            <div class="filter-item">
                <label>Date From</label>
                <input type="date" id="dateFrom" class="form-input">
            </div>
            <div class="filter-item">
                <label>Date To</label>
                <input type="date" id="dateTo" class="form-input">
            </div>
        </div>
        <div class="filter-actions">
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
            <label class="checkbox-label">
                <input type="checkbox" id="consolidatedView"> 👥 Consolidated by Contact
            </label>
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
                        <tr id="consolidatedHeader" style="display:none;">
                            <th>Contact Person</th>
                            <th>Follow-ups</th>
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
                                <td>
                                    <?= date('M d, Y', strtotime($followup['follow_up_date'])) ?>
                                    <?php if (strtotime($followup['follow_up_date']) < time() && $followup['status'] !== 'completed'): ?>
                                        <br><span class="badge badge--danger">Overdue</span>
                                    <?php endif; ?>
                                    <?php 
                                    if ($followup['reminder_time'] && !$followup['reminder_sent']) {
                                        $reminderDateTime = $followup['follow_up_date'] . ' ' . $followup['reminder_time'];
                                        if (strtotime($reminderDateTime) <= time()) {
                                            echo '<br><span class="badge badge--warning">🔔 Time</span>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $badgeClass = 'info';
                                    switch($followup['status']) {
                                        case 'completed': $badgeClass = 'success'; break;
                                        case 'pending': $badgeClass = 'pending'; break;
                                        case 'in_progress': $badgeClass = 'info'; break;
                                        case 'postponed': case 'rescheduled': $badgeClass = 'warning'; break;
                                        case 'cancelled': $badgeClass = 'cancelled'; break;
                                    }
                                    ?>
                                    <span class="badge badge--<?= $badgeClass ?>">
                                        <?= ucfirst($followup['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn--sm btn--primary" onclick="viewFollowup(<?= $followup['id'] ?>)" title="View Details">
                                            <span>👁️</span>
                                        </button>
                                        <?php if ($followup['status'] !== 'completed'): ?>
                                            <button class="btn btn--sm btn--success" onclick="completeFollowup(<?= $followup['id'] ?>)" title="Mark Complete">
                                                <span>✅</span>
                                            </button>
                                            <button class="btn btn--sm btn--warning" onclick="rescheduleFollowup(<?= $followup['id'] ?>)" title="Reschedule">
                                                <span>📅</span>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn--sm btn--info" onclick="showHistory(<?= $followup['id'] ?>)" title="View History">
                                            <span>📋</span>
                                        </button>
                                        <?php 
                                        $canDelete = ($followup['user_id'] == $_SESSION['user_id']) || in_array($_SESSION['role'] ?? '', ['admin', 'owner']);
                                        if ($canDelete): 
                                        ?>
                                            <button class="btn btn--sm btn--danger" onclick="deleteFollowup(<?= $followup['id'] ?>, '<?= htmlspecialchars($followup['title']) ?>')" title="Delete">
                                                <span>🗑️</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tbody id="consolidatedTable" style="display:none;">
                        <!-- Consolidated rows will be generated by JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <div id="gridView" class="followups-grid" style="display:none;">
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
                            $badgeClass = 'info';
                            switch($followup['status']) {
                                case 'completed': $badgeClass = 'success'; break;
                                case 'pending': $badgeClass = 'pending'; break;
                                case 'in_progress': $badgeClass = 'info'; break;
                                case 'postponed': case 'rescheduled': $badgeClass = 'warning'; break;
                                case 'cancelled': $badgeClass = 'cancelled'; break;
                            }
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
                                        if (!$followup['reminder_sent']) {
                                            $reminderDateTime = $followup['follow_up_date'] . ' ' . $followup['reminder_time'];
                                            if (strtotime($reminderDateTime) <= time()) {
                                                echo ' <span class="badge badge--warning">🔔</span>';
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
                            $canDelete = ($followup['user_id'] == $_SESSION['user_id']) || in_array($_SESSION['role'] ?? '', ['admin', 'owner']);
                            if ($canDelete): 
                            ?>
                                <button class="btn btn--sm btn--danger" onclick="deleteFollowup(<?= $followup['id'] ?>, '<?= htmlspecialchars($followup['title']) ?>')">Delete</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div id="consolidatedGridView" class="consolidated-grid" style="display:none;">
                <!-- Consolidated grid cards will be generated by JavaScript -->
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
<div id="rescheduleModal" class="modal" style="display:none;">
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
<div id="viewModal" class="modal" style="display:none;">
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
<div id="historyModal" class="modal" style="display:none;">
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
let isConsolidated = false;

function toggleFilters() {
    const panel = document.getElementById('filtersPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function toggleConsolidatedView() {
    isConsolidated = !isConsolidated;
    
    if (currentView === 'list') {
        const normalHeader = document.getElementById('normalHeader');
        const consolidatedHeader = document.getElementById('consolidatedHeader');
        const normalTable = document.getElementById('followupsTable');
        const consolidatedTable = document.getElementById('consolidatedTable');
        
        if (isConsolidated) {
            normalHeader.style.display = 'none';
            consolidatedHeader.style.display = '';
            normalTable.style.display = 'none';
            consolidatedTable.style.display = '';
            generateConsolidatedView();
        } else {
            normalHeader.style.display = '';
            consolidatedHeader.style.display = 'none';
            normalTable.style.display = '';
            consolidatedTable.style.display = 'none';
        }
    } else {
        const normalGrid = document.getElementById('gridView');
        const consolidatedGrid = document.getElementById('consolidatedGridView');
        
        if (isConsolidated) {
            normalGrid.style.display = 'none';
            consolidatedGrid.style.display = 'grid';
            generateConsolidatedGridView();
        } else {
            normalGrid.style.display = 'grid';
            consolidatedGrid.style.display = 'none';
        }
    }
}

function generateConsolidatedView() {
    const rows = document.querySelectorAll('.normal-row');
    const contactGroups = {};
    
    // Group by contact person
    rows.forEach(row => {
        const contact = row.dataset.contact || 'No Contact';
        if (!contactGroups[contact]) {
            contactGroups[contact] = [];
        }
        contactGroups[contact].push({
            id: row.dataset.id,
            title: row.dataset.title,
            description: row.dataset.description,
            date: row.dataset.date,
            status: row.dataset.status,
            company: row.dataset.company,
            project: row.dataset.project
        });
    });
    
    // Generate consolidated HTML
    let html = '';
    Object.keys(contactGroups).forEach(contact => {
        const followups = contactGroups[contact];
        html += `<tr class="consolidated-row">`;
        html += `<td><strong>${contact}</strong><br><small>${followups[0].company || 'No Company'}</small></td>`;
        html += `<td class="followups-list">`;
        
        followups.forEach(followup => {
            let statusClass = 'info';
            switch(followup.status) {
                case 'completed': statusClass = 'success'; break;
                case 'pending': statusClass = 'pending'; break;
                case 'in_progress': statusClass = 'info'; break;
                case 'postponed': case 'rescheduled': statusClass = 'warning'; break;
                case 'cancelled': statusClass = 'cancelled'; break;
            }
            
            html += `<div class="followup-item">`;
            html += `<div class="followup-header">`;
            html += `<strong>${followup.title}</strong>`;
            html += `<span class="badge badge--${statusClass}">${followup.status}</span>`;
            html += `</div>`;
            if (followup.description) {
                html += `<p class="followup-desc">${followup.description}</p>`;
            }
            html += `<div class="followup-meta">`;
            html += `<small>📅 ${new Date(followup.date).toLocaleDateString()}</small>`;
            if (followup.project) {
                html += `<small>📁 ${followup.project}</small>`;
            }
            html += `</div>`;
            html += `<div class="followup-actions">`;
            html += `<button class="btn btn--xs btn--primary" onclick="viewFollowup(${followup.id})">View</button>`;
            if (followup.status !== 'completed') {
                html += `<button class="btn btn--xs btn--success" onclick="completeFollowup(${followup.id})">Complete</button>`;
                html += `<button class="btn btn--xs btn--warning" onclick="rescheduleFollowup(${followup.id})">Reschedule</button>`;
            }
            html += `<button class="btn btn--xs btn--info" onclick="showHistory(${followup.id})">History</button>`;
            html += `</div>`;
            html += `</div>`;
        });
        
        html += `</td>`;
        html += `<td><button class="btn btn--sm btn--primary" onclick="contactActions('${contact}')">Contact Actions</button></td>`;
        html += `</tr>`;
    });
    
    document.getElementById('consolidatedTable').innerHTML = html;
}

function generateConsolidatedGridView() {
    const cards = document.querySelectorAll('.normal-card');
    const contactGroups = {};
    
    // Group by contact person (only visible cards)
    cards.forEach(card => {
        if (card.style.display === 'none') return; // Skip filtered out cards
        
        const contact = card.dataset.contact || 'No Contact';
        if (!contactGroups[contact]) {
            contactGroups[contact] = [];
        }
        contactGroups[contact].push({
            id: card.dataset.id,
            title: card.dataset.title,
            description: card.dataset.description,
            date: card.dataset.date,
            status: card.dataset.status,
            company: card.dataset.company,
            project: card.dataset.project
        });
    });
    
    // Generate consolidated grid HTML
    let html = '';
    Object.keys(contactGroups).forEach(contact => {
        const followups = contactGroups[contact];
        html += `<div class="contact-group-card">`;
        html += `<div class="contact-header">`;
        html += `<h3>${contact}</h3>`;
        html += `<span class="contact-company">${followups[0].company || 'No Company'}</span>`;
        html += `</div>`;
        html += `<div class="contact-followups">`;
        
        followups.forEach(followup => {
            let statusClass = 'info';
            switch(followup.status) {
                case 'completed': statusClass = 'success'; break;
                case 'pending': statusClass = 'pending'; break;
                case 'in_progress': statusClass = 'info'; break;
                case 'postponed': case 'rescheduled': statusClass = 'warning'; break;
                case 'cancelled': statusClass = 'cancelled'; break;
            }
            
            html += `<div class="mini-followup-card">`;
            html += `<div class="mini-header">`;
            html += `<strong>${followup.title}</strong>`;
            html += `<span class="badge badge--${statusClass}">${followup.status}</span>`;
            html += `</div>`;
            if (followup.description) {
                html += `<p class="mini-desc">${followup.description}</p>`;
            }
            html += `<div class="mini-meta">`;
            html += `<small>📅 ${new Date(followup.date).toLocaleDateString()}</small>`;
            if (followup.project) {
                html += `<small>📁 ${followup.project}</small>`;
            }
            html += `</div>`;
            html += `<div class="mini-actions">`;
            html += `<button class="btn btn--xs btn--primary" onclick="viewFollowup(${followup.id})">View</button>`;
            if (followup.status !== 'completed') {
                html += `<button class="btn btn--xs btn--success" onclick="completeFollowup(${followup.id})">Complete</button>`;
                html += `<button class="btn btn--xs btn--warning" onclick="rescheduleFollowup(${followup.id})">Reschedule</button>`;
            }
            html += `<button class="btn btn--xs btn--info" onclick="showHistory(${followup.id})">History</button>`;
            html += `</div>`;
            html += `</div>`;
        });
        
        html += `</div>`;
        html += `</div>`;
    });
    
    document.getElementById('consolidatedGridView').innerHTML = html;
}

function contactActions(contact) {
    alert(`Contact actions for: ${contact}`);
}

function toggleView() {
    const listView = document.getElementById('listView');
    const gridView = document.getElementById('gridView');
    const toggleBtn = document.getElementById('viewToggle');
    
    if (currentView === 'list') {
        listView.style.display = 'none';
        gridView.style.display = 'grid';
        toggleBtn.nextSibling.textContent = ' List View';
        currentView = 'grid';
    } else {
        listView.style.display = 'block';
        gridView.style.display = 'none';
        toggleBtn.nextSibling.textContent = ' Grid View';
        currentView = 'list';
    }
}

function viewFollowup(id) {
    fetch(`/ergon/followups/view/${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('viewContent').innerHTML = html;
            document.getElementById('viewModal').style.display = 'flex';
        })
        .catch(() => {
            alert('Error loading followup details');
        });
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
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
    document.getElementById('rescheduleModal').style.display = 'flex';
    document.getElementById('rescheduleFollowupId').value = id;
}

function closeRescheduleModal() {
    document.getElementById('rescheduleModal').style.display = 'none';
}

function showHistory(id) {
    document.getElementById('historyModal').style.display = 'flex';
    fetch(`/ergon/followups/history/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('historyContent').innerHTML = data.html || 'No history available';
        })
        .catch(() => {
            document.getElementById('historyContent').innerHTML = 'Error loading history';
        });
}

function closeHistoryModal() {
    document.getElementById('historyModal').style.display = 'none';
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
        
        row.style.display = show ? '' : 'none';
    });
    
    // Refresh consolidated view if active
    if (isConsolidated && currentView === 'grid') {
        generateConsolidatedGridView();
    }
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
        .catch(error => console.log('Reminder check failed:', error));
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
    
    // Consolidated view toggle
    document.getElementById('consolidatedView').addEventListener('change', toggleConsolidatedView);
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

<style>
.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.filter-item {
    display: flex;
    flex-direction: column;
}

.filter-item label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.filter-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.followups-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.followup-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    background: white;
}

.followup-card__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.followup-card__header h4 {
    margin: 0;
    font-size: 1.1rem;
}

.followup-info div {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.followup-desc {
    margin: 1rem 0;
    padding: 0.5rem;
    background: #f9fafb;
    border-radius: 4px;
    font-size: 0.9rem;
}

.followup-card__actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999999 !important;
    padding: 1rem;
    box-sizing: border-box;
}

/* Ensure modals appear above all other elements */
.modal * {
    z-index: inherit;
}

/* Override any header z-index */
header, .header, .navbar, .nav {
    z-index: 1000 !important;
}

/* Ensure modal content has proper stacking */
.modal-content {
    position: relative;
    z-index: 1000000 !important;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 100%;
    max-width: min(600px, 90vw);
    max-height: min(90vh, calc(100vh - 2rem));
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    transform: scale(1);
    transform-origin: center;
}

.modal-content--large {
    max-width: min(800px, 95vw);
}

@media (max-width: 768px) {
    .modal {
        padding: 0.5rem;
    }
    
    .modal-content {
        max-width: 100%;
        max-height: calc(100vh - 1rem);
    }
}

/* Fix for different zoom levels */
@media screen and (min-resolution: 120dpi) {
    .modal-content {
        transform: scale(0.9);
    }
}

@media screen and (min-resolution: 144dpi) {
    .modal-content {
        transform: scale(0.8);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
}

.modal-body {
    padding: 1rem;
}

.modal-footer {
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
    text-align: right;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.time-input {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 0.5rem;
    align-items: center;
}

.time-input input,
.time-input select {
    margin: 0;
    min-width: 0;
    box-sizing: border-box;
}

.followup-details {
    padding: 1rem 0;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.detail-item {
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 6px;
    border-left: 3px solid #3b82f6;
}

.detail-item strong {
    color: #374151;
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}

.detail-description {
    padding: 1rem;
    background: #f9fafb;
    border-radius: 6px;
    border-left: 3px solid #10b981;
}

.detail-description strong {
    color: #374151;
    display: block;
    margin-bottom: 0.5rem;
}

.detail-description p {
    margin: 0;
    color: #6b7280;
    line-height: 1.5;
}

.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.timeline-marker {
    position: absolute;
    left: -2rem;
    top: 0.5rem;
    width: 12px;
    height: 12px;
    background: #3b82f6;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 0 0 2px #e5e7eb;
}

.timeline-content h4 {
    margin: 0 0 0.5rem 0;
    color: #1f2937;
}

.timeline-content p {
    margin: 0.5rem 0;
    color: #6b7280;
}

.timeline-content small {
    color: #9ca3af;
    font-size: 0.875rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #374151;
    cursor: pointer;
    margin-right: 1rem;
}

.checkbox-label input[type="checkbox"] {
    margin: 0;
}

.consolidated-row {
    border-bottom: 2px solid #e5e7eb;
}

.followups-list {
    max-width: 600px;
}

.followup-item {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 1rem;
    background: #f9fafb;
}

.followup-item:last-child {
    margin-bottom: 0;
}

.followup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.followup-desc {
    margin: 0.5rem 0;
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.4;
}

.followup-meta {
    display: flex;
    gap: 1rem;
    margin: 0.5rem 0;
}

.followup-meta small {
    color: #9ca3af;
    font-size: 0.8rem;
}

.followup-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 0.75rem;
}

.btn--xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 4px;
}

.consolidated-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
}

.contact-group-card {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    background: white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.contact-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #f3f4f6;
}

.contact-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 1.25rem;
}

.contact-company {
    background: #f3f4f6;
    color: #6b7280;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.875rem;
}

.contact-followups {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.mini-followup-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    background: #f9fafb;
}

.mini-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.mini-desc {
    margin: 0.5rem 0;
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.4;
}

.mini-meta {
    display: flex;
    gap: 1rem;
    margin: 0.5rem 0;
}

.mini-meta small {
    color: #9ca3af;
    font-size: 0.8rem;
}

.mini-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 0.75rem;
}

.reminder-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
}

.reminder-popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    z-index: 10000;
    max-width: 400px;
    width: 90%;
}

.reminder-popup h3 {
    margin: 0 0 1rem 0;
    color: #1f2937;
}

.reminder-item {
    padding: 1rem;
    margin-bottom: 1rem;
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    border-radius: 6px;
}

.reminder-item:last-of-type {
    margin-bottom: 1.5rem;
}

/* Horizontal History Layout */
.history-horizontal {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-height: 400px;
    overflow-y: auto;
}

.history-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    border-left: 4px solid #3b82f6;
}

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.history-header h4 {
    margin: 0;
    color: #1f2937;
    font-size: 1rem;
    font-weight: 600;
}

.history-date {
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 500;
}

.history-content p {
    margin: 0 0 0.5rem 0;
    color: #374151;
    line-height: 1.5;
}

.history-change {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 4px;
    padding: 0.5rem;
    margin: 0.5rem 0;
    font-size: 0.875rem;
    color: #92400e;
}

.history-user {
    color: #9ca3af;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>