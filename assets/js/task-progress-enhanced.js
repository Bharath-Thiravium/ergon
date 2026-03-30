var currentTaskId;

function openProgressModal(taskId, progress, status) {
    currentTaskId = taskId;
    var container = document.querySelector('[data-task-id="' + taskId + '"]');
    var currentProgress = progress;
    
    if (container) {
        var percentageEl = container.querySelector('.progress-percentage');
        if (percentageEl && percentageEl.textContent) {
            currentProgress = percentageEl.textContent.replace('%', '');
        }
    }
    
    var slider = document.getElementById('progressSlider');
    var valueDisplay = document.getElementById('progressValue');
    var description = document.getElementById('progressDescription');
    var dialog = document.getElementById('progressDialog');
    
    if (slider) slider.value = currentProgress;
    if (valueDisplay) valueDisplay.textContent = currentProgress;
    if (description) description.value = '';
    if (dialog) dialog.style.display = 'flex';
    
    // Focus on description field
    setTimeout(() => {
        if (description) description.focus();
    }, 100);
}

function closeDialog() {
    document.getElementById('progressDialog').style.display = 'none';
    document.getElementById('progressHistoryDialog').style.display = 'none';
}

function saveProgress() {
    var progressSlider = document.getElementById('progressSlider');
    var descriptionEl = document.getElementById('progressDescription');
    
    if (!progressSlider || !currentTaskId) {
        alert('Error: Missing required elements');
        return;
    }
    
    var progress = progressSlider.value;
    var description = descriptionEl ? descriptionEl.value.trim() : '';
    
    if (!description) {
        alert('Please provide a description for this progress update.');
        if (descriptionEl) descriptionEl.focus();
        return;
    }
    
    // Show loading state
    var saveBtn = document.querySelector('#progressDialog .btn-primary');
    if (saveBtn) {
        var originalText = saveBtn.textContent;
        saveBtn.textContent = 'Updating...';
        saveBtn.disabled = true;
    }
    
    fetch('/ergon/tasks/update-status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            task_id: currentTaskId, 
            progress: parseInt(progress),
            description: description
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update progress in task list if present
            var container = document.querySelector('[data-task-id="' + currentTaskId + '"]');
            if (container) {
                var fill = container.querySelector('.progress-fill');
                var percentage = container.querySelector('.progress-percentage');
                var statusEl = container.querySelector('.progress-status');
                
                if (fill) fill.style.width = progress + '%';
                if (fill) fill.style.background = getProgressColor(progress);
                if (percentage) percentage.textContent = progress + '%';
                
                var status = progress >= 100 ? 'completed' : progress > 0 ? 'in_progress' : 'assigned';
                var icon = status === 'completed' ? '✅' : status === 'in_progress' ? '⚡' : '📋';
                if (statusEl) statusEl.textContent = icon + ' ' + status.replace('_', ' ');
            }
            
            // Update mini progress bar on view page
            var miniProgressFill = document.querySelector('.progress-fill-mini');
            var miniProgressText = document.querySelector('.progress-text');
            if (miniProgressFill) {
                miniProgressFill.style.width = progress + '%';
                var progressRange = progress == 0 ? '0' : progress >= 100 ? '100' : progress >= 75 ? '75-99' : progress >= 50 ? '50-74' : progress >= 25 ? '25-49' : '1-24';
                miniProgressFill.setAttribute('data-progress', progressRange);
            }
            if (miniProgressText) miniProgressText.textContent = progress + '%';
            
            // Update status badge on view page
            var statusBadges = document.querySelectorAll('.badge');
            statusBadges.forEach(badge => {
                if (badge.textContent.includes('Assigned') || badge.textContent.includes('In Progress') || badge.textContent.includes('Completed')) {
                    var status = progress >= 100 ? 'completed' : progress > 0 ? 'in_progress' : 'assigned';
                    var icon = status === 'completed' ? '✅' : status === 'in_progress' ? '⚡' : '📋';
                    var statusText = status === 'completed' ? 'Completed' : status === 'in_progress' ? 'In Progress' : 'Assigned';
                    badge.textContent = icon + ' ' + statusText;
                    badge.className = 'badge badge--' + (status === 'completed' ? 'success' : status === 'in_progress' ? 'info' : 'warning');
                }
            });
            
            closeDialog();
            
            // Show success message
            showNotification('Progress updated successfully!', 'success');
        } else {
            alert('Error: ' + (data.message || 'Failed to update progress'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating task progress');
    })
    .finally(() => {
        // Reset button state
        if (saveBtn) {
            saveBtn.textContent = originalText || 'Update Progress';
            saveBtn.disabled = false;
        }
    });
}

function getProgressColor(progress) {
    if (progress >= 100) return '#10b981';
    if (progress >= 75) return '#8b5cf6';
    if (progress >= 50) return '#3b82f6';
    if (progress >= 25) return '#f59e0b';
    return '#e2e8f0';
}

function showProgressHistory(taskId) {
    fetch('/ergon/tasks/progress-history/' + taskId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('progressHistoryContent').innerHTML = data.html;
            document.getElementById('progressHistoryDialog').style.display = 'flex';
        } else {
            alert('Error loading progress history: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading progress history');
    });
}

function showNotification(message, type = 'info') {
    // Create notification element
    var notification = document.createElement('div');
    notification.className = 'notification notification-' + type;
    notification.innerHTML = '<span>' + message + '</span><button onclick="this.parentElement.remove()">×</button>';
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 3000);
}

// Update progress slider display
document.addEventListener('DOMContentLoaded', function() {
    var progressSlider = document.getElementById('progressSlider');
    if (progressSlider) {
        progressSlider.oninput = function() {
            document.getElementById('progressValue').textContent = this.value + '%';
        };
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDialog();
    }
    
    // Ctrl+Enter to save progress
    if (e.ctrlKey && e.key === 'Enter') {
        var progressDialog = document.getElementById('progressDialog');
        if (progressDialog && progressDialog.style.display === 'flex') {
            saveProgress();
        }
    }
});
