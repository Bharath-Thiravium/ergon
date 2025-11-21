<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: /ergon/login.php');
    exit;
}

$db = Database::connect();
$stmt = $db->prepare("SELECT id, name, email FROM users WHERE status = 'active' ORDER BY name");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$content = ob_start();
?>

<div class="page-header">
    <h1><i class="bi bi-clock-history"></i> Manual Attendance Entry</h1>
    <p>Enter attendance for users who cannot clock in/out</p>
</div>

<div class="card">
    <div class="card__body">
        <form id="manualForm">
            <div class="form-row">
                <div class="form-group">
                    <label>User *</label>
                    <select name="user_id" required>
                        <option value="">Choose user...</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="entry_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Entry Type *</label>
                    <select name="entry_type" id="entryType" required>
                        <option value="">Select type...</option>
                        <option value="clock_in">Clock In</option>
                        <option value="clock_out">Clock Out</option>
                        <option value="full_day">Full Day</option>
                    </select>
                </div>
                <div class="form-group" id="timeGroup">
                    <label>Time *</label>
                    <input type="time" name="entry_time" id="entryTime" required>
                </div>
            </div>

            <div id="fullDayFields" style="display: none;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Clock In Time *</label>
                        <input type="time" name="clock_in_time" value="09:00">
                    </div>
                    <div class="form-group">
                        <label>Clock Out Time *</label>
                        <input type="time" name="clock_out_time" value="17:00">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Reason *</label>
                <select name="reason" required>
                    <option value="">Select reason...</option>
                    <option value="geo_fencing">Outside geo-fencing range</option>
                    <option value="technical_issue">Technical/App issue</option>
                    <option value="network_problem">Network problem</option>
                    <option value="emergency">Emergency</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="2" placeholder="Additional details..."></textarea>
            </div>

            <button type="submit" class="btn btn--primary">Submit Entry</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h3>Recent Entries</h3>
        <button onclick="loadEntries()" class="btn btn--sm">Refresh</button>
    </div>
    <div class="card__body">
        <div id="entries">Loading...</div>
    </div>
</div>

<style>
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
.form-group select, .form-group input, .form-group textarea { 
    width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; 
}
.btn { padding: 0.75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; }
.btn--primary { background: #3b82f6; color: white; }
.btn--sm { padding: 0.5rem 1rem; font-size: 0.875rem; }
.entry-item { padding: 1rem; margin-bottom: 0.5rem; background: #f9fafb; border-radius: 6px; }
@media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const entryType = document.getElementById('entryType');
    const timeGroup = document.getElementById('timeGroup');
    const fullDayFields = document.getElementById('fullDayFields');
    const entryTime = document.getElementById('entryTime');
    
    // Set current time
    const now = new Date();
    entryTime.value = now.getHours().toString().padStart(2, '0') + ':' + 
                     now.getMinutes().toString().padStart(2, '0');
    
    entryType.addEventListener('change', function() {
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
    
    document.getElementById('manualForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('/ergon/api/manual_attendance_simple.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Entry submitted successfully');
                this.reset();
                loadEntries();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });
    
    loadEntries();
});

function loadEntries() {
    fetch('/ergon/api/manual_attendance_simple.php')
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('entries');
        if (data.success && data.entries.length > 0) {
            container.innerHTML = data.entries.map(entry => `
                <div class="entry-item">
                    <strong>${entry.user_name}</strong><br>
                    <small>${entry.details}</small><br>
                    <small>By: ${entry.created_by_name} on ${entry.created_at}</small>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p>No entries found.</p>';
        }
    });
}
</script>

<?php
$content = ob_get_clean();
$title = 'Manual Attendance Entry';
$active_page = 'attendance';
include __DIR__ . '/../layouts/dashboard.php';
?>