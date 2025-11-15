// Task Progress Management
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
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            task_id: currentTaskId,
            progress: progress,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskUI(currentTaskId, progress, status);
            closeDialog();
        } else {
            alert('Error updating task: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating task');
    });
}

function updateTaskUI(taskId, progress, status) {
    var container = document.querySelector('[data-task-id="' + taskId + '"]');
    if (container) {
        var fill = container.querySelector('.progress-fill');
        var percentage = container.querySelector('.progress-percentage');
        var statusEl = container.querySelector('.progress-status');
        
        if (fill) {
            fill.style.width = progress + '%';
            fill.style.background = getProgressColor(progress);
        }
        
        if (percentage) {
            percentage.textContent = progress + '%';
        }
        
        if (statusEl) {
            var icon = getStatusIcon(status);
            statusEl.textContent = icon + ' ' + status.replace('_', ' ');
        }
    }
}

function getProgressColor(progress) {
    if (progress >= 100) return '#10b981';
    if (progress >= 75) return '#8b5cf6';
    if (progress >= 50) return '#3b82f6';
    if (progress >= 25) return '#f59e0b';
    return '#e2e8f0';
}

function getStatusIcon(status) {
    switch(status) {
        case 'completed': return '✅';
        case 'in_progress': return '⚡';
        default: return '📋';
    }
}

// Progress slider event listener
document.addEventListener('DOMContentLoaded', function() {
    var slider = document.getElementById('progressSlider');
    if (slider) {
        slider.oninput = function() {
            document.getElementById('progressValue').textContent = this.value;
        };
    }
});