<?php
$title = 'Holiday Management';
$active_page = 'holidays';
ob_start();
?>

<style>
.holiday-badge-national { background: #e3f2fd; color: #0d47a1; border-left: 4px solid #1976d2; }
.holiday-badge-festival { background: #fff3e0; color: #e65100; border-left: 4px solid #ff6f00; }
.holiday-badge-company { background: #e8f5e9; color: #1b5e20; border-left: 4px solid #388e3c; }
.holiday-badge-emergency { background: #ffebee; color: #b71c1c; border-left: 4px solid #d32f2f; }

.holiday-color-box {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 4px;
    margin-right: 8px;
    vertical-align: middle;
}

.holidays-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.holiday-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    transition: all 0.2s ease;
}

.holiday-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #d1d5db;
}

.holiday-card__header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 0.75rem;
}

.holiday-card__title {
    font-weight: 600;
    font-size: 1rem;
    color: #1f2937;
}

.holiday-card__type {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.holiday-card__date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    color: #4b5563;
}

.holiday-card__scope {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: #f3f4f6;
    border-radius: 4px;
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.5rem;
}

.holiday-card__actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.holiday-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.holiday-modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.holiday-modal__content {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.holiday-modal__header {
    margin-bottom: 1.5rem;
}

.holiday-modal__title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.holiday-form-group {
    margin-bottom: 1.5rem;
}

.holiday-form-group__label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.holiday-form-group__input,
.holiday-form-group__select,
.holiday-form-group__textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.95rem;
    font-family: inherit;
    transition: border-color 0.2s;
}

.holiday-form-group__input:focus,
.holiday-form-group__select:focus,
.holiday-form-group__textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.holiday-form-group__textarea {
    resize: vertical;
    min-height: 80px;
}

.holiday-form-group__checkbox {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.holiday-form-group__checkbox input {
    cursor: pointer;
}

.holiday-form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.holiday-form-actions button {
    flex: 1;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.95rem;
}

.holiday-form-actions .btn-primary {
    background: #3b82f6;
    color: white;
}

.holiday-form-actions .btn-primary:hover {
    background: #2563eb;
}

.holiday-form-actions .btn-secondary {
    background: #e5e7eb;
    color: #374151;
}

.holiday-form-actions .btn-secondary:hover {
    background: #d1d5db;
}

.holiday-filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.holiday-filters input,
.holiday-filters select {
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
}

.holiday-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.holiday-stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
}

.holiday-stat-card__value {
    font-size: 2rem;
    font-weight: 700;
}

.holiday-stat-card__label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-top: 0.5rem;
}

@media (max-width: 768px) {
    .holidays-grid {
        grid-template-columns: 1fr;
    }
    
    .holiday-modal__content {
        width: 95%;
        padding: 1.5rem;
    }
}
</style>

<div class="page-header">
    <div class="page-title">
        <h1><span>🗓️</span> Holiday Management</h1>
        <p>Mark company holidays, festivals, and special dates for all employees</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--primary" onclick="openHolidayModal()">
            <span>➕</span> Mark Holiday
        </button>
    </div>
</div>

<!-- Statistics -->
<div class="holiday-stats">
    <div class="holiday-stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="holiday-stat-card__value" id="totalHolidaysCount">0</div>
        <div class="holiday-stat-card__label">Total Holidays</div>
    </div>
    <div class="holiday-stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
        <div class="holiday-stat-card__value" id="upcomingHolidaysCount">0</div>
        <div class="holiday-stat-card__label">Upcoming (30 days)</div>
    </div>
    <div class="holiday-stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
        <div class="holiday-stat-card__value" id="todayHolidayStatus">-</div>
        <div class="holiday-stat-card__label">Today's Status</div>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card__body">
        <div class="holiday-filters">
            <input type="date" id="filterStartDate" value="<?= $start_date ?? date('Y-m-01') ?>" onchange="filterHolidays()">
            <input type="date" id="filterEndDate" value="<?= $end_date ?? date('Y-m-t') ?>" onchange="filterHolidays()">
            <select id="filterType" onchange="filterHolidays()">
                <option value="">All Types</option>
                <option value="National">National</option>
                <option value="Festival">Festival</option>
                <option value="Company">Company</option>
                <option value="Emergency">Emergency</option>
                <option value="Other">Other</option>
            </select>
            <button class="btn btn--secondary" onclick="resetFilters()">
                <span>↺</span> Reset
            </button>
        </div>
    </div>
