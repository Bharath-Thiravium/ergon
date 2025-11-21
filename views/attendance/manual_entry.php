<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// Check if user is owner or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: /ergon/login.php');
    exit;
}

$db = Database::connect();

// Get all users for dropdown
$stmt = $db->prepare("SELECT id, name, email FROM users WHERE status = 'active' ORDER BY name");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$content = ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="bi bi-clock-history"></i> Manual Attendance Entry</h1>
        <p>Enter attendance for users who cannot clock in/out due to geo-fencing or technical issues</p>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h3 class="card__title">Manual Entry Form</h3>
    </div>
    <div class="card__body">
        <form id="manualAttendanceForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="userId">Select User *</label>
                    <select id="userId" name="user_id" class="form-control" required>
                        <option value="">Choose user...</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="entryDate">Date *</label>
                    <input type="date" id="entryDate" name="entry_date" class="form-control" 
                           value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="entryType">Entry Type *</label>
                    <select id="entryType" name="entry_type" class="form-control" required>
                        <option value="">Select type...</option>
                        <option value="clock_in">Clock In</option>
                        <option value="clock_out">Clock Out</option>
                        <option value="full_day">Full Day Entry</option>
                    </select>
                </div>
                <div class="form-group" id="timeGroup">
                    <label for="entryTime">Time *</label>
                    <input type="time" id="entryTime" name="entry_time" class="form-control" required>
                </div>
            </div>

            <div id="fullDayFields" style="display: none;">
                <div class="form-row">
                    <div class="form-group">
                        <label for="clockInTime">Clock In Time *</label>
                        <input type="time" id="clockInTime" name="clock_in_time" class="form-control" value="09:00">
                    </div>
                    <div class="form-group">
                        <label for="clockOutTime">Clock Out Time *</label>
                        <input type="time" id="clockOutTime" name="clock_out_time" class="form-control" value="17:00">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="reason">Reason for Manual Entry *</label>
                <select id="reason" name="reason" class="form-control" required>
                    <option value="">Select reason...</option>
                    <option value="geo_fencing">Outside geo-fencing range</option>
                    <option value="technical_issue">Technical/App issue</option>
                    <option value="network_problem">Network connectivity problem</option>
                    <option value="device_malfunction">Device malfunction</option>
                    <option value="emergency">Emergency situation</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="notes">Additional Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="3" 
                          placeholder="Provide additional details about the manual entry..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <i class="bi bi-check-circle"></i> Submit Entry
                </button>
                <button type="reset" class="btn btn--secondary">
                    <i class="bi bi-arrow-clockwise"></i> Reset Form
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Recent Manual Entries -->
<div class="card">
    <div class="card__header">
        <h3 class="card__title">Recent Manual Entries</h3>
        <button class="btn btn--sm btn--secondary" onclick="loadRecentEntries()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
    <div class="card__body">
        <div id="recentEntries">Loading...</div>
    </div>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.entry-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    margin-bottom: 0.5rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.entry-info {
    flex: 1;
}

.entry-meta {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.entry-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-clock-in { background: #d1fae5; color: #065f46; }
.badge-clock-out { background: #fef3c7; color: #92400e; }
.badge-full-day { background: #dbeafe; color: #1e40af; }

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const entryTypeSelect = document.getElementById('entryType');
    const timeGroup = document.getElementById('timeGroup');
    const fullDayFields = document.getElementById('fullDayFields');
    const entryTime = document.getElementById('entryTime');
    
    // Handle entry type change
    entryTypeSelect.addEventListener('change', function() {
        if (this.value === 'full_day') {
            timeGroup.style.display = 'none';
            fullDayFields.style.display = 'block';
            entryTime.required = false;
        } else {
            timeGroup.style.display = 'block';
            fullDayFields.style.display = 'none';
            entryTime.required = true;
        }
    });
    
    // Set current time as default
    const now = new Date();
    const currentTime = now.getHours().toString().padStart(2, '0') + ':' + 
                       now.getMinutes().toString().padStart(2, '0');
    entryTime.value = currentTime;
    
    // Form submission
    document.getElementById('manualAttendanceForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitManualEntry();
    });
    
    // Load recent entries on page load
    loadRecentEntries();
});

function submitManualEntry() {
    const formData = new FormData(document.getElementById('manualAttendanceForm'));
    
    fetch('/ergon/api/manual_attendance.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Manual attendance entry submitted successfully', 'success');
            document.getElementById('manualAttendanceForm').reset();
            loadRecentEntries();
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    });
}

function loadRecentEntries() {
    fetch('/ergon/api/manual_attendance.php?action=recent')
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('recentEntries');
        
        if (data.success && data.entries.length > 0) {
            container.innerHTML = data.entries.map(entry => `
                <div class="entry-item">
                    <div class="entry-info">
                        <strong>${entry.user_name}</strong> - ${entry.entry_type_display}
                        <div class="entry-meta">
                            ${entry.entry_date} ${entry.entry_time || ''} | 
                            Reason: ${entry.reason_display} | 
                            By: ${entry.created_by_name}
                        </div>
                        ${entry.notes ? `<div class="entry-meta">Notes: ${entry.notes}</div>` : ''}
                    </div>
                    <span class="entry-badge badge-${entry.entry_type}">${entry.entry_type_display}</span>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-muted">No recent manual entries found.</p>';
        }
    })
    .catch(error => {
        document.getElementById('recentEntries').innerHTML = '<p class="text-danger">Error loading entries.</p>';
    });
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? '#10b981' : '#ef4444';
    notification.innerHTML = `
        <div style="position:fixed;top:20px;right:20px;background:${bgColor};color:white;padding:1rem 1.5rem;border-radius:6px;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
            ${message}
        </div>
    `;
    document.body.appendChild(notification);
    setTimeout(() => document.body.removeChild(notification), 4000);
}
</script>

<?php
$content = ob_get_clean();
$title = 'Manual Attendance Entry';
$active_page = 'attendance';
include __DIR__ . '/../layouts/dashboard.php';
?>