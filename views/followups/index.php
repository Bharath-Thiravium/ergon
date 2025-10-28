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
        <button class="btn btn--primary" onclick="showAddForm()">
            <span>➕</span> Add Follow-up
        </button>
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
        <h2 class="card__title">
            <span>📞</span> Follow-ups List
        </h2>
        <div class="card__actions">
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
                        <tr>
                            <th>Title</th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Project</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="followupsTable">
                        <?php foreach ($followups as $followup): ?>
                            <tr data-company="<?= htmlspecialchars($followup['company_name'] ?? '') ?>" 
                                data-project="<?= htmlspecialchars($followup['project_name'] ?? '') ?>"
                                data-contact="<?= htmlspecialchars($followup['contact_person'] ?? '') ?>"
                                data-status="<?= $followup['status'] ?>"
                                data-date="<?= $followup['follow_up_date'] ?>">
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
                                <td>
                                    <?= date('M d, Y', strtotime($followup['follow_up_date'])) ?>
                                    <?php if (strtotime($followup['follow_up_date']) < time() && $followup['status'] !== 'completed'): ?>
                                        <br><span class="badge badge--danger">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge--<?= $followup['status'] === 'completed' ? 'success' : (in_array($followup['status'], ['postponed', 'rescheduled']) ? 'warning' : 'info') ?>">
                                        <?= ucfirst($followup['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn--sm btn--primary" onclick="viewFollowup(<?= $followup['id'] ?>)">
                                            <span>👁️</span>
                                        </button>
                                        <?php if ($followup['status'] !== 'completed'): ?>
                                            <button class="btn btn--sm btn--success" onclick="completeFollowup(<?= $followup['id'] ?>)">
                                                <span>✅</span>
                                            </button>
                                            <button class="btn btn--sm btn--warning" onclick="rescheduleFollowup(<?= $followup['id'] ?>)">
                                                <span>📅</span>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn--sm btn--info" onclick="showHistory(<?= $followup['id'] ?>)">
                                            <span>📋</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div id="gridView" class="followups-grid" style="display:none;">
                <?php foreach ($followups as $followup): ?>
                    <div class="followup-card" data-company="<?= htmlspecialchars($followup['company_name'] ?? '') ?>" 
                         data-project="<?= htmlspecialchars($followup['project_name'] ?? '') ?>"
                         data-contact="<?= htmlspecialchars($followup['contact_person'] ?? '') ?>"
                         data-status="<?= $followup['status'] ?>"
                         data-date="<?= $followup['follow_up_date'] ?>">
                        <div class="followup-card__header">
                            <h4><?= htmlspecialchars($followup['title']) ?></h4>
                            <span class="badge badge--<?= $followup['status'] === 'completed' ? 'success' : (in_array($followup['status'], ['postponed', 'rescheduled']) ? 'warning' : 'info') ?>">
                                <?= ucfirst($followup['status']) ?>
                            </span>
                        </div>
                        <div class="followup-card__body">
                            <div class="followup-info">
                                <div><strong>Company:</strong> <?= htmlspecialchars($followup['company_name'] ?? '-') ?></div>
                                <div><strong>Project:</strong> <?= htmlspecialchars($followup['project_name'] ?? '-') ?></div>
                                <div><strong>Contact:</strong> <?= htmlspecialchars($followup['contact_person'] ?? '-') ?></div>
                                <div><strong>Due:</strong> <?= date('M d, Y', strtotime($followup['follow_up_date'])) ?></div>
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
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📞</div>
                <h3>No Follow-ups Yet</h3>
                <p>Create your first follow-up to get started</p>
                <button class="btn btn--primary" onclick="showAddForm()">Add Follow-up</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Form Modal -->
<div id="followupModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Follow-up</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" name="title" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" name="company_name" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="contact_phone" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Project</label>
                        <input type="text" name="project_name" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Follow-up Date *</label>
                        <input type="date" name="follow_up_date" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-input" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn--primary">Save Follow-up</button>
            </div>
        </form>
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

function toggleFilters() {
    const panel = document.getElementById('filtersPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
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

function showAddForm() {
    document.getElementById('followupModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('followupModal').style.display = 'none';
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

// Populate filter options
document.addEventListener('DOMContentLoaded', function() {
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
    z-index: 1000;
    padding: 1rem;
    box-sizing: border-box;
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
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>