</div>

<!-- Holidays Grid -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title"><span>📅</span> Marked Holidays</h2>
    </div>
    <div class="card__body">
        <div id="holidaysContainer" class="holidays-grid">
            <?php if (!empty($holidays)): ?>
                <?php foreach ($holidays as $holiday): ?>
                    <div class="holiday-card">
                        <div class="holiday-card__header">
                            <div class="holiday-card__title"><?= htmlspecialchars($holiday['holiday_name']) ?></div>
                            <div class="holiday-card__type" style="background: <?= $this->getHolidayTypeColor($holiday['holiday_type']) ?>20; color: <?= $this->getHolidayTypeColor($holiday['holiday_type']) ?>;">
                                <?= $holiday['holiday_type'] ?>
                            </div>
                        </div>
                        <div class="holiday-card__date">
                            <span>📅</span>
                            <span><?= date('M d, Y', strtotime($holiday['holiday_date'])) ?></span>
                            <span style="color: #9ca3af; margin-left: auto;"><?= date('l', strtotime($holiday['holiday_date'])) ?></span>
                        </div>
                        <?php if ($holiday['description']): ?>
                            <p style="font-size: 0.85rem; color: #6b7280; margin: 0.5rem 0;">
                                <?= htmlspecialchars(substr($holiday['description'], 0, 100)) ?><?= strlen($holiday['description']) > 100 ? '...' : '' ?>
                            </p>
                        <?php endif; ?>
                        <div style="font-size: 0.8rem; color: #9ca3af; margin-top: 0.5rem;">
                            <?php if ($holiday['applies_to'] === 'Department'): ?>
                                <span class="holiday-card__scope">📍 Department: <?= htmlspecialchars($holiday['department_name'] ?? 'N/A') ?></span>
                            <?php elseif ($holiday['applies_to'] === 'All'): ?>
                                <span class="holiday-card__scope">👥 All Employees</span>
                            <?php endif; ?>
                            <?php if ($holiday['repeat_yearly']): ?>
                                <span class="holiday-card__scope" style="margin-left: 0.5rem;">🔄 Yearly</span>
                            <?php endif; ?>
                        </div>
                        <div class="holiday-card__actions">
                            <button class="btn btn--sm btn--secondary" onclick="editHoliday(<?= $holiday['id'] ?>)">
                                ✏️ Edit
                            </button>
                            <button class="btn btn--sm btn--danger" onclick="deleteHoliday(<?= $holiday['id'] ?>)">
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem 1rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                    <h3 style="color: #374151;">No Holidays Found</h3>
                    <p style="color: #9ca3af;">Mark holidays for your company to organize attendance better.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Holiday Modal -->
<div id="holidayModal" class="holiday-modal">
    <div class="holiday-modal__content">
        <div class="holiday-modal__header">
            <h2 class="holiday-modal__title" id="modalTitle">Mark Holiday</h2>
        </div>
        <form id="holidayForm" onsubmit="submitHolidayForm(event)">
            <input type="hidden" id="holidayId" value="">
            
            <div class="holiday-form-group">
                <label class="holiday-form-group__label">Holiday Date *</label>
                <input type="date" id="holidayDate" class="holiday-form-group__input" required>
            </div>
            
            <div class="holiday-form-group">
                <label class="holiday-form-group__label">Holiday Name *</label>
                <input type="text" id="holidayName" class="holiday-form-group__input" placeholder="e.g., Diwali, New Year" required>
            </div>
            
            <div class="holiday-form-group">
                <label class="holiday-form-group__label">Holiday Type *</label>
                <select id="holidayType" class="holiday-form-group__select" required>
                    <option value="National">National Holiday</option>
                    <option value="Festival">Festival</option>
                    <option value="Company">Company Holiday</option>
                    <option value="Emergency">Emergency Closure</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="holiday-form-group">
                <label class="holiday-form-group__label">Description (Optional)</label>
                <textarea id="holidayDescription" class="holiday-form-group__textarea" placeholder="Add details about this holiday..."></textarea>
            </div>
            
            <div class="holiday-form-group">
                <label class="holiday-form-group__label">Apply To *</label>
                <select id="appliesTo" class="holiday-form-group__select" onchange="toggleDepartmentSelect()" required>
                    <option value="All">All Employees</option>
                    <option value="Department">Specific Department</option>
                    <option value="Specific">Specific Employees</option>
                </select>
            </div>
            
            <div class="holiday-form-group" id="departmentSelectGroup" style="display:none;">
                <label class="holiday-form-group__label">Select Department</label>
                <select id="departmentId" class="holiday-form-group__select">
                    <option value="">-- Select Department --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="holiday-form-group">
                <label class="holiday-form-group__checkbox">
                    <input type="checkbox" id="repeatYearly">
                    <span>Repeat this holiday every year</span>
                </label>
            </div>
            
            <div class="holiday-form-actions">
                <button type="button" class="btn-secondary" onclick="closeHolidayModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save Holiday</button>
            </div>
        </form>
    </div>
