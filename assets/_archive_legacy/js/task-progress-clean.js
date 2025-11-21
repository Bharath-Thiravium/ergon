/**
 * Daily Planner Progress Integration
 * Unified progress update functionality for both tasks and daily planner
 */

var currentTaskId;
var currentTaskSource = 'daily'; // 'daily' or 'tasks'

function openProgressModal(taskId, progress, status, source = 'daily') {
    currentTaskId = taskId;
    currentTaskSource = source;
    
    var container = document.querySelector('[data-task-id="' + taskId + '"]');
    var currentProgress = container ? 
        (container.querySelector('.progress-percentage')?.textContent.replace('%', '') || progress) : 
        progress;
    
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
    
    // Determine API endpoint based on source
    var apiUrl = currentTaskSource === 'daily' ? 
        '/ergon/api/daily_planner_workflow.php?action=update-progress' : 
        '/ergon/tasks/update-status';
    
    var requestBody = currentTaskSource === 'daily' ? 
        { 
            task_id: currentTaskId, 
            progress: parseInt(progress),
            status: status,
            reason: 'Progress updated via modal'
        } : 
        { 
            task_id: currentTaskId, 
            progress: progress, 
            status: status 
        };
    
    fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestBody)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskProgressUI(currentTaskId, progress, status);
            closeDialog();
            
            var message = progress >= 100 ? 
                'Task completed successfully!' : 
                `Progress updated to ${progress}%`;
            alert(message);
        } else {
            alert('Error updating progress: ' + (data.message || 'Failed to update'));
        }
    })
    .catch(() => alert('Network error occurred'));
}

function updateTaskProgressUI(taskId, progress, status) {
    var container = document.querySelector('[data-task-id="' + taskId + '"]');
    if (!container) return;
    
    // Update progress bar
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
    
    // Update task card status if in daily planner
    var statusBadge = container.querySelector('.badge');
    if (statusBadge) {
        statusBadge.textContent = status === 'completed' ? 'Completed' : 
                                 status === 'in_progress' ? 'In Progress' : 'Assigned';
        statusBadge.className = 'badge badge--' + status;
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
        case 'assigned': return '📋';
        default: return '📋';
    }
}

// Initialize progress slider
document.addEventListener('DOMContentLoaded', function() {
    var slider = document.getElementById('progressSlider');
    if (slider) {
        slider.oninput = function() {
            document.getElementById('progressValue').textContent = this.value;
        };
    }
});

// Export functions for global use
window.openProgressModal = openProgressModal;
window.closeDialog = closeDialog;
window.saveProgress = saveProgress;