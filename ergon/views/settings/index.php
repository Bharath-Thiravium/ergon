<?php
$title = 'System Settings';
$active_page = 'settings';
ob_start();
?>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>⚙️</span> General Settings
            </h2>
        </div>
        <div class="card__body">
            <form id="settingsForm">
                <div class="form-group">
                    <label class="form-label">Company Name</label>
                    <input type="text" class="form-control" name="company_name" value="ERGON Company">
                </div>
                <div class="form-group">
                    <label class="form-label">Timezone</label>
                    <select class="form-control" name="timezone">
                        <option value="Asia/Kolkata">Asia/Kolkata</option>
                        <option value="UTC">UTC</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Working Hours per Day</label>
                    <input type="number" class="form-control" name="working_hours" value="8">
                </div>
                <div class="form-group">
                    <label class="form-label">Attendance Radius (meters)</label>
                    <input type="number" class="form-control" name="attendance_radius" value="200">
                </div>
                <button type="submit" class="btn btn--primary">Save Settings</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>📊</span> System Information
            </h2>
        </div>
        <div class="card__body">
            <div class="table-responsive">
                <table class="table">
                    <tr>
                        <td>Version</td>
                        <td>2.0.0</td>
                    </tr>
                    <tr>
                        <td>Environment</td>
                        <td>Development</td>
                    </tr>
                    <tr>
                        <td>PHP Version</td>
                        <td><?= phpversion() ?></td>
                    </tr>
                    <tr>
                        <td>Database</td>
                        <td>MySQL</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('/ergon_clean/public/settings', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Settings saved successfully!');
        } else {
            alert(data.error);
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>