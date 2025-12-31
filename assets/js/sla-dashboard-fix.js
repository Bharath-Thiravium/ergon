/**
 * SLA Dashboard Fix
 * Ensures SLA timing fields are properly populated and updated
 */

// Global function to change date and refresh SLA data
window.changeDate = function(newDate) {
    if (newDate) {
        // Update the URL to reflect the new date
        const baseUrl = window.location.pathname.replace(/\/\d{4}-\d{2}-\d{2}$/, '');
        const newUrl = baseUrl + '/' + newDate;
        window.location.href = newUrl;
    }
};

// Function to show history info modal
window.showHistoryInfo = function() {
    alert('Historical View: This shows tasks that were assigned to or completed on the selected past date. Task execution controls are disabled for historical data.');
};

// Function to show read-only progress
window.showReadOnlyProgress = function(taskId, progress) {
    alert(`Task Progress: ${progress}%\n\nThis is a historical record and cannot be modified.`);
};

// Function to show task history
window.showTaskHistory = function(taskId, taskTitle) {
    alert(`Task History for: ${taskTitle}\n\nThis feature shows the complete timeline of task actions and status changes. Historical data is read-only.`);
};

// Function to activate postponed task
window.activatePostponedTask = function(taskId) {
    if (confirm('Start this postponed task now?')) {
        window.startTask(taskId);
    }
};

// SLA dashboard functions only - no timer conflicts
