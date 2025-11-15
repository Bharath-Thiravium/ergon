// Modern action buttons are now handled by CSS tooltips
var currentTaskId;
function openProgressModal(taskId, progress, status) {
    currentTaskId = taskId;
    var container = document.querySelector('[data-task-id="' + taskId + '"]');
    var currentProgress = container ? container.querySelector('.progress-percentage').textContent.replace('%', '') : progress;
    document.getElementById('progressSlider').value = currentProgress;
    document.getElementById('progressValue').textContent = currentProgress;
    document.getElementById('progressDialog').style.display = 'flex';
}
function closeDialog() {
    document.getElementById('progressDialog').style.display = 'none';
}
function saveProgress() {
    var progress = document.getElementById('progressSlider').value;
    var status = progress >= 100 ? 'completed' : progress > 0 ? 'in_progress' : 'assigned';
    fetch('/ergon/tasks/update-status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: currentTaskId, progress: progress, status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            var container = document.querySelector('[data-task-id="' + currentTaskId + '"]');
            if (container) {
                var fill = container.querySelector('.progress-fill');
                var percentage = container.querySelector('.progress-percentage');
                var statusEl = container.querySelector('.progress-status');
                fill.style.width = progress + '%';
                fill.style.background = progress >= 100 ? '#10b981' : (progress >= 75 ? '#8b5cf6' : (progress >= 50 ? '#3b82f6' : (progress >= 25 ? '#f59e0b' : '#e2e8f0')));
                percentage.textContent = progress + '%';
                var icon = status === 'completed' ? '✅' : status === 'in_progress' ? '⚡' : '📋';
                statusEl.textContent = icon + ' ' + status.replace('_', ' ');
            }
            closeDialog();
        } else {
            alert('Error updating task');
        }
    })
    .catch(() => alert('Error updating task'));
}
document.getElementById('progressSlider').oninput = function() {
    document.getElementById('progressValue').textContent = this.value;
}