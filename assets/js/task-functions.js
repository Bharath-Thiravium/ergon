// Fallback stubs for daily planner and other pages that don't load task-progress-enhanced.js.
// Each function is only defined if a real implementation hasn't already been registered.

if (typeof window.pauseTask === 'undefined') {
    window.pauseTask = function(taskId) {
        alert('Pause task ' + taskId);
    };
}

if (typeof window.resumeTask === 'undefined') {
    window.resumeTask = function(taskId) {
        alert('Resume task ' + taskId);
    };
}

if (typeof window.openProgressModal === 'undefined') {
    window.openProgressModal = function(taskId, progress) {
        alert('Progress modal for task ' + taskId + ' (progress: ' + progress + '%)');
    };
}

if (typeof window.postponeTask === 'undefined') {
    window.postponeTask = function(taskId) {
        var newDate = prompt('Enter new date (YYYY-MM-DD):');
        if (newDate) {
            alert('Postpone task ' + taskId + ' to ' + newDate);
        }
    };
}

if (typeof window.startTask === 'undefined') {
    window.startTask = function(taskId) {
        alert('Start task ' + taskId);
    };
}

// Expose as plain functions for inline onclick compatibility
function pauseTask(taskId)                   { return window.pauseTask(taskId); }
function resumeTask(taskId)                  { return window.resumeTask(taskId); }
function openProgressModal(taskId, progress) { return window.openProgressModal(taskId, progress); }
function postponeTask(taskId)                { return window.postponeTask(taskId); }
function startTask(taskId)                   { return window.startTask(taskId); }