</div>

<script>
function openHolidayModal() {
    document.getElementById('holidayId').value = '';
    document.getElementById('holidayForm').reset();
    document.getElementById('modalTitle').textContent = 'Mark Holiday';
    document.getElementById('holidayModal').classList.add('active');
}

function closeHolidayModal() {
    document.getElementById('holidayModal').classList.remove('active');
}

function toggleDepartmentSelect() {
    const appliesTo = document.getElementById('appliesTo').value;
    const deptGroup = document.getElementById('departmentSelectGroup');
    deptGroup.style.display = appliesTo === 'Department' ? 'block' : 'none';
}

function submitHolidayForm(event) {
    event.preventDefault();
    
    const holidayId = document.getElementById('holidayId').value;
    const formData = new FormData();
    
    formData.append('holiday_date', document.getElementById('holidayDate').value);
    formData.append('holiday_name', document.getElementById('holidayName').value);
    formData.append('holiday_type', document.getElementById('holidayType').value);
    formData.append('description', document.getElementById('holidayDescription').value);
    formData.append('applies_to', document.getElementById('appliesTo').value);
    formData.append('department_id', document.getElementById('departmentId').value);
    formData.append('repeat_yearly', document.getElementById('repeatYearly').checked ? 'on' : 'off');
    
    const url = holidayId ? '/ergon/holiday/update' : '/ergon/holiday/create';
    if (holidayId) {
        formData.append('id', holidayId);
    }
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Holiday ' + (holidayId ? 'updated' : 'created') + ' successfully!');
            closeHolidayModal();
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Operation failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Server error occurred');
    });
}

function editHoliday(id) {
    fetch('/ergon/holiday/get?id=' + id)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const holiday = data.data;
            document.getElementById('holidayId').value = holiday.id;
            document.getElementById('holidayDate').value = holiday.holiday_date;
            document.getElementById('holidayName').value = holiday.holiday_name;
            document.getElementById('holidayType').value = holiday.holiday_type;
            document.getElementById('holidayDescription').value = holiday.description || '';
            document.getElementById('appliesTo').value = holiday.applies_to;
            document.getElementById('departmentId').value = holiday.department_id || '';
            document.getElementById('repeatYearly').checked = holiday.repeat_yearly;
            document.getElementById('modalTitle').textContent = 'Edit Holiday';
            
            toggleDepartmentSelect();
            document.getElementById('holidayModal').classList.add('active');
        }
    });
}

function deleteHoliday(id) {
    if (!confirm('Are you sure you want to delete this holiday? All related attendance records will be updated.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('/ergon/holiday/delete', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Holiday deleted successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to delete'));
        }
    });
}

function filterHolidays() {
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;
    const type = document.getElementById('filterType').value;
    
    window.location.href = `/ergon/holidays?start_date=${startDate}&end_date=${endDate}${type ? '&type=' + type : ''}`;
}

function resetFilters() {
    window.location.href = '/ergon/holidays';
}

// Load statistics
document.addEventListener('DOMContentLoaded', function() {
    loadStatistics();
});

function loadStatistics() {
    // Total holidays count
    const totalCards = document.querySelectorAll('.holiday-card').length;
    document.getElementById('totalHolidaysCount').textContent = totalCards;
    
    // Upcoming holidays count
    fetch('/ergon/holiday/upcoming?days=30')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('upcomingHolidaysCount').textContent = data.data.length;
        }
    });
    
    // Check if today is holiday
    fetch('/ergon/holiday/today')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('todayHolidayStatus').textContent = data.is_holiday ? '🎉 Yes' : '📅 No';
        }
    });
}

// Close modal when clicking outside
document.getElementById('holidayModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeHolidayModal();
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
