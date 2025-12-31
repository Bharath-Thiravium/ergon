<?php
$title = 'Service History';
$active_page = 'attendance';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📋</span> Service History</h1>
        <p>Track your project-based work history</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/attendance" class="btn btn--secondary">
            <span>🔙</span> Back to Attendance
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📊</span> Filter History
        </h2>
    </div>
    <div class="card__body">
        <div style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
            <div>
                <label class="form-label">Start Date</label>
                <input type="date" id="startDate" class="form-control" value="<?= date('Y-m-01') ?>">
            </div>
            <div>
                <label class="form-label">End Date</label>
                <input type="date" id="endDate" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <button class="btn btn--primary" onclick="loadServiceHistory()">
                <span>🔍</span> Load History
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📋</span> Service Records
        </h2>
    </div>
    <div class="card__body">
        <div id="historyContainer">
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <span>📊</span> Click "Load History" to view your service records
            </div>
        </div>
    </div>
</div>

<script>
function loadServiceHistory() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const container = document.getElementById('historyContainer');
    
    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }
    
    container.innerHTML = '<div style="text-align: center; padding: 2rem;"><span>⏳</span> Loading...</div>';
    
    fetch(`/ergon/api/service-history?start_date=${startDate}&end_date=${endDate}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayServiceHistory(data.history);
        } else {
            container.innerHTML = `<div style="text-align: center; padding: 2rem; color: #ef4444;"><span>❌</span> ${data.error}</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        container.innerHTML = '<div style="text-align: center; padding: 2rem; color: #ef4444;"><span>❌</span> Failed to load history</div>';
    });
}

function displayServiceHistory(history) {
    const container = document.getElementById('historyContainer');
    
    if (!history.length) {
        container.innerHTML = '<div style="text-align: center; padding: 2rem; color: #6b7280;"><span>📭</span> No service records found for the selected period</div>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table"><thead><tr>';
    html += '<th>Date</th><th>Project</th><th>Start Time</th><th>End Time</th><th>Hours</th><th>Status</th><th>Location</th>';
    html += '</tr></thead><tbody>';
    
    history.forEach(record => {
        const statusBadge = record.status === 'completed' ? 'success' : (record.status === 'active' ? 'warning' : 'secondary');
        const locationIcon = record.project_lat && record.project_lng ? '📍' : '🏢';
        
        html += `<tr>
            <td>${new Date(record.service_date).toLocaleDateString()}</td>
            <td><strong>${record.project_name}</strong></td>
            <td>${record.start_time || '-'}</td>
            <td>${record.end_time || '-'}</td>
            <td>${record.hours_worked || 0}h</td>
            <td><span class="badge badge--${statusBadge}">${record.status}</span></td>
            <td><span title="Project location">${locationIcon}</span></td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    
    // Add summary
    const totalHours = history.reduce((sum, record) => sum + parseFloat(record.hours_worked || 0), 0);
    const completedDays = history.filter(record => record.status === 'completed').length;
    
    html += `<div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
        <h4>Summary</h4>
        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
            <div><strong>Total Hours:</strong> ${totalHours.toFixed(2)}h</div>
            <div><strong>Completed Days:</strong> ${completedDays}</div>
            <div><strong>Total Records:</strong> ${history.length}</div>
        </div>
    </div>`;
    
    container.innerHTML = html;
}

// Auto-load current month on page load
document.addEventListener('DOMContentLoaded', function() {
    loadServiceHistory();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
